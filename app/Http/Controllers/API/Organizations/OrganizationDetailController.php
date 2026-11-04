<?php

namespace App\Http\Controllers\API\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\QurbanProgramDetailResource;
use App\Models\CharityTypes\CharityType;
use App\Models\Organizations\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationDetailController extends Controller
{
    public function detail(string $slug): JsonResponse
    {
        $org = Organization::where('slug', $slug)
            ->with([
                'profile',
                'mosqueProfile',
                'rtProfile',
                'qurbanPrograms' => fn ($q) => $q->where('status', 'open'),
                'members',
                'citizensAssociation',
                'neighborhoodAssociation',
                'city',
            ])
            ->firstOrFail();

        $charityTypes = CharityType::with('source')
            ->where('organization_id', $org->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($ct) => [
                'id'         => $ct->id,
                'name'       => $ct->source?->name ?? 'Amal',
                'year'       => $ct->year,
                'is_rice'    => (bool) $ct->is_rice,
                'min_amount' => $ct->min_amount ? (int) $ct->min_amount : null,
                'max_amount' => $ct->max_amount ? (int) $ct->max_amount : null,
            ]);

        return response()->json([
            'id'          => $org->id,
            'slug'        => $org->slug,
            'name'        => $org->name,
            'type'        => $org->type,
            'logo'        => $org->profile?->logo_url,
            'cover'       => $org->profile?->cover_url ?? null,
            'description' => $org->profile?->description,
            'address'     => $org->profile?->address_line,
            'location'    => trim(implode(', ', array_filter([
                $org->neighborhoodAssociation?->name,
                $org->citizensAssociation?->name,
                $org->city?->name,
            ]))),
            'mosque_info' => $org->mosqueProfile ? [
                'built_year'     => $org->mosqueProfile->built_year,
                'floor_area'     => $org->mosqueProfile->floor_area,
                'latitude'       => $org->mosqueProfile->latitude,
                'longitude'      => $org->mosqueProfile->longitude,
            ] : null,
            'stats' => [
                'active_programs'   => $org->qurbanPrograms->count(),
                'members'           => $org->members->count(),
                'charity_types'     => $charityTypes->count(),
            ],
            'charity_types'         => $charityTypes,
            'has_active_qurban'     => $org->qurbanPrograms->isNotEmpty(),
        ]);
    }

    public function qurbanPrograms(Request $request, string $slug): AnonymousResourceCollection
    {
        $org = Organization::where('slug', $slug)->firstOrFail();

        $query = $org->qurbanPrograms()
            ->with(['packages', 'distributionBatches'])
            ->orderByDesc('year');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } else {
            $query->whereIn('status', ['open', 'closed']);
        }

        $programs = $query->paginate(10);

        return QurbanProgramDetailResource::collection($programs);
    }

    public function qurbanProgramDetail(string $slug, int $programId): QurbanProgramDetailResource
    {
        $org = Organization::where('slug', $slug)->firstOrFail();

        $program = $org->qurbanPrograms()
            ->with(['packages', 'distributionBatches', 'organization.profile'])
            ->findOrFail($programId);

        return new QurbanProgramDetailResource($program);
    }
}
