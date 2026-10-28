<?php

namespace App\Http\Controllers\API\Home;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\OrganizationResource;
use App\Http\Resources\API\QurbanProgramResource;
use App\Models\Organizations\Organization;
use App\Models\Qurbans\QurbanProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // User's organizations (RT, Masjid they belong to)
        $myOrgs = $user->organizations()
            ->with(['profile'])
            ->get();

        // Active qurban programs from the user's organizations
        $orgIds = $myOrgs->pluck('id');

        $activePrograms = QurbanProgram::query()
            ->whereIn('organization_id', $orgIds)
            ->where('status', QurbanProgram::STATUS_OPEN)
            ->with(['organization.profile'])
            ->latest('period_start_at')
            ->take(5)
            ->get();

        // Public programs from any mosque (discoverable)
        $publicPrograms = QurbanProgram::query()
            ->where('status', QurbanProgram::STATUS_OPEN)
            ->where('is_public', true)
            ->whereNotIn('organization_id', $orgIds)
            ->with(['organization.profile'])
            ->latest('period_start_at')
            ->take(3)
            ->get();

        return response()->json([
            'my_organizations' => OrganizationResource::collection($myOrgs),
            'active_programs'  => QurbanProgramResource::collection($activePrograms),
            'public_programs'  => QurbanProgramResource::collection($publicPrograms),
        ]);
    }
}
