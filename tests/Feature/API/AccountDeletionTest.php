<?php

namespace Tests\Feature\API;

use App\Models\Charities\CharityTransaction;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use App\Services\Otp\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Hapus akun": the person closes their own account. What goes, what stays
 * for the organizations' books, and who may not do it from a tap.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{phone: string, message: string}> */
    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(WhatsAppSender::class, new class($this) implements WhatsAppSender {
            public function __construct(private AccountDeletionTest $test) {}

            public function send(string $phone, string $message): void
            {
                $this->test->sent[] = ['phone' => $phone, 'message' => $message];
            }
        });
    }

    #[Test]
    public function a_self_registered_account_is_closed_with_a_whatsapp_code(): void
    {
        $user = $this->user('Budi Santoso', '081234567890', selfRegistered: true);
        $mosque = $this->organization(Organization::TYPE_MOSQUE);
        $payment = $this->charity($mosque, $user);
        $user->createToken('mobile-app');

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/me/account')
            ->assertOk()
            ->assertJsonPath('can_delete', true)
            ->assertJsonPath('confirm_with', 'code')
            ->assertJsonPath('phone_hint', '7890');

        $this->postJson('/api/v1/me/account/code')->assertStatus(202);

        $this->deleteJson('/api/v1/me/account', ['code' => $this->sentCode()])
            ->assertOk();

        $user->refresh();
        $this->assertNotNull($user->closed_at);
        $this->assertSame('Akun dihapus', $user->name);
        $this->assertNull($user->phone);
        $this->assertNull($user->phone_normalized);
        $this->assertSame(0, $user->tokens()->count());

        // The mosque's books do not change because a payer left.
        $this->assertDatabaseHas('charity_transactions', ['id' => $payment->id, 'payer_name' => 'Budi Santoso']);
    }

    #[Test]
    public function a_closed_account_cannot_be_signed_in_to_again(): void
    {
        $user = $this->user('Budi', '081234567890', selfRegistered: true);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/me/account/code');
        $this->deleteJson('/api/v1/me/account', ['code' => $this->sentCode()])->assertOk();

        // A WhatsApp code to the same number now opens a fresh account, not
        // the closed one.
        RateLimiter::clear('otp-phone:6281234567890');
        $this->travel(2)->minutes();
        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);
        $this->postJson('/api/auth/otp/verify', ['phone' => '081234567890', 'code' => $this->sentCode()])
            ->assertOk()
            ->assertJsonPath('registration_required', true);
    }

    #[Test]
    public function a_warga_the_rt_registered_stays_in_the_rt_register(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $user = $this->user('Citra Lestari', '081234567890');
        UserResidentProfile::forceCreate(['user_id' => $user->id, 'organization_id' => $rt->id]);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/me/account')->assertJsonPath('keeps_rt_record', true);
        $this->postJson('/api/v1/me/account/code');
        $this->deleteJson('/api/v1/me/account', ['code' => $this->sentCode()])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->closed_at);
        // The RT's population record, not an app account: the RT decides.
        $this->assertSame('Citra Lestari', $user->name);
        $this->assertNull($user->phone_normalized);
        $this->assertDatabaseHas('user_resident_profiles', ['user_id' => $user->id]);
    }

    #[Test]
    public function a_pengurus_must_be_released_first(): void
    {
        $mosque = $this->organization(Organization::TYPE_MOSQUE);
        $treasurer = $this->user('Bendahara', '081234567890');
        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $treasurer->id,
            'role' => 'mosque_admin',
            'level_slug' => 'mosque-finance',
            'is_primary' => true,
        ]);

        Sanctum::actingAs($treasurer);
        $this->getJson('/api/v1/me/account')->assertJsonPath('can_delete', false);
        $this->postJson('/api/v1/me/account/code');

        $this->deleteJson('/api/v1/me/account', ['code' => $this->sentCode()])->assertStatus(409);
        $this->assertNull($treasurer->fresh()->closed_at);
    }

    #[Test]
    public function a_wrong_code_or_password_deletes_nothing(): void
    {
        $user = $this->user('Budi', '081234567890', selfRegistered: true);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/me/account/code');

        $this->deleteJson('/api/v1/me/account', ['code' => '000000'])->assertStatus(422);
        $this->deleteJson('/api/v1/me/account', ['password' => 'salah'])->assertStatus(422);
        $this->deleteJson('/api/v1/me/account', [])->assertStatus(422);

        $this->assertNull($user->fresh()->closed_at);
    }

    #[Test]
    public function an_account_without_a_phone_confirms_with_its_password(): void
    {
        $user = $this->user('Admin Lama', null);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/account')->assertJsonPath('confirm_with', 'password');
        $this->deleteJson('/api/v1/me/account', ['password' => 'secret-123'])->assertOk();

        $this->postJson('/api/auth/login', ['login' => $user->fresh()->username, 'password' => 'secret-123'])
            ->assertUnauthorized();
    }

    #[Test]
    public function the_deletion_page_is_reachable_without_the_app(): void
    {
        $this->get('/hapus-akun')->assertOk()->assertSee('Hapus akun Nexcity');
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function sentCode(): string
    {
        $this->assertNotEmpty($this->sent, 'No WhatsApp message was sent.');
        preg_match('/\b(\d{6})\b/', end($this->sent)['message'], $matches);

        return $matches[1];
    }

    protected function user(string $name, ?string $phone, bool $selfRegistered = false): User
    {
        RateLimiter::clear('otp-self:*');

        return User::forceCreate([
            'name' => $name,
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone,
            'password' => bcrypt('secret-123'),
            'registered_via' => $selfRegistered ? 'self' : null,
        ]);
    }

    protected function organization(string $type): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => $type . '-' . fake()->unique()->numerify('#####'),
            'name' => 'Organisasi Uji',
            'type' => $type,
        ]);
    }

    protected function charity(Organization $mosque, User $payer): CharityTransaction
    {
        return CharityTransaction::forceCreate([
            'organization_id' => $mosque->id,
            'year' => now()->year,
            'payer_name' => $payer->name,
            'payer_user_id' => $payer->id,
            'payment_method' => 'cash',
            'status' => CharityTransaction::STATUS_PAID,
        ]);
    }
}
