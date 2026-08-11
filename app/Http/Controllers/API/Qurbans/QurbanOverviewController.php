<?php

namespace App\Http\Controllers\API\Qurbans;

use App\Http\Controllers\Controller;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The mosque's own qurban season, as the Qurban tab reads it.
 *
 * Distinct from the resident-facing endpoint on OrganizationDetailController:
 * that one answers "what can I buy from this mosque", this one answers "how is
 * my programme going". A pengurus needs the quota left and where the animals
 * are; a resident needs neither.
 *
 * Everything hangs off a programme — packages, orders, animals, coupons,
 * beneficiaries all reference one. With no programme open there is nothing to
 * report, and the screen says so rather than showing a row of zeroes.
 */
class QurbanOverviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('capability:browse-qurban');
    }

    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        if ($organizationId === 0) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
        $year = (int) ($validated['year'] ?? now()->year);

        $programs = QurbanProgram::query()
            ->where('organization_id', $organizationId)
            ->where('year', $year)
            ->with(['packages' => fn ($q) => $q->where('is_active', true)])
            ->orderByDesc('period_start_at')
            ->get();

        // Animals are held by the organization, not the programme — a mosque
        // buys livestock and allocates it afterwards — so they are counted
        // once for the year rather than per programme.
        $animals = QurbanAnimal::query()
            ->where('organization_id', $organizationId)
            ->get();

        return response()->json([
            'year' => $year,
            'has_program' => $programs->isNotEmpty(),

            'programs' => $programs->map(fn (QurbanProgram $program) => [
                'id' => $program->id,
                'slug' => $program->slug,
                'title' => $program->title,
                'status' => $program->status,
                'is_public' => (bool) $program->is_public,
                'starts_at' => $program->period_start_at?->toIso8601String(),
                'ends_at' => $program->period_end_at?->toIso8601String(),

                'packages' => $program->packages->map(fn ($package) => [
                    'id' => $package->id,
                    'title' => $package->title,
                    'animal_type' => $package->animal_type,
                    'package_type' => $package->package_type,
                    'share_count' => $package->share_count,
                    'price' => (float) $package->price,
                    'quota' => (int) $package->quota,
                    // What is left, not what was sold: an officer at a table
                    // needs to know whether they can still take an order.
                    'remaining_quota' => (int) $package->remaining_quota,
                ])->values(),
            ])->values(),

            // Where the livestock is. The stages come from the animals
            // themselves rather than a hand-kept list, so a stage added later
            // appears here without anyone remembering to.
            'animals' => [
                'total' => $animals->count(),
                'by_status' => $animals
                    ->groupBy('status')
                    ->map->count()
                    ->sortDesc(),
            ],
        ]);
    }
}
