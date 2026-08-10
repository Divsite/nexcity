<?php

namespace App\Http\Controllers\API\Charities;

use App\Http\Controllers\Controller;
use App\Models\Charities\CharityTransaction;
use App\Services\Menus\MenuContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The mosque's charity ledger, as the Amal tab reads it.
 *
 * The per-transaction figure comes from `detailMoneyAmount()` on the model, not
 * from a column. There is no single amount column: a transaction's money is the
 * sum of whichever of six receipts it carries — fitrah, fidyah, mal, donation,
 * alms, endowment. Those relations are eager-loaded on purpose; without them
 * every row would read Rp 0 and nothing would look broken.
 *
 * Deriving the figure here rather than reusing the accessor would make a second
 * source of truth for money, and the app and the web recap would eventually
 * disagree about the same day.
 */
class CharityListController extends Controller
{
    public function __construct(protected MenuContextResolver $context)
    {
        $this->middleware('capability:browse-mosque-charity-transactions');
    }

    public function index(Request $request): JsonResponse
    {
        [, $organization] = $this->context->resolve($request->user());

        if (! $organization) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $validated = $request->validate([
            'type_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = CharityTransaction::query()
            ->where('organization_id', $organization->id)
            ->when(
                $validated['type_id'] ?? null,
                fn ($q, $typeId) => $q->where('charity_type_id', $typeId),
            )
            ->with([
                // The type's label lives on its source, not on the type — the
                // same relation QuickCharityController reads to list them.
                'charityType.source:id,name,slug',
                // Six relations, one figure. See the class note.
                'fitrahReceipt',
                'fidyaReceipt',
                'malReceipt',
                'donationReceipt',
                'almsReceipt',
                'endowmentReceipt',
            ])
            ->latest('id');

        // Paginated: a mosque in Ramadan records hundreds a week, and the
        // screen shows them newest first rather than all at once.
        $transactions = $query->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => collect($transactions->items())->map(
                fn (CharityTransaction $t) => [
                    'id' => $t->id,
                    'type' => $t->charityType?->source?->name ?? 'Amal',
                    'type_id' => $t->charity_type_id,
                    'payer_name' => $t->payer_name,
                    'payment_method' => $t->payment_method,
                    'money' => $t->detailMoneyAmount(),
                    'rice' => $t->detailRiceAmount(),
                    'recorded_at' => $t->created_at?->toIso8601String(),
                ],
            )->values(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}
