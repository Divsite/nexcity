<?php

namespace Tests\Feature\Auth;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use App\Support\DefaultPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DefaultPasswordTest extends TestCase
{
    use RefreshDatabase;

    // ── API (the app) ───────────────────────────────────────────────────────

    #[Test]
    public function a_warga_on_the_default_password_is_sent_to_whatsapp(): void
    {
        $warga = $this->warga('081234567890', 'password');

        $this->postJson('/api/auth/login', ['login' => $warga->phone, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonMissingPath('access_token')
            ->assertJsonPath('message', fn (string $m) => str_contains($m, 'WhatsApp'));
    }

    #[Test]
    public function a_pengurus_on_the_default_password_is_sent_to_the_web_first(): void
    {
        $officer = $this->officer('passworrd');

        $this->postJson('/api/auth/login', ['login' => $officer->email, 'password' => 'passworrd'])
            ->assertStatus(422)
            ->assertJsonMissingPath('access_token')
            ->assertJsonPath('message', fn (string $m) => str_contains($m, 'web'));
    }

    #[Test]
    public function a_password_someone_chose_still_signs_in(): void
    {
        $warga = $this->warga('081234567890', 'rahasia-sendiri');

        $this->postJson('/api/auth/login', ['login' => $warga->phone, 'password' => 'rahasia-sendiri'])
            ->assertOk()
            ->assertJsonStructure(['access_token']);
    }

    #[Test]
    public function a_wrong_password_is_still_just_wrong(): void
    {
        // The default check runs only after the password matched, so it
        // cannot be used to learn whether an account is on the default.
        $warga = $this->warga('081234567890', 'rahasia-sendiri');

        $this->postJson('/api/auth/login', ['login' => $warga->phone, 'password' => 'password'])
            ->assertStatus(401);
    }

    // ── Web ─────────────────────────────────────────────────────────────────

    #[Test]
    public function on_the_web_a_warga_on_the_default_is_refused(): void
    {
        $warga = $this->warga('081234567890', 'password');

        $this->postJson('/login', ['email' => $warga->email, 'password' => 'password'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    #[Test]
    public function on_the_web_a_pengurus_is_held_on_the_change_password_page(): void
    {
        $officer = $this->officer('password');

        $this->postJson('/login', ['email' => $officer->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('redirect', route('profile.change-password-form'));

        $this->assertAuthenticatedAs($officer);

        // Nothing else opens until it is changed.
        $this->get(route('dashboard'))->assertRedirect(route('profile.change-password-form'));
    }

    #[Test]
    public function changing_it_lets_the_pengurus_through(): void
    {
        $officer = $this->officer('password');
        $this->postJson('/login', ['email' => $officer->email, 'password' => 'password']);

        $this->post(route('profile.change-password'), [
            'current_password' => 'password',
            'new_password' => 'Kata-Sandi-Baru-2026',
            'new_password_confirmation' => 'Kata-Sandi-Baru-2026',
        ])->assertSessionHasNoErrors();

        $this->assertFalse(session()->has(DefaultPassword::SESSION_KEY));
        $this->get(route('dashboard'))->assertOk();
    }

    #[Test]
    public function the_other_default_cannot_be_chosen_as_the_new_one(): void
    {
        $officer = $this->officer('password');
        $this->postJson('/login', ['email' => $officer->email, 'password' => 'password']);

        $this->post(route('profile.change-password'), [
            'current_password' => 'password',
            'new_password' => 'passworrd',
            'new_password_confirmation' => 'passworrd',
        ])->assertSessionHasErrors('new_password');
    }

    // ── users:default-passwords ─────────────────────────────────────────────

    #[Test]
    public function the_command_retires_warga_defaults_and_leaves_pengurus_alone(): void
    {
        $warga = $this->warga('081234567890', 'password');
        $officer = $this->officer('password');
        $chosen = $this->warga('081299990000', 'rahasia-sendiri');

        $this->artisan('users:default-passwords')->assertExitCode(1);

        $this->artisan('users:default-passwords', ['--retire-warga' => true])
            ->expectsOutputToContain('retired: 1')
            ->assertExitCode(1);

        $this->assertFalse(Hash::check('password', $warga->fresh()->password));
        // A pengurus keeps the default — a random one would lock them out.
        $this->assertTrue(Hash::check('password', $officer->fresh()->password));
        $this->assertTrue(Hash::check('rahasia-sendiri', $chosen->fresh()->password));
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function user(string $password, ?string $phone = null): User
    {
        return User::forceCreate([
            'name' => 'Uji ' . fake()->unique()->numerify('###'),
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => $phone,
            'password' => Hash::make($password),
        ]);
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

    protected function warga(string $phone, string $password): User
    {
        $rt = $this->rt();
        $user = $this->user($password, $phone);

        UserResidentProfile::forceCreate(['user_id' => $user->id, 'organization_id' => $rt->id]);
        OrganizationUser::forceCreate([
            'organization_id' => $rt->id,
            'user_id' => $user->id,
            'role' => 'resident',
            'is_primary' => true,
        ]);

        return $user;
    }

    protected function officer(string $password): User
    {
        $rt = $this->rt();
        $level = UserLevel::forceCreate(['organization_id' => $rt->id, 'name' => 'Ketua', 'slug' => 'rt-uji']);
        UserLevelPermission::forceCreate(['user_level_id' => $level->id, 'permission_name' => 'browse-rt-residents']);

        $user = $this->user($password);

        // As RolePermissionSeeder gives it: every role may change its own
        // password, which is what keeps the forced change from becoming a
        // redirect loop.
        $role = Role::firstOrCreate(['name' => 'rt_admin', 'guard_name' => 'web'], ['display_name' => 'RT Admin']);
        $role->givePermissionTo(Permission::firstOrCreate(
            ['name' => 'change-password', 'guard_name' => 'web'],
            ['display_name' => 'change_password'],
        ));
        $user->assignRole($role);

        OrganizationUser::forceCreate([
            'organization_id' => $rt->id,
            'user_id' => $user->id,
            'role' => 'rt_admin',
            'level_slug' => 'rt-uji',
            'is_primary' => true,
        ]);

        return $user;
    }
}
