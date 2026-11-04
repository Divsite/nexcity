<?php

namespace App\Http\Controllers\API\Charities;

use App\Http\Controllers\Controller;
use App\Models\Charities\CharityTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Riwayat amal": the zakat, infaq and sedekah a warga paid, at any mosque.
 *
 * Only payments a pengurus linked to the warga's account at the counter. A
 * payment recorded by name alone has no owner to show it to, and guessing one
 * from a matching name would put a neighbour's zakat in the wrong history.
 *
 * Scoped to the caller; there is no id here to tamper with.
 */
class MyCharityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = CharityTransaction::query()
            ->with([
                'charityType.source',
                'organization',
                // The amount lives on the receipt for the type, not the row.
                'fitrahReceipt', 'fidyaReceipt', 'malReceipt',
                'donationReceipt', 'almsReceipt', 'endowmentReceipt',
            ])
            ->where('payer_user_id', $request->user()->id)
            ->where('status', '!=', CharityTransaction::STATUS_CANCELLED)
            // Two seasons: enough to answer "did I pay fitrah last year?",
            // without turning the screen into an archive.
            ->where('year', '>=', now()->year - 1)
            ->latest('id')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (CharityTransaction $t) => [
                'id' => $t->id,
                'type' => $t->charityType?->source?->name ?? 'Amal',
                'organization' => $t->organization?->name,
                'organization_slug' => $t->organization?->slug,
                'year' => $t->year,
                'total_money' => $t->detailMoneyAmount(),
                'total_rice' => $t->detailIsRice() ? $t->detailRiceAmount() : null,
                'payment_method' => $t->payment_method,
                'status' => $t->status,
                // The table has no received_at; a counter payment is recorded
                // the moment it is received.
                'received_at' => $t->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
