<?php

namespace App\Actions\Distributions;

use App\Models\Distributions\DistributionRecipient;
use App\Models\Distributions\DistributionRecipientStatusLog;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records that a recipient's distribution status changed.
 *
 * Extracted so the field app and the web table write the same thing. Two
 * separate write paths for a record that decides who received money and rice
 * would drift, and the drift would only show up in a reconciliation months
 * later.
 *
 * Every change is logged with who made it and when — that audit trail is the
 * only real protection against a lifelong printed card being misused, since
 * the card itself proves nothing.
 */
class MarkRecipientStatus
{
    public const STATUSES = ['pending', 'distributed', 'failed', 'rescheduled', 'redirected'];

    /**
     * @throws RuntimeException when the recipient was already distributed
     */
    public function handle(
        DistributionRecipient $recipient,
        string $status,
        User $officer,
        ?string $note = null,
        ?string $rescheduleAt = null,
    ): DistributionRecipient {
        if (! in_array($status, self::STATUSES, true)) {
            throw new RuntimeException("Status tidak dikenal: {$status}");
        }

        // Guard against a double claim: two officers scanning the same person,
        // or one card presented twice. The caller turns this into a message
        // saying when it happened and who did it, rather than a bare failure.
        if ($recipient->status === 'distributed' && $status === 'distributed') {
            throw new RuntimeException('Penerima ini sudah menerima distribusi.');
        }

        return DB::transaction(function () use ($recipient, $status, $officer, $note, $rescheduleAt) {
            $previous = $recipient->status;

            $recipient->update([
                'status' => $status,
                'status_note' => $note,
                'officer_id' => $officer->id,
                'distributed_at' => $status === 'distributed' ? now() : null,
                'reschedule_at' => $status === 'rescheduled' ? $rescheduleAt : null,
            ]);

            DistributionRecipientStatusLog::create([
                'distribution_recipient_id' => $recipient->id,
                'from_status' => $previous,
                'to_status' => $status,
                'status_note' => $note,
                'reschedule_at' => $status === 'rescheduled' ? $rescheduleAt : null,
                'created_by' => $officer->id,
            ]);

            return $recipient->fresh();
        });
    }
}
