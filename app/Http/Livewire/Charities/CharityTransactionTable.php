<?php

namespace App\Http\Livewire\Charities;

use Cknow\Money\Money;
use App\Models\Charities\CharityTransaction;
use App\Models\CharityTypes\CharityType;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
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
            ->with([
                'charityType.source',
                'organization',
                'fitrahReceipt',
                'fidyaReceipt',
                'malReceipt',
                'donationReceipt',
                'almsReceipt',
                'endowmentReceipt',
            ])
            ->withCount('payers');

        if ($this->contextOrganizationId) {
            $query->where('charity_transactions.organization_id', $this->contextOrganizationId);
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
                ->footer(fn () => $this->filteredTotals()['count'] . ' ' . __('messages.transactions')),
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
                ->options([
                    '' => __('messages.all'),
                    'cash' => __('messages.cash'),
                    'transfer' => __('messages.transfer'),
                    'qris' => __('messages.qris'),
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('payment_method', $value)),
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
                        $builder->whereDate('charity_transactions.created_at', now()->toDateString());
                    }

                    if ($value === 'this_year') {
                        $builder->whereYear('charity_transactions.created_at', (int) now()->year);
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
                    $builder
                        ->whereDate('charity_transactions.created_at', '>=', $dateRange['minDate'])
                        ->whereDate('charity_transactions.created_at', '<=', $dateRange['maxDate']);
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

        $query = CharityTransaction::query();

        if ($this->contextOrganizationId) {
            $query->where('charity_transactions.organization_id', $this->contextOrganizationId);
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
            $query->where('charity_type_id', $charityTypeId);
        }

        $paymentMethod = $this->getAppliedFilterWithValue('payment_method');
        if (! empty($paymentMethod)) {
            $query->where('payment_method', $paymentMethod);
        }

        $status = $this->getAppliedFilterWithValue('status');
        if (! empty($status)) {
            $query->where('status', $status);
        }

        $period = $this->getAppliedFilterWithValue('period');
        if ($period === 'today') {
            $query->whereDate('charity_transactions.created_at', now()->toDateString());
        }

        if ($period === 'this_year') {
            $query->whereYear('charity_transactions.created_at', (int) now()->year);
        }

        $range = $this->getAppliedFilterWithValue('transaction_date');
        if (is_array($range) && ! empty($range['minDate']) && ! empty($range['maxDate'])) {
            $query
                ->whereDate('charity_transactions.created_at', '>=', Carbon::parse($range['minDate'])->toDateString())
                ->whereDate('charity_transactions.created_at', '<=', Carbon::parse($range['maxDate'])->toDateString());
        }

        $transactionIds = (clone $query)
            ->select('charity_transactions.id')
            ->distinct()
            ->pluck('charity_transactions.id');

        $totals = $this->aggregateReceiptTotals($transactionIds->all());

        $this->totalsCache = [
            'total_money' => $totals['total_money'],
            'total_rice' => $totals['total_rice'],
            'count' => (int) $transactionIds->count(),
        ];

        return $this->totalsCache;
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

    protected function aggregateReceiptTotals(array $transactionIds): array
    {
        if (empty($transactionIds)) {
            return [
                'total_money' => 0.0,
                'total_rice' => 0.0,
            ];
        }

        $totalMoney = 0.0;
        $totalRice = 0.0;

        $totalMoney += (float) DB::table('charity_fitrah_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_money');
        $totalMoney += (float) DB::table('charity_fidya_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_money');
        $totalMoney += (float) DB::table('charity_mal_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_money');
        $totalMoney += (float) DB::table('charity_donation_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_money');
        $totalMoney += (float) DB::table('charity_alms_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_money');
        $totalMoney += (float) DB::table('charity_endowment_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_money');

        $totalRice += (float) DB::table('charity_fitrah_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_rice');
        $totalRice += (float) DB::table('charity_fidya_receipts')
            ->whereIn('charity_transaction_id', $transactionIds)
            ->sum('amount_rice');

        return [
            'total_money' => $totalMoney,
            'total_rice' => $totalRice,
        ];
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
