<?php

namespace Tests\Feature\API;

use App\Models\Users\PhoneOtp;
use App\Models\Users\User;
use App\Services\Otp\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{phone: string, message: string}> */
    protected array $sent = [];

    protected bool $gatewayDown = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(WhatsAppSender::class, new class($this) implements WhatsAppSender {
            public function __construct(private OtpLoginTest $test)
            {
            }

            public function send(string $phone, string $message): void
            {
                $this->test->record($phone, $message);
            }
        });

        RateLimiter::clear('otp-phone:6281234567890');
    }

    public function record(string $phone, string $message): void
    {
        if ($this->gatewayDown) {
            throw new RuntimeException('down');
        }

        $this->sent[] = ['phone' => $phone, 'message' => $message];
    }

    #[Test]
    public function a_registered_warga_gets_a_code_and_signs_in_with_it(): void
    {
        // Stored the way the seeders write numbers, typed the way people do.
        $user = $this->makeUser('+62 812 3456 7890');

        $this->postJson('/api/auth/otp/request', ['phone' => '0812-3456-7890'])
            ->assertStatus(202);

        $this->assertCount(1, $this->sent);
        $this->assertSame('6281234567890', $this->sent[0]['phone']);

        $this->postJson('/api/auth/otp/verify', [
            'phone' => '081234567890',
            'code' => $this->sentCode(),
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['access_token', 'memberships', 'global_capabilities']);
    }

    #[Test]
    public function an_unknown_number_gets_the_same_answer_and_no_message(): void
    {
        // Answering "not registered" would let anyone walk the RT's phone list.
        $this->makeUser('081234567890');

        $known = $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);
        $unknown = $this->postJson('/api/auth/otp/request', ['phone' => '089999999999']);

        $unknown->assertStatus(202);
        $this->assertSame($known->json(), $unknown->json());
        $this->assertCount(1, $this->sent);
    }

    #[Test]
    public function the_code_itself_is_never_stored(): void
    {
        $this->makeUser('081234567890');
        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);

        $this->assertNotSame($this->sentCode(), PhoneOtp::first()->code_hash);
        $this->assertStringNotContainsString($this->sentCode(), PhoneOtp::first()->code_hash);
    }

    #[Test]
    public function a_wrong_code_is_refused_and_five_wrong_codes_burn_it(): void
    {
        $this->makeUser('081234567890');
        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);
        $wrong = $this->sentCode() === '111111' ? '222222' : '111111';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/otp/verify', ['phone' => '081234567890', 'code' => $wrong])
                ->assertStatus(422);
        }

        // The right code no longer works once the attempts are spent.
        $this->postJson('/api/auth/otp/verify', [
            'phone' => '081234567890',
            'code' => $this->sentCode(),
        ])->assertStatus(422);
    }

    #[Test]
    public function a_code_works_once(): void
    {
        $this->makeUser('081234567890');
        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);
        $payload = ['phone' => '081234567890', 'code' => $this->sentCode()];

        $this->postJson('/api/auth/otp/verify', $payload)->assertOk();
        $this->postJson('/api/auth/otp/verify', $payload)->assertStatus(422);
    }

    #[Test]
    public function an_expired_code_is_refused(): void
    {
        $this->makeUser('081234567890');
        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);

        $this->travel(6)->minutes();

        $this->postJson('/api/auth/otp/verify', [
            'phone' => '081234567890',
            'code' => $this->sentCode(),
        ])->assertStatus(422);
    }

    #[Test]
    public function asking_again_too_soon_sends_nothing_new(): void
    {
        $this->makeUser('081234567890');

        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);
        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890'])->assertStatus(202);

        $this->assertCount(1, $this->sent);
    }

    #[Test]
    public function a_new_code_retires_the_previous_one(): void
    {
        $this->makeUser('081234567890');

        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);
        $first = $this->sentCode();

        $this->travel(2)->minutes();
        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);

        if ($first !== $this->sentCode()) {
            $this->postJson('/api/auth/otp/verify', ['phone' => '081234567890', 'code' => $first])
                ->assertStatus(422);
        }

        $this->postJson('/api/auth/otp/verify', [
            'phone' => '081234567890',
            'code' => $this->sentCode(),
        ])->assertOk();
    }

    #[Test]
    public function one_number_on_two_accounts_signs_into_neither(): void
    {
        // A ketua RT with a warga account and a pengurus account on one phone.
        $this->makeUser('081234567890');
        $this->makeUser('+62 812 3456 7890');

        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);

        $this->postJson('/api/auth/otp/verify', [
            'phone' => '081234567890',
            'code' => $this->sentCode(),
        ])
            ->assertStatus(409)
            ->assertJsonMissingPath('access_token');
    }

    #[Test]
    public function the_malaysian_formatting_mistake_still_signs_in(): void
    {
        $user = $this->makeUser('+6081234567890');

        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890']);

        $this->postJson('/api/auth/otp/verify', [
            'phone' => '081234567890',
            'code' => $this->sentCode(),
        ])->assertOk()->assertJsonPath('user.id', $user->id);
    }

    #[Test]
    public function a_number_that_is_not_indonesian_is_a_validation_error(): void
    {
        $this->postJson('/api/auth/otp/request', ['phone' => '021 765 4321'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    #[Test]
    public function a_gateway_failure_is_reported_not_swallowed(): void
    {
        // Saying "sent" when nothing went out leaves a warga waiting forever.
        $this->makeUser('081234567890');
        $this->gatewayDown = true;

        $this->postJson('/api/auth/otp/request', ['phone' => '081234567890'])
            ->assertStatus(503);
    }

    #[Test]
    public function one_number_cannot_be_flooded_with_codes(): void
    {
        $this->makeUser('081234567890');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/otp/request', ['phone' => '081234567890'])->assertStatus(202);
        }

        $this->postJson('/api/auth/otp/request', ['phone' => '0812 3456 7890'])->assertStatus(429);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function sentCode(): string
    {
        $this->assertNotEmpty($this->sent, 'No WhatsApp message was sent.');
        preg_match('/\b(\d{6})\b/', end($this->sent)['message'], $matches);

        return $matches[1];
    }

    protected function makeUser(string $phone): User
    {
        return User::forceCreate([
            'name' => 'Warga Uji',
            'username' => 'warga' . fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone,
            'password' => bcrypt('secret'),
        ]);
    }
}
