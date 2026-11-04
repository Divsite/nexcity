<?php

namespace App\Actions\Dues;

use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesRate;
use App\Models\Profiles\UserResidentProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Issues bills for one period, at each household's own rate.
 *
 * Idempotent: running it again only adds bills for residents who did not have
 * one. The treasurer will press the button twice, and a family that moved in
 * mid-month gets billed without anyone having to remember.
 */
class IssueDuesBills
{
    /**
     * @param  list<int>|null  $residentIds  who to bill; null means everyone in
     *                                       the RT, which is the usual case.
     *                                       A one-off collection sometimes
     *                                       targets only part of the block.
     * @return int how many bills were created
     */
    public function handle(RtDuesPeriod $period, ?array $residentIds = null): int
    {
        $rates = $period->scheme->rates()->get();

        if ($rates->isEmpty()) {
            return 0;
        }

        $profiles = $this->residents($period, $residentIds);

        if ($profiles->isEmpty()) {
            return 0;
        }

        $alreadyBilled = RtDuesBill::query()
            ->where('rt_dues_period_id', $period->id)
            ->pluck('resident_id')
            ->flip();

        $rows = $profiles
            ->reject(fn (UserResidentProfile $p) => $alreadyBilled->has($p->user_id))
            ->map(function (UserResidentProfile $profile) use ($period, $rates) {
                $rate = $this->rateFor($rates, $profile->dues_tier);

                if (! $rate) {
                    return null;
                }

                return [
                    'rt_dues_period_id' => $period->id,
                    'resident_id' => $profile->user_id,
                    // Amount and golongan are both snapshots. Correcting a rate
                    // later must not rewrite what a household was already told,
                    // and "why 20.000?" has to stay answerable afterwards.
                    'amount' => $rate->amount,
                    'tier' => $rate->tier,
                    'status' => RtDuesBill::STATUS_PENDING,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty()) {
            return 0;
        }

        DB::transaction(fn () => RtDuesBill::query()->insert($rows->all()));

        return $rows->count();
    }

    /**
     * @param  list<int>|null  $residentIds
     * @return Collection<int, UserResidentProfile>
     */
    protected function residents(RtDuesPeriod $period, ?array $residentIds): Collection
    {
        return UserResidentProfile::query()
            ->where('organization_id', $period->organization_id)
            ->when($residentIds !== null, fn ($query) => $query->whereIn('user_id', $residentIds))
            ->get();
    }

    /**
     * The rate that applies to a household.
     *
     * Falls back to the scheme's default, then to a flat rate, because most
     * residents have no golongan recorded — only a handful of profiles carry a
     * family card number. A household nobody has classified yet must still be
     * billable, or the treasurer's first run would silently skip almost
     * everyone.
     *
     * @param  Collection<int, RtDuesRate>  $rates
     */
    protected function rateFor(Collection $rates, ?string $tier): ?RtDuesRate
    {
        if ($tier !== null) {
            $exact = $rates->firstWhere('tier', $tier);

            if ($exact) {
                return $exact;
            }
        }

        return $rates->firstWhere('is_default', true)
            ?? $rates->firstWhere('tier', null)
            ?? $rates->first();
    }
}
