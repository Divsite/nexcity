<?php

namespace App\Http\Controllers\Charities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Distributions\StoreDistributionRequest;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionFundSource;
use App\Http\Resources\Charities\CharityDistributionResource;
use App\Services\Charities\CharityDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CharityDistributionController extends Controller
{
    public function __construct(protected CharityDistributionService $service)
    {
        $this->middleware('permission:browse-mosque-charity-distributions')->only(['show', 'residents', 'summary', 'fundSources', 'form']);
        $this->middleware('permission:add-mosque-charity-distributions')->only(['store']);
        $this->middleware('permission:edit-mosque-charity-distributions')->only(['update', 'storeFundSource', 'deleteFundSource']);
    }

    public function store(StoreDistributionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isModal = $request->input('_mode') === 'modal';
        $distribution = $this->service->store($data);

        if ($isModal) {
            return response()->json([
                'success' => true,
                'message' => __('messages.created_successfully'),
                'id' => $distribution->id,
            ]);
        }

        flash()->success(__('messages.created_successfully'));

        return response()->json([
            'redirect' => route('mosque.charity-transactions.index'),
        ]);
    }

    public function form(Distribution $distribution): JsonResponse
    {
        $this->service->authorizeDistribution($distribution);

        return response()->json(
            (new CharityDistributionResource($this->service->formPayload($distribution)))->toArray(request())
        );
    }

    public function show(Distribution $distribution): View
    {
        $this->service->authorizeDistribution($distribution);

        $distribution->load([
            'organization',
            'officers.officer',
            'neighborhoodAssociation',
            'citizensAssociation',
        ]);

        $distributionClass = $distribution->recipients()
            ->with('distributionClass.source')
            ->whereNotNull('distribution_class_id')
            ->first()?->distributionClass;

        return view('distributions.show', [
            'distribution' => $distribution,
            'distributionClass' => $distributionClass,
            'canViewAll' => $this->service->canViewAllDistributions(),
        ]);
    }

    public function update(StoreDistributionRequest $request, Distribution $distribution): JsonResponse
    {
        $data = $request->validated();
        $isModal = $request->input('_mode') === 'modal';

        $updated = $this->service->update($distribution, $data);

        if ($isModal) {
            return response()->json([
                'success' => true,
                'message' => __('messages.updated_successfully'),
                'id' => $updated->id,
            ]);
        }

        flash()->success(__('messages.updated_successfully'));

        return response()->json([
            'redirect' => route('mosque.charity-distributions.show', $updated),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'distribution_class_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2000'],
            'neighborhood_association_id' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'summary' => $this->service->distributionSummaryWithFilters(
                $data['distribution_class_id'] ?? null,
                $data['year'] ?? null,
                $data['neighborhood_association_id'] ?? null
            ),
        ]);
    }

    public function fundSources(Request $request): JsonResponse
    {
        $data = $request->validate([
            'distribution_class_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2000'],
            'neighborhood_association_id' => ['nullable', 'integer'],
        ]);

        return response()->json(
            $this->service->fundSourcesPayload(
                $data['distribution_class_id'] ?? null,
                $data['year'] ?? null,
                $data['neighborhood_association_id'] ?? null
            )
        );
    }

    public function storeFundSource(Request $request): JsonResponse
    {
        $data = $request->validate([
            'distribution_class_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2000'],
            'neighborhood_association_id' => ['nullable', 'integer'],
            'charity_type_ids' => ['nullable', 'array'],
            'charity_type_ids.*' => ['integer', 'exists:charity_types,id'],
            'other_source_name' => ['nullable', 'string', 'max:255'],
            'other_source_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->service->storeFundSource(
            $data['distribution_class_id'] ?? null,
            $data['year'] ?? null,
            $data['neighborhood_association_id'] ?? null,
            $data
        );

        return response()->json([
            'success' => true,
            'payload' => $this->service->fundSourcesPayload(
                $data['distribution_class_id'] ?? null,
                $data['year'] ?? null,
                $data['neighborhood_association_id'] ?? null
            ),
        ]);
    }

    public function deleteFundSource(DistributionFundSource $fundSource): JsonResponse
    {
        $this->service->deleteFundSource($fundSource);

        return response()->json([
            'success' => true,
        ]);
    }

    public function residents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'exists:loc_countries,id'],
            'province_id' => ['nullable', 'exists:loc_provinces,id'],
            'city_id' => ['nullable', 'exists:loc_cities,id'],
            'district_id' => ['nullable', 'exists:loc_districts,id'],
            'village_id' => ['nullable', 'exists:loc_villages,id'],
            'citizens_association_id' => ['nullable', 'exists:loc_citizens_associations,id'],
            'neighborhood_association_id' => ['nullable', 'exists:loc_neighborhood_associations,id'],
        ]);

        return response()->json(
            (new CharityDistributionResource($this->service->residents($data)))->toArray($request)
        );
    }
}
