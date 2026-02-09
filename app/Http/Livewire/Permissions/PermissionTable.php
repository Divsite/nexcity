<?php

namespace App\Http\Livewire\Permissions;

use App\Localization\PermissionLocalization;
use App\Models\Permissions\Permission;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class PermissionTable extends DataTableComponent
{
    protected $model = Permission::class;

    protected int $index = 0;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects('id')
            ->setSearchDisabled()
            ->setColumnSelectStatus(false)
            ->setFilterLayoutSlideDown()
            ->setTableWrapperAttributes([
                'class' => 'table-card mt-2',
            ])
            ->setTheadAttributes([
                'class' => 'table-light',
            ])
            ->setTableAttributes([
                'default' => false,
                'class' => 'table table-striped'
            ])
            ->setTrAttributes(fn($row, $index) => ['default' => false, 'class' => 'align-middle']);
    }

    public function columns(): array
    {
        return [
            Column::make('#')->label(
                fn($row, Column $column) => ++$this->index + ($this->paginators['page'] - 1) * $this->perPage
            ),
            Column::make(__('messages.name'), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__('messages.display_name'), 'display_name')
                ->sortable()
                ->searchable(),
            Column::make(__('messages.description'), 'description')
                ->sortable()
                ->searchable(),
            Column::make(__('messages.scope'), 'scope')
                ->sortable(),
            Column::make(__('messages.context'), 'context')
                ->sortable(),
            Column::make(__('messages.actions'))
                ->label(
                    fn($row, Column $column) => view('permissions.columns.table-actions')->withRow($row)
                )
                ->hideIf(
                    !auth()->user()->can('read-permissions')
                )
        ];
    }

    public function filters(): array
    {
        $scopeOptions = [
            '' => __('messages.all'),
            'global' => __('messages.global'),
            'partner' => __('messages.partner'),
        ];
        $contextOptions = [
            '' => __('messages.all'),
            'rt' => 'RT',
            'mosque' => __('messages.mosque'),
            'umkm' => __('messages.umkm'),
            'institution' => __('messages.institution'),
        ];

        return [
            TextFilter::make(__('messages.name'), 'name')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function(Builder $builder, string $value) {
                    $builder->where('name', 'like', '%'.$value.'%');
                }),
            TextFilter::make(__('messages.display_name'), 'display_name')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $displayName = PermissionLocalization::displayName();
                    $search = $displayName->filter(function ($item) use ($value) {
                        return false !== stripos($item, $value);
                    });
                    $builder->whereIn('display_name', $search->keys());
                }),
            TextFilter::make(__('messages.description'), 'description')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $description = PermissionLocalization::description();
                    $search = $description->filter(function ($item) use ($value) {
                        return false !== stripos($item, $value);
                    });
                    $builder->whereIn('description', $search->keys());
                }),
            SelectFilter::make(__('messages.scope'), 'scope')
                ->setWireLive()
                ->options($scopeOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('scope', $value);
                    }
                }),
            SelectFilter::make(__('messages.context'), 'context')
                ->setWireLive()
                ->options($contextOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('context', $value);
                    }
                }),
        ];
    }
}
