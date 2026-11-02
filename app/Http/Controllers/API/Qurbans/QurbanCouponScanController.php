<?php

namespace App\Http\Controllers\API\Qurbans;

use App\Http\Controllers\Controller;
use App\Models\Qurbans\QurbanCoupon;
use App\Services\Qurbans\QurbanDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Claiming a qurban meat coupon from the officer's phone.
 *
 * The same claim the web scanner makes — QurbanDistributionService::scanCoupon
 * decides, logs every attempt (duplicates included) and refuses another
 * mosque's coupon — but in the organization the officer is acting in, not
 * their first mosque membership.
 *
 * Unlike /v1/scan for kartu warga, a scan here **is** the claim: a coupon is a
 * single-use ticket for a bag of meat, and that is how the web has always
 * treated it. Every outcome answers 200 with a `result`; only the HTTP layer's
 * own failures are errors.
 */
class QurbanCouponScanController extends Controller
{
    public function __construct(protected QurbanDistributionService $service)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->service->scanCoupon(
            $validated['code'],
            $request->user()->id,
            null,
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            (int) $request->attributes->get('active_organization_id'),
        );

        /** @var QurbanCoupon|null $coupon */
        $coupon = $result['coupon'] ?? null;

        return response()->json([
            'result' => $result['result'],
            'message' => $result['message'],
            'coupon' => $coupon ? [
                'code' => $coupon->coupon_code,
                // Who it was issued to, so the officer can check against the
                // person in front of them, as with kartu warga.
                'name' => $coupon->beneficiary?->name_snapshot,
                'package_label' => $coupon->package_label,
                'meat_weight' => $coupon->meat_weight,
                'batch' => $coupon->batch?->title,
            ] : null,
        ]);
    }
}
