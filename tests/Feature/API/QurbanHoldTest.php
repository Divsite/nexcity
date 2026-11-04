<?php

namespace Tests\Feature\API;

use App\Actions\Qurban\ReleaseUnpaidHolds;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanAnimalAllocation;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanProgramPackage;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A patungan line taken from the app is held until the first deposit, then
 * released for someone else — so nobody can sit on a cow for free.
 */
class QurbanHoldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unpaid_app_line_is_released_when_its_hold_ends(): void
    {
        $mosque = $this->mosque();
        $cow = $this->cow($mosque);
        $warga = $this->member($mosque, 'Citra');

        Sanctum::actingAs($warga);
        $response = $this->postJson('/api/v1/qurban-slots', ['animal_id' => $cow->id, 'names' => ['Citra', 'Ibu Citra']])
            ->assertCreated();
        $this->assertNotNull($response->json('pay_before'));

        // Still held the day before.
        $this->travel(config('core.qurban_hold_days') - 1)->days();
        $this->assertSame(0, app(ReleaseUnpaidHolds::class)->handle());
        $this->assertSame(2, QurbanAnimalAllocation::count());

        $this->travel(2)->days();
        $this->assertSame(1, app(ReleaseUnpaidHolds::class)->handle());

        $order = QurbanOrder::sole();
        $this->assertSame(QurbanOrder::STATUS_CANCELLED, $order->status);
        $this->assertNotNull($order->expired_at);
        $this->assertStringContainsString('Dilepas otomatis', $order->notes);
        // Both lines are back on the board.
        $this->assertSame(0, QurbanAnimalAllocation::count());
        $this->getJson('/api/v1/qurban-slots?organization_id=' . $mosque->id)
            ->assertJsonPath('animals.0.filled_count', 0);
        // And gone from "Qurban saya".
        $this->getJson('/api/v1/me/qurban')->assertJsonCount(0, 'data');
    }

    #[Test]
    public function the_first_deposit_ends_the_hold(): void
    {
        $mosque = $this->mosque();
        $cow = $this->cow($mosque);
        $warga = $this->member($mosque, 'Citra');

        Sanctum::actingAs($warga);
        $orderId = $this->postJson('/api/v1/qurban-slots', ['animal_id' => $cow->id, 'names' => ['Citra']])
            ->json('order_id');
        $this->getJson('/api/v1/me/qurban')->assertJsonPath('data.0.pay_before', fn ($v) => $v !== null);

        Sanctum::actingAs($this->officer($mosque));
        $this->postJson("/api/v1/qurban-orders/{$orderId}/payments", [
            'amount' => 100000,
            'payment_method' => 'cash',
        ], ['X-Organization-Id' => (string) $mosque->id])->assertCreated();

        $this->travel(30)->days();
        $this->assertSame(0, app(ReleaseUnpaidHolds::class)->handle());
        $this->assertNull(QurbanOrder::find($orderId)->hold_expires_at);
        $this->assertSame(1, QurbanAnimalAllocation::count());
    }

    #[Test]
    public function a_counter_line_is_never_released_automatically(): void
    {
        $mosque = $this->mosque();
        $cow = $this->cow($mosque);

        Sanctum::actingAs($this->officer($mosque));
        $this->postJson('/api/v1/qurban-slots', ['animal_id' => $cow->id, 'names' => ['Pak Haji']])
            ->assertCreated()
            ->assertJsonPath('pay_before', null);

        $this->travel(60)->days();
        $this->assertSame(0, app(ReleaseUnpaidHolds::class)->handle());
        $this->assertSame(1, QurbanAnimalAllocation::count());
    }

    #[Test]
    public function the_scheduled_command_runs_the_release(): void
    {
        $this->artisan('qurban:release-unpaid-holds')
            ->expectsOutputToContain('Released 0')
            ->assertSuccessful();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function mosque(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'masjid-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid Al-amanah',
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    protected function cow(Organization $mosque): QurbanAnimal
    {
        $program = QurbanProgram::forceCreate([
            'organization_id' => $mosque->id,
            'title' => 'Qurban 1448 H',
            'slug' => 'qurban-' . fake()->unique()->numerify('#####'),
            'year' => (int) now()->year,
            'status' => 'open',
            'is_public' => true,
        ]);

        QurbanProgramPackage::create([
            'qurban_program_id' => $program->id,
            'title' => 'Patungan Sapi 1/7',
            'animal_type' => 'cow',
            'package_type' => 'share',
            'share_count' => 7,
            'base_price' => 3000000,
            'service_fee' => 200000,
            'quota' => 49,
            'remaining_quota' => 49,
            'is_active' => true,
        ]);

        return QurbanAnimal::forceCreate([
            'organization_id' => $mosque->id,
            'qurban_program_id' => $program->id,
            'animal_type' => 'cow',
            'animal_code' => 'Sapi 1',
            'share_slots' => 7,
            'status' => QurbanAnimal::STATUS_READY,
        ]);
    }

    protected function member(Organization $mosque, string $name, ?string $level = null): User
    {
        $user = User::forceCreate([
            'name' => $name,
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => $level ? 'mosque_admin' : 'resident',
            'level_slug' => $level,
            'is_primary' => true,
        ]);

        return $user;
    }

    protected function officer(Organization $mosque): User
    {
        $level = UserLevel::forceCreate(['organization_id' => $mosque->id, 'name' => 'Petugas Qurban', 'slug' => 'mosque-qurban']);

        foreach (['browse-qurban', 'read-qurban', 'add-qurban', 'edit-qurban'] as $permission) {
            UserLevelPermission::forceCreate(['user_level_id' => $level->id, 'permission_name' => $permission]);
        }

        return $this->member($mosque, 'Petugas', 'mosque-qurban');
    }
}
