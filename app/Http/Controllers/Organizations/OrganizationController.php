<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Models\Locations\Country;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:browse-organizations')->only('index');
        $this->middleware('permission:add-organizations')->only(['create', 'store']);
        $this->middleware('permission:edit-organizations')->only(['edit', 'update']);
        $this->middleware('permission:delete-organizations')->only('destroy');
    }

    public function index(): View
    {
        return view('organizations.index');
    }

    public function create(): View
    {
        return view('organizations.create', [
            'formPayload' => $this->formPayload(new Organization()),
        ]);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        $organization = Organization::create($data);

        if (! empty($profileData)) {
            $organization->profile()->create($profileData);
        }

        flash()->success(__('messages.created_successfully'));

        return response()->json([
            'redirect' => route('organizations.index'),
        ]);
    }

    public function edit(Organization $organization): View
    {
        $organization->load('profile');

        return view('organizations.edit', [
            'formPayload' => $this->formPayload($organization),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $data = $request->validated();
        $profileData = $data['profile'] ?? [];
        unset($data['profile']);

        $organization->update($data);

        if (! empty($profileData)) {
            $organization->profile()->updateOrCreate(
                ['organization_id' => $organization->id],
                $profileData
            );
        }

        flash()->success(__('messages.updated_successfully'));

        return response()->json([
            'redirect' => route('organizations.index'),
        ]);
    }

    protected function formPayload(Organization $organization): array
    {
        $organization->loadMissing('profile');
        $profile = $organization->profile;
        $defaultCountryId = $organization->country_id ?? Country::query()->orderBy('name')->value('id');

        return [
            'mode' => $organization->exists ? 'edit' : 'create',
            'form' => [
                'id' => $organization->id,
                'organization_category_id' => $organization->organization_category_id,
                'country_id' => $organization->country_id ?? $defaultCountryId,
                'province_id' => $organization->province_id,
                'city_id' => $organization->city_id,
                'district_id' => $organization->district_id,
                'village_id' => $organization->village_id,
                'citizens_association_id' => $organization->citizens_association_id,
                'neighborhood_association_id' => $organization->neighborhood_association_id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type ?? Organization::TYPE_RT,
                'status' => $organization->status ?? 'active',
                'email' => $organization->email,
                'phone' => $organization->phone,
                'website' => $organization->website,
                'timezone' => $organization->timezone ?? config('app.timezone'),
                'profile' => [
                    'description' => $profile->description ?? null,
                    'address_line' => $profile->address_line ?? null,
                ],
            ],
            'options' => [
                'countries' => Country::select('id', 'name', 'code')->orderBy('name')->get(),
                'categories' => OrganizationCategory::select('id', 'name')->orderBy('name')->get(),
                'types' => $this->typeOptions(),
                'statuses' => $this->statusOptions(),
            ],
            'routes' => [
                'store' => route('organizations.store'),
                'update' => $organization->exists ? route('organizations.update', $organization) : null,
                'locations' => [
                    'provinces' => route('ajax.locations.provinces'),
                    'cities' => route('ajax.locations.cities'),
                    'districts' => route('ajax.locations.districts'),
                    'villages' => route('ajax.locations.villages'),
                    'citizens' => route('ajax.locations.citizens'),
                    'neighborhoods' => route('ajax.locations.neighborhoods'),
                ],
            ],
        ];
    }

    protected function typeOptions(): array
    {
        return collect(Organization::typeLabels())
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    protected function statusOptions(): array
    {
        return [
            ['value' => 'active', 'label' => __('messages.active')],
            ['value' => 'inactive', 'label' => __('messages.inactive')],
        ];
    }
}
