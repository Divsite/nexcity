<?php

namespace App\Http\Livewire\Memberships;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class MembershipTable extends DataTableComponent
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
            ->setTableWrapperAttributes(['class' => 'table-card mt-2'])
            ->setTheadAttributes(['class' => 'table-light'])
            ->setTableAttributes(['default' => false, 'class' => 'table table-striped'])
            ->setTrAttributes(fn () => ['default' => false, 'class' => 'align-middle']);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.name'), 'name')
                ->searchable()
                ->sortable()
                ->view('users.columns.avatar-name'),
            Column::make(__('messages.username'), 'username')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.email'), 'email')
                ->searchable()
                ->sortable(),
            Column::make(__('messages.position'), 'rtProfile.position')
                ->label(fn ($row) => $row->rtProfile?->position ?? '-'),
            Column::make(__('messages.level'), 'id')
                ->label(fn ($row) => $row->organizationMemberships
                    ->firstWhere('organization_id', $this->organizationId())?->level_slug ?? '-'),
        ];
    }

    public function builder(): Builder
    {
        return User::query()
            ->with(['rtProfile', 'organizationMemberships'])
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
                ->config(['placeholder' => __('messages.search')])
                ->filter(fn (Builder $builder, string $value) => $builder->where('name', 'like', '%' . $value . '%')),
            TextFilter::make(__('messages.email'), 'email')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(fn (Builder $builder, string $value) => $builder->where('email', 'like', '%' . $value . '%')),
        ];
    }

    private function organizationId(): int
    {
        $user = auth()->user();
        $membership = $user?->organizationMemberships()
            ->where('level_slug', 'like', 'rt-%')
            ->orderByDesc('is_primary')
            ->first();

        return $membership?->organization_id ?? 0;
    }
}
