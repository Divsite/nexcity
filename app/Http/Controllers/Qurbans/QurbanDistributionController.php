<?php

namespace App\Http\Controllers\Qurbans;

use App\Http\Controllers\Controller;
use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\Country;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Qurbans\QurbanCoupon;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Models\Users\User;
use App\Services\Qurbans\QurbanDistributionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QurbanDistributionController extends Controller
{
    public function __construct(protected QurbanDistributionService $service)
    {
        $this->middleware('capability:browse-qurban')->only(['index', 'residents']);
        $this->middleware('capability:add-qurban')->only(['storeBatch', 'updateBatch', 'storeCoupon', 'storeBulkCoupons']);
        $this->middleware('capability:delete-qurban')->only('deleteBatch');
        $this->middleware('capability:scan-qurban-coupon')->only('scanCoupon');
    }

    public function index(Request $request): View
    {
        $context = $this->service->partnerContext();
        if (! $context) {
            abort(403);
        }

        $year = $request->integer('year') ?: now()->year;

        $batches = QurbanDistributionBatch::query()
            ->with(['program', 'createdBy', 'officers', 'citizensAssociation', 'neighborhoodAssociation'])
            ->withCount([
                'coupons',
                'coupons as claimed_coupons_count' => fn (Builder $query) => $query->where('status', QurbanCoupon::STATUS_CLAIMED),
            ])
            ->where('organization_id', $context['organization_id'])
            ->where(function (Builder $query) use ($year) {
                $query->where('year', $year)
                    ->orWhere(function (Builder $legacyQuery) use ($year) {
                        $legacyQuery->whereNull('year')
                            ->whereYear('distribution_date', $year);
                    });
            })
            ->latest()
            ->get();

        $selectedBatch = $request->boolean('create')
            ? null
            : ($request->integer('batch_id')
            ? $batches->firstWhere('id', $request->integer('batch_id'))
            : $batches->first());

        if ($selectedBatch) {
            $selectedBatch->load([
                'coupons.beneficiary',
                'coupons.claims.scanner',
                'officers',
                'citizensAssociation',
                'neighborhoodAssociation',
            ]);
        }

        $residents = $selectedBatch
            ? $this->residentsForBatch($selectedBatch)
            : collect();

        $organization = Organization::query()
            ->with(['village', 'citizensAssociation.neighborhoodAssociations', 'neighborhoodAssociation'])
            ->find($context['organization_id']);

        $citizensAssociations = CitizensAssociation::query()
            ->select('id', 'name', 'number', 'village_id')
            ->when($organization?->village_id, fn (Builder $query) => $query->where('village_id', $organization->village_id))
            ->orderBy('number')
            ->get();

        $neighborhoodAssociations = NeighborhoodAssociation::query()
            ->select('id', 'name', 'number', 'citizens_association_id')
            ->when($citizensAssociations->isNotEmpty(), fn (Builder $query) => $query->whereIn('citizens_association_id', $citizensAssociations->pluck('id')))
            ->orderBy('number')
            ->get();

        $countries = Country::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $officers = $this->officersForOrganization((int) $context['organization_id']);
        $summary = $this->summaryForBatches($batches);

        return view('qurbans.index', [
            'batches' => $batches,
            'selectedBatch' => $selectedBatch,
            'residents' => $residents,
            'organization' => $organization,
            'countries' => $countries,
            'citizensAssociations' => $citizensAssociations,
            'neighborhoodAssociations' => $neighborhoodAssociations,
            'officers' => $officers,
            'summary' => $summary,
            'year' => $year,
            'organizationName' => $context['organization_name'],
        ]);
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'claim_date' => ['required', 'date'],
            'claim_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'coupon_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'country_id' => ['nullable', 'integer', 'exists:loc_countries,id'],
            'province_id' => ['nullable', 'integer', 'exists:loc_provinces,id'],
            'city_id' => ['nullable', 'integer', 'exists:loc_cities,id'],
            'district_id' => ['nullable', 'integer', 'exists:loc_districts,id'],
            'village_id' => ['nullable', 'integer', 'exists:loc_villages,id'],
            'citizens_association_id' => ['required', 'integer', 'exists:loc_citizens_associations,id'],
            'neighborhood_association_id' => ['required', 'integer', 'exists:loc_neighborhood_associations,id'],
            'officer_ids' => ['required', 'array', 'min:1'],
            'officer_ids.*' => ['integer', 'exists:users,id'],
            'mode' => ['required', 'string', 'in:residents,blank'],
            'count' => ['nullable', 'required_if:mode,blank', 'integer', 'min:1', 'max:1000'],
            'resident_ids' => ['nullable', 'required_if:mode,residents', 'array'],
            'resident_ids.*' => ['integer', 'exists:users,id'],
            'package_label' => ['nullable', 'string', 'max:255'],
            'meat_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $neighborhoodExists = NeighborhoodAssociation::query()
            ->where('id', $data['neighborhood_association_id'])
            ->where('citizens_association_id', $data['citizens_association_id'])
            ->exists();

        if (! $neighborhoodExists) {
            throw ValidationException::withMessages([
                'neighborhood_association_id' => __('messages.selected_location_invalid'),
            ]);
        }

        $exists = QurbanDistributionBatch::query()
            ->where('organization_id', $this->service->partnerContext()['organization_id'] ?? 0)
            ->where('year', $data['year'])
            ->where('neighborhood_association_id', $data['neighborhood_association_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'neighborhood_association_id' => __('messages.qurban_batch_location_year_exists'),
            ]);
        }

        $data['claim_starts_at'] = Carbon::parse($data['claim_date'] . ' ' . $data['claim_time']);
        $data['distribution_date'] = $data['claim_date'];
        $data['location_label'] = null;
        $data['coupon_color'] = $data['coupon_color'] ?? '#111111';

        $result = $this->service->createBatchWithCoupons($data);
        $batch = $result['batch'];

        flash()->success(__('messages.coupons_generated_successfully', ['count' => $result['coupons']->count()]));

        return redirect()->route('mosque.qurban', ['year' => $data['year'], 'batch_id' => $batch->id]);
    }

    public function updateBatch(Request $request, QurbanDistributionBatch $batch): RedirectResponse
    {
        $this->service->authorizeBatch($batch);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'claim_date' => ['required', 'date'],
            'claim_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'coupon_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'officer_ids' => ['required', 'array', 'min:1'],
            'officer_ids.*' => ['integer', 'exists:users,id'],
            'mode' => ['required', 'string', 'in:residents,blank'],
            'count' => ['nullable', 'required_if:mode,blank', 'integer', 'min:1', 'max:1000'],
            'resident_ids' => ['nullable', 'required_if:mode,residents', 'array'],
            'resident_ids.*' => ['integer', 'exists:users,id'],
            'package_label' => ['nullable', 'string', 'max:255'],
            'meat_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['claim_starts_at'] = Carbon::parse($data['claim_date'] . ' ' . $data['claim_time']);
        $data['distribution_date'] = $data['claim_date'];
        $data['coupon_color'] = $data['coupon_color'] ?? '#111111';

        $this->service->updateBatchWithCoupons($batch, $data);

        flash()->success(__('messages.updated_successfully'));

        return redirect()->route('mosque.qurban', ['year' => $data['year'], 'batch_id' => $batch->id]);
    }

    public function deleteBatch(QurbanDistributionBatch $batch): RedirectResponse
    {
        $year = $batch->year ?: now()->year;

        $this->service->deleteBatch($batch);

        flash()->success(__('messages.deleted_successfully'));

        return redirect()->route('mosque.qurban', ['year' => $year]);
    }

    public function storeCoupon(Request $request, QurbanDistributionBatch $batch): RedirectResponse
    {
        $data = $request->validate([
            'resident_id' => ['nullable', 'integer', 'exists:users,id'],
            'name_snapshot' => ['nullable', 'required_without:resident_id', 'string', 'max:255'],
            'phone_snapshot' => ['nullable', 'string', 'max:50'],
            'address_snapshot' => ['nullable', 'string', 'max:1000'],
            'package_label' => ['nullable', 'string', 'max:255'],
            'meat_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->service->issueCoupon($batch, $data);

        flash()->success(__('messages.created_successfully'));

        return redirect()->route('mosque.qurban', ['batch_id' => $batch->id]);
    }

    public function storeBulkCoupons(Request $request, QurbanDistributionBatch $batch): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'string', 'in:blank,residents'],
            'count' => ['nullable', 'required_if:mode,blank', 'integer', 'min:1', 'max:1000'],
            'resident_ids' => ['nullable', 'required_if:mode,residents', 'array'],
            'resident_ids.*' => ['integer', 'exists:users,id'],
            'package_label' => ['nullable', 'string', 'max:255'],
            'meat_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sharedPayload = [
            'package_label' => $data['package_label'] ?? null,
            'meat_weight' => $data['meat_weight'] ?? null,
        ];

        if ($data['mode'] === 'blank') {
            $created = $this->service->bulkIssueBlankCoupons($batch, (int) $data['count'], $sharedPayload);
        } else {
            $created = $this->service->bulkIssueResidentCoupons($batch, $data['resident_ids'] ?? [], $sharedPayload);
        }

        flash()->success(__('messages.coupons_generated_successfully', ['count' => $created->count()]));

        return redirect()->route('mosque.qurban', ['batch_id' => $batch->id]);
    }

    public function stats(Request $request): JsonResponse
    {
        $context = $this->service->partnerContext();
        if (! $context) {
            abort(403);
        }

        $year = $request->integer('year') ?: now()->year;

        $batches = QurbanDistributionBatch::query()
            ->withCount([
                'coupons',
                'coupons as claimed_coupons_count' => fn (Builder $query) => $query->where('status', QurbanCoupon::STATUS_CLAIMED),
            ])
            ->where('organization_id', $context['organization_id'])
            ->where(function (Builder $query) use ($year) {
                $query->where('year', $year)
                    ->orWhere(function (Builder $legacyQuery) use ($year) {
                        $legacyQuery->whereNull('year')
                            ->whereYear('distribution_date', $year);
                    });
            })
            ->get();

        return response()->json($this->summaryForBatches($batches));
    }

    public function scanCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'batch_id' => ['nullable', 'integer', 'exists:qurban_distribution_batches,id'],
            'scan_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'scan_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'scan_location_label' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->service->scanCoupon($data['code'], auth()->id(), $data['notes'] ?? null, [
            'qurban_distribution_batch_id' => $data['batch_id'] ?? null,
            'scan_latitude' => $data['scan_latitude'] ?? null,
            'scan_longitude' => $data['scan_longitude'] ?? null,
            'scan_location_label' => $data['scan_location_label'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($result['success'] ?? false) {
            flash()->success($result['message']);
        } else {
            flash()->error($result['message']);
        }

        $batchId = ($result['coupon'] ?? null)?->qurban_distribution_batch_id ?: ($request->integer('batch_id') ?: null);
        $batchYear = ($result['coupon'] ?? null)?->batch?->year;

        if (! $batchYear && $batchId) {
            $batchYear = QurbanDistributionBatch::query()->whereKey($batchId)->value('year');
        }

        return redirect()->route('mosque.qurban', array_filter([
            'year' => $batchYear,
            'batch_id' => $batchId,
        ]));
    }

    public function residents(Request $request): JsonResponse
    {
        $context = $this->service->partnerContext();
        if (! $context) {
            abort(403);
        }

        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'batch_id' => ['nullable', 'integer', 'exists:qurban_distribution_batches,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'exists:loc_countries,id'],
            'province_id' => ['nullable', 'exists:loc_provinces,id'],
            'city_id' => ['nullable', 'exists:loc_cities,id'],
            'district_id' => ['nullable', 'exists:loc_districts,id'],
            'village_id' => ['nullable', 'exists:loc_villages,id'],
            'citizens_association_id' => ['nullable', 'exists:loc_citizens_associations,id'],
            'neighborhood_association_id' => ['required', 'exists:loc_neighborhood_associations,id'],
        ]);

        return response()->json([
            'data' => $this->residentOptions($data, (int) $context['organization_id']),
        ]);
    }

    protected function residentsForBatch(QurbanDistributionBatch $batch)
    {
        if (! $batch->citizens_association_id || ! $batch->neighborhood_association_id) {
            return collect();
        }

        $couponResidentIds = $batch->coupons
            ->pluck('beneficiary.resident_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        return User::query()
            ->with(['residentProfile.citizensAssociation', 'residentProfile.neighborhoodAssociation'])
            ->whereHas('residentProfile', function (Builder $query) use ($batch) {
                if ($batch->citizens_association_id) {
                    $query->where('citizens_association_id', $batch->citizens_association_id);
                }

                if ($batch->neighborhood_association_id) {
                    $query->where('neighborhood_association_id', $batch->neighborhood_association_id);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $resident) use ($couponResidentIds) {
                $profile = $resident->residentProfile;

                return (object) [
                    'id' => $resident->id,
                    'name' => $resident->name,
                    'phone' => $resident->phone,
                    'address' => $profile?->address_line,
                    'rw' => $profile?->citizensAssociation?->number,
                    'rt' => $profile?->neighborhoodAssociation?->number,
                    'has_coupon' => in_array((int) $resident->id, $couponResidentIds, true),
                ];
            });
    }

    protected function residentOptions(array $data, int $organizationId)
    {
        $year = (int) ($data['year'] ?? now()->year);
        $batchId = isset($data['batch_id']) ? (int) $data['batch_id'] : null;
        $assignedResidentIds = QurbanCoupon::query()
            ->whereHas('batch', function (Builder $query) use ($organizationId, $year, $data, $batchId) {
                $query->where('organization_id', $organizationId)
                    ->where('year', $year)
                    ->where('neighborhood_association_id', $data['neighborhood_association_id']);

                if ($batchId) {
                    $query->where('id', '!=', $batchId);
                }
            })
            ->whereHas('beneficiary', fn (Builder $query) => $query->whereNotNull('resident_id'))
            ->with('beneficiary:id,resident_id')
            ->get()
            ->pluck('beneficiary.resident_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        return User::query()
            ->with(['residentProfile.citizensAssociation', 'residentProfile.neighborhoodAssociation'])
            ->whereHas('residentProfile', function (Builder $profile) use ($data) {
                foreach (Arr::only($data, [
                    'country_id',
                    'province_id',
                    'city_id',
                    'district_id',
                    'village_id',
                    'citizens_association_id',
                    'neighborhood_association_id',
                ]) as $field => $value) {
                    if ($value) {
                        $profile->where($field, $value);
                    }
                }
            })
            ->when(! empty($data['search']), fn (Builder $query) => $query->where('name', 'like', '%' . $data['search'] . '%'))
            ->orderBy('name')
            ->limit(300)
            ->get()
            ->map(function (User $resident) use ($assignedResidentIds) {
                $profile = $resident->residentProfile;

                return [
                    'id' => $resident->id,
                    'name' => $resident->name,
                    'phone' => $resident->phone,
                    'address' => $profile?->address_line,
                    'rw' => $profile?->citizensAssociation?->number,
                    'rt' => $profile?->neighborhoodAssociation?->number,
                    'disabled' => in_array((int) $resident->id, $assignedResidentIds, true),
                ];
            })
            ->values();
    }

    protected function officersForOrganization(int $organizationId)
    {
        return OrganizationUser::query()
            ->with(['user.mosqueProfile'])
            ->where('organization_id', $organizationId)
            ->where('level_slug', 'like', 'mosque-%')
            ->orderBy('id')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->user_id,
                'name' => $member->user?->name,
                'position' => $member->user?->mosqueProfile?->position
                    ?? ($member->level_slug === 'mosque-officer' ? __('messages.zakat_officer') : null),
            ])
            ->filter(fn ($item) => ! empty($item['id']))
            ->values();
    }

    protected function summaryForBatches($batches): array
    {
        $total = (int) $batches->sum('coupons_count');
        $claimed = (int) $batches->sum('claimed_coupons_count');

        return [
            'total' => $total,
            'claimed' => $claimed,
            'remaining' => max($total - $claimed, 0),
            'progress' => $total > 0 ? round(($claimed / $total) * 100, 2) : 0,
        ];
    }
}
