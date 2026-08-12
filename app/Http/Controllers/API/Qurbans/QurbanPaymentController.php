<?php

namespace App\Http\Controllers\API\Qurbans;

use App\Http\Controllers\Controller;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanOrderPayment;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Instalments against one line of the patungan board.
 *
 * Patungan is paid off over months, not on the day the line is taken. A takmir
 * records each deposit in a notebook and reconciles from memory at the end —
 * which is where the money goes missing, and where somebody gets asked twice
 * for a payment they already made.
 *
 * No new table: `qurban_order_payments` already holds several payments against
 * one order, and orders already carry `partial_paid`. What was missing was
 * somewhere to write them from and a rule for when the line turns paid.
 */
class QurbanPaymentController extends Controller
{
    public function __construct(protected CapabilityResolver $capabilities) {}

    /**
     * The deposits made against one line, and what is left.
     */
    public function index(Request $request, QurbanOrder $order): JsonResponse
    {
        if (! $this->mayRead($request, $order)) {
            // 404, not 403: telling someone an order exists but is not theirs
            // leaks who is in the patungan and for how much.
            return response()->json(['message' => __('messages.not_found')], 404);
        }

        $order->loadMissing(['items', 'payments' => fn ($q) => $q->latest('paid_at')]);

        $target = (float) $order->items->sum('subtotal');
        $paid = (float) $order->payments
            ->where('status', '!=', 'cancelled')
            ->sum('amount');

        return response()->json([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'customer_name' => $order->customer_name,
            'status' => $order->status,

            'target' => $target,
            'paid' => $paid,
            // Never below zero. An overpayment is a bookkeeping matter, not a
            // negative amount owed, and showing one would read as a refund due.
            'remaining' => max(0.0, $target - $paid),
            'is_settled' => $paid >= $target && $target > 0,

            'payments' => $order->payments->map(fn (QurbanOrderPayment $payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $payment->payment_method,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'reference_number' => $payment->reference_number,
                'status' => $payment->status,
            ])->values(),
        ]);
    }

    /**
     * Records one deposit.
     */
    public function store(Request $request, QurbanOrder $order): JsonResponse
    {
        $organizationId = (int) $order->organization_id;

        // Only a pengurus records money. A warga paying by transfer is a
        // different flow — the mosque confirms receipt — and letting the payer
        // mark their own instalment received would make the ledger a claim
        // rather than a record.
        if (! $this->capabilities->holds($request->user(), 'add-qurban', $organizationId)) {
            return response()->json(['message' => __('messages.forbidden')], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,qris'],
            'paid_at' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:64'],
        ]);

        $order->loadMissing('items');
        $target = (float) $order->items->sum('subtotal');

        $payment = DB::transaction(function () use ($order, $validated, $target, $request) {
            $alreadyPaid = (float) $order->payments()
                ->where('status', '!=', 'cancelled')
                ->sum('amount');

            $amount = (float) $validated['amount'];

            // Refused rather than trimmed. A deposit bigger than what is owed
            // usually means the wrong line was picked, and silently accepting
            // part of it would hide the mistake.
            if ($target > 0 && $alreadyPaid + $amount > $target) {
                throw ValidationException::withMessages([
                    'amount' => 'Sisa tagihan tinggal Rp '
                        . number_format(max(0, $target - $alreadyPaid), 0, ',', '.') . '.',
                ]);
            }

            $payment = QurbanOrderPayment::create([
                'qurban_order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'amount' => $amount,
                'paid_at' => $validated['paid_at'] ?? now(),
                'reference_number' => $validated['reference_number'] ?? null,
                'status' => 'confirmed',
                'received_by' => $request->user()->id,
            ]);

            // The line's status follows its money, in the same transaction. A
            // deposit written without the status moving would leave a fully
            // paid share still showing as owing, and the mosque chasing
            // somebody who has already paid.
            $order->update([
                'status' => match (true) {
                    $target > 0 && $alreadyPaid + $amount >= $target => 'paid',
                    default => 'partial_paid',
                },
            ]);

            return $payment;
        });

        return response()->json([
            'id' => $payment->id,
            'order_status' => $order->fresh()->status,
            'message' => 'Setoran dicatat.',
        ], 201);
    }

    /**
     * A pengurus of the organization, or the person who holds the line.
     *
     * A warga must be able to see their own instalments — that is half the
     * point of putting them in a system — but nobody else's.
     */
    protected function mayRead(Request $request, QurbanOrder $order): bool
    {
        $user = $request->user();

        if ((int) $order->user_id === (int) $user->id) {
            return true;
        }

        return $this->capabilities->holds(
            $user,
            'browse-qurban',
            (int) $order->organization_id,
        );
    }
}
