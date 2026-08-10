<?php

namespace App\Actions\Dues;

use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesScheme;
use Illuminate\Support\Facades\DB;

/**
 * Opens a scheme's collection points, then bills them.
 *
 * A monthly scheme gets all twelve months in one go, mirroring the printed
 * card: an RT prints the whole year in advance and ticks months off as the
 * money comes in. Opening one month at a time was my own invention and matched
 * nothing anybody does.
 *
 * A one-off gets exactly one period, on the date the RT names.
 */
class OpenDuesScheme
{
    public function __construct(protected IssueDuesBills $issue)
    {
    }

    /**
     * @param  list<int>|null  $residentIds  who to bill; null means everyone.
     * @return array{periods: int, bills: int}
     */
    public function handle(
        RtDuesScheme $scheme,
        ?string $dueDate = null,
        ?array $residentIds = null,
        ?int $createdBy = null,
    ): array {
        $months = $scheme->isMonthly() ? range(1, 12) : [null];

        $periods = [];

        DB::transaction(function () use ($scheme, $months, $dueDate, $createdBy, &$periods) {
            foreach ($months as $month) {
                // firstOrNew, not create: reopening a year must not duplicate
                // it. A treasurer correcting the due date should not meet a
                // unique-constraint error for pressing the button again.
                $period = RtDuesPeriod::query()->firstOrNew([
                    'rt_dues_scheme_id' => $scheme->id,
                    'year' => $scheme->year,
                    'month' => $month,
                ]);

                $period->fill([
                    'organization_id' => $scheme->organization_id,
                    'label' => $scheme->isMonthly() ? null : $scheme->name,
                    'due_date' => $dueDate,
                    'created_by' => $period->exists ? $period->created_by : $createdBy,
                ])->save();

                $periods[] = $period;
            }
        });

        $bills = 0;

        foreach ($periods as $period) {
            $bills += $this->issue->handle($period->load('scheme.rates'), $residentIds);
        }

        return ['periods' => count($periods), 'bills' => $bills];
    }
}
