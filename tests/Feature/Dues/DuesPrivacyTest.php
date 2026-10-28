<?php

namespace Tests\Feature\Dues;

use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesRate;
use App\Models\Dues\RtDuesScheme;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * What a household owes is private.
 *
 * The rule is stated in two places already — `docs/modules/warga.md` and
 * `.ai/guidelines/project-overview.md`, both listing "status iuran saya" under
 * **Privat: warga itu sendiri**. This file is the part that makes it true
 * rather than merely written down.
 *
 * Two shapes of leak are worth guarding separately: a neighbour reading someone
 * else's arrears, and an officer of a different RT reading the whole block's.
 */
class DuesPrivacyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_resident_cannot_see_a_neighbours_dues(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt);
        $neighbour = $this->resident($rt);

        $period = $this->periodWithBills($rt, [$me, $neighbour]);

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/v1/me/dues')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $neighbourBillIds = RtDuesBill::query()
            ->where('rt_dues_period_id', $period->id)
            ->where('resident_id', $neighbour->id)
            ->pluck('id');

        $this->assertCount(1, $ids);
        $this->assertTrue(
            $ids->intersect($neighbourBillIds)->isEmpty(),
            'A resident must never receive a neighbour\'s bill.',
        );
    }

    #[Test]
    public function the_endpoint_has_no_id_to_tamper_with(): void
    {
        // The strongest guarantee available: there is no parameter naming whose
        // dues to return, so there is nothing to change. Any future route that
        // takes a resident id needs its own scoping test.
        $rt = $this->rt();
        $me = $this->resident($rt);

        $neighbour = $this->resident($rt);
        $this->periodWithBills($rt, [$me, $neighbour]);

        Sanctum::actingAs($me);

        $plain = $this->getJson('/api/v1/me/dues')->assertOk()->json();

        // Every shape someone might try. None of them can widen the answer,
        // because the endpoint never reads an id from the request at all.
        foreach ([
            '?resident_id=' . $neighbour->id,
            '?user_id=' . $neighbour->id,
            '?id=' . $neighbour->id,
        ] as $attempt) {
            $this->assertSame(
                $plain,
                $this->getJson('/api/v1/me/dues' . $attempt)->assertOk()->json(),
                'A query parameter must not change whose dues come back.',
            );
        }
    }

    #[Test]
    public function an_officer_of_another_rt_cannot_read_the_bill_list(): void
    {
        $mine = $this->rt();
        $theirs = $this->rt();

        $period = $this->periodWithBills($theirs, [$this->resident($theirs)]);

        $this->actingAs($this->officer($mine))
            ->get(route('rt.dues.period', $period))
            ->assertForbidden();
    }

    #[Test]
    public function an_officer_of_another_rt_cannot_read_the_golongan_list(): void
    {
        // Golongan is household data too — knowing who is Ber KK across the
        // road is nobody else's business.
        $mine = $this->rt();
        $theirs = $this->rt();

        $stranger = $this->resident($theirs);

        $this->actingAs($this->officer($mine))
            ->get(route('rt.dues.tiers'))
            ->assertOk()
            ->assertDontSee($stranger->name);
    }

    #[Test]
    public function a_resident_cannot_open_the_treasurers_pages(): void
    {
        $rt = $this->rt();
        $resident = $this->resident($rt);

        $this->actingAs($resident)->get(route('rt.dues'))->assertForbidden();
        $this->actingAs($resident)->get(route('rt.dues.tiers'))->assertForbidden();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function rt(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'rt-' . fake()->unique()->numerify('#####'),
            'name' => 'RT Uji',
            'type' => Organization::TYPE_RT,
        ]);
    }

    protected function resident(Organization $rt): User
    {
        $user = User::forceCreate([
            'name' => 'Warga ' . fake()->unique()->numerify('#####'),
            'username' => 'w' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        Permission::firstOrCreate(
            ['name' => 'browse-resident-dues', 'guard_name' => 'web'],
            ['display_name' => 'Browse Resident Dues'],
        );

        $role = Role::firstOrCreate(
            ['name' => 'resident', 'guard_name' => 'web'],
            ['display_name' => 'Resident'],
        );
        $role->givePermissionTo('browse-resident-dues');
        $user->assignRole($role);

        UserResidentProfile::forceCreate([
            'user_id' => $user->id,
            'organization_id' => $rt->id,
        ]);

        return $user->fresh();
    }

    protected function officer(Organization $rt): User
    {
        $user = User::forceCreate([
            'name' => 'Bendahara',
            'username' => 'o' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'rt_admin', 'guard_name' => 'web'],
            ['display_name' => 'RT Admin'],
        ));

        $level = UserLevel::firstOrCreate(
            ['organization_id' => null, 'slug' => 'rt-finance'],
            ['name' => 'rt-finance', 'is_global' => true],
        );

        foreach (['browse-rt-dues', 'edit-rt-dues'] as $permission) {
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
            'organization_id' => $rt->id,
            'user_id' => $user->id,
            'role' => 'rt_admin',
            'level_slug' => 'rt-finance',
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    /**
     * @param  list<User>  $residents
     */
    protected function periodWithBills(Organization $rt, array $residents): RtDuesPeriod
    {
        $scheme = RtDuesScheme::forceCreate([
            'organization_id' => $rt->id,
            'name' => 'Iuran Bulanan',
            'type' => RtDuesScheme::TYPE_MONTHLY,
            'year' => 2026,
        ]);

        RtDuesRate::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'label' => 'Semua warga',
            'amount' => 25000,
            'is_default' => true,
        ]);

        $period = RtDuesPeriod::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'organization_id' => $rt->id,
            'year' => 2026,
            'month' => 8,
        ]);

        foreach ($residents as $resident) {
            RtDuesBill::forceCreate([
                'rt_dues_period_id' => $period->id,
                'resident_id' => $resident->id,
                'amount' => 25000,
                'status' => RtDuesBill::STATUS_PENDING,
            ]);
        }

        return $period;
    }
}
