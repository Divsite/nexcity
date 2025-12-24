<?php

namespace App\Http\Livewire\ActivityLogs;

use App\Models\ActivityLogs\ActivityLog;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AllUserActivitiesTable extends DataTableComponent
{
    protected $model = ActivityLog::class;

    protected int $index = 0;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects(['activity_log.id', 'activity_log.causer_id', 'activity_log.causer_type'])
            ->setDefaultSort('created_at', 'desc')
            ->setSearchDisabled()
            ->setEagerLoadAllRelationsEnabled()
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

    public function builder(): Builder
    {
        return ActivityLog::query()->with('causer');
    }

    public function columns(): array
    {
        return [
            Column::make('#')->label(
                fn($row, Column $column) => ++$this->index + ($this->paginators['page'] - 1) * $this->perPage
            ),
            Column::make(__('messages.log_name'), "log_name")
                ->sortable(),
            Column::make(__('messages.description'), "description")
                ->sortable(),
            Column::make(__('messages.created_by'), 'causer_id')
                ->sortable(function (Builder $builder, string $direction) {
                    return $builder->leftJoin('users', function ($join) {
                        $join->on('users.id', '=', 'activity_log.causer_id')
                            ->where('activity_log.causer_type', User::class);
                    })->orderBy('users.name', $direction)->select(['users.*', 'activity_log.*']);
                })
                ->format(
                    fn($value, $row, Column $column) => $row->causer->name,
                ),
            Column::make(__('messages.created_at'), "created_at")
                ->sortable()
                ->format(
                    fn($value, $row, Column $column) => $value->format('d/m/Y h:i A')
                ),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make(__('messages.log_name'), 'log_name')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('log_name', 'like', '%'.$value.'%');
                }),
            TextFilter::make(__('messages.description'), 'description')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('description', 'like', '%'.$value.'%');
                }),
            TextFilter::make(__('messages.created_by'), 'created_by')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas('causer', function (Builder $query) use ($value) {
                        $query->where('name', 'like', '%'.$value.'%');
                    });
                }),
            DateRangeFilter::make(__('messages.created_at'), 'created_at')
                ->setWireLive()
                ->config([
                    'allowInput' => false,   // Allow manual input of dates
                    'altFormat' => 'd/m/Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'd/m/Y', // An aria-friendly date format
                ])
                ->filter(function (Builder $builder, array $dateRange) { // Expects an array.
                    $builder
                        ->whereDate('created_at', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('created_at', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),
        ];
    }
}
