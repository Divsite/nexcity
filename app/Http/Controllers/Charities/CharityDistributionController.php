<?php

namespace App\Http\Controllers\Charities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Distributions\StoreDistributionRequest;
use App\Models\Distributions\Distribution;
use App\Http\Resources\Charities\CharityDistributionResource;
use App\Services\Charities\CharityDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CharityDistributionController extends Controller
{
    public function __construct(protected CharityDistributionService $service)
    {
        $this->middleware('permission:browse-mosque-charity-distributions')->only(['show', 'residents']);
        $this->middleware('permission:add-mosque-charity-distributions')->only(['store']);
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
