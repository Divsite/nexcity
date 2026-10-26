<?php

namespace App\Http\Controllers\Residents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Residents\StoreResidentRequest;
use App\Http\Requests\Residents\UpdateResidentRequest;
use App\Models\Locations\Country;
use App\Models\Masters\Education;
use App\Models\Masters\EducationMajor;
use App\Models\Masters\MaritalStatus;
use App\Models\Masters\Religion;
use App\Models\Masters\ResidenceStatus;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Users\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravolt\Avatar\Facade as Avatar;

class ResidentController extends Controller
{
    public function __construct()
    {
        // `capability:` reads the caller's level, not their Spatie role. A role
        // check cannot separate a bendahara from a field officer, because every
        // RT officer carries the same rt_admin role.
        // See docs/operations/authorization-audit.md.
        //
        // The `add-residents` / `edit-residents` / `delete-residents`
        // alternatives that used to sit here were never defined as permissions,
        // so they could only ever have matched nothing.
        $this->middleware('capability:browse-rt-residents')->only('index');
        $this->middleware('capability:add-rt-residents')->only(['create', 'store']);
        $this->middleware('capability:edit-rt-residents')->only(['edit', 'update']);
        $this->middleware('capability:delete-rt-residents')->only('destroy');
        $this->middleware('capability:browse-rt-residents')->only(['qrCard', 'qrCards']);
    }

    public function index(): View
    {
        return view('residents.index');
    }

    public function create(): View
    {
        return view('residents.create', [
            'formPayload' => $this->formPayload(new User()),
        ]);
    }

    public function store(StoreResidentRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $profileData = $data['profile'];
            unset($data['profile']);

            $context = $this->partnerContext();
            if ($context) {
                $profileData = array_merge($profileData, $context['location']);
                $profileData['organization_id'] = $context['organization_id'];
                $profileData = $this->applyPartnerDefaults($profileData);
            }

            $username = $data['username'] ?? null;
            if (! $username) {
                $username = $this->generateUsername($data['name'], $context);
            }

            $email = $data['email'] ?? null;
            if (! $email) {
                $email = $this->generateEmail($data['name'], $context['organization_slug'] ?? null);
            }

            $user = DB::transaction(function () use ($data, $profileData, $username, $email) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $email,
                    'username' => $username,
                    'phone' => $data['phone'] ?? null,
                    'email_verified_at' => now(),
                    'password' => Hash::make($data['password'] ?? 'password'),
                    'initial_name' => User::AVATAR_INITIAL_NAME,
                    'avatar' => null,
                ]);

                $fileName = Str::random(30) . '.png';
                Avatar::create($user->name)
                    ->save(storage_path('app/public/' . User::AVATAR_PATH . $fileName), 100);
                $user->avatar = $fileName;
                $user->initial_name = User::AVATAR_INITIAL_NAME;
                $user->save();

                $user->assignRole('resident');
                $user->residentProfile()->create($profileData);

                if (! empty($profileData['organization_id'])) {
                    $user->organizations()->syncWithoutDetaching([
                        $profileData['organization_id'] => [
                            'role' => 'resident',
                            'level_slug' => null,
                            'is_primary' => true,
                            'joined_at' => now(),
                        ],
                    ]);
                }

                return $user;
            });

            activity(__('messages.residents'))
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log(__('messages.residents_has_been_created', ['name' => $user->name]));

            flash()->success(__('messages.created_successfully'));

            return response()->json([
                'success' => true,
                'redirect' => route('residents.index'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Resident create failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function edit(User $resident): View
    {
        $resident->load('residentProfile');

        return view('residents.edit', [
            'formPayload' => $this->formPayload($resident),
        ]);
    }

    public function update(UpdateResidentRequest $request, User $resident): JsonResponse
    {
        $data = $request->validated();
        $profileData = $data['profile'];
        unset($data['profile']);

        $context = $this->partnerContext();
        if ($context) {
            $profileData = array_merge($profileData, $context['location']);
            $profileData['organization_id'] = $context['organization_id'];
            $profileData = $this->applyPartnerDefaults($profileData);
        }

        $resident->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? $resident->email,
            'username' => $data['username'] ?? $resident->username,
            'phone' => $data['phone'] ?? null,
        ]);

        $resident->residentProfile()->updateOrCreate(
            ['user_id' => $resident->id],
            $profileData
        );

        activity(__('messages.residents'))
            ->causedBy(auth()->user())
            ->performedOn($resident)
            ->log(__('messages.residents_has_been_updated', ['name' => $resident->name]));

        flash()->success(__('messages.updated_successfully'));

        return response()->json([
            'success' => true,
            'redirect' => route('residents.index'),
        ]);
    }

    protected function formPayload(User $resident): array
    {
        $resident->loadMissing('residentProfile');
        $profile = $resident->residentProfile;
        $defaultCountryId = $profile?->country_id ?? Country::query()->orderBy('name')->value('id');
        $context = $this->partnerContext();

        return [
            'mode' => $resident->exists ? 'edit' : 'create',
            'context' => $context,
            'form' => [
                'id' => $resident->id,
                'name' => $resident->name,
                'email' => $resident->email,
                'username' => $resident->username,
                'phone' => $resident->phone,
                'password' => null,
                'profile' => [
                    'organization_id' => $context['organization_id'] ?? $profile?->organization_id,
                    'country_id' => $context['location']['country_id'] ?? $profile?->country_id ?? $defaultCountryId,
                    'province_id' => $context['location']['province_id'] ?? $profile?->province_id,
                    'city_id' => $context['location']['city_id'] ?? $profile?->city_id,
                    'district_id' => $context['location']['district_id'] ?? $profile?->district_id,
                    'village_id' => $context['location']['village_id'] ?? $profile?->village_id,
                    'citizens_association_id' => $context['location']['citizens_association_id'] ?? $profile?->citizens_association_id,
                    'neighborhood_association_id' => $context['location']['neighborhood_association_id'] ?? $profile?->neighborhood_association_id,
                    'national_id_number' => $profile?->national_id_number,
                    'family_card_number' => $profile?->family_card_number,
                    'birth_place' => $profile?->birth_place,
                    'birth_date' => optional($profile?->birth_date)?->format('Y-m-d'),
                    'gender' => $profile?->gender,
                    'residence_status_id' => $profile?->residence_status_id,
                    'marital_status_id' => $profile?->marital_status_id,
                    'education_id' => $profile?->education_id,
                    'education_major_id' => $profile?->education_major_id,
                    'religion_id' => $profile?->religion_id,
                    'occupation' => $profile?->occupation,
                    'is_head_family' => $profile?->is_head_family ?? false,
                    'family_members_count' => $profile?->family_members_count ?? 0,
                    'interests' => $profile?->interests ?? [],
                    'talents' => $profile?->talents ?? [],
                    'ktp_photo_path' => $profile?->ktp_photo_path,
                    'house_photo_paths' => $profile?->house_photo_paths ?? [],
                    'address_line' => $profile?->address_line,
                ],
            ],
            'options' => [
                'countries' => Country::select('id', 'name', 'code')->orderBy('name')->get(),
                'organizations' => Organization::select('id', 'name', 'type')
                    ->orderBy('name')
                    ->get(),
                'residence_statuses' => ResidenceStatus::select('id', 'name')->orderBy('name')->get(),
                'marital_statuses' => MaritalStatus::select('id', 'name')->orderBy('name')->get(),
                'educations' => Education::select('id', 'name')->orderBy('name')->get(),
                'education_majors' => EducationMajor::select('id', 'name', 'education_id')
                    ->orderBy('name')
                    ->get(),
                'religions' => Religion::select('id', 'name')->orderBy('name')->get(),
                'genders' => [
                    ['value' => 'male', 'label' => __('messages.male')],
                    ['value' => 'female', 'label' => __('messages.female')],
                ],
            ],
            'routes' => [
                'store' => route('residents.store'),
                'update' => $resident->exists ? route('residents.update', $resident) : null,
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

    protected function partnerContext(): ?array
    {
        $user = auth()->user();

        if (! $user || $user->hasRole('superadmin')) {
            return null;
        }

        $membership = $user->organizationMemberships()
            ->where('level_slug', 'like', 'rt-%')
            ->orderByDesc('is_primary')
            ->first();

        $organizationId = $membership?->organization_id;

        if (! $organizationId && $user->relationLoaded('rtProfile') === false) {
            $user->load('rtProfile');
        }

        if (! $organizationId && $user->rtProfile?->organization_id) {
            $organizationId = $user->rtProfile->organization_id;
        }

        if (! $organizationId) {
            return null;
        }

        $organization = Organization::query()->with([
            'country',
            'province',
            'city',
            'district',
            'village',
            'citizensAssociation',
            'neighborhoodAssociation',
        ])->find($organizationId);

        if (! $organization) {
            return null;
        }

        return [
            'mode' => 'partner',
            'organization_id' => $organization->id,
            'organization_slug' => $organization->slug,
            'location_meta' => [
                'rt' => $organization->neighborhoodAssociation?->number,
                'rw' => $organization->citizensAssociation?->number,
                'village' => $organization->village?->name,
                'district' => $organization->district?->name,
                'city' => $organization->city?->name,
                'province' => $organization->province?->name,
            ],
            'location' => [
                'country_id' => $organization->country_id,
                'province_id' => $organization->province_id,
                'city_id' => $organization->city_id,
                'district_id' => $organization->district_id,
                'village_id' => $organization->village_id,
                'citizens_association_id' => $organization->citizens_association_id,
                'neighborhood_association_id' => $organization->neighborhood_association_id,
            ],
        ];
    }

    protected function generateUsername(string $name, ?array $context = null): string
    {
        $base = Str::slug($name);
        $meta = $context['location_meta'] ?? [];
        $rt = $meta['rt'] ?? null;
        $rw = $meta['rw'] ?? null;

        $parts = array_filter([
            $rt ? 'rt' . str_pad($rt, 3, '0', STR_PAD_LEFT) : null,
            $rw ? 'rw' . str_pad($rw, 3, '0', STR_PAD_LEFT) : null,
            $this->shortSlug($meta['village'] ?? null),
            $this->shortSlug($meta['district'] ?? null),
            $this->shortSlug($meta['city'] ?? null),
            $this->shortSlug($meta['province'] ?? null),
        ]);

        $suffix = ! empty($parts) ? implode('.', $parts) : 'resident';

        return $base . '.' . $suffix . '.' . Str::lower(Str::random(4));
    }

    protected function generateEmail(string $name, ?string $organizationSlug = null): string
    {
        $base = Str::slug($name);
        $suffix = $organizationSlug ? Str::slug($organizationSlug) : 'resident';

        return $base . '.' . $suffix . '.' . Str::lower(Str::random(4)) . '@nexcity.local';
    }

    protected function applyPartnerDefaults(array $profileData): array
    {
        $defaults = $this->defaultMasterIds();

        return array_merge([
            'residence_status_id' => $defaults['residence_status_id'],
            'marital_status_id' => $defaults['marital_status_id'],
            'education_id' => $defaults['education_id'],
            'education_major_id' => $defaults['education_major_id'],
            'religion_id' => $defaults['religion_id'],
        ], $profileData);
    }

    protected function defaultMasterIds(): array
    {
        $residenceStatusId = ResidenceStatus::query()->orderBy('name')->value('id');
        $maritalStatusId = MaritalStatus::query()->orderBy('name')->value('id');
        $educationId = Education::query()->orderBy('name')->value('id');
        $educationMajorId = EducationMajor::query()->orderBy('name')->value('id');
        $religionId = Religion::query()->orderBy('name')->value('id');

        return [
            'residence_status_id' => $residenceStatusId,
            'marital_status_id' => $maritalStatusId,
            'education_id' => $educationId,
            'education_major_id' => $educationMajorId,
            'religion_id' => $religionId,
        ];
    }

    protected function shortSlug(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $slug = Str::slug($value, '');

        return strlen($slug) > 6 ? substr($slug, 0, 6) : $slug;
    }

    /**
     * One resident's card, as a print-ready PDF.
     */
    public function qrCard(User $resident): Response
    {
        $this->authorizeResidentAccess($resident);

        $resident->load(['residentProfile.neighborhoodAssociation', 'residentProfile.citizensAssociation']);
        $profile = $resident->residentProfile;

        abort_if(! $profile || ! $profile->qr_token, 404);

        // CR80, the ID-card size — same as a KTP, so it fits an existing wallet
        // or lanyard rather than needing something bought for it.
        $pdf = Pdf::loadView('residents.qr-card', ['residents' => [$resident]])
            ->setPaper([0, 0, 241.89, 153.07]);

        return $pdf->stream("kartu-warga-{$resident->id}.pdf");
    }

    /**
     * Every resident of the current RT, one card per page.
     *
     * Printing one at a time does not match how these are actually used: cards
     * are handed out to a whole RT before a distribution, not individually.
     */
    public function qrCards(): Response
    {
        $organizationId = $this->partnerOrganizationId();

        $residents = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'resident'))
            ->whereHas('residentProfile', function ($query) use ($organizationId) {
                $query->whereNotNull('qr_token');
                if ($organizationId) {
                    $query->where('organization_id', $organizationId);
                }
            })
            ->with(['residentProfile.neighborhoodAssociation', 'residentProfile.citizensAssociation'])
            ->orderBy('name')
            ->get();

        abort_if($residents->isEmpty(), 404, __('messages.no_residents_with_qr_token'));

        $pdf = Pdf::loadView('residents.qr-card', ['residents' => $residents])
            ->setPaper([0, 0, 241.89, 153.07]);

        return $pdf->stream('kartu-warga.pdf');
    }

    /**
     * A partner may only reach residents of the organization they belong to.
     *
     * The listing screen already scopes by organization, but a direct URL does
     * not go through it — without this, guessing an id would print a card for
     * someone in another RT.
     */
    protected function authorizeResidentAccess(User $resident): void
    {
        $organizationId = $this->partnerOrganizationId();

        // Superadmin has no partner organization and is not scoped.
        if (! $organizationId) {
            return;
        }

        abort_if(
            $resident->residentProfile?->organization_id !== $organizationId,
            403,
        );
    }

    /**
     * The RT this user administers, or null for superadmin.
     *
     * Mirrors ResidentTable::partnerOrganizationId() so the listing and the
     * print routes agree on who is visible.
     */
    protected function partnerOrganizationId(): ?int
    {
        $user = auth()->user();

        if (! $user || $user->hasRole('superadmin')) {
            return null;
        }

        return $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', 'rt-%')
            ->first()?->organization_id;
    }

    public function destroy(User $resident): JsonResponse
    {
        if ($resident->hasRole('resident')) {
            $resident->delete();
        }

        flash()->success(__('messages.deleted_successfully'));

        return response()->json([
            'redirect' => route('residents.index'),
        ]);
    }
}
