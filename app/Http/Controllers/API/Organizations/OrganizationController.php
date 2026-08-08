<?php

namespace App\Http\Controllers\API\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\OrganizationResource;
use App\Models\Organizations\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Organization::query()
            ->with(['profile', 'qurbanPrograms', 'members']);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $organizations = $query
            ->orderBy('name')
            ->paginate(20);

        return OrganizationResource::collection($organizations);
    }

    public function show(string $slug): OrganizationResource
    {
        $organization = Organization::query()
            ->where('slug', $slug)
            ->with(['profile', 'qurbanPrograms.packages', 'members'])
            ->firstOrFail();

        return new OrganizationResource($organization);
    }
}
