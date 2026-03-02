<?php

namespace App\Http\Livewire\Distributions;

use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\DistributionClasses\DistributionClass;
use App\Models\DistributionTypes\DistributionType;
use App\Models\Locations\NeighborhoodAssociation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class DistributionTable extends DataTableComponent
{
    protected $model = Distribution::class;

    protected ?int $contextOrganizationId = null;

    protected ?int $zakatTypeId = null;

    protected ?bool $canViewAll = false;

    protected ?array $totalsCache = null;

    protected $listeners = [
        'distributionSaved' => '$refresh',
        'refreshDistributionTable' => '$refresh',
        'triggerDelete' => 'destroy',
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

    protected function syncContext(): void
    {
        $context = $this->partnerContext();
        $this->contextOrganizationId = $context['organization_id'] ?? null;
        $this->canViewAll = $context['can_view_all'] ?? false;
        $this->zakatTypeId = DistributionType::query()->where('slug', 'zakat')->value('id');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('distributions.created_at', 'desc')
            ->setSearchDisabled()
            ->setColumnSelectStatus(false)
            ->setFooterEnabled()
            ->setFilterLayoutSlideDown()
            ->setTableWrapperAttributes(['class' => 'table-card mt-2'])
            ->setTheadAttributes(['class' => 'table-light'])
            ->setTableAttributes(['default' => false, 'class' => 'table table-striped'])
            ->setTrAttributes(fn () => ['default' => false, 'class' => 'align-middle']);
    }

    public function builder(): Builder
    {
        $query = Distribution::query()
            ->with([
                'createdBy',
                'recipients.distributionClass.source',
                'neighborhoodAssociation',
                'citizensAssociation',
                'officers.officer',
            ])
            ->withCount([
                'recipients',
                'recipients as distributed_count' => fn (Builder $builder) => $builder->where('status', 'distributed'),
                'recipients as failed_count' => fn (Builder $builder) => $builder->where('status', 'failed'),
            ]);

        if ($this->contextOrganizationId) {
            $query->where('distributions.organization_id', $this->contextOrganizationId);
        }

        if ($this->zakatTypeId) {
            $query->where('distribution_type_id', $this->zakatTypeId);
        }

        if (! $this->canViewAll) {
            $query->whereHas('officers', fn (Builder $builder) => $builder->where('officer_id', auth()->id()));
        }

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.distribution_class'))
                ->label(fn ($row) => $this->distributionClassLabel($row)),
            Column::make(__('messages.year'), 'year')
                ->sortable()
                ->format(fn ($value) => $value ?? '-'),
            Column::make(__('messages.location'))
                ->label(fn ($row) => $this->locationLabel($row)),
            Column::make(__('messages.total_recipients'))
                ->label(fn ($row) => (int) ($row->recipients_count ?? 0))
                ->footer(fn () => (int) $this->filteredTotals()['total_recipients']),
            Column::make(__('messages.money_per_person'))
                ->label(fn ($row) => $this->formatCurrency($this->distributionMoneyPerPerson($row))),
            Column::make(__('messages.rice_per_person'))
                ->label(fn ($row) => $this->formatQuantity($this->distributionRicePerPerson($row))),
            Column::make(__('messages.total_money'))
                ->label(fn ($row) => $this->formatCurrency($this->distributionTotalMoney($row)))
                ->footer(fn () => $this->formatCurrency($this->filteredTotals()['total_money'])),
            Column::make(__('messages.total_rice'))
                ->label(fn ($row) => $this->formatQuantity($this->distributionTotalRice($row)))
                ->footer(fn () => $this->formatQuantity($this->filteredTotals()['total_rice'])),
            Column::make(__('messages.progress'))
                ->label(fn ($row) => view('distributions.columns.progress')->withRow($row)),
            Column::make(__('messages.officers'))
                ->label(fn ($row) => $this->officerLabel($row)),
            Column::make(__('messages.created_by'), 'createdBy.name')
                ->label(fn ($row) => $row->createdBy?->name ?? '-'),
            Column::make(__('messages.status'), 'status')
                ->label(fn ($row) => view('distributions.columns.status')->withRow($row))
                ->footer(function () {
                    $totals = $this->filteredTotals();

                    return __('messages.pending') . ': ' . $totals['pending_count']
                        . ' · ' . __('messages.completed') . ': ' . $totals['completed_count']
                        . ' · ' . __('messages.failed') . ': ' . $totals['failed_count'];
                }),
            Column::make(__('messages.actions'))
                ->label(fn ($row) => view('distributions.columns.actions')->withRow($row)),
        ];
    }

    public function filters(): array
    {
        $years = Distribution::query()
            ->select('year')
            ->when($this->contextOrganizationId, fn (Builder $query) => $query->where('organization_id', $this->contextOrganizationId))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values()
            ->toArray();

        $classes = DistributionClass::query()
            ->with('source')
            ->when($this->contextOrganizationId, fn (Builder $query) => $query->where('organization_id', $this->contextOrganizationId))
            ->orderBy('id', 'desc')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->id => ($item->source?->name ?? '-') . ' (' . $item->year . ')',
            ])
            ->toArray();

        $neighborhoodIds = Distribution::query()
            ->when($this->contextOrganizationId, fn (Builder $query) => $query->where('organization_id', $this->contextOrganizationId))
            ->whereNotNull('neighborhood_association_id')
            ->pluck('neighborhood_association_id')
            ->unique()
            ->values()
            ->toArray();

        $neighborhoods = NeighborhoodAssociation::query()
            ->when(! empty($neighborhoodIds), fn (Builder $query) => $query->whereIn('id', $neighborhoodIds))
            ->orderBy('number')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->id => $item->name ?? ('RT ' . $item->number),
            ])
            ->toArray();

        $yearOptions = ['' => __('messages.all')];
        foreach ($years as $year) {
            $yearOptions[$year] = $year;
        }

        return [
            SelectFilter::make(__('messages.year'), 'year')
                ->setWireLive()
                ->options($yearOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return;
                    }
                    $builder->where('year', $value);
                }),
            SelectFilter::make(__('messages.distribution_class'), 'distribution_class_id')
                ->setWireLive()
                ->options(['' => __('messages.all')] + $classes)
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return;
                    }
                    $builder->whereHas('recipients', fn (Builder $query) => $query->where('distribution_class_id', $value));
                }),
            SelectFilter::make(__('messages.neighborhood_association'), 'neighborhood_association_id')
                ->setWireLive()
                ->options(['' => __('messages.all')] + $neighborhoods)
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return;
                    }
                    $builder->where('neighborhood_association_id', $value);
                }),
            SelectFilter::make(__('messages.status'), 'status')
                ->setWireLive()
                ->options([
                    '' => __('messages.all'),
                    'pending' => __('messages.pending'),
                    'completed' => __('messages.completed'),
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return;
                    }
                    $builder->where('status', $value);
                }),
            DateRangeFilter::make(__('messages.transaction_date'), 'transaction_date')
                ->setWireLive()
                ->config([
                    'allowInput' => false,
                    'altFormat' => 'd/m/Y',
                    'ariaDateFormat' => 'd/m/Y',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('distributions.created_at', '>=', $dateRange['minDate'])
                        ->whereDate('distributions.created_at', '<=', $dateRange['maxDate']);
                }),
        ];
    }

    protected function locationLabel($row): string
    {
        $rt = $row->neighborhoodAssociation?->number;
        $rw = $row->citizensAssociation?->number;

        if ($rt || $rw) {
            return trim(sprintf('RT %s / RW %s', $rt ?? '-', $rw ?? '-'));
        }

        return $row->neighborhoodAssociation?->name
            ?? $row->citizensAssociation?->name
            ?? '-';
    }

    protected function distributionClassLabel($row): string
    {
        $class = $row->recipients
            ->first()?->distributionClass;

        return $class?->source?->name ?? '-';
    }

    protected function distributionClassForRow($row): ?DistributionClass
    {
        $classId = $row->recipients
            ->first()?->distribution_class_id;

        if (! $classId) {
            return null;
        }

        if ($row->relationLoaded('recipients')) {
            return $row->recipients->first()?->distributionClass;
        }

        return DistributionClass::query()->find($classId);
    }

    protected function distributionTotalMoney($row): float
    {
        $class = $this->distributionClassForRow($row);
        $moneyPer = $this->distributionMoneyPerPerson($row);
        $recipients = (int) ($row->recipients_count ?? 0);

        return $moneyPer * $recipients;
    }

    protected function distributionTotalRice($row): float
    {
        $class = $this->distributionClassForRow($row);
        $ricePer = $this->distributionRicePerPerson($row);
        $recipients = (int) ($row->recipients_count ?? 0);

        return $ricePer * $recipients;
    }

    protected function distributionMoneyPerPerson($row): float
    {
        $class = $this->distributionClassForRow($row);

        return (float) ($class?->get_money ?? 0);
    }

    protected function distributionRicePerPerson($row): float
    {
        $class = $this->distributionClassForRow($row);

        return (float) ($class?->get_rice ?? 0);
    }

    protected function officerLabel($row): string
    {
        $officers = $row->officers
            ->map(fn ($officer) => $officer->officer?->name)
            ->filter()
            ->values();

        if ($officers->isEmpty()) {
            return '-';
        }

        $shown = $officers->take(2)->implode(', ');
        $extra = $officers->count() - 2;

        return $extra > 0 ? $shown . ' +' . $extra : $shown;
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
            'can_view_all' => str_contains($membership->level_slug, 'superadmin'),
        ];
    }

    protected function formatCurrency(float $value): string
    {
        $currency = strtoupper((string) config('money.defaultCurrency', 'IDR'));

        try {
            return \Cknow\Money\Money::{$currency}($value)->format(app()->getLocale());
        } catch (\Throwable $exception) {
            return Number::format($value, 2, 2, App::currentLocale());
        }
    }

    protected function formatQuantity(float $value): string
    {
        return Number::format($value, 2, 2, App::currentLocale());
    }

    protected function filteredTotals(): array
    {
        if ($this->totalsCache !== null) {
            return $this->totalsCache;
        }

        $query = $this->filteredQuery();
        $distributions = $query->get();
        $distributionIds = $distributions->pluck('id')->filter()->values()->all();

        $classMap = DistributionRecipient::query()
            ->whereIn('distribution_id', $distributionIds)
            ->whereNotNull('distribution_class_id')
            ->select('distribution_id', 'distribution_class_id')
            ->groupBy('distribution_id', 'distribution_class_id')
            ->pluck('distribution_class_id', 'distribution_id');

        $classIds = $classMap->values()->unique()->filter()->values()->all();
        $classes = DistributionClass::query()
            ->whereIn('id', $classIds)
            ->get()
            ->keyBy('id');

        $totalMoney = 0.0;
        $totalRice = 0.0;
        $totalRecipients = 0;
        $pendingCount = 0;
        $completedCount = 0;
        $failedCount = 0;

        foreach ($distributions as $distribution) {
            $classId = $classMap[$distribution->id] ?? null;
            $class = $classId ? $classes->get($classId) : null;
            $moneyPer = (float) ($class?->get_money ?? 0);
            $ricePer = (float) ($class?->get_rice ?? 0);
            $recipients = (int) ($distribution->recipients_count ?? 0);

            $totalRecipients += $recipients;
            $totalMoney += $moneyPer * $recipients;
            $totalRice += $ricePer * $recipients;

            $status = $distribution->status ?? null;
            if ($status === 'completed') {
                $completedCount++;
            } elseif ($status === 'failed') {
                $failedCount++;
            } else {
                $pendingCount++;
            }
        }

        $this->totalsCache = [
            'total_money' => $totalMoney,
            'total_rice' => $totalRice,
            'total_recipients' => $totalRecipients,
            'pending_count' => $pendingCount,
            'completed_count' => $completedCount,
            'failed_count' => $failedCount,
        ];

        return $this->totalsCache;
    }

    protected function filteredQuery(): Builder
    {
        $query = Distribution::query()
            ->with([
                'createdBy',
                'recipients.distributionClass.source',
                'neighborhoodAssociation',
                'citizensAssociation',
                'officers.officer',
            ])
            ->withCount([
                'recipients',
                'recipients as distributed_count' => fn (Builder $builder) => $builder->where('status', 'distributed'),
                'recipients as failed_count' => fn (Builder $builder) => $builder->where('status', 'failed'),
            ]);

        if ($this->contextOrganizationId) {
            $query->where('distributions.organization_id', $this->contextOrganizationId);
        }

        if ($this->zakatTypeId) {
            $query->where('distribution_type_id', $this->zakatTypeId);
        }

        if (! $this->canViewAll) {
            $query->whereHas('officers', fn (Builder $builder) => $builder->where('officer_id', auth()->id()));
        }

        $year = $this->getAppliedFilterWithValue('year');
        if (! empty($year)) {
            $query->where('year', $year);
        }

        $classId = $this->getAppliedFilterWithValue('distribution_class_id');
        if (! empty($classId)) {
            $query->whereHas('recipients', fn (Builder $builder) => $builder->where('distribution_class_id', $classId));
        }

        $neighborhoodId = $this->getAppliedFilterWithValue('neighborhood_association_id');
        if (! empty($neighborhoodId)) {
            $query->where('neighborhood_association_id', $neighborhoodId);
        }

        $status = $this->getAppliedFilterWithValue('status');
        if (! empty($status)) {
            $query->where('status', $status);
        }

        $range = $this->getAppliedFilterWithValue('transaction_date');
        if (is_array($range) && ! empty($range['minDate']) && ! empty($range['maxDate'])) {
            $query->whereDate('distributions.created_at', '>=', Carbon::parse($range['minDate'])->toDateString())
                ->whereDate('distributions.created_at', '<=', Carbon::parse($range['maxDate'])->toDateString());
        }

        return $query;
    }

    public function destroy($id)
    {
        if (! auth()->user()?->can('delete-mosque-charity-distributions')) {
            abort(403);
        }

        $model = Distribution::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.deleted_successfully'));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        $this->dispatch('$refresh');
    }
}
