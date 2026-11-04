<?php

namespace Tests\Feature\API;

use App\Models\Charities\CharityTransaction;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
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

/**
 * One year selector governs the whole Amal tab.
 *
 * If only some panels honoured it the screen would show two different years at
 * once and nobody could tell which figure belonged to which.
 */
class YearFilterTest extends TestCase
{
    use RefreshDatabase;

    // ── Which years are offered ─────────────────────────────────────────────

    #[Test]
    public function only_years_with_data_are_offered(): void
    {
        // A generated range would put empty years in front of an officer, who
        // taps one, sees nothing, and cannot tell empty from broken.
        $mosque = $this->mosque();

        $this->transaction($mosque, year: 2024);
        $this->transaction($mosque, year: 2026);

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->getJson('/api/v1/years')->assertOk();

        $this->assertSame([2026, 2024], $response->json('data.*.year'));
    }

    #[Test]
    public function the_current_year_is_offered_before_it_has_anything_in_it(): void
    {
        // Otherwise an officer opening the app in January cannot record into
        // the year they are standing in.
        $mosque = $this->mosque();
        $this->transaction($mosque, year: 2024);

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->getJson('/api/v1/years')->assertOk();

        $this->assertContains((int) now()->year, $response->json('data.*.year'));
    }

    #[Test]
    public function a_year_with_only_distributions_still_counts(): void
    {
        // One selector governs both panels, so a year that has distributions
        // but no charity must still be reachable.
        $mosque = $this->mosque();
        $this->distribution($mosque, year: 2023);

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->getJson('/api/v1/years')->assertOk();

        $this->assertContains(2023, $response->json('data.*.year'));
    }

    // ── The panels honour it ────────────────────────────────────────────────

    #[Test]
    public function the_ledger_shows_only_the_selected_year(): void
    {
        $mosque = $this->mosque();
        $this->transaction($mosque, year: 2024, money: 111000);
        $this->transaction($mosque, year: 2026, money: 222000);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-transactions?year=2024')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.year', 2024)
            ->assertJsonPath('data.0.money', 111000);
    }

    #[Test]
    public function the_ledger_defaults_to_this_year(): void
    {
        // No year and every year at once would put a 2024 receipt at the top
        // of a screen labelled with today's date.
        $mosque = $this->mosque();
        $this->transaction($mosque, year: 2024);
        $this->transaction($mosque, year: (int) now()->year);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.year', (int) now()->year);
    }

    #[Test]
    public function the_type_chips_follow_the_year(): void
    {
        // A mosque that retired a type would otherwise offer this year's chips
        // above last year's rows.
        $mosque = $this->mosque();
        $this->charityType($mosque, 'Zakat Fitrah', year: 2024);
        $this->charityType($mosque, 'Zakat Fitrah', year: 2026);
        $this->charityType($mosque, 'Waqf', year: 2026);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-types?year=2024')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/charity-types?year=2026')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function distributions_are_filtered_by_year(): void
    {
        $mosque = $this->mosque();
        $this->distribution($mosque, year: 2024);
        $this->distribution($mosque, year: (int) now()->year);

        Sanctum::actingAs($this->officer($mosque, [
            'browse-mosque-charity-transactions',
            'browse-mosque-charity-distributions',
        ]));

        $this->getJson('/api/v1/distributions?year=2024')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function a_past_year_widens_the_report_to_the_whole_year(): void
    {
        // There is no "today" in 2024. Rather than answer for a window nobody
        // asked about, the period widens and the response says so.
        $mosque = $this->mosque();
        $this->transaction($mosque, year: 2024, money: 500000, at: Carbon::parse('2024-03-15'));

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?year=2024&period=day')
            ->assertOk()
            ->assertJsonPath('period', 'year')
            ->assertJsonPath('year', 2024)
            ->assertJsonPath('total_money', 500000);
    }

    #[Test]
    public function the_current_year_still_honours_the_period(): void
    {
        $mosque = $this->mosque();
        $this->transaction($mosque, money: 400000);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-report?period=month')
            ->assertOk()
            ->assertJsonPath('period', 'month')
            ->assertJsonPath('total_money', 400000);
    }

    // ── Distribution summary ────────────────────────────────────────────────

    #[Test]
    public function the_summary_names_who_has_not_received(): void
    {
        // "7 belum tersalurkan" is a number; seven names with a reason beside
        // each is the afternoon's work.
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque, year: 2026);
        $class = $this->distributionClass($mosque, money: 100000, rice: 5);

        $this->recipient($distribution, $class, 'Ahmad', 'distributed');
        $this->recipient($distribution, $class, 'Marfuah', 'failed');
        $this->recipient($distribution, $class, 'Sukarno', 'pending');

        Sanctum::actingAs($this->officer($mosque, [
            'browse-mosque-charity-transactions',
            'browse-mosque-charity-distributions',
        ]));

        $response = $this->getJson('/api/v1/distribution-summary?year=2026')->assertOk();

        $response->assertJsonPath('recipients_count', 3)
            ->assertJsonPath('distributed_count', 1)
            ->assertJsonPath('has_distributions', true);

        // Entitlement comes from the golongan: 3 recipients at 100k each.
        $this->assertSame(300000.0, (float) $response->json('total_money'));
        $this->assertSame(100000.0, (float) $response->json('distributed_money'));
        $this->assertSame(15.0, (float) $response->json('total_rice'));

        // Failed first: someone has already spent a trip on it.
        $this->assertSame(
            ['Marfuah', 'Sukarno'],
            $response->json('not_distributed.*.name'),
        );
    }

    #[Test]
    public function an_empty_year_is_not_a_finished_one(): void
    {
        // Zero distributions and everyone-received both give a count of zero.
        // The screen has to tell them apart, and cannot from counts alone.
        $mosque = $this->mosque();

        Sanctum::actingAs($this->officer($mosque, [
            'browse-mosque-charity-transactions',
            'browse-mosque-charity-distributions',
        ]));

        $this->getJson('/api/v1/distribution-summary?year=2019')
            ->assertOk()
            ->assertJsonPath('has_distributions', false)
            ->assertJsonPath('recipients_count', 0)
            ->assertJsonCount(0, 'not_distributed');
    }

    #[Test]
    public function a_finished_year_reports_nobody_outstanding(): void
    {
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque, year: 2026);
        $class = $this->distributionClass($mosque, money: 100000, rice: 4);

        $this->recipient($distribution, $class, 'Ahmad', 'distributed');
        $this->recipient($distribution, $class, 'Budi', 'distributed');

        Sanctum::actingAs($this->officer($mosque, [
            'browse-mosque-charity-transactions',
            'browse-mosque-charity-distributions',
        ]));

        $this->getJson('/api/v1/distribution-summary?year=2026')
            ->assertOk()
            ->assertJsonPath('has_distributions', true)
            ->assertJsonPath('distributed_count', 2)
            ->assertJsonCount(0, 'not_distributed');
    }

    #[Test]
    public function another_mosques_year_is_never_summarised(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();
        $this->distribution($theirs, year: 2026);

        Sanctum::actingAs($this->officer($mine, [
            'browse-mosque-charity-transactions',
            'browse-mosque-charity-distributions',
        ]));

        $this->getJson('/api/v1/distribution-summary?year=2026')
            ->assertOk()
            ->assertJsonPath('has_distributions', false);
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
            ['organization_id' => null, 'slug' => 'mosque-finance'],
            ['name' => 'Bendahara', 'is_global' => true],
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
            'level_slug' => 'mosque-finance',
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function charityType(Organization $mosque, string $name, ?int $year = null): int
    {
        $sourceId = DB::table('m_charity_type_sources')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('####'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('charity_types')->insertGetId([
            'organization_id' => $mosque->id,
            'charity_type_source_id' => $sourceId,
            'year' => $year ?? (int) now()->year,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function transaction(
        Organization $mosque,
        ?int $year = null,
        float $money = 0,
        ?Carbon $at = null,
    ): CharityTransaction {
        $year ??= (int) now()->year;
        $at ??= now();

        $transaction = CharityTransaction::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_id' => $this->charityType($mosque, 'Infaq', $year),
            'year' => $year,
            'payer_name' => 'Warga ' . fake()->unique()->numerify('###'),
            'payment_method' => 'cash',
            'status' => 'paid',
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        if ($money > 0) {
            DB::table('charity_fitrah_receipts')->insert([
                'charity_transaction_id' => $transaction->id,
                'amount_money' => $money,
                'amount_rice' => 0,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }

        return $transaction;
    }

    protected function distribution(Organization $mosque, ?int $year = null): Distribution
    {
        $suffix = fake()->unique()->numerify('####');
        $typeId = DB::table('m_distribution_types')->insertGetId([
            'name' => 'Tipe ' . $suffix,
            'slug' => 'tipe-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Distribution::forceCreate([
            'organization_id' => $mosque->id,
            'distribution_type_id' => $typeId,
            'title' => 'Distribusi ' . $suffix,
            'year' => $year ?? (int) now()->year,
            'status' => 'ongoing',
        ]);
    }

    protected function distributionClass(Organization $mosque, float $money, float $rice): int
    {
        $sourceId = DB::table('m_distribution_class_sources')->insertGetId([
            'name' => 'Fakir Miskin Gol 1',
            'slug' => 'fakir-' . fake()->unique()->numerify('####'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('distribution_classes')->insertGetId([
            'organization_id' => $mosque->id,
            'distribution_class_source_id' => $sourceId,
            'year' => (int) now()->year,
            'get_money' => $money,
            'get_rice' => $rice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function recipient(
        Distribution $distribution,
        int $classId,
        string $name,
        string $status,
    ): DistributionRecipient {
        return DistributionRecipient::forceCreate([
            'distribution_id' => $distribution->id,
            'distribution_class_id' => $classId,
            'recipient_name' => $name,
            'status' => $status,
        ]);
    }
}
