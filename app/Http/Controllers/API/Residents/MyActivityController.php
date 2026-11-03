<?php

namespace App\Http\Controllers\API\Residents;

use App\Http\Controllers\Controller;
use App\Models\Charities\CharityTransaction;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Dues\RtDuesBill;
use App\Models\Organizations\Organization;
use App\Models\Qurbans\QurbanCoupon;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Models\Qurbans\QurbanOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "Aktivitas saya": everything recorded on this warga's account, from every
 * mosque and RT, in one list.
 *
 * The warga is the centre (docs/architecture/system-map.md §6). Zakat paid at
 * Masjid B, a meat coupon from Masjid A and the RT's iuran are three events
 * on one account, not three apps. Each row names the organization it came
 * from; none is hidden behind choosing one first.
 *
 * The sources and their own screens stay as they are — this only gathers
 * them. Each row says which screen holds its detail (`target`).
 *
 * Scoped to the caller. Two seasons: enough to answer "did I pay fitrah last
 * year?", not an archive.
 */
class MyActivityController extends Controller
{
    /** Per source; the list is a summary, the detail screens hold the rest. */
    protected const LIMIT = 100;

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $sinceYear = (int) now()->year - 1;

        $charity = $this->charity($userId, $sinceYear);
        $qurban = $this->qurban($userId, $sinceYear);
        $coupons = $this->coupons($userId, $sinceYear);
        $aid = $this->aid($userId, $sinceYear);
        $dues = $this->dues($userId, $sinceYear);

        $items = $charity->concat($qurban)->concat($coupons)->concat($aid)->concat($dues)
            ->sortByDesc(fn (array $row) => $row['occurred_at'] ?? '')
            ->values();

        return response()->json([
            // What the warga has to do something about, wherever it came
            // from: meat to collect, iuran unpaid, a patungan still owed.
            'needs_action' => $items->where('needs_action', true)->values(),
            'summary' => $this->summary($charity, $dues),
            'data' => $items,
        ]);
    }

    // ── sources ─────────────────────────────────────────────────────────────

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function charity(int $userId, int $sinceYear): Collection
    {
        return CharityTransaction::query()
            ->with([
                'charityType.source', 'organization',
                'fitrahReceipt', 'fidyaReceipt', 'malReceipt',
                'donationReceipt', 'almsReceipt', 'endowmentReceipt',
            ])
            ->where('payer_user_id', $userId)
            ->where('status', '!=', CharityTransaction::STATUS_CANCELLED)
            ->where('year', '>=', $sinceYear)
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (CharityTransaction $t) => $this->row(
                id: "charity:{$t->id}",
                kind: 'amal',
                title: $t->charityType?->source?->name ?? 'Amal',
                organization: $t->organization,
                occurredAt: $t->created_at,
                status: $t->status === CharityTransaction::STATUS_PAID ? 'paid' : 'pending',
                money: $t->detailMoneyAmount() ?: null,
                rice: $t->detailIsRice() ? $t->detailRiceAmount() : null,
                target: ['screen' => 'charity', 'id' => $t->id],
            ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function qurban(int $userId, int $sinceYear): Collection
    {
        return QurbanOrder::query()
            ->with(['organization', 'program', 'items', 'payments'])
            ->where('user_id', $userId)
            ->where('status', '!=', QurbanOrder::STATUS_CANCELLED)
            ->whereHas('program', fn (Builder $q) => $q->where('year', '>=', $sinceYear))
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(function (QurbanOrder $order) {
                $bill = $order->paymentSummary();
                // Marked paid at the counter with no instalments recorded is
                // still paid; the order's own status says so.
                $settled = $bill['is_settled'] || $order->status === 'paid';

                return $this->row(
                    id: "qurban:{$order->id}",
                    kind: 'qurban',
                    title: $order->program?->title ?? 'Patungan qurban',
                    organization: $order->organization,
                    occurredAt: $order->created_at,
                    status: $settled ? 'settled' : 'unsettled',
                    money: $bill['target'] ?: null,
                    remaining: $settled ? 0.0 : $bill['remaining'],
                    needsAction: ! $settled,
                    target: ['screen' => 'qurban', 'id' => $order->id],
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function coupons(int $userId, int $sinceYear): Collection
    {
        return QurbanCoupon::query()
            ->with(['batch.organization'])
            ->whereHas('beneficiary', fn (Builder $q) => $q->where('resident_id', $userId))
            // Same rule as "Hak saya": a cancelled batch's coupons are void.
            ->whereHas('batch', fn (Builder $q) => $q
                ->where('status', '!=', QurbanDistributionBatch::STATUS_CANCELLED)
                ->where('year', '>=', $sinceYear))
            ->where('status', '!=', QurbanCoupon::STATUS_CANCELLED)
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (QurbanCoupon $c) => $this->row(
                id: "coupon:{$c->id}",
                kind: 'hak',
                title: 'Kupon daging qurban',
                organization: $c->batch?->organization,
                occurredAt: $c->batch?->distribution_date ?? $c->created_at,
                status: $c->status,
                quantity: $c->package_label,
                needsAction: $c->status === QurbanCoupon::STATUS_ISSUED,
                target: ['screen' => 'entitlements', 'id' => $c->id],
            ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function aid(int $userId, int $sinceYear): Collection
    {
        return DistributionRecipient::query()
            ->with(['distribution.organization', 'distributionClass.source'])
            ->where('resident_id', $userId)
            ->whereHas('distribution', fn (Builder $q) => $q->where('year', '>=', $sinceYear))
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (DistributionRecipient $r) => $this->row(
                id: "aid:{$r->id}",
                kind: 'hak',
                title: $r->distribution?->title ?? 'Bantuan',
                organization: $r->distribution?->organization,
                occurredAt: $r->distributed_at ?? $r->created_at,
                status: $r->status === 'distributed' ? 'received' : 'waiting',
                money: (float) ($r->amount_money ?? $r->distributionClass?->get_money) ?: null,
                rice: (float) ($r->amount_rice ?? $r->distributionClass?->get_rice) ?: null,
                target: ['screen' => 'entitlements', 'id' => $r->id],
            ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function dues(int $userId, int $sinceYear): Collection
    {
        return RtDuesBill::query()
            ->with(['period.scheme.organization'])
            ->where('resident_id', $userId)
            ->whereHas('period', fn (Builder $q) => $q->where('year', '>=', $sinceYear))
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            ->map(function (RtDuesBill $bill) {
                $period = $bill->period;
                $when = $period?->due_date
                    ?? ($period?->year ? Carbon::create($period->year, $period->month ?: 1, 1) : $bill->created_at);

                return $this->row(
                    id: "dues:{$bill->id}",
                    kind: 'iuran',
                    title: trim(($period?->scheme?->name ?? 'Iuran') . ' ' . ($period?->label ?? '')),
                    organization: $period?->scheme?->organization,
                    occurredAt: $when,
                    status: $bill->status,
                    money: (float) $bill->amount,
                    needsAction: $bill->isOutstanding(),
                    target: ['screen' => 'dues', 'id' => $bill->id],
                );
            });
    }

    // ── shape ───────────────────────────────────────────────────────────────

    /**
     * One row, the same shape whatever it came from.
     *
     * `status` stays the source's own word (`paid`, `issued`, `pending`…);
     * the app turns it into copy, so wording lives in one place.
     *
     * @param  array{screen: string, id: int}  $target
     * @return array<string, mixed>
     */
    protected function row(
        string $id,
        string $kind,
        string $title,
        ?Organization $organization,
        mixed $occurredAt,
        string $status,
        array $target,
        ?float $money = null,
        ?float $rice = null,
        ?float $remaining = null,
        ?string $quantity = null,
        bool $needsAction = false,
    ): array {
        return [
            'id' => $id,
            'kind' => $kind,
            'title' => $title,
            'organization' => $organization ? [
                'name' => $organization->name,
                'type' => $organization->type,
                'slug' => $organization->slug,
            ] : null,
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt)->toIso8601String() : null,
            'status' => $status,
            'money' => $money,
            'rice' => $rice,
            'remaining' => $remaining,
            'quantity' => $quantity,
            'needs_action' => $needsAction,
            'target' => $target,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $charity
     * @param  Collection<int, array<string, mixed>>  $dues
     * @return array<string, mixed>
     */
    protected function summary(Collection $charity, Collection $dues): array
    {
        $year = (int) now()->year;
        $thisYear = fn (array $row) => $row['occurred_at']
            && Carbon::parse($row['occurred_at'])->year === $year;

        $charityThisYear = $charity->filter($thisYear)->where('status', 'paid');
        $duesThisYear = $dues->filter($thisYear)->where('status', '!=', RtDuesBill::STATUS_WAIVED);

        return [
            'year' => $year,
            'charity_money' => (float) $charityThisYear->sum('money'),
            'charity_organizations' => $charityThisYear
                ->pluck('organization.slug')->filter()->unique()->count(),
            'dues_paid' => $duesThisYear->where('status', RtDuesBill::STATUS_PAID)->count(),
            'dues_total' => $duesThisYear->count(),
        ];
    }
}
