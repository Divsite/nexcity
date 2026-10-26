<?php

namespace Tests\Feature\Authorization;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * `capability:` authorizes by level; `permission:` authorizes by role.
 *
 * The difference is the whole point: every mosque officer carries the same
 * mosque_admin role, so a role check cannot tell a bendahara from a pengurus
 * qurban. See docs/operations/authorization-audit.md.
 */
class CapabilityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'capability:scan-qurban-coupon'])
            ->get('/_test/qurban-scan', fn () => response('ok'));

        // The OR form, mirroring Spatie's.
        Route::middleware(['web', 'auth', 'capability:add-rt-residents|edit-rt-residents'])
            ->get('/_test/residents-create', fn () => response('ok'));

        // The API stack, where an organization switcher exists. The order is
        // the point: `organization.context` must resolve the header before
        // `capability:` reads it.
        Route::middleware(['auth', 'organization.context', 'capability:scan-qurban-coupon'])
            ->get('/_test/api/qurban-scan', fn () => response('ok'));
    }

    #[Test]
    public function a_level_that_grants_the_capability_is_allowed(): void
    {
        $user = $this->officer('mosque-qurban', ['browse-qurban', 'scan-qurban-coupon']);

        $this->actingAs($user)->get('/_test/qurban-scan')->assertOk();
    }

    #[Test]
    public function a_level_without_it_is_refused_even_though_the_role_grants_it(): void
    {
        // The regression this whole change exists for: mosque_admin holds
        // scan-qurban-coupon, so `permission:` let the bendahara straight in.
        $user = $this->officer('mosque-finance', ['browse-mosque-charity-transactions']);

        $this->assertTrue(
            $user->can('scan-qurban-coupon'),
            'The role is expected to still grant it — that is the hole being closed.',
        );

        $this->actingAs($user)->get('/_test/qurban-scan')->assertForbidden();
    }

    #[Test]
    public function superadmin_is_never_scoped_by_a_level(): void
    {
        $user = $this->officer('mosque-finance', []);
        $this->giveRole($user, 'superadmin', ['scan-qurban-coupon']);

        $this->actingAs($user->fresh())->get('/_test/qurban-scan')->assertOk();
    }

    #[Test]
    public function a_member_with_no_level_is_refused(): void
    {
        $user = $this->officer(null, []);

        $this->actingAs($user)->get('/_test/qurban-scan')->assertForbidden();
    }

    #[Test]
    public function any_one_of_the_alternatives_is_enough(): void
    {
        // `capability:a|b` mirrors Spatie's OR form.
        $user = $this->officer('rt-secretary', ['add-rt-residents'], type: Organization::TYPE_RT);

        $this->actingAs($user)->get('/_test/residents-create')->assertOk();
    }

    #[Test]
    public function holding_none_of_the_alternatives_is_refused(): void
    {
        $user = $this->officer('rt-humas', ['browse-rt-news'], type: Organization::TYPE_RT);

        $this->actingAs($user)->get('/_test/residents-create')->assertForbidden();
    }

    #[Test]
    public function a_guest_is_refused(): void
    {
        $this->get('/_test/qurban-scan')->assertRedirect();
    }

    // ── the active organization decides, not the default one ────────────────

    #[Test]
    public function the_level_in_the_organization_being_acted_in_is_what_counts(): void
    {
        // A volunteer whose scanning level sits in the *second* mosque. Judged
        // by their primary membership they hold nothing, and every scan at the
        // mosque they are actually standing in would have been refused.
        [$user, , $second] = $this->memberOfTwo(
            primaryPermissions: ['browse-mosque-charity-transactions'],
            secondPermissions: ['scan-qurban-coupon'],
        );

        $this->actingAs($user)
            ->getJson('/_test/api/qurban-scan', ['X-Organization-Id' => (string) $second->id])
            ->assertOk();
    }

    #[Test]
    public function a_level_held_elsewhere_does_not_travel_to_another_organization(): void
    {
        // The other direction, and the one that matters: authority earned at
        // one mosque must not follow the user into another.
        [$user, , $second] = $this->memberOfTwo(
            primaryPermissions: ['scan-qurban-coupon'],
            secondPermissions: ['browse-mosque-charity-transactions'],
        );

        $this->actingAs($user)
            ->getJson('/_test/api/qurban-scan', ['X-Organization-Id' => (string) $second->id])
            ->assertForbidden();
    }

    #[Test]
    public function without_a_header_the_default_membership_still_decides(): void
    {
        // How the web app calls in: no organization switcher on the request.
        [$user] = $this->memberOfTwo(
            primaryPermissions: ['scan-qurban-coupon'],
            secondPermissions: [],
        );

        $this->actingAs($user)->getJson('/_test/api/qurban-scan')->assertOk();
    }

    #[Test]
    public function claiming_an_organization_the_user_does_not_belong_to_is_refused(): void
    {
        // ResolveActiveOrganization rejects this before capabilities are even
        // resolved, so the header cannot be used to go shopping for a level.
        [$user] = $this->memberOfTwo(
            primaryPermissions: ['scan-qurban-coupon'],
            secondPermissions: [],
        );

        $stranger = Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'org-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid Orang Lain',
            'type' => Organization::TYPE_MOSQUE,
        ]);

        $this->actingAs($user)
            ->getJson('/_test/api/qurban-scan', ['X-Organization-Id' => (string) $stranger->id])
            ->assertForbidden();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * A user belonging to two mosques, with a different level in each.
     *
     * @param  list<string>  $primaryPermissions
     * @param  list<string>  $secondPermissions
     * @return array{User, Organization, Organization}
     */
    protected function memberOfTwo(
        array $primaryPermissions,
        array $secondPermissions,
    ): array {
        $user = $this->officer('mosque-a-level', $primaryPermissions);

        $primary = Organization::query()
            ->whereIn('id', $user->organizationMemberships()->pluck('organization_id'))
            ->firstOrFail();

        $second = Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'org-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid Kedua',
            'type' => Organization::TYPE_MOSQUE,
        ]);

        $level = UserLevel::forceCreate([
            'organization_id' => $second->id,
            'name' => 'mosque-b-level',
            'slug' => 'mosque-b-level',
        ]);

        foreach ($secondPermissions as $permission) {
            UserLevelPermission::forceCreate([
                'user_level_id' => $level->id,
                'permission_name' => $permission,
            ]);
        }

        OrganizationUser::forceCreate([
            'organization_id' => $second->id,
            'user_id' => $user->id,
            'role' => 'member',
            'level_slug' => 'mosque-b-level',
            'is_primary' => false,
        ]);

        return [$user->fresh(), $primary, $second];
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(
        ?string $levelSlug,
        array $permissions,
        string $type = Organization::TYPE_MOSQUE,
    ): User {
        $organization = Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'org-' . fake()->unique()->numerify('#####'),
            'name' => 'Organisasi Uji',
            'type' => $type,
        ]);

        $user = User::forceCreate([
            'name' => 'Pengurus',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        // The role deliberately grants everything, so the tests prove the level
        // is what decides.
        $this->giveRole($user, $type === Organization::TYPE_MOSQUE ? 'mosque_admin' : 'rt_admin', [
            'scan-qurban-coupon',
            'browse-qurban',
            'add-rt-residents',
            'edit-rt-residents',
        ]);

        if ($levelSlug !== null) {
            $level = UserLevel::forceCreate([
                'organization_id' => $organization->id,
                'name' => $levelSlug,
                'slug' => $levelSlug,
            ]);

            foreach ($permissions as $permission) {
                UserLevelPermission::forceCreate([
                    'user_level_id' => $level->id,
                    'permission_name' => $permission,
                ]);
            }
        }

        OrganizationUser::forceCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function giveRole(User $user, string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['display_name' => $roleName],
        );

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['display_name' => $permission],
            ));
        }

        $user->assignRole($role);
    }
}
