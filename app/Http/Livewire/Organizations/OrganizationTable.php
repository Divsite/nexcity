<?php

namespace App\Http\Livewire\Organizations;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationCategory;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class OrganizationTable extends DataTableComponent
{
    protected $model = Organization::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects([
                'organizations.*',
            ])
            ->setDefaultSort('updated_at', 'desc')
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
        return Organization::query()
            ->with([
                'category',
                'province',
                'city',
                'district',
                'village',
                'citizensAssociation',
                'neighborhoodAssociation',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.organization'), 'name')
                ->sortable()
                ->searchable()
                ->view('organizations.columns.name-type'),
            Column::make(__('messages.organization_category'), 'organization_category_id')
                ->label(fn ($row) => view('organizations.columns.category')->withRow($row)),
            Column::make(__('messages.status'), 'status')
                ->sortable()
                ->view('organizations.columns.status'),
            Column::make(__('messages.village'), 'village_id')
                ->label(fn ($row) => view('organizations.columns.location')->withRow($row)),
            Column::make(__('messages.actions'))
                ->label(fn ($row) => view('organizations.columns.actions')->withRow($row))
                ->hideIf(
                    !auth()->user()->can('read-organizations') &&
                    !auth()->user()->can('edit-organizations') &&
                    !auth()->user()->can('delete-organizations')
                ),
        ];
    }

    public function filters(): array
    {
        $typeOptions = ['' => __('messages.all')] + collect(Organization::typeLabels())->toArray();
        $statusOptions = ['' => __('messages.all'), 'active' => __('messages.active'), 'inactive' => __('messages.inactive')];
        $categoryOptions = ['' => __('messages.all')] + OrganizationCategory::query()->orderBy('name')->pluck('name', 'id')->toArray();

        return [
            TextFilter::make(__('messages.name'), 'name')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(fn (Builder $builder, string $value) => $builder->where('name', 'like', '%' . $value . '%')),
            SelectFilter::make(__('messages.organization_type'), 'type')
                ->setWireLive()
                ->options($typeOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('type', $value);
                    }
                }),
            SelectFilter::make(__('messages.status'), 'status')
                ->setWireLive()
                ->options($statusOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('status', $value);
                    }
                }),
            SelectFilter::make(__('messages.organization_category'), 'organization_category_id')
                ->setWireLive()
                ->options($categoryOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('organization_category_id', $value);
                    }
                }),
        ];
    }

    public function destroy($id)
    {
        if (auth()->user()->cannot('delete-organizations')) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));

            return to_route('organizations.index');
        }

        $organization = Organization::query()->find($id);

        if (! $organization) {
            flash()->error(__('messages.something_went_wrong'));

            return to_route('organizations.index');
        }

        $organization->delete();

        flash()->success(__('messages.deleted_successfully'));

        return to_route('organizations.index');
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }
}
