<?php

namespace App\Http\Controllers\API\Charities;

use App\Http\Controllers\Controller;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationWhatsappGroup;
use App\Services\Charities\CharityTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharitySummaryController extends Controller
{
    public function __construct(
        protected CharityTransactionService $transactionService
    ) {
    }

    public function daily(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'jid' => ['nullable', 'string'],
            'organization_id' => ['nullable', 'integer'],
        ]);

        $organization = null;

        if (! empty($data['jid'])) {
            $channel = OrganizationWhatsappGroup::query()
                ->where('jid', $data['jid'])
                ->where('is_active', true)
                ->first();

            if (! $channel) {
                return response()->json([
                    'message' => 'JID not found.',
                ], 404);
            }

            $organization = $channel->organization;
        } elseif (! empty($data['organization_id'])) {
            $organization = Organization::query()->find($data['organization_id']);
        }

        if (! $organization) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        $summary = $this->transactionService->dailySummaryByOrganization(
            $organization->id,
            $data['date'] ?? null
        );

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
            ],
            'summary' => $summary,
        ]);
    }
}
