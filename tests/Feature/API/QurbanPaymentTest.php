<?php

namespace Tests\Feature\API;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanOrderItem;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanProgramPackage;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Patungan is paid off over months, not on the day a line is taken.
 *
 * A takmir records each deposit in a notebook and reconciles from memory. That
 * is where money goes missing and where somebody gets asked twice for a payment
 * they already made.
 */
class QurbanPaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_pengurus_can_record_a_deposit(): void
    {
        $mosque = $this->mosque();
        $order = $this->order($mosque, subtotal: 3200000);

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson("/api/v1/qurban-orders/{$order->id}/payments", [
            'amount' => 1200000,
            'payment_method' => 'cash',
        ])->assertCreated()->assertJsonPath('order_status', 'partial_paid');

        $this->getJson("/api/v1/qurban-orders/{$order->id}/payments")
            ->assertOk()
            ->assertJsonPath('target', 3200000)
            ->assertJsonPath('paid', 1200000)
            ->assertJsonPath('remaining', 2000000)
            ->assertJsonPath('is_settled', false);
    }

    #[Test]
    public function the_line_turns_paid_when_the_money_does(): void
    {
        // Written in the same transaction as the deposit. A payment recorded
        // without the status moving leaves a settled share still showing as
        // owing, and the mosque chasing somebody who has already paid.
        $mosque = $this->mosque();
        $order = $this->order($mosque, subtotal: 3200000);

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson("/api/v1/qurban-orders/{$order->id}/payments", [
            'amount' => 2000000,
            'payment_method' => 'transfer',
        ])->assertCreated();

        $this->postJson("/api/v1/qurban-orders/{$order->id}/payments", [
            'amount' => 1200000,
            'payment_method' => 'cash',
        ])->assertCreated()->assertJsonPath('order_status', 'paid');

        $this->getJson("/api/v1/qurban-orders/{$order->id}/payments")
            ->assertOk()
            ->assertJsonPath('remaining', 0)
            ->assertJsonPath('is_settled', true)
            ->assertJsonCount(2, 'payments');
    }

    #[Test]
    public function a_deposit_larger_than_what_is_owed_is_refused(): void
    {
        // Usually it means the wrong line was picked. Trimming it silently
        // would hide the mistake.
        $mosque = $this->mosque();
        $order = $this->order($mosque, subtotal: 3200000);

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson("/api/v1/qurban-orders/{$order->id}/payments", [
            'amount' => 3000000,
            'payment_method' => 'cash',
        ])->assertCreated();

        $this->postJson("/api/v1/qurban-orders/{$order->id}/payments", [
            'amount' => 500000,
            'payment_method' => 'cash',
        ])->assertStatus(422)->assertJsonValidationErrors('amount');

        // Nothing half-written.
        $this->assertDatabaseCount('qurban_order_payments', 1);
    }

    #[Test]
    public function a_warga_may_read_their_own_instalments(): void
    {
        // Half the point of putting them in a system is that the payer can
        // check without asking anyone.
        $mosque = $this->mosque();
        $warga = $this->warga($mosque);
        $order = $this->order($mosque, subtotal: 3200000, owner: $warga);

        Sanctum::actingAs($warga);

        $this->getJson("/api/v1/qurban-orders/{$order->id}/payments")
            ->assertOk()
            ->assertJsonPath('target', 3200000);
    }

    #[Test]
    public function a_warga_may_not_read_somebody_elses(): void
    {
        // 404, not 403: confirming the order exists leaks who is in the
        // patungan and for how much.
        $mosque = $this->mosque();
        $order = $this->order($mosque, subtotal: 3200000, owner: $this->warga($mosque));

        Sanctum::actingAs($this->warga($mosque));

        $this->getJson("/api/v1/qurban-orders/{$order->id}/payments")
            ->assertNotFound();
    }

    #[Test]
    public function a_warga_may_not_record_their_own_payment(): void
    {
        // The mosque confirms receipt. Letting the payer mark their own
        // instalment received would make the ledger a claim, not a record.
        $mosque = $this->mosque();
        $warga = $this->warga($mosque);
        $order = $this->order($mosque, subtotal: 3200000, owner: $warga);

        Sanctum::actingAs($warga);

        $this->postJson("/api/v1/qurban-orders/{$order->id}/payments", [
            'amount' => 100000,
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    #[Test]
    public function reading_does_not_grant_recording(): void
    {
        $mosque = $this->mosque();
        $order = $this->order($mosque, subtotal: 3200000);

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban']));

        $this->getJson("/api/v1/qurban-orders/{$order->id}/payments")->assertOk();

        $this->postJson("/api/v1/qurban-orders/{$order->id}/payments", [
            'amount' => 100000,
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    #[Test]
    public function another_mosques_order_is_not_readable(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();
        $order = $this->order($theirs, subtotal: 3200000);

        Sanctum::actingAs($this->officer($mine, ['browse-qurban', 'add-qurban']));

        $this->getJson("/api/v1/qurban-orders/{$order->id}/payments")
            ->assertNotFound();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function mosque(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'masjid-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid Uji',
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    protected function user(Organization $mosque, string $role, ?string $level): User
    {
        $user = User::forceCreate([
            'name' => 'Warga ' . fake()->unique()->numerify('###'),
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => $role, 'guard_name' => 'web'],
            ['display_name' => $role],
        ));

        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => $role,
            'level_slug' => $level,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function warga(Organization $mosque): User
    {
        return $this->user($mosque, 'resident', null);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(Organization $mosque, array $permissions): User
    {
        $level = UserLevel::firstOrCreate(
            ['organization_id' => null, 'slug' => 'mosque-qurban'],
            ['name' => 'Pengurus Qurban', 'is_global' => true],
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

        return $this->user($mosque, 'mosque_admin', 'mosque-qurban');
    }

    protected function order(
        Organization $mosque,
        float $subtotal,
        ?User $owner = null,
    ): QurbanOrder {
        $program = QurbanProgram::firstOrCreate(
            ['organization_id' => $mosque->id, 'year' => (int) now()->year],
            [
                'title' => 'Qurban Uji',
                'slug' => 'qurban-' . fake()->unique()->numerify('#####'),
                'status' => 'open',
                'is_public' => true,
            ],
        );

        $package = QurbanProgramPackage::firstOrCreate(
            ['qurban_program_id' => $program->id, 'title' => 'Patungan Sapi 1/7'],
            [
                'animal_type' => 'cow',
                'package_type' => 'share',
                'share_count' => 7,
                'base_price' => 3000000,
                'service_fee' => 200000,
                'quota' => 49,
                'remaining_quota' => 49,
                'is_active' => true,
            ],
        );

        $order = QurbanOrder::forceCreate([
            'organization_id' => $mosque->id,
            'qurban_program_id' => $program->id,
            'user_id' => $owner?->id,
            'customer_name' => $owner?->name ?? 'Pak Slamet',
            'source_type' => $owner ? 'app' : 'counter',
            'order_code' => 'QRB-' . strtoupper(Str::random(8)),
            'status' => 'pending_payment',
        ]);

        QurbanOrderItem::forceCreate([
            'qurban_order_id' => $order->id,
            'qurban_program_package_id' => $package->id,
            'qty' => 1,
            'share_qty' => 1,
            'price' => $subtotal,
            'subtotal' => $subtotal,
            'status' => 'active',
        ]);

        return $order->fresh();
    }
}
