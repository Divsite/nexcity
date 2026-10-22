<?php

namespace App\Http\Controllers\API\Stats;

use App\Http\Controllers\Controller;
use App\Services\Stats\UsageStatsService;
use Illuminate\Http\JsonResponse;

class UsageSummaryController extends Controller
{
    public function __construct(
        protected UsageStatsService $statsService
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json($this->statsService->summary());
    }
}
