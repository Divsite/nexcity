<?php

namespace Tests\Feature\API;

use App\Models\Charities\CharityTransaction;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CharityReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_totals_the_current_month(): void
    {
        $mosque = $this->mosque();

        $this->transaction($mosque, money: 250000);
        $this->transaction($mosque, money: 150000);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=month')
            ->assertOk()
            ->assertJsonPath('total_money', 400000)
            ->assertJsonPath('transactions', 2);
    }

    #[Test]
    public function cancelled_transactions_are_left_out(): void
    {
        // The web recap counts paid only. Including cancelled ones would have
        // the app and the web disagree about the same month — 288 against 285
        // in the real data.
        $mosque = $this->mosque();

        $this->transaction($mosque, money: 100000);
        $this->transaction($mosque, money: 999000, status: 'cancelled');

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=month')
            ->assertOk()
            ->assertJsonPath('total_money', 100000)
            ->assertJsonPath('transactions', 1);
    }

    #[Test]
    public function the_trend_compares_against_the_previous_month(): void
    {
        $mosque = $this->mosque();

        $this->transaction($mosque, money: 100000, at: now()->subMonthNoOverflow());
        $this->transaction($mosque, money: 150000);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=month')
            ->assertOk()
            ->assertJsonPath('total_money', 150000)
            ->assertJsonPath('previous_money', 100000)
            // Numeric: PHP encodes 50.0 as 50, so compare by value.
            ->assertJsonPath('change_percent', fn ($v) => (float) $v === 50.0);
    }

    #[Test]
    public function a_first_month_has_no_trend_rather_than_zero_percent(): void
    {
        // A mosque's first month has nothing to compare against, and "↑ 0%"
        // would be an invented fact.
        $mosque = $this->mosque();
        $this->transaction($mosque, money: 100000);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=month')
            ->assertOk()
            ->assertJsonPath('change_percent', null);
    }

    #[Test]
    public function it_breaks_the_total_down_by_type(): void
    {
        // Zakat fitrah, zakat mal and infaq are different obligations with
        // different rules — one pot of money would tell an officer nothing.
        $mosque = $this->mosque();

        $fitrah = $this->charityType($mosque, 'Zakat Fitrah');
        $mal = $this->charityType($mosque, 'Zakat Mal');

        $this->transaction($mosque, typeId: $fitrah, money: 50000);
        $this->transaction($mosque, typeId: $fitrah, money: 70000);
        $this->transaction($mosque, typeId: $mal, money: 500000);

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->getJson('/api/v1/charity-report?period=month')->assertOk();

        // Largest first: that is the order an officer reads them in.
        $this->assertSame('Zakat Mal', $response->json('by_type.0.name'));
        $this->assertSame(500000, (int) $response->json('by_type.0.money'));
        $this->assertSame('Zakat Fitrah', $response->json('by_type.1.name'));
        $this->assertSame(120000, (int) $response->json('by_type.1.money'));
        $this->assertSame(2, $response->json('by_type.1.transactions'));
    }

    #[Test]
    public function rice_is_totalled_separately_from_money(): void
    {
        // Zakat fitrah is often paid in rice. Folding it into a rupiah figure
        // would invent a price nobody agreed.
        $mosque = $this->mosque();
        $this->transaction($mosque, rice: 2.5);
        $this->transaction($mosque, rice: 4);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=month')
            ->assertOk()
            ->assertJsonPath('total_rice', 6.5)
            ->assertJsonPath('total_money', 0);
    }

    #[Test]
    public function a_year_window_covers_the_whole_year(): void
    {
        // Zakat is annual and clusters in Ramadan: a mosque can hold months of
        // nothing and then take a year's worth in a fortnight.
        $mosque = $this->mosque();
        $this->transaction($mosque, money: 100000, at: now()->startOfYear()->addDays(3));

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=year')
            ->assertOk()
            ->assertJsonPath('total_money', 100000);
    }

    #[Test]
    public function another_mosques_figures_are_never_included(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();

        $this->transaction($mine, money: 100000);
        $this->transaction($theirs, money: 999000);

        Sanctum::actingAs($this->officer($mine));

        $this->getJson('/api/v1/charity-report?period=month')
            ->assertOk()
            ->assertJsonPath('total_money', 100000);
    }

    #[Test]
    public function a_level_without_the_capability_is_refused(): void
    {
        $mosque = $this->mosque();
        $qurban = $this->officer($mosque, 'mosque-qurban', ['browse-qurban']);

        Sanctum::actingAs($qurban);

        $this->getJson('/api/v1/charity-report')->assertForbidden();
    }

    #[Test]
    public function an_unknown_period_is_refused(): void
    {
        $mosque = $this->mosque();

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=decade')->assertStatus(422);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function mosque(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'masjid-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid Uji',
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(
        Organization $mosque,
        string $levelSlug = 'mosque-finance',
        array $permissions = ['browse-mosque-charity-transactions'],
    ): User {
        $user = User::forceCreate([
            'name' => 'Bendahara',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'mosque_admin', 'guard_name' => 'web'],
            ['display_name' => 'Mosque Admin'],
        ));

        $level = UserLevel::firstOrCreate(
            ['organization_id' => null, 'slug' => $levelSlug],
            ['name' => $levelSlug, 'is_global' => true],
        );

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['display_name' => $permission],
            );
            UserLevelPermission::firstOrCreate([
                'user_level_id' => $level->id,
                'permission_name' => $permission,
            ]);
        }

        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function charityType(Organization $mosque, string $name): int
    {
        $sourceId = DB::table('m_charity_type_sources')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('charity_types')->insertGetId([
            'organization_id' => $mosque->id,
            'charity_type_source_id' => $sourceId,
            'year' => (int) now()->year,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function transaction(
        Organization $mosque,
        ?int $typeId = null,
        float $money = 0,
        float $rice = 0,
        string $status = 'paid',
        ?Carbon $at = null,
    ): CharityTransaction {
        $at ??= now();

        $transaction = CharityTransaction::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_id' => $typeId ?? $this->charityType($mosque, 'Infaq'),
            'year' => (int) $at->year,
            'payer_name' => 'Warga ' . fake()->unique()->numerify('###'),
            'payment_method' => 'cash',
            'status' => $status,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        if ($money > 0 || $rice > 0) {
            // Money and rice both arrive through a receipt; the transaction
            // itself carries neither.
            DB::table('charity_fitrah_receipts')->insert([
                'charity_transaction_id' => $transaction->id,
                'amount_money' => $money,
                'amount_rice' => $rice,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }

        return $transaction;
    }
}
