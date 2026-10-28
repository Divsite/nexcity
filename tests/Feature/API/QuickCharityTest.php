<?php

namespace Tests\Feature\API;

use App\Models\CharityTypes\CharityType;
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
 * Recording charity at the counter.
 *
 * The limits an organization set on a charity type are enforced here rather
 * than trusted to the phone: a mistyped amount caught now is a correction, the
 * same amount caught in a reconciliation is an investigation.
 */
class QuickCharityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_active_types_with_their_limits(): void
    {
        $mosque = $this->mosque();
        $this->charityType($mosque, minAmount: 50000, isRice: true);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-types', $this->header($mosque))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Zakat Fitrah')
            ->assertJsonPath('data.0.accepts_rice', true)
            // Compared numerically: decimal comes back formatted differently
            // on SQLite than on MySQL, and the format is not what matters.
            ->assertJsonPath(
                'data.0.min_amount',
                fn ($value) => (float) $value === 50000.0,
            );
    }

    #[Test]
    public function it_records_a_payment(): void
    {
        $mosque = $this->mosque();
        $type = $this->charityType($mosque);
        $officer = $this->officer($mosque);

        Sanctum::actingAs($officer);

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_name' => 'Pak Budi',
            'total_money' => 75000,
            'payment_method' => 'cash',
        ], $this->header($mosque))->assertCreated();

        $this->assertDatabaseHas('charity_transactions', [
            'organization_id' => $mosque->id,
            'payer_name' => 'Pak Budi',
            // Money taken at the counter is already in hand.
            'status' => 'paid',
            'received_by' => $officer->id,
        ]);
    }

    #[Test]
    public function it_refuses_an_amount_below_the_minimum(): void
    {
        $mosque = $this->mosque();
        $type = $this->charityType($mosque, minAmount: 50000);

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_name' => 'Pak Budi',
            'total_money' => 10000,
            'payment_method' => 'cash',
        ], $this->header($mosque))
            ->assertStatus(422)
            ->assertJsonValidationErrors('total_money');
    }

    #[Test]
    public function it_refuses_an_amount_above_the_maximum(): void
    {
        $mosque = $this->mosque();
        $type = $this->charityType($mosque, minAmount: 22500, maxAmount: 65000);

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_name' => 'Pak Budi',
            'total_money' => 900000,
            'payment_method' => 'cash',
        ], $this->header($mosque))
            ->assertStatus(422)
            ->assertJsonValidationErrors('total_money');
    }

    #[Test]
    public function it_refuses_rice_for_a_type_that_does_not_take_it(): void
    {
        $mosque = $this->mosque();
        $type = $this->charityType($mosque, isRice: false);

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_name' => 'Pak Budi',
            'total_rice' => 2.5,
            'payment_method' => 'cash',
        ], $this->header($mosque))
            ->assertStatus(422)
            ->assertJsonValidationErrors('total_rice');
    }

    #[Test]
    public function it_refuses_a_transaction_with_neither_money_nor_rice(): void
    {
        // A record of nothing is worse than no record: it looks like income
        // that was never received.
        $mosque = $this->mosque();
        $type = $this->charityType($mosque);

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_name' => 'Pak Budi',
            'payment_method' => 'cash',
        ], $this->header($mosque))
            ->assertStatus(422)
            ->assertJsonValidationErrors('total_money');
    }

    #[Test]
    public function it_refuses_a_type_belonging_to_another_organization(): void
    {
        // Without this check, one mosque could book income against another's
        // charity type simply by sending its id.
        $mine = $this->mosque();
        $theirs = $this->mosque();
        $foreignType = $this->charityType($theirs);

        Sanctum::actingAs($this->officer($mine));

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $foreignType->id,
            'payer_name' => 'Pak Budi',
            'total_money' => 75000,
            'payment_method' => 'cash',
        ], $this->header($mine))
            ->assertStatus(422)
            ->assertJsonValidationErrors('charity_type_id');
    }

    #[Test]
    public function a_level_without_the_add_capability_is_refused(): void
    {
        $mosque = $this->mosque();
        $type = $this->charityType($mosque);
        // A qurban officer may not record charity.
        $qurban = $this->officer($mosque, 'mosque-qurban', ['scan-qurban-coupon']);

        Sanctum::actingAs($qurban);

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_name' => 'Pak Budi',
            'total_money' => 75000,
            'payment_method' => 'cash',
        ], $this->header($mosque))->assertForbidden();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    protected function header(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    protected function mosque(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'masjid-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid Uji',
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    protected function charityType(
        Organization $organization,
        ?int $minAmount = null,
        ?int $maxAmount = null,
        bool $isRice = true,
    ): CharityType {
        $sourceId = DB::table('m_charity_type_sources')->insertGetId([
            'name' => 'Zakat Fitrah',
            'slug' => 'zakat-fitrah-' . fake()->unique()->numerify('####'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return CharityType::forceCreate([
            'organization_id' => $organization->id,
            'charity_type_source_id' => $sourceId,
            'year' => now()->year,
            'min_amount' => $minAmount,
            'max_amount' => $maxAmount,
            'is_rice' => $isRice,
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(
        Organization $organization,
        string $levelSlug = 'mosque-zakat',
        array $permissions = [
            'browse-mosque-charity-transactions',
            'add-mosque-charity-transactions',
        ],
    ): User {
        $user = User::forceCreate([
            'name' => 'Amil',
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
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }
}
