<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatsSummaryTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-openclaw-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openclaw.api_key' => self::KEY, 'services.openclaw.stats_excluded_organization_ids' => '1, 9']);
    }

    private function organization(int $id, string $type, string $status = 'active'): int
    {
        DB::table('organizations')->insert(['id' => $id, 'uuid' => (string) Str::uuid(), 'slug' => 'org-'.$id, 'name' => 'Organisasi '.$id, 'type' => $type, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
        return $id;
    }

    private function transaction(?int $organizationId, string $status, ?int $year, string $createdAt): void
    {
        DB::table('charity_transactions')->insert(['organization_id' => $organizationId, 'status' => $status, 'year' => $year, 'payer_name' => 'Pembayar Rahasia', 'payer_phone' => '081200000000', 'created_at' => $createdAt, 'updated_at' => $createdAt]);
    }

    private function resident(int $organizationId, bool $headOfFamily): void
    {
        $userId = DB::table('users')->insertGetId(['name' => 'Warga Rahasia', 'username' => Str::random(10), 'email' => Str::random(10).'@example.test', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_resident_profiles')->insert(['user_id' => $userId, 'organization_id' => $organizationId, 'national_id_number' => '3201000000000001', 'is_head_family' => $headOfFamily, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function qurbanBatch(int $organizationId, int $year, string $status, array $couponStatuses, array $scans): void
    {
        $batchId = DB::table('qurban_distribution_batches')->insertGetId(['organization_id' => $organizationId, 'title' => 'Batch Rahasia', 'year' => $year, 'status' => $status, 'location_label' => 'Lokasi Rahasia', 'created_at' => now(), 'updated_at' => now()]);
        $couponIds = [];
        foreach ($couponStatuses as $couponStatus) {
            $couponIds[] = DB::table('qurban_coupons')->insertGetId(['qurban_distribution_batch_id' => $batchId, 'coupon_code' => 'KODE-'.Str::random(8), 'status' => $couponStatus, 'created_at' => now(), 'updated_at' => now()]);
        }
        $minute = 0;
        foreach ($scans as $result => $count) {
            for ($i = 0; $i < $count; $i++) {
                $minute++;
                DB::table('qurban_coupon_claims')->insert(['qurban_distribution_batch_id' => $batchId, 'qurban_coupon_id' => $couponIds[0], 'scanned_code' => 'KODE-SCAN', 'scan_result' => $result, 'claimed_at' => $result === 'success' ? sprintf('2026-05-27 14:%02d:00', $minute) : null, 'scan_latitude' => -6.27, 'scan_longitude' => 106.7, 'scan_location_label' => 'Lokasi Rahasia', 'created_at' => sprintf('2026-05-27 14:%02d:30', $minute), 'updated_at' => now()]);
            }
        }
    }

    private function seedUsage(): void
    {
        $demo = $this->organization(1, 'mosque');
        $mosque = $this->organization(2, 'mosque');
        $rt = $this->organization(3, 'rt');
        $this->organization(4, 'rt', 'inactive');
        $this->organization(5, 'umkm');

        $this->transaction($mosque, 'paid', 2025, '2025-03-30 08:00:00');
        $this->transaction($mosque, 'paid', null, '2025-12-01 08:00:00');
        $this->transaction($rt, 'paid', 2026, '2026-03-20 09:30:00');
        $this->transaction(4, 'paid', 2026, '2026-04-01 10:00:00');
        $this->transaction($mosque, 'draft', 2026, '2026-05-01 10:00:00');
        $this->transaction($mosque, 'cancelled', 2026, '2026-05-01 10:00:00');
        $this->transaction($demo, 'paid', 2024, '2024-01-01 10:00:00');
        $this->transaction(null, 'paid', 2023, '2023-01-01 10:00:00');

        $typeId = DB::table('m_distribution_types')->insertGetId(['name' => 'Zakat', 'slug' => 'zakat', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([$mosque => ['distributed', 'distributed', 'pending'], $demo => ['distributed']] as $organizationId => $statuses) {
            $distributionId = DB::table('distributions')->insertGetId(['organization_id' => $organizationId, 'distribution_type_id' => $typeId, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
            foreach ($statuses as $i => $status) {
                DB::table('distribution_recipients')->insert(['distribution_id' => $distributionId, 'recipient_name' => 'Penerima Rahasia', 'recipient_phone' => '081299999999', 'amount_money' => 500000, 'status' => $status, 'distributed_at' => $status === 'distributed' ? '2026-04-0'.($i + 1).' 07:00:00' : null, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $this->qurbanBatch($mosque, 2026, 'active', ['claimed', 'claimed', 'issued', 'cancelled'], ['success' => 2, 'already_claimed' => 3, 'invalid' => 1]);
        $this->qurbanBatch($mosque, 2026, 'cancelled', ['claimed'], ['success' => 1]);
        $this->qurbanBatch($demo, 2026, 'active', ['claimed', 'claimed'], ['success' => 2, 'already_claimed' => 4]);

        $this->resident($mosque, true);
        $this->resident($mosque, false);
        $this->resident($rt, true);
        $this->resident($demo, true);
    }

    public function test_requires_the_openclaw_key(): void
    {
        $this->getJson('/api/v1/stats/summary')->assertUnauthorized();
        $this->getJson('/api/v1/stats/summary', ['X-Api-Key' => 'wrong'])->assertUnauthorized();
        $this->getJson('/api/v1/stats/summary', ['Authorization' => 'Bearer '.self::KEY])->assertOk();
    }

    public function test_counts_real_usage_and_leaves_out_demo_organizations(): void
    {
        $this->seedUsage();

        $this->getJson('/api/v1/stats/summary', ['X-Api-Key' => self::KEY])
            ->assertOk()
            ->assertJsonPath('excluded_organization_ids', [1, 9])
            ->assertJsonPath('organizations', ['mosque' => 1, 'rt' => 1])
            ->assertJsonPath('charity_transactions.paid', 4)
            ->assertJsonPath('charity_transactions.per_year', ['unknown' => 1, '2025' => 1, '2026' => 2])
            ->assertJsonPath('charity_transactions.first_at', fn ($value) => str_starts_with($value, '2025-03-30T08:00:00'))
            ->assertJsonPath('charity_transactions.last_at', fn ($value) => str_starts_with($value, '2026-04-01T10:00:00'))
            ->assertJsonPath('distribution_recipients.distributed', 2)
            ->assertJsonPath('distribution_recipients.first_at', fn ($value) => str_starts_with($value, '2026-04-01T07:00:00'))
            ->assertJsonPath('distribution_recipients.last_at', fn ($value) => str_starts_with($value, '2026-04-02T07:00:00'))
            ->assertJsonPath('residents', ['profiles' => 3, 'heads_of_family' => 2])
            ->assertJsonPath('qurban.batches', 1)
            ->assertJsonPath('qurban.coupons_issued', 3)
            ->assertJsonPath('qurban.coupons_claimed', 2)
            ->assertJsonPath('qurban.claimed_per_year', ['2026' => 2])
            ->assertJsonPath('qurban.duplicate_scans_blocked', 3)
            ->assertJsonPath('qurban.first_claimed_at', fn ($value) => str_starts_with($value, '2026-05-27T14:01:00'))
            ->assertJsonPath('qurban.last_claimed_at', fn ($value) => str_starts_with($value, '2026-05-27T14:02:00'));
    }

    public function test_response_contains_no_personal_data_or_money(): void
    {
        $this->seedUsage();

        $body = $this->getJson('/api/v1/stats/summary', ['X-Api-Key' => self::KEY])->assertOk()->getContent();

        foreach (['Rahasia', 'KODE', '106.7', '0812', '3201000000000001', '2500000', '500000', 'Organisasi', 'org-', 'total_money', 'amount_money', 'email'] as $private) {
            $this->assertStringNotContainsString($private, $body);
        }
    }

    public function test_empty_database_returns_zeroes(): void
    {
        $this->getJson('/api/v1/stats/summary', ['X-Api-Key' => self::KEY])
            ->assertOk()
            ->assertJsonPath('organizations', ['mosque' => 0, 'rt' => 0])
            ->assertJsonPath('charity_transactions', ['paid' => 0, 'first_at' => null, 'last_at' => null, 'per_year' => []])
            ->assertJsonPath('distribution_recipients', ['distributed' => 0, 'first_at' => null, 'last_at' => null])
            ->assertJsonPath('residents', ['profiles' => 0, 'heads_of_family' => 0])
            ->assertJsonPath('qurban', ['batches' => 0, 'coupons_issued' => 0, 'coupons_claimed' => 0, 'claimed_per_year' => [], 'duplicate_scans_blocked' => 0, 'first_claimed_at' => null, 'last_claimed_at' => null]);
    }
}
