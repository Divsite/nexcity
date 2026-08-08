<?php

namespace Tests\Feature\API;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CapabilityResolverTest extends TestCase
{
    use RefreshDatabase;

    protected CapabilityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(CapabilityResolver::class);
    }

    #[Test]
    public function it_grants_organization_permissions_from_the_level_not_the_role(): void
    {
        // Every mosque officer carries the same role. If the role decided
        // things, a bendahara could scan qurban coupons -- which is the whole
        // reason levels exist.
        $organization = $this->makeOrganization();

        $finance = $this->makeMember($organization, 'mosque-finance', [
            'browse-mosque-charity-transactions',
        ]);

        $qurban = $this->makeMember($organization, 'mosque-qurban', [
            'browse-qurban',
            'scan-qurban-coupon',
        ]);

        $this->assertNotContains(
            'scan-qurban-coupon',
            $this->capabilitiesFor($finance, $organization),
        );

        $this->assertContains(
            'scan-qurban-coupon',
            $this->capabilitiesFor($qurban, $organization),
        );
    }

    #[Test]
    public function a_member_without_a_level_gets_no_organization_capabilities(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeMember($organization, null, []);

        $this->assertSame([], $this->capabilitiesFor($user, $organization));
    }

    #[Test]
    public function a_level_that_does_not_exist_grants_nothing(): void
    {
        // A stale level_slug must fail closed, not fall back to the role.
        $organization = $this->makeOrganization();
        $user = $this->makeMember($organization, 'mosque-ghost', []);

        $this->assertSame([], $this->capabilitiesFor($user, $organization));
    }

    #[Test]
    public function levels_are_scoped_to_their_own_organization(): void
    {
        // Same slug, two mosques, different permissions. Resolving for one must
        // never pick up the other's grants.
        $mosqueA = $this->makeOrganization('mosque-a');
        $mosqueB = $this->makeOrganization('mosque-b');

        $this->makeLevel($mosqueB, 'mosque-officer', ['scan-qurban-coupon']);

        $user = $this->makeMember($mosqueA, 'mosque-officer', ['browse-qurban']);

        $capabilities = $this->capabilitiesFor($user, $mosqueA);

        $this->assertContains('browse-qurban', $capabilities);
        $this->assertNotContains('scan-qurban-coupon', $capabilities);
    }

    #[Test]
    public function global_capabilities_exclude_organization_scoped_permissions(): void
    {
        $user = $this->makeUser();
        $this->giveRole($user, 'mosque_admin', [
            'my-account',
            'change-password',
            'browse-mosque-charity-transactions',
            'scan-qurban-coupon',
        ]);

        $global = $this->resolver->globalCapabilities($user->fresh());

        $this->assertContains('my-account', $global);
        $this->assertContains('change-password', $global);
        // These belong to an organization context and must come from a level.
        $this->assertNotContains('browse-mosque-charity-transactions', $global);
        $this->assertNotContains('scan-qurban-coupon', $global);
    }

    #[Test]
    public function superadmin_is_not_limited_by_a_level(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeMember($organization, null, []);
        $this->giveRole($user, 'superadmin', ['scan-qurban-coupon', 'browse-qurban']);

        $capabilities = $this->capabilitiesFor($user->fresh(), $organization);

        $this->assertContains('scan-qurban-coupon', $capabilities);
    }

    #[Test]
    public function the_default_organization_is_the_primary_membership(): void
    {
        $user = $this->makeUser();
        $first = $this->makeOrganization('first');
        $primary = $this->makeOrganization('primary');

        $this->attach($user, $first, null, isPrimary: false);
        $this->attach($user, $primary, null, isPrimary: true);

        $this->assertSame(
            $primary->id,
            $this->resolver->defaultOrganizationId($user, $user->organizationMemberships()->get()),
        );
    }

    #[Test]
    public function the_default_organization_falls_back_to_the_first_membership(): void
    {
        $user = $this->makeUser();
        $organization = $this->makeOrganization();
        $this->attach($user, $organization, null, isPrimary: false);

        $this->assertSame(
            $organization->id,
            $this->resolver->defaultOrganizationId($user, $user->organizationMemberships()->get()),
        );
    }

    #[Test]
    public function a_user_with_no_membership_has_no_default_organization(): void
    {
        $user = $this->makeUser();

        $this->assertNull(
            $this->resolver->defaultOrganizationId($user, collect()),
        );
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * @return list<string>
     */
    protected function capabilitiesFor(User $user, Organization $organization): array
    {
        $resolved = $this->resolver->resolveByOrganization(
            $user,
            $user->organizationMemberships()->get(),
        );

        return $resolved[$organization->id]['capabilities'] ?? [];
    }

    protected function makeUser(): User
    {
        return User::forceCreate([
            'name' => 'Test User',
            'username' => 'user' . fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
    }

    protected function makeOrganization(string $slug = 'test-mosque'): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'slug' => $slug . '-' . fake()->unique()->numerify('####'),
            'name' => 'Test Organization',
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function makeMember(Organization $organization, ?string $levelSlug, array $permissions): User
    {
        $user = $this->makeUser();

        if ($levelSlug !== null && $permissions !== []) {
            $this->makeLevel($organization, $levelSlug, $permissions);
        }

        $this->attach($user, $organization, $levelSlug);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function makeLevel(Organization $organization, string $slug, array $permissions): UserLevel
    {
        $level = UserLevel::forceCreate([
            'organization_id' => $organization->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
        ]);

        foreach ($permissions as $permission) {
            UserLevelPermission::forceCreate([
                'user_level_id' => $level->id,
                'permission_name' => $permission,
            ]);
        }

        return $level;
    }

    protected function attach(User $user, Organization $organization, ?string $levelSlug, bool $isPrimary = false): void
    {
        OrganizationUser::forceCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
            'level_slug' => $levelSlug,
            'is_primary' => $isPrimary,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function giveRole(User $user, string $roleName, array $permissions): void
    {
        // This project adds a NOT NULL `display_name` to Spatie's roles table,
        // so Role::findOrCreate() alone is not enough.
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['display_name' => ucfirst($roleName)],
        );

        foreach ($permissions as $permission) {
            $role->givePermissionTo($this->permission($permission));
        }

        $user->assignRole($role);
    }

    protected function permission(string $name): Permission
    {
        return Permission::firstOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            ['display_name' => $name],
        );
    }
}
