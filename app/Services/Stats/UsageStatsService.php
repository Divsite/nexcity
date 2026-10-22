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
            'qurban' => $this->qurban($excluded),
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

    protected function qurban(array $excluded): array
    {
        $coupons = fn (): Builder => $this->forRealOrganizations(
            DB::table('qurban_coupons')
                ->join('qurban_distribution_batches', 'qurban_distribution_batches.id', '=', 'qurban_coupons.qurban_distribution_batch_id')
                ->where('qurban_distribution_batches.status', '!=', 'cancelled')
                ->where('qurban_coupons.status', '!=', 'cancelled'),
            'qurban_distribution_batches.organization_id',
            $excluded
        );
        $totals = $coupons()->selectRaw("COUNT(*) as issued, SUM(CASE WHEN qurban_coupons.status = 'claimed' THEN 1 ELSE 0 END) as claimed, COUNT(DISTINCT qurban_distribution_batches.id) as batches")->first();
        $claimedPerYear = $coupons()
            ->where('qurban_coupons.status', 'claimed')
            ->selectRaw('qurban_distribution_batches.year as year, COUNT(*) as total')
            ->groupBy('qurban_distribution_batches.year')
            ->orderBy('qurban_distribution_batches.year')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->year === null ? 'unknown' : (string) $row->year => (int) $row->total])
            ->all();

        // Scans read from the claim log: successful claims date the distribution, rejected
        // repeats show the QR check stopping double claims in the field.
        $scans = $this->forRealOrganizations(
            DB::table('qurban_coupon_claims')
                ->join('qurban_distribution_batches', 'qurban_distribution_batches.id', '=', 'qurban_coupon_claims.qurban_distribution_batch_id'),
            'qurban_distribution_batches.organization_id',
            $excluded
        )->selectRaw("SUM(CASE WHEN scan_result = 'already_claimed' THEN 1 ELSE 0 END) as duplicates, MIN(CASE WHEN scan_result = 'success' THEN COALESCE(claimed_at, qurban_coupon_claims.created_at) END) as first_at, MAX(CASE WHEN scan_result = 'success' THEN COALESCE(claimed_at, qurban_coupon_claims.created_at) END) as last_at")->first();

        return [
            'batches' => (int) $totals->batches,
            'coupons_issued' => (int) $totals->issued,
            'coupons_claimed' => (int) $totals->claimed,
            'claimed_per_year' => $claimedPerYear,
            'duplicate_scans_blocked' => (int) $scans->duplicates,
            'first_claimed_at' => $this->iso($scans->first_at),
            'last_claimed_at' => $this->iso($scans->last_at),
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
