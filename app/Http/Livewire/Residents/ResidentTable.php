<?php

namespace App\Http\Livewire\Residents;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class ResidentTable extends DataTableComponent
{
    protected $model = User::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects([
                'users.id as id',
                'users.email',
                'users.username',
                'users.phone',
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
        $builder = User::query()
            ->with([
                'residentProfile.organization',
                'residentProfile.village',
                'residentProfile.citizensAssociation',
                'residentProfile.neighborhoodAssociation',
            ])
            ->whereHas('roles', fn ($query) => $query->where('name', 'resident'));

        $organizationId = $this->partnerOrganizationId();
        if ($organizationId) {
            $builder->whereHas('residentProfile', fn ($query) => $query->where('organization_id', $organizationId));
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.resident'), 'name')
                ->sortable()
                ->searchable()
                ->view('residents.columns.identity'),
            Column::make(__('messages.organization'), 'residentProfile.organization_id')
                ->label(fn ($row) => view('residents.columns.organization')->withRow($row)),
            Column::make(__('messages.phone_number'), 'phone')
                ->label(fn ($row) => view('residents.columns.contact')->withRow($row)),
            Column::make(__('messages.village'), 'residentProfile.village_id')
                ->label(fn ($row) => view('residents.columns.location')->withRow($row)),
            Column::make(__('messages.actions'))
                ->label(fn ($row) => view('residents.columns.actions')->withRow($row))
                ->hideIf(
                    !auth()->user()->can('read-rt-residents') &&
                    !auth()->user()->can('edit-rt-residents') &&
                    !auth()->user()->can('delete-rt-residents')
                ),
        ];
    }

    public function filters(): array
    {
        $organizationQuery = Organization::query()->where('type', Organization::TYPE_RT);
        $organizationId = $this->partnerOrganizationId();
        if ($organizationId) {
            $organizationQuery->where('id', $organizationId);
        }

        $organizationOptions = ['' => __('messages.all')] + $organizationQuery
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $genderOptions = [
            '' => __('messages.all'),
            'male' => __('messages.male'),
            'female' => __('messages.female'),
        ];

        return [
            TextFilter::make(__('messages.name'), 'name')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(fn (Builder $builder, string $value) => $builder->where('name', 'like', '%' . $value . '%')),
            TextFilter::make(__('messages.email'), 'email')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(fn (Builder $builder, string $value) => $builder->where('email', 'like', '%' . $value . '%')),
            TextFilter::make(__('messages.national_id'), 'national_id_number')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas('residentProfile', fn ($query) => $query->where('national_id_number', 'like', '%' . $value . '%'));
                }),
            SelectFilter::make(__('messages.organization'), 'organization_id')
                ->setWireLive()
                ->options($organizationOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->whereHas('residentProfile', fn ($query) => $query->where('organization_id', $value));
                    }
                }),
            SelectFilter::make(__('messages.gender'), 'gender')
                ->setWireLive()
                ->options($genderOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->whereHas('residentProfile', fn ($query) => $query->where('gender', $value));
                    }
                }),
        ];
    }

    private function partnerOrganizationId(): ?int
    {
        $user = auth()->user();

        if (! $user || $user->hasRole('superadmin')) {
            return null;
        }

        $membership = $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', 'rt-%')
            ->first();

        return $membership?->organization_id;
    }

    public function destroy($id)
    {
        if (auth()->user()->cannot('delete-rt-residents')) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));

            return to_route('residents.index');
        }

        $resident = User::query()->find($id);

        if (! $resident || ! $resident->hasRole('resident')) {
            flash()->error(__('messages.something_went_wrong'));

            return to_route('residents.index');
        }

        $resident->delete();

        flash()->success(__('messages.deleted_successfully'));

        return to_route('residents.index');
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }
}
