<?php

namespace App\Actions\Qurban;

use App\Models\Qurbans\QurbanAnimalAllocation;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanOrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Releases patungan lines taken from the app that never got a deposit.
 *
 * A line on the board is a promise the mosque plans around: seven names on a
 * cow is a cow to buy. A warga taps "ambil bagian" in a second; nothing made
 * them follow it with money. So an app line is *held* for
 * `core.qurban_hold_days` and released if no deposit arrived — the lines go
 * back on the board and the order is cancelled with `expired_at` set, so the
 * books show what happened rather than a line that silently vanished.
 *
 * Not released: a line a pengurus filled at the counter (they know the
 * person and took responsibility), and any line with a deposit, however
 * small — the first payment ends the hold (QurbanPaymentController).
 */
class ReleaseUnpaidHolds
{
    /**
     * @return int how many orders were released
     */
    public function handle(): int
    {
        $released = 0;

        QurbanOrder::query()
            ->where('status', QurbanOrder::STATUS_PENDING_PAYMENT)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->each(function (QurbanOrder $order) use (&$released) {
                DB::transaction(function () use ($order, &$released) {
                    // Re-read under a lock: a deposit recorded a moment ago
                    // must win over the release.
                    $fresh = QurbanOrder::query()->lockForUpdate()->find($order->id);

                    $hasMoney = $fresh?->payments()->where('status', '!=', 'cancelled')->exists();

                    if (! $fresh
                        || $fresh->status !== QurbanOrder::STATUS_PENDING_PAYMENT
                        || $fresh->hold_expires_at === null
                        || $hasMoney) {
                        return;
                    }

                    $itemIds = QurbanOrderItem::query()
                        ->where('qurban_order_id', $fresh->id)
                        ->pluck('id');

                    // The lines go back on the board.
                    QurbanAnimalAllocation::query()->whereIn('qurban_order_item_id', $itemIds)->delete();
                    QurbanOrderItem::query()->whereIn('id', $itemIds)->update(['status' => 'cancelled']);

                    $note = 'Dilepas otomatis ' . now()->format('d-m-Y H:i')
                        . ': belum ada setoran dalam ' . config('core.qurban_hold_days') . ' hari.';

                    $fresh->forceFill([
                        'status' => QurbanOrder::STATUS_CANCELLED,
                        'expired_at' => now(),
                        'notes' => trim(($fresh->notes ? $fresh->notes . "\n" : '') . $note),
                    ])->save();

                    $released++;
                });
            });

        return $released;
    }
}
