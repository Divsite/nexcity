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
 * Patungan is the part of qurban only a mosque can do — holding seven
 * strangers' money and guaranteeing the result — so what this endpoint serves
 * is a list of **people**, not of stock.
 */
class QurbanParticipantTest extends TestCase
{
    use RefreshDatabase;

    // ── Reading ─────────────────────────────────────────────────────────────

    #[Test]
    public function it_lists_who_holds_a_share(): void
    {
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque));

        $this->participant($mosque, $package, 'Pak Slamet', shares: 2);
        $this->participant($mosque, $package, 'Bu Aminah', shares: 1);

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->getJson('/api/v1/qurban-participants')->assertOk();

        $response->assertJsonPath('has_participants', true)
            ->assertJsonPath('shares_taken', 3)
            ->assertJsonCount(2, 'participants');
    }

    #[Test]
    public function an_unpaid_pledge_is_not_a_share_of_a_cow(): void
    {
        // Counting it would have a pengurus order an animal against money
        // nobody has handed over.
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque));

        $this->participant($mosque, $package, 'Sudah bayar', shares: 3);
        $this->participant($mosque, $package, 'Belum bayar', shares: 2, paid: false);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/qurban-participants')
            ->assertOk()
            ->assertJsonPath('shares_taken', 5)
            ->assertJsonPath('shares_paid', 3);
    }

    #[Test]
    public function a_share_with_no_animal_yet_says_so_rather_than_guessing(): void
    {
        // Filling is the normal state for most of the season; a blank is the
        // honest answer, not an animal picked at random.
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque));
        $this->participant($mosque, $package, 'Pak Slamet');

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/qurban-participants')
            ->assertOk()
            ->assertJsonPath('participants.0.animal_code', null);
    }

    #[Test]
    public function another_mosques_participants_are_never_listed(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();

        $this->participant($theirs, $this->package($this->program($theirs)), 'Orang lain');

        Sanctum::actingAs($this->officer($mine));

        $this->getJson('/api/v1/qurban-participants')
            ->assertOk()
            ->assertJsonPath('has_participants', false);
    }

    // ── Recording at the counter ────────────────────────────────────────────

    #[Test]
    public function a_pengurus_can_record_a_participant(): void
    {
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque), quota: 7, remaining: 7);

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson('/api/v1/qurban-participants', [
            'package_id' => $package->id,
            'customer_name' => 'Pak Slamet',
            'customer_phone' => '08123456789',
            'shares' => 2,
        ])->assertCreated();

        $this->assertDatabaseHas('qurban_orders', [
            'organization_id' => $mosque->id,
            'customer_name' => 'Pak Slamet',
            'status' => 'paid',
        ]);

        // The quota moves in the same breath: a share counted as sold with no
        // order behind it, or an order against a quota never reduced, are both
        // worse than the write failing.
        $this->assertSame(5, $package->fresh()->remaining_quota);
    }

    #[Test]
    public function a_participant_with_no_account_is_normal(): void
    {
        // Most patungan buyers at a village mosque have no account and never
        // will. The free-text name is the record.
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque));

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson('/api/v1/qurban-participants', [
            'package_id' => $package->id,
            'customer_name' => 'Hamba Allah',
            'shares' => 1,
        ])->assertCreated();

        $this->getJson('/api/v1/qurban-participants')
            ->assertOk()
            ->assertJsonPath('participants.0.name', 'Hamba Allah');
    }

    #[Test]
    public function it_refuses_more_shares_than_are_left(): void
    {
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque), quota: 7, remaining: 2);

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson('/api/v1/qurban-participants', [
            'package_id' => $package->id,
            'customer_name' => 'Pak Slamet',
            'shares' => 4,
        ])->assertStatus(422)->assertJsonValidationErrors('shares');

        // Nothing half-written.
        $this->assertSame(2, $package->fresh()->remaining_quota);
        $this->assertDatabaseCount('qurban_orders', 0);
    }

    #[Test]
    public function a_package_belonging_to_another_mosque_is_refused(): void
    {
        // The package id arrives from the client; without this check one mosque
        // could book a participant against another's programme.
        $mine = $this->mosque();
        $theirs = $this->mosque();
        $package = $this->package($this->program($theirs));

        Sanctum::actingAs($this->officer($mine, ['browse-qurban', 'add-qurban']));

        $this->postJson('/api/v1/qurban-participants', [
            'package_id' => $package->id,
            'customer_name' => 'Pak Slamet',
            'shares' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('package_id');
    }

    #[Test]
    public function an_unpaid_participant_is_recorded_as_unpaid(): void
    {
        // People do reserve a share and pay on Friday. Recording it as paid
        // would have the mosque order an animal it cannot fund.
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque));

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson('/api/v1/qurban-participants', [
            'package_id' => $package->id,
            'customer_name' => 'Bayar Jumat',
            'shares' => 1,
            'is_paid' => false,
        ])->assertCreated();

        $this->assertDatabaseHas('qurban_orders', [
            'customer_name' => 'Bayar Jumat',
            'status' => 'pending_payment',
        ]);
    }

    #[Test]
    public function reading_does_not_grant_recording(): void
    {
        $mosque = $this->mosque();
        $package = $this->package($this->program($mosque));

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban']));

        $this->postJson('/api/v1/qurban-participants', [
            'package_id' => $package->id,
            'customer_name' => 'Pak Slamet',
            'shares' => 1,
        ])->assertForbidden();
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

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(
        Organization $mosque,
        array $permissions = ['browse-qurban'],
    ): User {
        $user = User::forceCreate([
            'name' => 'Pengurus Qurban',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'mosque_admin', 'guard_name' => 'web'],
            ['display_name' => 'Mosque Admin'],
        ));

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

        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => 'mosque-qurban',
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function program(Organization $mosque): QurbanProgram
    {
        return QurbanProgram::forceCreate([
            'organization_id' => $mosque->id,
            'title' => 'Qurban Uji',
            'slug' => 'qurban-' . fake()->unique()->numerify('#####'),
            'year' => (int) now()->year,
            'status' => 'open',
            'is_public' => true,
        ]);
    }

    protected function package(
        QurbanProgram $program,
        int $quota = 49,
        int $remaining = 49,
    ): QurbanProgramPackage {
        return QurbanProgramPackage::forceCreate([
            'qurban_program_id' => $program->id,
            'animal_type' => 'cow',
            'package_type' => 'share',
            'share_count' => 7,
            'title' => 'Patungan Sapi 1/7',
            'base_price' => 3000000,
            'service_fee' => 200000,
            'quota' => $quota,
            'remaining_quota' => $remaining,
            'is_active' => true,
        ]);
    }

    protected function participant(
        Organization $mosque,
        QurbanProgramPackage $package,
        string $name,
        int $shares = 1,
        bool $paid = true,
    ): QurbanOrderItem {
        $order = QurbanOrder::forceCreate([
            'organization_id' => $mosque->id,
            'qurban_program_id' => $package->qurban_program_id,
            'customer_name' => $name,
            'source_type' => 'counter',
            'order_code' => 'QRB-' . strtoupper(Str::random(8)),
            'status' => $paid ? 'paid' : 'pending_payment',
        ]);

        return QurbanOrderItem::forceCreate([
            'qurban_order_id' => $order->id,
            'qurban_program_package_id' => $package->id,
            'qty' => 1,
            'share_qty' => $shares,
            'price' => $package->price,
            'subtotal' => (float) $package->price * $shares,
            'status' => 'active',
        ]);
    }
}
