<?php

namespace App\Http\Controllers\API\Dues;

use App\Http\Controllers\Controller;
use App\Models\Dues\RtDuesBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A resident's own dues.
 *
 * Read-only. Payments are recorded by the RT treasurer on the web; this exists
 * so a resident can answer "have I paid?" without asking anyone — which is the
 * question that takes up most of a treasurer's evening.
 *
 * Scoped to the caller, never to a parameter: there is no id to tamper with,
 * so one resident cannot read another's.
 */
class MyDuesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $bills = RtDuesBill::query()
            ->with('period.scheme')
            ->where('resident_id', $user->id)
            ->get()
            // Newest month first. Sorted here rather than in SQL because the
            // ordering lives on the period, and the list is one household's
            // months — small enough that the join is not worth it.
            ->sortByDesc(fn (RtDuesBill $bill) => [
                $bill->period?->year ?? 0,
                $bill->period?->month ?? 0,
            ])
            ->values();

        $outstanding = $bills->filter(fn (RtDuesBill $bill) => $bill->isOutstanding());

        return response()->json([
            'summary' => [
                'outstanding_count' => $outstanding->count(),
                'outstanding_amount' => (float) $outstanding->sum('amount'),
                // The one figure most residents open the screen for.
                'current' => $this->currentMonth($bills),
            ],
            'data' => $bills->map(fn (RtDuesBill $bill) => [
                'id' => $bill->id,
                'period' => $bill->period?->label,
                // Which collection this belongs to. A resident may owe monthly
                // dues and a HUT RI contribution in the same list, and "Juli
                // 2026" twice over would be unreadable.
                'scheme' => $bill->period?->scheme?->name,
                'tier' => $bill->tierLabel(),
                'year' => $bill->period?->year,
                'month' => $bill->period?->month,
                'amount' => (float) $bill->amount,
                'status' => $bill->status,
                'due_date' => $bill->period?->due_date?->toDateString(),
                'paid_at' => $bill->paid_at?->toIso8601String(),
                'payment_method' => $bill->payment_method,
            ])->values(),
        ]);
    }

    /**
     * This month's bill, if the RT has issued one.
     *
     * Null is a real answer, not an error: an RT that has not set this month's
     * rate yet simply has nothing to show, and saying so beats implying the
     * resident is in arrears.
     *
     * @param  \Illuminate\Support\Collection<int, RtDuesBill>  $bills
     * @return array<string, mixed>|null
     */
    protected function currentMonth($bills): ?array
    {
        // A one-off has no month, so it can never be "this month's bill" —
        // otherwise a HUT RI contribution would masquerade as the monthly dues.
        $bill = $bills->first(
            fn (RtDuesBill $b) => $b->period?->year === (int) now()->year
                && $b->period?->month === (int) now()->month
        );

        if (! $bill) {
            return null;
        }

        return [
            'period' => $bill->period?->label,
            'scheme' => $bill->period?->scheme?->name,
            'amount' => (float) $bill->amount,
            'status' => $bill->status,
            'due_date' => $bill->period?->due_date?->toDateString(),
        ];
    }
}
