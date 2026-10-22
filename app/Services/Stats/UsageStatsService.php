<?php

namespace App\Services\Stats;

use App\Models\Organizations\Organization;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate usage numbers for public proof (samsulhadiss.com Proof Register).
 * Only counts and date ranges leave this class: no names, contacts, IDs of people or money amounts.
 */
class UsageStatsService
{
    public function summary(): array
    {
        $excluded = $this->excludedOrganizationIds();

        return [
            'generated_at' => now()->toIso8601String(),
            'excluded_organization_ids' => $excluded,
            'organizations' => $this->organizations($excluded),
            'charity_transactions' => $this->charityTransactions($excluded),
            'distribution_recipients' => $this->distributionRecipients($excluded),
            'residents' => $this->residents($excluded),
        ];
    }

    /** Demo/seed organizations, configured with STATS_EXCLUDED_ORGANIZATION_IDS. */
    public function excludedOrganizationIds(): array
    {
        $ids = config('services.openclaw.stats_excluded_organization_ids', []);

        return collect(is_array($ids) ? $ids : explode(',', (string) $ids))
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected function organizations(array $excluded): array
    {
        $counts = DB::table('organizations')
            ->whereIn('type', [Organization::TYPE_MOSQUE, Organization::TYPE_RT])
            ->where('status', 'active')
            ->whereNotIn('id', $excluded)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            Organization::TYPE_MOSQUE => (int) ($counts[Organization::TYPE_MOSQUE] ?? 0),
            Organization::TYPE_RT => (int) ($counts[Organization::TYPE_RT] ?? 0),
        ];
    }

    // History counts every non-demo organization, including ones no longer active:
    // their past transactions and distributions were still real usage.
    protected function charityTransactions(array $excluded): array
    {
        $paid = fn (): Builder => $this->forRealOrganizations(
            DB::table('charity_transactions')->where('status', 'paid'),
            'charity_transactions.organization_id',
            $excluded
        );
        // created_at is the transaction date, the same one the daily recap uses (received_at was dropped).
        $range = $paid()->selectRaw('COUNT(*) as total, MIN(created_at) as first_at, MAX(created_at) as last_at')->first();
        $perYear = $paid()
            ->selectRaw('year, COUNT(*) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->year === null ? 'unknown' : (string) $row->year => (int) $row->total])
            ->all();

        return [
            'paid' => (int) $range->total,
            'first_at' => $this->iso($range->first_at),
            'last_at' => $this->iso($range->last_at),
            'per_year' => $perYear,
        ];
    }

    protected function distributionRecipients(array $excluded): array
    {
        $range = $this->forRealOrganizations(
            DB::table('distribution_recipients')
                ->join('distributions', 'distributions.id', '=', 'distribution_recipients.distribution_id')
                ->where('distribution_recipients.status', 'distributed'),
            'distributions.organization_id',
            $excluded
        )->selectRaw('COUNT(*) as total, MIN(distribution_recipients.distributed_at) as first_at, MAX(distribution_recipients.distributed_at) as last_at')->first();

        return [
            'distributed' => (int) $range->total,
            'first_at' => $this->iso($range->first_at),
            'last_at' => $this->iso($range->last_at),
        ];
    }

    protected function residents(array $excluded): array
    {
        $totals = $this->forRealOrganizations(
            DB::table('user_resident_profiles'),
            'user_resident_profiles.organization_id',
            $excluded
        )->selectRaw('COUNT(*) as profiles, SUM(CASE WHEN is_head_family = 1 THEN 1 ELSE 0 END) as heads_of_family')->first();

        return [
            'profiles' => (int) $totals->profiles,
            'heads_of_family' => (int) $totals->heads_of_family,
        ];
    }

    // Rows without an organization cannot be attributed to a real partner, so they are left out.
    protected function forRealOrganizations(Builder $query, string $column, array $excluded): Builder
    {
        return $query->whereNotNull($column)->whereNotIn($column, $excluded);
    }

    protected function iso(?string $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toIso8601String();
    }
}
