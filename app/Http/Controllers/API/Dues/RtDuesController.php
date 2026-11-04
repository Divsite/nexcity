<?php

namespace App\Http\Controllers\API\Dues;

use App\Actions\Dues\RecordDuesPayment;
use App\Http\Controllers\Controller;
use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Iuran RT from the treasurer's phone.
 *
 * Setting up a year's scheme and its rates stays on the web: that is a desk job
 * done once. What happens on the phone is the evening round — who has not paid,
 * and "Pak Ahmad just paid me, mark it".
 *
 * Every query is scoped to the active RT; an id in the URL is checked against
 * it, never trusted.
 */
class RtDuesController extends Controller
{
    /**
     * A year of periods with what was billed and collected in each.
     */
    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $year = (int) ($request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ])['year'] ?? now()->year);

        $periods = RtDuesPeriod::query()
            ->with('scheme')
            ->where('organization_id', $organizationId)
            ->where('year', $year)
            ->withCount([
                'bills',
                'bills as paid_count' => fn ($q) => $q->where('status', RtDuesBill::STATUS_PAID),
                'bills as outstanding_count' => fn ($q) => $q->where('status', RtDuesBill::STATUS_PENDING),
            ])
            ->withSum(['bills as collected_amount' => fn ($q) => $q->where('status', RtDuesBill::STATUS_PAID)], 'amount')
            ->withSum(['bills as outstanding_amount' => fn ($q) => $q->where('status', RtDuesBill::STATUS_PENDING)], 'amount')
            // Newest month first; a one-off (no month) after the months.
            ->orderByRaw('month is null')
            ->orderByDesc('month')
            ->get();

        $outstanding = $this->outstandingBills($organizationId);

        return response()->json([
            'year' => $year,
            'years' => RtDuesPeriod::query()
                ->where('organization_id', $organizationId)
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year'),
            // Across every year, not only the one shown: a debt from last
            // December is still a debt.
            'summary' => [
                'outstanding_bills' => (clone $outstanding)->count(),
                'outstanding_amount' => (float) (clone $outstanding)->sum('amount'),
                'residents_in_arrears' => (clone $outstanding)->distinct()->count('resident_id'),
            ],
            'periods' => $periods->map(fn (RtDuesPeriod $period) => [
                'id' => $period->id,
                'label' => $period->label,
                'scheme' => $period->scheme?->name,
                'year' => $period->year,
                'month' => $period->month,
                'due_date' => $period->due_date?->toDateString(),
                'bills_count' => $period->bills_count,
                'paid_count' => $period->paid_count,
                'outstanding_count' => $period->outstanding_count,
                'collected_amount' => (float) $period->collected_amount,
                'outstanding_amount' => (float) $period->outstanding_amount,
            ])->values(),
        ]);
    }

    /**
     * Who owes, one row per warga, every unpaid month gathered together.
     *
     * The question a treasurer actually asks is "siapa yang belum bayar", not
     * "who has not paid August": Pak Budi owing three months is one visit.
     * The phone number is here, and only here, because this list exists to
     * remind people.
     */
    public function arrears(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);

        $rows = $this->outstandingBills($organizationId)
            ->with(['period.scheme', 'resident'])
            ->get()
            ->groupBy('resident_id')
            ->map(function (Collection $bills) {
                /** @var User|null $resident */
                $resident = $bills->first()->resident;

                $ordered = $bills->sortBy(fn (RtDuesBill $b) => [$b->period?->year, $b->period?->month ?? 13]);

                return [
                    'resident_id' => $resident?->id,
                    'name' => $resident?->name ?? '-',
                    'phone' => $resident?->phone_normalized,
                    'bills_count' => $bills->count(),
                    'amount' => (float) $bills->sum('amount'),
                    'bills' => $ordered->map(fn (RtDuesBill $b) => $this->bill($b))->values(),
                ];
            })
            // Longest-owing first: that is who the treasurer visits first.
            ->sortByDesc('bills_count')
            ->values();

        return response()->json(['data' => $rows]);
    }

    /**
     * One period's bills, for working down a single month.
     */
    public function bills(Request $request, RtDuesPeriod $period): JsonResponse
    {
        if ((int) $period->organization_id !== $this->organizationId($request)) {
            return response()->json(['message' => 'Periode tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'in:all,pending,paid,waived'],
        ]);

        $bills = $period->bills()
            ->with('resident')
            ->when(($validated['status'] ?? 'all') !== 'all', fn (Builder $q) => $q->where('status', $validated['status']))
            ->get()
            ->sortBy(fn (RtDuesBill $b) => $b->resident?->name)
            ->values();

        return response()->json([
            'period' => [
                'id' => $period->id,
                'label' => $period->label,
                'scheme' => $period->scheme?->name,
            ],
            'data' => $bills->map(fn (RtDuesBill $b) => $this->bill($b) + [
                'resident_id' => $b->resident_id,
                'name' => $b->resident?->name ?? '-',
            ])->values(),
        ]);
    }

    /**
     * Records a payment, a waiver, or undoes either.
     */
    public function update(Request $request, RtDuesBill $bill, RecordDuesPayment $record): JsonResponse
    {
        if ((int) $bill->period?->organization_id !== $this->organizationId($request)) {
            return response()->json(['message' => 'Tagihan tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', RtDuesBill::STATUSES)],
            'payment_method' => ['nullable', 'in:cash,transfer,qris'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $record->handle(
            $bill,
            $validated['status'],
            $request->user(),
            $validated['payment_method'] ?? null,
            $validated['note'] ?? null,
        );

        return response()->json(['data' => $this->bill($bill->fresh('period.scheme'))]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function bill(RtDuesBill $bill): array
    {
        return [
            'id' => $bill->id,
            'period' => $bill->period?->label,
            'scheme' => $bill->period?->scheme?->name,
            'tier' => $bill->tierLabel(),
            'amount' => (float) $bill->amount,
            'status' => $bill->status,
            'paid_at' => $bill->paid_at?->toIso8601String(),
            'payment_method' => $bill->payment_method,
            'note' => $bill->note,
        ];
    }

    /**
     * @return Builder<RtDuesBill>
     */
    protected function outstandingBills(int $organizationId): Builder
    {
        return RtDuesBill::query()
            ->where('status', RtDuesBill::STATUS_PENDING)
            ->whereHas('period', fn (Builder $q) => $q->where('organization_id', $organizationId));
    }

    protected function organizationId(Request $request): int
    {
        return (int) $request->attributes->get('active_organization_id');
    }
}
