<?php

namespace Tests\Feature\Authorization;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Menus\UserMenu;
use App\Models\Users\User;
use App\Services\Menus\MenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A menu item may be granted by the user's **level**, not only by their role.
 *
 * This is the regression that emptied the RT and mosque sidebars: levels were
 * consolidated into global rows (`organization_id` null), but MenuBuilder still
 * looked them up by organization id only. Nothing matched, so every
 * level-granted item vanished and only role-granted ones survived — a treasurer
 * saw 2 menus instead of 4, with no error anywhere.
 */
class MenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // MenuBuilder caches the row list forever; a stale cache would make
        // these tests read each other's data.
        Cache::flush();
    }

    #[Test]
    public function a_global_level_grants_its_menu(): void
    {
        $rt = $this->rt();
        $menu = $this->menu('rt.dues', ['browse-rt-dues']);

        $user = $this->officer($rt, 'rt-finance', ['browse-rt-dues'], organizationId: null);

        $this->assertTrue($this->sees($user, $rt, $menu->route_name));
    }

    #[Test]
    public function an_organization_specific_level_still_wins(): void
    {
        // A future per-partner override must keep working; the global row is a
        // fallback, not a replacement.
        $rt = $this->rt();
        $menu = $this->menu('rt.dues', ['browse-rt-dues']);

        $user = $this->officer($rt, 'rt-finance', ['browse-rt-dues'], organizationId: $rt->id);

        $this->assertTrue($this->sees($user, $rt, $menu->route_name));
    }

    #[Test]
    public function a_level_without_the_permission_does_not_see_it(): void
    {
        // The half that matters: the fix must not turn into "everyone sees
        // everything".
        $rt = $this->rt();
        $this->menu('rt.dues', ['browse-rt-dues']);

        $user = $this->officer($rt, 'rt-humas', ['browse-rt-news'], organizationId: null);

        $this->assertFalse($this->sees($user, $rt, 'rt.dues'));
    }

    #[Test]
    public function the_role_alone_does_not_reveal_an_organization_menu(): void
    {
        // The complaint that prompted this: a bendahara saw Kependudukan and
        // Keorganisasian because `rt_admin` grants them, even though the
        // rt-finance level does not. The menu described the role while the
        // route obeyed the level, so every one of those entries led to a 403.
        $rt = $this->rt();
        $this->menu('rt.citizen.data', ['browse-rt-residents']);

        $user = $this->officer($rt, 'rt-finance', ['browse-rt-dues'], organizationId: null);

        // Grant it on the role, exactly as the real seeder does.
        $permission = Permission::firstOrCreate(
            ['name' => 'browse-rt-residents', 'guard_name' => 'web'],
            ['display_name' => 'browse-rt-residents'],
        );
        Role::findByName('rt_admin')->givePermissionTo($permission);

        $this->assertTrue(
            $user->fresh()->can('browse-rt-residents'),
            'The role is expected to still grant it — that is the leak being closed.',
        );
        $this->assertFalse($this->sees($user->fresh(), $rt, 'rt.citizen.data'));
    }

    #[Test]
    public function a_member_with_no_level_sees_nothing_level_granted(): void
    {
        $rt = $this->rt();
        $this->menu('rt.dues', ['browse-rt-dues']);

        $user = $this->officer($rt, null, [], organizationId: null);

        $this->assertFalse($this->sees($user, $rt, 'rt.dues'));
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function sees(User $user, Organization $organization, string $routeName): bool
    {
        return app(MenuBuilder::class)
            ->forUser($user, 'rt', $organization)
            ->flatten()
            ->pluck('route_name')
            ->contains($routeName);
    }

    protected function rt(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'rt-' . fake()->unique()->numerify('#####'),
            'name' => 'RT Uji',
            'type' => Organization::TYPE_RT,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function menu(string $routeName, array $permissions): UserMenu
    {
        return UserMenu::forceCreate([
            'context' => 'rt',
            'section' => 'keuangan',
            'label' => $routeName,
            'icon' => 'ri-money-dollar-circle-line',
            'route_name' => $routeName,
            'order' => 10,
            'visibility_rules' => [
                'organization_types' => ['rt'],
                'permissions' => $permissions,
            ],
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(
        Organization $rt,
        ?string $levelSlug,
        array $permissions,
        ?int $organizationId = null,
    ): User {
        $user = User::forceCreate([
            'name' => 'Pengurus',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        // The role deliberately grants nothing, so the tests prove the level is
        // what decides.
        $user->assignRole(Role::firstOrCreate(
            ['name' => 'rt_admin', 'guard_name' => 'web'],
            ['display_name' => 'RT Admin'],
        ));

        if ($levelSlug !== null) {
            $level = UserLevel::firstOrCreate(
                ['organization_id' => $organizationId, 'slug' => $levelSlug],
                ['name' => $levelSlug, 'is_global' => $organizationId === null],
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
        }

        OrganizationUser::forceCreate([
            'organization_id' => $rt->id,
            'user_id' => $user->id,
            'role' => 'rt_admin',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }
}
