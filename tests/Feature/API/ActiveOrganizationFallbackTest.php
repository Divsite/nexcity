<?php

namespace Tests\Feature\API;

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
 * A request with no X-Organization-Id header still knows which mosque it is
 * for.
 *
 * The app was sending that header from exactly two places — login and scanning
 * — so every other endpoint that read it resolved organization 0 and returned
 * an empty result. An empty list looks like a mosque with no data, which is
 * why this went unnoticed: nothing errored, the screen was simply blank.
 */
class ActiveOrganizationFallbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function charity_types_are_listed_without_the_header(): void
    {
        $mosque = $this->mosque();
        $this->charityType($mosque, 'Zakat Fitrah');
        $this->charityType($mosque, 'Infaq');

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/charity-types')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function an_explicit_header_still_wins(): void
    {
        // The fallback must not override a client that knows better — someone
        // who belongs to two mosques and has switched to the second one.
        $first = $this->mosque();
        $second = $this->mosque();

        $this->charityType($first, 'Zakat Fitrah');
        $this->charityType($second, 'Infaq');
        $this->charityType($second, 'Sedekah');

        $officer = $this->officer($first);
        $this->join($officer, $second);

        Sanctum::actingAs($officer);

        $this->getJson('/api/v1/charity-types', ['X-Organization-Id' => (string) $second->id])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function a_header_naming_someone_elses_mosque_is_still_refused(): void
    {
        // The fallback is derived from the user's own memberships; it must not
        // become a way to soften the check on a header that was supplied.
        $mine = $this->mosque();
        $theirs = $this->mosque();

        Sanctum::actingAs($this->officer($mine));

        $this->getJson('/api/v1/charity-types', ['X-Organization-Id' => (string) $theirs->id])
            ->assertForbidden();
    }

    #[Test]
    public function recording_charity_works_without_the_header(): void
    {
        // The worst of it: the type id was checked against organization 0, so
        // every recording attempt from a phone was rejected as "not available
        // in your organization".
        $mosque = $this->mosque();
        $typeId = $this->charityType($mosque, 'Infaq');

        Sanctum::actingAs($this->officer($mosque, ['add-mosque-charity-transactions']));

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $typeId,
            'payer_name' => 'Hamba Allah',
            'payment_method' => 'cash',
            'total_money' => 50000,
        ])->assertCreated();

        $this->assertDatabaseHas('charity_transactions', [
            'organization_id' => $mosque->id,
            'payer_name' => 'Hamba Allah',
        ]);
    }

    #[Test]
    public function a_user_belonging_nowhere_gets_no_organization_invented(): void
    {
        $user = User::forceCreate([
            'name' => 'Tanpa Organisasi',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole(Role::firstOrCreate(
            ['name' => 'resident', 'guard_name' => 'web'],
            ['display_name' => 'Resident'],
        ));

        Sanctum::actingAs($user);

        // Refused for want of a capability, not served someone else's data.
        $this->getJson('/api/v1/charity-types')->assertForbidden();
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

        $this->join($user, $mosque, primary: true);

        return $user->fresh();
    }

    protected function join(User $user, Organization $mosque, bool $primary = false): void
    {
        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => 'mosque-finance',
            'is_primary' => $primary,
        ]);
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
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
