<?php

namespace App\Actions\Dues;

use App\Models\Dues\RtDuesBill;
use App\Models\Users\User;

/**
 * Sets a bill's status: paid, waived, or back to pending.
 *
 * One place for the web table and the treasurer's phone, so the two can never
 * disagree about what "lunas" leaves behind on a bill.
 */
class RecordDuesPayment
{
    public function handle(
        RtDuesBill $bill,
        string $status,
        User $recordedBy,
        ?string $paymentMethod = null,
        ?string $note = null,
    ): RtDuesBill {
        $paid = $status === RtDuesBill::STATUS_PAID;

        $bill->update([
            'status' => $status,
            // Cleared when a bill moves back off "paid", so a correction does
            // not leave a payment date on a bill nobody paid.
            'paid_at' => $paid ? ($bill->paid_at ?? now()) : null,
            'payment_method' => $paid ? ($paymentMethod ?? 'cash') : null,
            'note' => $note,
            'recorded_by' => $recordedBy->id,
        ]);

        return $bill;
    }
}
