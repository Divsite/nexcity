<?php

namespace App\Http\Controllers\Locations;

use App\Http\Controllers\Controller;
use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\City;
use App\Models\Locations\District;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Locations\Province;
use App\Models\Locations\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationLookupController extends Controller
{
    public function provinces(Request $request): JsonResponse
    {
        $items = Province::query()
            ->select('id', 'name', 'code', 'country_id')
            ->when($request->filled('country_id'), function ($query) use ($request) {
                $query->where('country_id', $request->integer('country_id'));
            })
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => ['required', 'exists:loc_provinces,id'],
        ]);

        $items = City::query()
            ->select('id', 'name', 'code', 'province_id')
            ->where('province_id', $data['province_id'])
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    public function districts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:loc_cities,id'],
        ]);

        $items = District::query()
            ->select('id', 'name', 'code', 'city_id')
            ->where('city_id', $data['city_id'])
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    public function villages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district_id' => ['required', 'exists:loc_districts,id'],
        ]);

        $items = Village::query()
            ->select('id', 'name', 'code', 'postal_code', 'district_id')
            ->where('district_id', $data['district_id'])
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    public function citizensAssociations(Request $request): JsonResponse
    {
        $data = $request->validate([
            'village_id' => ['required', 'exists:loc_villages,id'],
        ]);

        $items = CitizensAssociation::query()
            ->select('id', 'name', 'slug', 'number', 'village_id', 'start_period', 'end_period')
            ->where('village_id', $data['village_id'])
            ->orderBy('number')
            ->get();

        return response()->json($items);
    }

    public function neighborhoodAssociations(Request $request): JsonResponse
    {
        $data = $request->validate([
            'citizens_association_id' => ['required', 'exists:loc_citizens_associations,id'],
        ]);

        $items = NeighborhoodAssociation::query()
            ->select('id', 'name', 'slug', 'number', 'citizens_association_id', 'start_period', 'end_period')
            ->where('citizens_association_id', $data['citizens_association_id'])
            ->orderBy('number')
            ->get();

        return response()->json($items);
    }
}
