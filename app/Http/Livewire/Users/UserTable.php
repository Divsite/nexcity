<?php

namespace App\Http\Livewire\Users;

use App\Models\Roles\Role;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class UserTable extends DataTableComponent
{
    protected $model = User::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects(['id', 'avatar'])
            ->setSearchDisabled()
            ->setDefaultSort('updated_at', 'desc')
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
            Column::make(__('messages.name'), "name")
                ->searchable()
                ->sortable()
                ->view('users.columns.avatar-name'),
            Column::make(__('messages.username'), "username")
                ->sortable()
                ->searchable(),
            Column::make(__('messages.email'), "email")
                ->sortable()
                ->searchable(),
            Column::make(__('messages.verified'), "email_verified_at")
                ->sortable()
                ->view('users.columns.verified'),
            Column::make(__('messages.role'), 'id')
//                ->sortable(function(Builder $query, $direction) {
//                    return $query->leftJoin('model_has_roles', function ($join) {
//                        $join->on('model_has_roles.model_id', '=', 'users.id')
//                            ->where('model_has_roles.model_type', User::class);
//                    })->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
//                        ->orderBy('roles.display_name', $direction)->select('users.*');
//                })
                ->label(
                    fn($row, Column $column) => $row->roles->implode('display_name', ', '),
                ),
            Column::make(__('messages.created_at'), "created_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
            Column::make(__('messages.updated_at'), "updated_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->diffForHumans() : '-'),
            Column::make(__('messages.actions'))
                ->label(
                    fn($row, Column $column) => view('users.columns.table-actions')->withRow($row)
                )
                ->hideIf(
                    !auth()->user()->can('read-users') &&
                    !auth()->user()->can('edit-users') &&
                    !auth()->user()->can('delete-users')
                )
        ];
    }

    public function builder(): Builder
    {
        return User::query();
    }

    public function filters(): array
    {
        $roles = Role::all()->pluck('display_name', 'id')->toArray();
        $rolesOptions = ['' => __('messages.all')] + $roles;

        $status = [User::VERIFIED => __('messages.verified'), User::UNVERIFIED => __('messages.unverified')];
        $statusOptions = ['' => __('messages.all')] + $status;

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
            TextFilter::make(__('messages.email'), 'email')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function(Builder $builder, string $value) {
                    $builder->where('email', 'like', '%'.$value.'%');
                }),
            SelectFilter::make(__('messages.verified'), 'email_verified_at')
                ->setWireLive()
                ->options($statusOptions)
                ->filter(function(Builder $builder, string $value) {
                    if ($value == User::VERIFIED) {
                        $builder->whereNotNull('email_verified_at');
                    }

                    if ($value == User::UNVERIFIED) {
                        $builder->whereNull('email_verified_at');
                    }
                }),
            SelectFilter::make(__('messages.role'))
                ->setWireLive()
                ->options($rolesOptions)
                ->filter(function(Builder $builder, string $value) {
                    $builder->whereHas('roles', fn($query) => $query->where('id', $value));
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
                        ->whereDate('users.created_at', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('users.created_at', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),
        ];
    }

    public function destroy($id)
    {
        if (auth()->user()->cannot('delete-users')) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));
            return to_route('dashboard');
        }

        if (config('core.custom_user_module_enabled')) {
            return redirect()->route(config('core.user_module_route_name'));
        }

        $model = User::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.user_successfully_deleted'));

            activity(__('messages.users'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.users_has_been_deleted', ['name' => $model->name]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        return to_route('users.index');
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }
}
