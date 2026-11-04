<?php

namespace Tests\Feature\API;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Qurbans\QurbanAnimal;
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
 * "Qurban saya", driven through the real flow: the warga takes lines on the
 * board, a pengurus records a deposit, and the warga's own screen must agree
 * with both.
 */
class MyQurbanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_pekurban_sees_their_lines_what_is_paid_and_the_animal(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque, 'Sapi 1');
        $warga = $this->member($mosque, 'Citra');

        Sanctum::actingAs($warga);
        $orderId = $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Citra', 'Alm. Bapak Citra'],
        ])->assertCreated()->json('order_id');

        Sanctum::actingAs($this->officer($mosque));
        $this->postJson("/api/v1/qurban-orders/{$orderId}/payments", [
            'amount' => 1000000,
            'payment_method' => 'transfer',
        ], $this->in($mosque))->assertCreated();

        $animal->update(['status' => QurbanAnimal::STATUS_ARRIVED, 'ear_tag_code' => 'AA-17']);

        Sanctum::actingAs($warga);
        $this->getJson('/api/v1/me/qurban')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.organization', $mosque->name)
            // Two lines at Rp 3.200.000 (animal + service fee).
            ->assertJsonPath('data.0.target', 6400000)
            ->assertJsonPath('data.0.paid', 1000000)
            ->assertJsonPath('data.0.remaining', 5400000)
            ->assertJsonPath('data.0.is_settled', false)
            ->assertJsonPath('data.0.shares.0.name', 'Citra')
            ->assertJsonPath('data.0.shares.1.name', 'Alm. Bapak Citra')
            ->assertJsonPath('data.0.shares.0.animal.code', 'Sapi 1')
            ->assertJsonPath('data.0.shares.0.animal.status', 'arrived')
            ->assertJsonPath('data.0.shares.0.animal.ear_tag_code', 'AA-17')
            ->assertJsonPath('data.0.payments.0.amount', 1000000);
    }

    #[Test]
    public function another_warga_sees_none_of_it(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque, 'Sapi 1');

        Sanctum::actingAs($this->member($mosque, 'Citra'));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Citra'],
        ])->assertCreated();

        Sanctum::actingAs($this->member($mosque, 'Tetangga'));
        $this->getJson('/api/v1/me/qurban')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function a_cancelled_order_is_not_listed(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque, 'Sapi 1');
        $warga = $this->member($mosque, 'Citra');

        Sanctum::actingAs($warga);
        $orderId = $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Citra'],
        ])->json('order_id');

        QurbanOrder::whereKey($orderId)->update(['status' => QurbanOrder::STATUS_CANCELLED]);

        $this->getJson('/api/v1/me/qurban')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    protected function in(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
    }

    protected function mosque(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'masjid-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid Al-amanah',
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    protected function cow(Organization $mosque, string $code): QurbanAnimal
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
            'animal_code' => $code,
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
