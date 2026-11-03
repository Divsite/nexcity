<?php

namespace App\Http\Controllers\API\Charities;

use App\Http\Controllers\Controller;
use App\Models\Charities\CharityTransaction;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Organizations\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * A mosque's charity, in the open: what came in, by type, and what went out.
 *
 * Ruled by the product owner, 3 November 2026: a mosque's charity report may
 * be public. It is the trust a mosque runs on — the jamaah giving zakat wants
 * to see that it arrived and was passed on.
 *
 * Totals only. No payer, no recipient, no single transaction: "Rp 12 jt
 * zakat fitrah, Rp 11 jt disalurkan ke 140 penerima" is transparency; a list
 * of who gave how much, or who received, is a privacy breach. A type with
 * fewer than [MIN_PER_TYPE] payments is folded into "Lainnya", so a total can
 * never be read back to one person's gift.
 *
 * Mosques only. An RT's money is its warga's internal business, the same rule
 * that keeps iuran private.
 */
class PublicCharityReportController extends Controller
{
    /** Below this many payments a type's total could name its payer. */
    public const MIN_PER_TYPE = 3;

    public function show(Request $request, string $slug): JsonResponse
    {
        $organization = Organization::query()
            ->where('slug', $slug)
            ->where('type', Organization::TYPE_MOSQUE)
            ->first();

        if (! $organization) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
        $year = (int) ($validated['year'] ?? now()->year);

        $income = CharityTransaction::query()
            ->where('organization_id', $organization->id)
            ->paid()
            ->where('year', $year)
            ->withCharityRelations()
            ->get();

        $recipients = DistributionRecipient::query()
            ->whereIn('distribution_id', Distribution::query()
                ->where('organization_id', $organization->id)
                ->where('year', $year)
                ->select('id'))
            ->where('status', 'distributed')
            ->with('distributionClass')
            ->get();

        return response()->json([
            'organization' => $organization->name,
            'year' => $year,
            'income' => [
                'money' => (float) $income->sum(fn (CharityTransaction $t) => $t->detailMoneyAmount()),
                'rice' => (float) $income->sum(fn (CharityTransaction $t) => $t->detailRiceAmount()),
                'transactions' => $income->count(),
                'by_type' => $this->byType($income),
            ],
            'distributed' => [
                'money' => (float) $recipients->sum(fn (DistributionRecipient $r) => (float) ($r->amount_money ?? $r->distributionClass?->get_money ?? 0)),
                'rice' => (float) $recipients->sum(fn (DistributionRecipient $r) => (float) ($r->amount_rice ?? $r->distributionClass?->get_rice ?? 0)),
                'recipients' => $recipients->count(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, CharityTransaction>  $transactions
     * @return list<array<string, mixed>>
     */
    protected function byType(Collection $transactions): array
    {
        $groups = $transactions
            ->groupBy(fn (CharityTransaction $t) => $t->charityType?->source?->name ?? 'Lainnya');

        $shown = $groups->filter(fn (Collection $rows) => $rows->count() >= self::MIN_PER_TYPE);
        $folded = $groups->reject(fn (Collection $rows) => $rows->count() >= self::MIN_PER_TYPE)->flatten(1);

        // Folded only when the fold itself is big enough to hide in; a single
        // lone payment stays inside the overall total and nowhere else.
        if ($folded->count() >= self::MIN_PER_TYPE) {
            $shown = $shown->put('Lainnya', ($shown->get('Lainnya') ?? collect())->concat($folded));
        }

        return $shown
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'money' => (float) $rows->sum(fn (CharityTransaction $t) => $t->detailMoneyAmount()),
                'rice' => (float) $rows->sum(fn (CharityTransaction $t) => $t->detailRiceAmount()),
                'transactions' => $rows->count(),
            ])
            ->sortByDesc('money')
            ->values()
            ->all();
    }
}
