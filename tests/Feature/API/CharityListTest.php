<?php

namespace Tests\Feature\API;

use App\Models\Charities\CharityTransaction;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The ledger behind the Amal tab.
 *
 * The figure per row is the thing worth guarding: a transaction has no amount
 * column, and its money is the sum of whichever receipts it carries. Drop the
 * eager loads and every row reads Rp 0 — with no error anywhere, which is the
 * worst shape a bug about money can take.
 */
class CharityListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_amount_is_summed_from_the_receipts(): void
    {
        $mosque = $this->mosque();
        $transaction = $this->transaction($mosque, money: 250000);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-transactions', [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.id', $transaction->id)
            ->assertJsonPath('data.0.money', 250000);
    }

    #[Test]
    public function a_transaction_with_no_receipt_reads_zero_rather_than_failing(): void
    {
        // Half-entered records exist. The row must still render.
        $mosque = $this->mosque();
        $this->transaction($mosque);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-transactions', [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.money', 0);
    }

    #[Test]
    public function another_mosques_ledger_is_never_included(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();

        $this->transaction($mine, money: 100000);
        $this->transaction($theirs, money: 999000);
        $this->transaction($theirs, money: 999000);

        Sanctum::actingAs($this->officer($mine));

        $response = $this->getJson('/api/v1/charity-transactions', [
            'X-Organization-Id' => (string) $mine->id,
        ])->assertOk();

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame(100000.0, (float) $response->json('data.0.money'));
    }

    #[Test]
    public function it_filters_by_charity_type(): void
    {
        $mosque = $this->mosque();

        $zakat = $this->charityType($mosque, 'Zakat Fitrah');
        $infaq = $this->charityType($mosque, 'Infaq');

        $this->transaction($mosque, typeId: $zakat, money: 50000);
        $this->transaction($mosque, typeId: $infaq, money: 75000);

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->getJson(
            '/api/v1/charity-transactions?type_id=' . $infaq,
            ['X-Organization-Id' => (string) $mosque->id],
        )->assertOk();

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Infaq', $response->json('data.0.type'));
    }

    #[Test]
    public function the_type_name_comes_from_its_source(): void
    {
        // There is no `name` column on charity_types; the label lives on the
        // source. Guessing otherwise was a 500 in development.
        $mosque = $this->mosque();
        $this->transaction($mosque, typeId: $this->charityType($mosque, 'Zakat Mal'));

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-transactions', [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.type', 'Zakat Mal');
    }

    #[Test]
    public function a_level_without_the_browse_capability_is_refused(): void
    {
        $mosque = $this->mosque();
        $qurban = $this->officer($mosque, 'mosque-qurban', ['browse-qurban']);

        Sanctum::actingAs($qurban);

        $this->getJson('/api/v1/charity-transactions', [
            'X-Organization-Id' => (string) $mosque->id,
        ])->assertForbidden();
    }

    #[Test]
    public function an_absurd_page_size_is_refused(): void
    {
        // Nothing should be able to ask for the whole ledger in one response.
        $mosque = $this->mosque();

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-transactions?per_page=5000', [
            'X-Organization-Id' => (string) $mosque->id,
        ])->assertStatus(422);
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

    /** A charity type whose label lives on its source row. */
    protected function charityType(Organization $mosque, string $name): int
    {
        // `m_` prefix: this is master data shared across organizations, which
        // is also why the label lives here rather than on the type.
        $sourceId = DB::table('m_charity_type_sources')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('charity_types')->insertGetId([
            'organization_id' => $mosque->id,
            'charity_type_source_id' => $sourceId,
            'year' => 2026,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function transaction(
        Organization $mosque,
        ?int $typeId = null,
        float $money = 0,
    ): CharityTransaction {
        $transaction = CharityTransaction::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_id' => $typeId ?? $this->charityType($mosque, 'Infaq'),
            'year' => 2026,
            'payer_name' => 'Warga ' . fake()->unique()->numerify('###'),
            'payment_method' => 'cash',
        ]);

        if ($money > 0) {
            // Money arrives through a receipt, never as a column on the
            // transaction — which is the whole point of these tests.
            DB::table('charity_alms_receipts')->insert([
                'charity_transaction_id' => $transaction->id,
                'amount_money' => $money,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $transaction;
    }
}
