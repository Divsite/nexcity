<?php

namespace App\Http\Controllers\API\Charities;

use App\Http\Controllers\Controller;
use App\Models\Charities\CharityTransaction;
use App\Models\CharityTypes\CharityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Recording charity at the counter, from a phone.
 *
 * Deliberately the short path. The web form carries packages, family members,
 * representatives and receipts; none of that belongs on a phone with a queue
 * waiting. What is here is what an amil actually does standing at a table:
 * pick the type, name the payer, take the amount.
 *
 * Anything richer stays on the web — see docs/web-vs-mobile.md in the Flutter
 * repo. A transaction recorded here is a complete, valid record; it is simply
 * not an elaborate one.
 */
class QuickCharityController extends Controller
{
    /**
     * The charity types this organization is accepting this year.
     *
     * Returns the limits with them so the phone can reject an out-of-range
     * amount before the officer's thumb leaves the screen, rather than after a
     * round trip.
     */
    public function types(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        $types = CharityType::query()
            ->with('source')
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('year', now()->year)
            ->get()
            ->map(fn (CharityType $type) => [
                'id' => $type->id,
                'name' => $type->source?->name ?? 'Amal',
                'slug' => $type->source?->slug,
                'min_amount' => $type->min_amount,
                'max_amount' => $type->max_amount,
                'accepts_rice' => (bool) $type->is_rice,
                'rice_per_person' => $type->total_rice,
            ]);

        return response()->json(['data' => $types]);
    }

    /**
     * Records one payment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'charity_type_id' => 'required|integer',
            'payer_name' => 'required|string|max:255',
            'payer_phone' => 'nullable|string|max:32',
            'total_money' => 'nullable|numeric|min:0',
            'total_rice' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,transfer,qris',
            'notes' => 'nullable|string|max:500',
        ]);

        $organizationId = (int) $request->attributes->get('active_organization_id');

        $type = CharityType::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->find($validated['charity_type_id']);

        // Checked rather than trusted: the type id arrives from the client, and
        // without this one organization could book income against another.
        if (! $type) {
            throw ValidationException::withMessages([
                'charity_type_id' => 'Jenis amal ini tidak tersedia di organisasi Anda.',
            ]);
        }

        $this->assertAmountIsUsable($validated, $type);

        $transaction = CharityTransaction::create([
            'organization_id' => $organizationId,
            'charity_type_id' => $type->id,
            'year' => now()->year,
            'payer_name' => $validated['payer_name'],
            'payer_phone' => $validated['payer_phone'] ?? null,
            'payment_method' => $validated['payment_method'],
            'total_money' => $validated['total_money'] ?? null,
            'total_rice' => $validated['total_rice'] ?? null,
            // Recorded at the counter means the money is already in hand;
            // there is no pending state to sit in.
            'status' => 'paid',
            'received_by' => $request->user()->id,
            'received_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'id' => $transaction->id,
            'payer_name' => $transaction->payer_name,
            'total_money' => $transaction->total_money,
            'total_rice' => $transaction->total_rice,
            'received_at' => $transaction->received_at?->toIso8601String(),
            'message' => 'Amal tercatat.',
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    protected function assertAmountIsUsable(array $validated, CharityType $type): void
    {
        $money = $validated['total_money'] ?? null;
        $rice = $validated['total_rice'] ?? null;

        // A transaction with neither is not a record of anything.
        if (blank($money) && blank($rice)) {
            throw ValidationException::withMessages([
                'total_money' => 'Isi nominal uang atau beras.',
            ]);
        }

        if (filled($rice) && ! $type->is_rice) {
            throw ValidationException::withMessages([
                'total_rice' => 'Jenis amal ini tidak menerima beras.',
            ]);
        }

        // The organization set these limits for a reason; enforcing them here
        // stops a mistyped amount becoming a reconciliation problem later.
        if (filled($money) && filled($type->min_amount) && $money < (float) $type->min_amount) {
            throw ValidationException::withMessages([
                'total_money' => 'Nominal kurang dari minimum ' . (int) $type->min_amount . '.',
            ]);
        }

        if (filled($money) && filled($type->max_amount) && $money > (float) $type->max_amount) {
            throw ValidationException::withMessages([
                'total_money' => 'Nominal melebihi maksimum ' . (int) $type->max_amount . '.',
            ]);
        }
    }
}
