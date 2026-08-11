<?php

namespace App\Http\Controllers\API\Distributions;

use App\Http\Controllers\Controller;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Distributions and their recipients, for officers working in the field.
 *
 * Everything here is scoped to the caller's active organization. The header is
 * verified by middleware, but each query re-applies the scope: middleware
 * proves membership, not that the record belongs to the organization being
 * acted for.
 */
class DistributionController extends Controller
{
    /**
     * Distributions the officer can work on right now.
     */
    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        $distributions = Distribution::query()
            ->where('organization_id', $organizationId)
            ->withCount([
                'recipients',
                // Only 'distributed'. The web's own summary tallies redirected
                // with failed, and progress that disagrees between the two is
                // worse than progress that is simply wrong — nobody can tell
                // which figure to trust.
                'recipients as distributed_count' => fn ($query) => $query
                    ->where('status', 'distributed'),
            ])
            ->latest('id')
            ->get()
            ->map(fn (Distribution $distribution) => [
                'id' => $distribution->id,
                'title' => $distribution->title,
                'year' => $distribution->year,
                'status' => $distribution->status,
                'recipients_count' => $distribution->recipients_count,
                'distributed_count' => $distribution->distributed_count,
            ]);

        return response()->json(['data' => $distributions]);
    }

    /**
     * The recipient list for one distribution.
     *
     * Returned whole rather than paginated: an officer in a queue needs to
     * search the entire list, and losing the page they were on because the
     * signal dropped would be worse than the payload size. A distribution is
     * one RT's worth of people, not thousands.
     */
    public function recipients(Request $request, Distribution $distribution): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        if ((int) $distribution->organization_id !== $organizationId) {
            return response()->json(['message' => 'Distribusi ini bukan milik organisasi Anda.'], 403);
        }

        $recipients = DistributionRecipient::query()
            ->with(['resident.residentProfile', 'officer', 'distributionClass.source'])
            ->where('distribution_id', $distribution->id)
            ->get()
            ->map(fn (DistributionRecipient $recipient) => [
                'id' => $recipient->id,
                // Falls back to the free-text name: a recipient may have no
                // account at all — someone in a kontrakan without local
                // papers — and those people have no QR card to scan. They are
                // not an edge case.
                'name' => $recipient->resident?->name ?? $recipient->recipient_name ?? '-',
                'has_card' => $recipient->resident?->residentProfile?->qr_token !== null,
                'address' => $recipient->recipient_address,
                'status' => $recipient->status,
                // The golongan is where the entitlement usually lives: 200 of
                // 207 real rows leave the per-recipient columns null and take
                // the figure from their class. An officer needs to know what to
                // hand over, not merely that someone qualifies.
                'class_name' => $recipient->distributionClass?->source?->name,
                'amount_money' => $recipient->amount_money
                    ?? $recipient->distributionClass?->get_money,
                'amount_rice' => $recipient->amount_rice
                    ?? $recipient->distributionClass?->get_rice,
                'distributed_at' => $recipient->distributed_at?->toIso8601String(),
                'distributed_by' => $recipient->officer?->name,
            ]);

        return response()->json([
            'distribution' => [
                'id' => $distribution->id,
                'title' => $distribution->title,
                'year' => $distribution->year,
            ],
            'data' => $recipients,
        ]);
    }
}
