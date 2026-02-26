<?php

namespace App\Http\Livewire\Distributions;

use App\Models\Distributions\Distribution;
use App\Models\DistributionClasses\DistributionClass;
use App\Models\DistributionTypes\DistributionType;
use App\Models\Locations\NeighborhoodAssociation;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class DistributionTable extends DataTableComponent
{
    protected $model = Distribution::class;

    protected ?int $contextOrganizationId = null;

    protected ?int $zakatTypeId = null;

    protected ?bool $canViewAll = false;

    protected $listeners = [
        'distributionSaved' => '$refresh',
    ];

    public function mount(): void
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
                ->label(fn ($row) => (int) ($row->recipients_count ?? 0)),
            Column::make(__('messages.progress'))
                ->label(fn ($row) => view('distributions.columns.progress')->withRow($row)),
            Column::make(__('messages.officers'))
                ->label(fn ($row) => $this->officerLabel($row)),
            Column::make(__('messages.status'), 'status')
                ->format(fn ($value) => $value ? __('messages.' . $value) : '-'),
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
                ->filter(fn (Builder $builder, string $value) => $builder->where('year', $value)),
            SelectFilter::make(__('messages.distribution_class'), 'distribution_class_id')
                ->setWireLive()
                ->options(['' => __('messages.all')] + $classes)
                ->filter(fn (Builder $builder, string $value) => $builder->whereHas('recipients', fn (Builder $query) => $query->where('distribution_class_id', $value))),
            SelectFilter::make(__('messages.neighborhood_association'), 'neighborhood_association_id')
                ->setWireLive()
                ->options(['' => __('messages.all')] + $neighborhoods)
                ->filter(fn (Builder $builder, string $value) => $builder->where('neighborhood_association_id', $value)),
            SelectFilter::make(__('messages.status'), 'status')
                ->setWireLive()
                ->options([
                    '' => __('messages.all'),
                    'pending' => __('messages.pending'),
                    'completed' => __('messages.completed'),
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),
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
}
