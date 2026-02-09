<?php

namespace App\Http\Livewire\Menus;

use App\Models\Menus\UserMenu;
use App\Models\Organizations\Organization;
use App\Models\Organizations\UserLevel;
use App\Services\Menus\MenuCacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class MenuTable extends DataTableComponent
{
    protected $model = UserMenu::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects([
                'user_menus.id as id',
                'user_menus.section',
                'user_menus.icon',
                'user_menus.route_name',
                'user_menus.route_parameters',
                'user_menus.url',
                'user_menus.order',
                'user_menus.visibility_rules',
            ])
            ->setDefaultSort('order')
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
        return UserMenu::query()
            ->with(['organization', 'userLevel.organization']);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.label'), 'label')
                ->sortable()
                ->searchable()
                ->view('settings.menus.columns.label'),
            Column::make('Context', 'context')
                ->sortable()
                ->view('settings.menus.columns.context'),
            Column::make(__('messages.organization'), 'organization_id')
                ->label(fn ($row) => view('settings.menus.columns.target')->withRow($row)),
            Column::make('Route / URL', 'route_name')
                ->label(fn ($row) => view('settings.menus.columns.route')->withRow($row)),
            Column::make(__('messages.status'), 'is_active')
                ->view('settings.menus.columns.status'),
            Column::make(__('messages.actions'))
                ->label(fn ($row) => view('settings.menus.columns.actions')->withRow($row))
                ->hideIf(auth()->user()->cannot('edit-user-menus')),
        ];
    }

    public function filters(): array
    {
        $contextOptions = ['' => __('messages.all')] + Arr::mapWithKeys($this->contexts(), function ($label, $key) {
            return [$key => $label];
        });

        $statusOptions = ['' => __('messages.all'), '1' => __('messages.active'), '0' => __('messages.inactive')];

        $organizationOptions = ['' => __('messages.all')] + Organization::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $levelOptions = ['' => __('messages.all')] + UserLevel::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return [
            TextFilter::make(__('messages.label'), 'label')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(fn (Builder $builder, string $value) => $builder->where('label', 'like', '%' . $value . '%')),
            SelectFilter::make('Context', 'context')
                ->setWireLive()
                ->options($contextOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('context', $value);
                    }
                }),
            SelectFilter::make(__('messages.status'), 'is_active')
                ->setWireLive()
                ->options($statusOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('is_active', $value === '1');
                    }
                }),
            SelectFilter::make(__('messages.organization'), 'organization_id')
                ->setWireLive()
                ->options($organizationOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('organization_id', $value);
                    }
                }),
            SelectFilter::make('Level', 'user_level_id')
                ->setWireLive()
                ->options($levelOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('user_level_id', $value);
                    }
                }),
        ];
    }

    public function destroy($id)
    {
        if (auth()->user()->cannot('edit-user-menus')) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));

            return to_route('menus.index');
        }

        $menu = UserMenu::query()->find($id);

        if (! $menu) {
            flash()->error(__('messages.something_went_wrong'));

            return to_route('menus.index');
        }

        $menu->delete();

        app(MenuCacheService::class)->flush($menu->context, $menu->organization_id);

        flash()->success(__('messages.deleted_successfully'));

        return to_route('menus.index');
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }

    protected function contexts(): array
    {
        return [
            'admin' => 'Admin',
            'rt' => 'RT',
            'mosque' => 'Mosque',
            'partner' => 'Partner',
            'resident' => 'Resident',
        ];
    }
}
