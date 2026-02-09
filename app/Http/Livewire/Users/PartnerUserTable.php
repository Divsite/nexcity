<?php

namespace App\Http\Livewire\Users;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class PartnerUserTable extends DataTableComponent
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
            Column::make(__('messages.role'), 'id')
                ->label(
                    fn($row, Column $column) => $row->organizationMemberships
                        ->firstWhere('organization_id', $this->organizationId())?->level_slug ?? '-',
                ),
            Column::make(__('messages.created_at'), "created_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
            Column::make(__('messages.actions'))
                ->label(
                    fn($row, Column $column) => view('settings.partner-users.columns.table-actions')->withRow($row)
                ),
        ];
    }

    public function builder(): Builder
    {
        return User::query()
            ->whereHas('organizationMemberships', function (Builder $query) {
                $query->where('organization_id', $this->organizationId());
            })
            ->whereDoesntHave('roles', function (Builder $query) {
                $query->where('name', 'resident');
            });
    }

    public function filters(): array
    {
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
            DateRangeFilter::make(__('messages.created_at'), 'created_at')
                ->setWireLive()
                ->config([
                    'allowInput' => false,
                    'altFormat' => 'd/m/Y',
                    'ariaDateFormat' => 'd/m/Y',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder
                        ->whereDate('users.created_at', '>=', $dateRange['minDate'])
                        ->whereDate('users.created_at', '<=', $dateRange['maxDate']);
                }),
        ];
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $isOrgSuperadmin = $user
            ? $user->organizationMemberships()
                ->where('is_primary', true)
                ->where('level_slug', 'like', '%-superadmin')
                ->exists()
            : false;

        if (! $isOrgSuperadmin) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));
            return to_route('settings.users.index');
        }

        $model = User::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.user_successfully_deleted'));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        return to_route('settings.users.index');
    }

    private function organizationId(): int
    {
        $user = auth()->user();
        $membership = $user?->organizationMemberships()
            ->where('is_primary', true)
            ->first();

        return $membership?->organization_id ?? 0;
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }
}
