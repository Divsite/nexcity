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
            ->assertJsonPath('residents', ['profiles' => 3, 'heads_of_family' => 2]);
    }

    public function test_response_contains_no_personal_data_or_money(): void
    {
        $this->seedUsage();

        $body = $this->getJson('/api/v1/stats/summary', ['X-Api-Key' => self::KEY])->assertOk()->getContent();

        foreach (['Rahasia', '0812', '3201000000000001', '2500000', '500000', 'Organisasi', 'org-', 'total_money', 'amount_money', 'email'] as $private) {
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
            ->assertJsonPath('residents', ['profiles' => 0, 'heads_of_family' => 0]);
    }
}
