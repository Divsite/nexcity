<?php

namespace App\Http\Livewire\Charities;

use Cknow\Money\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\Charities\CharityTransactionExport;
use App\Models\Charities\CharityTransaction;
use App\Models\CharityTypes\CharityType;
use App\Services\Charities\CharityReceiptTotalsService;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Maatwebsite\Excel\Excel as ExcelService;
use Maatwebsite\Excel\Facades\Excel;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class CharityTransactionTable extends DataTableComponent
{
    protected $model = CharityTransaction::class;

    protected ?int $contextOrganizationId = null;

    protected ?array $totalsCache = null;

    protected $listeners = [
        'charityTransactionSaved' => '$refresh',
    ];

    public function mount(): void
    {
        $this->syncContext();
    }

    public function hydrate(): void
    {
        $this->syncContext();
        $this->totalsCache = null;
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('charity_transactions.created_at', 'desc')
            ->setSearchDisabled()
            ->setColumnSelectStatus(false)
            ->setFooterEnabled()
            ->setFilterLayoutSlideDown()
            ->setConfigurableArea('before-toolbar', 'charities.table.preset-filters')
            ->setTableWrapperAttributes(['class' => 'table-card mt-2'])
            ->setTheadAttributes(['class' => 'table-light'])
            ->setTableAttributes(['default' => false, 'class' => 'table table-striped'])
            ->setTrAttributes(fn () => ['default' => false, 'class' => 'align-middle']);
    }

    public function builder(): Builder
    {
        $query = CharityTransaction::query()
            ->withCharityRelations()
            ->withCount('payers');

        if ($this->contextOrganizationId) {
            $query->forOrganization($this->contextOrganizationId);
        }

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.charity_type'), 'charityType.source.name')
                ->searchable()
                ->sortable()
                ->format(fn ($value) => $value ?? '-')
                ->footer(fn () => __('messages.filtered_total')),
            Column::make(__('messages.payer'), 'payer_name')
                ->searchable()
                ->sortable()
                ->label(fn ($row) => $this->payerLabel($row)),
            Column::make(__('messages.total_money'))
                ->label(fn ($row) => $this->formatCurrency($row->detailMoneyAmount()))
                ->footer(fn () => $this->formatCurrency($this->filteredTotals()['total_money'])),
            Column::make(__('messages.total_rice'))
                ->label(fn ($row) => $this->formatQuantity($row->detailRiceAmount()))
                ->footer(fn () => $this->formatQuantity($this->filteredTotals()['total_rice'])),
            Column::make(__('messages.status'), 'status')
                ->label(fn ($row) => view('charities.columns.status')->withRow($row))
                ->footer(function () {
                    $totals = $this->filteredTotals();

                    return __('messages.paid') . ': ' . $totals['paid_count']
                        . ' · ' . __('messages.draft') . ': ' . $totals['draft_count']
                        . ' · ' . __('messages.cancelled') . ': ' . $totals['cancelled_count'];
                }),
            Column::make(__('messages.received_by'), 'receivedBy.name')
                ->label(fn ($row) => $row->receivedBy?->name ?? '-'),
            Column::make(__('messages.created_at'), 'created_at')
                ->sortable()
                ->format(fn ($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
            Column::make(__('messages.updated_at'), 'updated_at')
                ->sortable()
                ->format(fn ($value) => $value ? $value->diffForHumans() : '-'),
            Column::make(__('messages.actions'))
                ->label(fn ($row) => view('charities.columns.actions')->withRow($row)),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make(__('messages.payer'), 'payer_name')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(function (Builder $query) use ($value) {
                        $query->where('payer_name', 'like', '%' . $value . '%')
                            ->orWhereHas('payers', fn (Builder $payerQuery) => $payerQuery->where('payer_name', 'like', '%' . $value . '%'));
                    });
                }),
            SelectFilter::make(__('messages.charity_type'), 'charity_type_id')
                ->setWireLive()
                ->options(
                    ['' => __('messages.all')] + CharityType::query()
                        ->with('source')
                        ->where('year', (int) now()->year)
                        ->when($this->contextOrganizationId, fn (Builder $query) => $query->where('charity_types.organization_id', $this->contextOrganizationId))
                        ->orderBy('id', 'desc')
                        ->get()
                        ->mapWithKeys(fn ($type) => [
                            $type->id => ($type->source?->name ?? '-') . ' (' . $type->year . ')'
                        ])
                        ->toArray()
                )
                ->filter(fn (Builder $builder, string $value) => $builder->where('charity_type_id', $value)),
            SelectFilter::make(__('messages.payment_method'), 'payment_method')
                ->setWireLive()
                ->options(['' => __('messages.all')] + CharityTransaction::paymentMethodLabels())
                ->filter(fn (Builder $builder, string $value) => $builder->forPaymentMethod($value)),
            SelectFilter::make(__('messages.status'), 'status')
                ->setWireLive()
                ->options([
                    '' => __('messages.all'),
                    'draft' => __('messages.draft'),
                    'paid' => __('messages.paid'),
                    'cancelled' => __('messages.cancelled'),
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),
            SelectFilter::make(__('messages.period'), 'period')
                ->setWireLive()
                ->options([
                    '' => __('messages.all'),
                    'today' => __('messages.today'),
                    'this_year' => __('messages.this_year'),
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'today') {
                        $builder->createdOn(now()->toDateString());
                    }

                    if ($value === 'this_year') {
                        $builder->createdInYear((int) now()->year);
                    }
                }),
            DateRangeFilter::make(__('messages.transaction_date'), 'transaction_date')
                ->setWireLive()
                ->config([
                    'allowInput' => false,
                    'altFormat' => 'd/m/Y',
                    'ariaDateFormat' => 'd/m/Y',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->createdBetweenDates($dateRange['minDate'], $dateRange['maxDate']);
                }),
        ];
    }

    public function destroy($id)
    {
        $model = CharityTransaction::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.deleted_successfully'));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        return to_route('mosque.charity-transactions.index');
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! auth()->user()?->can('edit-mosque-charity-transactions')) {
            abort(403);
        }

        if (! in_array($status, ['draft', 'paid', 'cancelled'], true)) {
            return;
        }

        $model = CharityTransaction::query()->find($id);
        if (! $model) {
            return;
        }

        if ($this->contextOrganizationId && $model->organization_id !== $this->contextOrganizationId) {
            abort(403);
        }

        $model->update(['status' => $status]);
        $this->totalsCache = null;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('messages.updated_successfully'),
        ]);
    }

    public function bulkActions(): array
    {
        return [
            'exportSelectedToPDF' => __('messages.export_pdf'),
            'exportSelectedExcel' => __('messages.export_xlsx'),
        ];
    }

    public function exportSelectedToPDF()
    {
        $models = $this->exportQuery()->get();

        if ($models->isEmpty()) {
            flash()->error(__('messages.data_not_found'));
            return to_route('mosque.charity-transactions.index');
        }

        $pdfContent = Pdf::loadView('charities.exports.pdf.index', [
            'models' => $models,
        ])->output();

        $filename = 'Charity_Transactions_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(fn () => print($pdfContent), $filename);
    }

    public function exportSelectedExcel()
    {
        $models = $this->exportQuery()->get();

        if ($models->isEmpty()) {
            flash()->error(__('messages.data_not_found'));
            return to_route('mosque.charity-transactions.index');
        }

        return Excel::download(
            new CharityTransactionExport($models),
            'Charity_Transactions_' . now()->format('Ymd_His') . '.xlsx',
            ExcelService::XLSX
        );
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }

    public function applyPreset(string $preset): void
    {
        if (! in_array($preset, ['today', 'this_year', ''], true)) {
            return;
        }

        $this->totalsCache = null;
        $this->setFilter('period', $preset);
    }

    protected function filteredTotals(): array
    {
        if ($this->totalsCache !== null) {
            return $this->totalsCache;
        }

        $baseQuery = $this->filteredQuery(false);

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();

        $paidTotals = $this->receiptTotalsService()->totalsForQuery(
            (clone $baseQuery)->where('status', CharityTransaction::STATUS_PAID)
        );

        $this->totalsCache = [
            'total_money' => $paidTotals['total_money'],
            'total_rice' => $paidTotals['total_rice'],
            'count' => (int) ($paidTotals['count'] ?? 0),
            'paid_count' => (int) ($statusCounts[CharityTransaction::STATUS_PAID] ?? 0),
            'draft_count' => (int) ($statusCounts[CharityTransaction::STATUS_DRAFT] ?? 0),
            'cancelled_count' => (int) ($statusCounts[CharityTransaction::STATUS_CANCELLED] ?? 0),
            'total_count' => array_sum($statusCounts),
        ];

        return $this->totalsCache;
    }

    protected function filteredQuery(bool $applyStatus = true): Builder
    {
        $query = CharityTransaction::query();

        if ($this->contextOrganizationId) {
            $query->forOrganization($this->contextOrganizationId);
        }

        $payer = $this->getAppliedFilterWithValue('payer_name');
        if (! empty($payer)) {
            $query->where(function (Builder $builder) use ($payer) {
                $builder->where('payer_name', 'like', '%' . $payer . '%')
                    ->orWhereHas('payers', fn (Builder $payerQuery) => $payerQuery->where('payer_name', 'like', '%' . $payer . '%'));
            });
        }

        $charityTypeId = $this->getAppliedFilterWithValue('charity_type_id');
        if (! empty($charityTypeId)) {
            $query->forCharityType($charityTypeId);
        }

        $paymentMethod = $this->getAppliedFilterWithValue('payment_method');
        if (! empty($paymentMethod)) {
            $query->forPaymentMethod($paymentMethod);
        }

        $status = $this->getAppliedFilterWithValue('status');
        if ($applyStatus && ! empty($status)) {
            $query->where('status', $status);
        }

        $period = $this->getAppliedFilterWithValue('period');
        if ($period === 'today') {
            $query->createdOn(now()->toDateString());
        }

        if ($period === 'this_year') {
            $query->createdInYear((int) now()->year);
        }

        $range = $this->getAppliedFilterWithValue('transaction_date');
        if (is_array($range) && ! empty($range['minDate']) && ! empty($range['maxDate'])) {
            $query->createdBetweenDates(
                Carbon::parse($range['minDate'])->toDateString(),
                Carbon::parse($range['maxDate'])->toDateString()
            );
        }

        return $query;
    }

    protected function exportQuery(): Builder
    {
        $query = $this->filteredQuery()
            ->withCharityRelations()
            ->withCount('payers');

        if (! empty($this->getSelected())) {
            $query->whereIn('charity_transactions.id', $this->getSelected());
        }

        return $query;
    }

    protected function formatCurrency($value): string
    {
        $currency = strtoupper((string) config('money.defaultCurrency', 'IDR'));
        $amount = (float) ($value ?? 0);

        try {
            return Money::{$currency}($amount)->format(App::currentLocale());
        } catch (\Throwable $exception) {
            return Money::IDR($amount)->format(App::currentLocale());
        }
    }

    protected function formatQuantity($value): string
    {
        return Number::format((float) ($value ?? 0), 2, 2, App::currentLocale());
    }

    protected function payerLabel(CharityTransaction $row): string
    {
        $name = trim((string) ($row->payer_name ?? ''));

        if (! $row->is_package) {
            return $name !== '' ? $name : '-';
        }

        $memberCount = (int) ($row->package_members_count ?: ($row->payers_count ?? 0));
        $baseLabel = $name !== '' ? $name : __('messages.is_package');

        if ($memberCount <= 0) {
            return $baseLabel;
        }

        return $baseLabel . ' (' . __('messages.family_members_count') . ': ' . $memberCount . ')';
    }

    protected function receiptTotalsService(): CharityReceiptTotalsService
    {
        return app(CharityReceiptTotalsService::class);
    }

    protected function partnerContext(): ?array
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $membership = $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', 'mosque-%')
            ->first();

        if (! $membership) {
            return null;
        }

        return [
            'organization_id' => $membership->organization_id,
        ];
    }

    protected function syncContext(): void
    {
        $context = $this->partnerContext();
        $this->contextOrganizationId = $context['organization_id'] ?? null;
    }
}
