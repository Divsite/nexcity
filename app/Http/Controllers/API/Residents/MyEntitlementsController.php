<?php

namespace App\Http\Controllers\API\Residents;

use App\Http\Controllers\Controller;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Qurbans\QurbanCoupon;
use App\Models\Qurbans\QurbanDistributionBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Hak saya": what a warga is owed, by whom, and whether it has reached them.
 *
 * Two sources, both linked to the account the RT registered:
 *
 * - zakat and bansos rows a mosque or RT listed them on, claimed by showing
 *   the kartu warga;
 * - qurban meat coupons, claimed by showing the coupon's QR — from the phone,
 *   so a lost paper coupon no longer means a lost share.
 *
 * Scoped to the caller only. Across organizations on purpose: this is the
 * warga's own list, and seeing that two mosques both listed them is their
 * business. What one mosque gave is never shown to another mosque's officer;
 * see ScanResolver.
 */
class MyEntitlementsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        // This season and the last: older rows are history, not something to
        // go and collect.
        $sinceYear = now()->year - 1;

        $aid = DistributionRecipient::query()
            ->with(['distribution.organization', 'distributionClass.source'])
            ->where('resident_id', $userId)
            ->whereHas('distribution', fn (Builder $q) => $q->where('year', '>=', $sinceYear))
            ->latest('id')
            ->get()
            ->map(fn (DistributionRecipient $r) => [
                'id' => $r->id,
                'title' => $r->distribution?->title,
                'year' => $r->distribution?->year,
                'organization' => $r->distribution?->organization?->name,
                'organization_type' => $r->distribution?->organization?->type,
                'class_name' => $r->distributionClass?->source?->name,
                // Same fallback as the officer sees: the golongan carries the
                // figure unless this row overrides it.
                'amount_money' => $r->amount_money ?? $r->distributionClass?->get_money,
                'amount_rice' => $r->amount_rice ?? $r->distributionClass?->get_rice,
                'status' => $r->status,
                'distributed_at' => $r->distributed_at?->toIso8601String(),
            ])
            ->values();

        $coupons = QurbanCoupon::query()
            ->with(['batch.organization'])
            ->whereHas('beneficiary', fn (Builder $q) => $q->where('resident_id', $userId))
            // A cancelled batch's coupons are void; showing them would send
            // someone to collect meat that will not be there.
            ->whereHas('batch', fn (Builder $q) => $q
                ->where('status', '!=', QurbanDistributionBatch::STATUS_CANCELLED)
                ->where('year', '>=', $sinceYear))
            ->where('status', '!=', QurbanCoupon::STATUS_CANCELLED)
            ->latest('id')
            ->get()
            ->map(fn (QurbanCoupon $c) => [
                'id' => $c->id,
                'code' => $c->coupon_code,
                // What the officer's scanner reads; defaults to the code.
                'qr' => $c->qr_code ?: $c->coupon_code,
                'package_label' => $c->package_label,
                'meat_weight' => $c->meat_weight,
                'status' => $c->status,
                'batch' => $c->batch?->title,
                'organization' => $c->batch?->organization?->name,
                'distribution_date' => $c->batch?->distribution_date?->toDateString(),
                'claim_starts_at' => $c->batch?->claim_starts_at?->toIso8601String(),
                'location' => $c->batch?->location_label,
            ])
            ->values();

        return response()->json([
            'qurban_coupons' => $coupons,
            'aid' => $aid,
        ]);
    }
}
