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
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        // Defaults to the year the officer is standing in. Sending no year and
        // getting every year ever recorded would put a 2024 receipt at the top
        // of a screen labelled with today's date.
        $year = (int) ($validated['year'] ?? now()->year);

        $query = CharityTransaction::query()
            ->where('organization_id', $organization->id)
            ->where('year', $year)
            ->when(
                $validated['type_id'] ?? null,
                fn ($q, $typeId) => $q->where('charity_type_id', $typeId),
            )
            // The model's own scope, not a hand-written list: it already loads
            // the six receipts and the type's source, and staying on it means
            // a seventh receipt type would reach here without anyone
            // remembering to add it.
            ->withCharityRelations()
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
                    // Cancelled rows stay visible — a ledger that hides them
                    // cannot be reconciled — but they are marked, and the
                    // totals below leave them out.
                    'status' => $t->status,
                    'money' => $t->detailMoneyAmount(),
                    'rice' => $t->detailRiceAmount(),
                    'recorded_at' => $t->created_at?->toIso8601String(),
                ],
            )->values(),
            'meta' => [
                'year' => $year,
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
                // Paid only, matching the web recap. Counting cancelled ones
                // would have the app and the web report different totals for
                // the same day — 288 against 285.
                'total_paid' => (clone $query)->paid()->count(),
            ],
        ]);
    }
}
