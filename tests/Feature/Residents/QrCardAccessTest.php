<?php

namespace Tests\Feature\Residents;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Users\User;
use App\Models\Profiles\UserResidentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The print routes hand out a resident's identity card, so they need the same
 * scoping the listing screen has.
 *
 * The listing is filtered by organization in the Livewire table, but a direct
 * URL never goes through it — without these checks, guessing an id would print
 * a card for someone in another RT.
 */
class QrCardAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_rt_admin_can_print_a_card_for_their_own_resident(): void
    {
        $rt = $this->makeOrganization('rt-02');
        $admin = $this->makeRtAdmin($rt);
        $resident = $this->makeResident($rt);

        $this->actingAs($admin)
            ->get(route('residents.qr-card', $resident))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function an_rt_admin_cannot_print_a_card_for_another_rts_resident(): void
    {
        $mine = $this->makeOrganization('rt-02');
        $theirs = $this->makeOrganization('rt-03');

        $admin = $this->makeRtAdmin($mine);
        $outsider = $this->makeResident($theirs);

        $this->actingAs($admin)
            ->get(route('residents.qr-card', $outsider))
            ->assertForbidden();
    }

    #[Test]
    public function a_user_without_the_browse_permission_is_refused(): void
    {
        // Previously qrCard carried no permission middleware at all, so any
        // signed-in account could print anyone's card.
        $rt = $this->makeOrganization('rt-02');
        $resident = $this->makeResident($rt);
        $nobody = $this->makeUser();

        $this->actingAs($nobody)
            ->get(route('residents.qr-card', $resident))
            ->assertForbidden();
    }

    #[Test]
    public function a_resident_without_a_qr_token_has_no_card(): void
    {
        // UserResidentProfile generates a token on create, so in practice this
        // only happens for rows written straight to the database — seeds,
        // imports, or profiles that predate that behaviour.
        $rt = $this->makeOrganization('rt-02');
        $admin = $this->makeRtAdmin($rt);
        $resident = $this->makeResident($rt, withToken: false);

        $this->actingAs($admin)
            ->get(route('residents.qr-card', $resident))
            ->assertNotFound();
    }

    #[Test]
    public function bulk_printing_covers_only_the_admins_own_rt(): void
    {
        $mine = $this->makeOrganization('rt-02');
        $theirs = $this->makeOrganization('rt-03');

        $admin = $this->makeRtAdmin($mine);
        $this->makeResident($mine, name: 'Warga Kita');
        $this->makeResident($theirs, name: 'Warga Lain');

        $response = $this->actingAs($admin)->get(route('residents.qr-cards'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');

        // One card per page: our RT has exactly one resident with a token.
        $pdf = $response->getContent();
        $pages = substr_count($pdf, '/Type /Page') - substr_count($pdf, '/Type /Pages');
        $this->assertSame(1, $pages);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function makeUser(string $name = 'User'): User
    {
        return User::forceCreate([
            'name' => $name,
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
    }

    protected function makeOrganization(string $slug): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => $slug . '-' . fake()->unique()->numerify('####'),
            'name' => strtoupper($slug),
            'type' => Organization::TYPE_RT,
        ]);
    }

    protected function makeRtAdmin(Organization $organization): User
    {
        $user = $this->makeUser('Pengurus RT');

        $role = Role::firstOrCreate(
            ['name' => 'rt_admin', 'guard_name' => 'web'],
            ['display_name' => 'RT Admin'],
        );
        $role->givePermissionTo(Permission::firstOrCreate(
            ['name' => 'browse-rt-residents', 'guard_name' => 'web'],
            ['display_name' => 'browse-rt-residents'],
        ));
        $user->assignRole($role);

        OrganizationUser::forceCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'rt_admin',
            'level_slug' => 'rt-superadmin',
            'is_primary' => true,
        ]);

        return $user;
    }

    protected function makeResident(
        Organization $organization,
        string $name = 'Warga',
        bool $withToken = true,
    ): User {
        $user = $this->makeUser($name);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'resident', 'guard_name' => 'web'],
            ['display_name' => 'Resident'],
        ));

        $profile = UserResidentProfile::forceCreate([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        // The model generates a token on create, so clearing it has to happen
        // afterwards and straight through the query builder.
        if (! $withToken) {
            UserResidentProfile::query()
                ->whereKey($profile->id)
                ->update(['qr_token' => null]);
        }

        return $user;
    }
}
