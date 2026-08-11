<?php

namespace App\Http\Controllers\API\Charities;

use App\Http\Controllers\Controller;
use App\Models\Charities\CharityTransaction;
use App\Services\Menus\MenuContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Charity totals for a period, and the same period a year or month earlier.
 *
 * One endpoint serves two screens: the trend line on the mosque home
 * ("↑ 12% dari bulan lalu") and the Laporan tab. They are the same aggregate
 * asked over different windows, and splitting them would be two places to keep
 * agreeing about one figure.
 *
 * Amounts come from `detailMoneyAmount()` per transaction — a charity
 * transaction has no amount column, its money is the sum of up to six
 * receipts. Only **paid** transactions count, matching the web recap: three of
 * Alamanah's 288 rows are cancelled, and including them would have the app and
 * the web disagree about the same month.
 */
class CharityReportController extends Controller
{
    public const PERIODS = ['day', 'month', 'year'];

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
            'period' => ['nullable', 'in:' . implode(',', self::PERIODS)],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $now = CarbonImmutable::now();
        $year = (int) ($validated['year'] ?? $now->year);
        $period = $validated['period'] ?? 'month';

        // "Hari ini" and "bulan ini" have no meaning inside a year that has
        // already ended — there is no today in 2024. Rather than answer for a
        // window nobody asked about, the period widens to the year and the
        // response says so, so the screen can label what it is showing.
        if ($year !== $now->year) {
            $period = 'year';
        }

        $anchor = $year === $now->year
            ? $now
            : $now->setDate($year, 12, 31)->endOfDay();

        [$start, $end] = $this->window($period, $anchor);
        [$prevStart, $prevEnd] = $this->previousWindow($period, $start);

        $current = $this->totals($organization->id, $start, $end);
        $previous = $this->totals($organization->id, $prevStart, $prevEnd);

        return response()->json([
            'period' => $period,
            'year' => $year,
            'starts_at' => $start->toDateString(),
            'ends_at' => $end->toDateString(),

            'total_money' => $current['money'],
            'total_rice' => $current['rice'],
            'transactions' => $current['count'],

            // Null rather than 0% when there is nothing to compare against. A
            // mosque's first month has no previous month, and "↑ 0%" would be
            // an invented fact.
            'change_percent' => $this->change($previous['money'], $current['money']),
            'previous_money' => $previous['money'],

            'by_type' => $current['by_type'],
        ]);
    }

    /**
     * @return array{money: float, rice: float, count: int, by_type: list<array<string, mixed>>}
     */
    protected function totals(int $organizationId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $transactions = CharityTransaction::query()
            ->where('organization_id', $organizationId)
            ->paid()
            ->withCharityRelations()
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->get();

        return [
            'money' => (float) $transactions->sum(fn (CharityTransaction $t) => $t->detailMoneyAmount()),
            'rice' => (float) $transactions->sum(fn (CharityTransaction $t) => $t->detailRiceAmount()),
            'count' => $transactions->count(),
            'by_type' => $this->byType($transactions),
        ];
    }

    /**
     * Broken down the way an officer thinks about it: zakat fitrah separate
     * from zakat mal separate from infaq, because they are different
     * obligations with different rules — not one pot of money.
     *
     * @param  Collection<int, CharityTransaction>  $transactions
     * @return list<array<string, mixed>>
     */
    protected function byType(Collection $transactions): array
    {
        return $transactions
            ->groupBy(fn (CharityTransaction $t) => $t->charity_type_id ?: 0)
            ->map(function (Collection $rows) {
                $type = $rows->first()?->charityType;

                return [
                    'type_id' => $type?->id,
                    'name' => $type?->source?->name ?? __('messages.unknown'),
                    'money' => (float) $rows->sum(fn (CharityTransaction $t) => $t->detailMoneyAmount()),
                    'rice' => (float) $rows->sum(fn (CharityTransaction $t) => $t->detailRiceAmount()),
                    'transactions' => $rows->count(),
                ];
            })
            ->sortByDesc('money')
            ->values()
            ->all();
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    protected function window(string $period, CarbonImmutable $now): array
    {
        return match ($period) {
            'day' => [$now, $now],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    protected function previousWindow(string $period, CarbonImmutable $start): array
    {
        return match ($period) {
            'day' => [$start->subDay(), $start->subDay()],
            'year' => [$start->subYear(), $start->subYear()->endOfYear()],
            default => [$start->subMonth(), $start->subMonth()->endOfMonth()],
        };
    }

    /**
     * Growth against the comparable window, or null when there is nothing to
     * compare against.
     */
    protected function change(float $previous, float $current): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
