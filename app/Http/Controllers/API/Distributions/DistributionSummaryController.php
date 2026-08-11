<?php

namespace App\Http\Controllers\API\Distributions;

use App\Http\Controllers\Controller;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What a year of distributions came to, and who is still owed.
 *
 * This answers a different question from the charity report. Charity asks how
 * much came in; distribution asks **who has not received theirs yet** — which
 * is the question an officer carries into the field, and the reason the names
 * matter more than the totals.
 *
 * Entitlement follows the same rule as everywhere else: the golongan carries
 * the figure, and a per-recipient column overrides it when set. 200 of the 207
 * real rows leave those columns null.
 */
class DistributionSummaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('capability:browse-mosque-charity-distributions');
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

        $distributionIds = Distribution::query()
            ->where('organization_id', $organizationId)
            ->where('year', $year)
            ->pluck('id');

        $recipients = DistributionRecipient::query()
            ->whereIn('distribution_id', $distributionIds)
            ->with(['resident', 'distributionClass.source', 'distribution'])
            ->get();

        $money = fn (DistributionRecipient $r) => (float) (
            $r->amount_money ?? $r->distributionClass?->get_money ?? 0
        );
        $rice = fn (DistributionRecipient $r) => (float) (
            $r->amount_rice ?? $r->distributionClass?->get_rice ?? 0
        );

        $distributed = $recipients->where('status', 'distributed');

        return response()->json([
            'year' => $year,

            // Zero distributions is not the same as everyone having received.
            // The screen needs to tell an empty year from a finished one, and
            // it cannot do that from counts alone.
            'has_distributions' => $distributionIds->isNotEmpty(),

            'distributions_count' => $distributionIds->count(),
            'recipients_count' => $recipients->count(),
            'distributed_count' => $distributed->count(),

            'total_money' => $recipients->sum($money),
            'distributed_money' => $distributed->sum($money),
            'total_rice' => $recipients->sum($rice),
            'distributed_rice' => $distributed->sum($rice),

            // Named, not counted. "7 belum tersalurkan" is a number; seven
            // names with a reason beside each is the afternoon's work.
            'not_distributed' => $this->notDistributed($recipients),
        ]);
    }

    /**
     * Everyone who has not received, newest problem first.
     *
     * Includes the ones nobody has attempted yet as well as the ones that were
     * tried and failed. An officer who only saw the failures would think the
     * untouched names were done.
     *
     * @param  \Illuminate\Support\Collection<int, DistributionRecipient>  $recipients
     * @return list<array<string, mixed>>
     */
    protected function notDistributed($recipients): array
    {
        return $recipients
            ->filter(fn (DistributionRecipient $r) => $r->status !== 'distributed')
            ->sortBy(fn (DistributionRecipient $r) => match ($r->status) {
                // Tried and failed sits above never attempted: someone has
                // already spent a trip on it.
                'failed' => 0,
                'redirected' => 1,
                'rescheduled' => 2,
                default => 3,
            })
            ->map(fn (DistributionRecipient $r) => [
                'id' => $r->id,
                'name' => $r->resident?->name ?? $r->recipient_name ?? '-',
                'address' => $r->recipient_address,
                'status' => $r->status,
                'class_name' => $r->distributionClass?->source?->name,
                'distribution_title' => $r->distribution?->title,
                // The officer's own words from when it failed. "Pindah alamat"
                // is actionable; "gagal" is not.
                'note' => $this->latestNote($r),
            ])
            ->values()
            ->all();
    }

    protected function latestNote(DistributionRecipient $recipient): ?string
    {
        return $recipient->statusLogs()
            ->whereNotNull('status_note')
            ->latest('id')
            ->value('status_note');
    }
}
