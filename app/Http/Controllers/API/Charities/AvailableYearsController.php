<?php

namespace App\Http\Controllers\API\Charities;

use App\Http\Controllers\Controller;
use App\Models\Charities\CharityTransaction;
use App\Models\Distributions\Distribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Which years this organization actually has something in.
 *
 * The year picker offers these and nothing else. Generating a range from 2020
 * to today would put empty years in front of an officer, who taps one, sees
 * nothing, and cannot tell an empty year from a broken screen.
 *
 * Charity and distribution years are merged because one year selector governs
 * the whole Amal tab — the same way the web puts one filter above every panel.
 * A year with distributions but no charity still belongs in the list.
 */
class AvailableYearsController extends Controller
{
    public function __construct()
    {
        $this->middleware('capability:browse-mosque-charity-transactions');
    }

    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        if ($organizationId === 0) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $charity = CharityTransaction::query()
            ->where('organization_id', $organizationId)
            ->paid()
            ->selectRaw('year, COUNT(*) as total')
            ->groupBy('year')
            ->pluck('total', 'year');

        $distributions = Distribution::query()
            ->where('organization_id', $organizationId)
            ->selectRaw('year, COUNT(*) as total')
            ->groupBy('year')
            ->pluck('total', 'year');

        $currentYear = (int) now()->year;

        $years = $charity->keys()
            ->merge($distributions->keys())
            // The current year is always offered, even before the first
            // transaction of it. An officer opening the app in January must be
            // able to record into the year they are standing in.
            ->push($currentYear)
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn (int $year) => [
                'year' => $year,
                'charity_transactions' => (int) ($charity[$year] ?? 0),
                'distributions' => (int) ($distributions[$year] ?? 0),
                'is_current' => $year === $currentYear,
            ]);

        return response()->json(['data' => $years]);
    }
}
