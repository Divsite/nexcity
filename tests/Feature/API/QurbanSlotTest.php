<?php

namespace Tests\Feature\API;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Qurbans\QurbanAnimal;
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
 * The patungan board is a sign-up sheet, not a shopping cart.
 *
 * A takmir already runs it on paper — a heading and seven lines — and recruits
 * people into it one at a time. These tests describe replacing the paper
 * without replacing the recruiting.
 */
class QurbanSlotTest extends TestCase
{
    use RefreshDatabase;

    // ── Reading the board ───────────────────────────────────────────────────

    #[Test]
    public function empty_lines_are_returned_as_well_as_taken_ones(): void
    {
        // A board listing only the taken places answers "who is in" but not "is
        // there room" — and the second is what someone opens this to ask.
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));

        $response = $this->getJson('/api/v1/qurban-slots')->assertOk();

        $response->assertJsonPath('has_board', true)
            ->assertJsonPath('animals.0.code', 'Sapi 1')
            ->assertJsonPath('animals.0.share_slots', 7)
            ->assertJsonPath('animals.0.filled_count', 0)
            ->assertJsonCount(7, 'animals.0.slots')
            ->assertJsonPath('animals.0.slots.0.is_taken', false);

        $this->assertSame(7, $animal->share_slots);
    }

    #[Test]
    public function a_warga_sees_who_they_would_be_sharing_with(): void
    {
        // At the mosque these names are read out loud. Seeing them is half the
        // reason to join one animal rather than another.
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Abbas'],
        ])->assertCreated();

        $this->getJson('/api/v1/qurban-slots')
            ->assertOk()
            ->assertJsonPath('animals.0.slots.0.name', 'Abbas')
            ->assertJsonPath('animals.0.filled_count', 1);
    }

    #[Test]
    public function a_warga_never_sees_another_persons_phone_number(): void
    {
        // Nobody needs to collect six neighbours' phone numbers off a screen.
        // That is pengurus business.
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Abbas'],
            'phone' => '08123456789',
        ])->assertCreated();

        Sanctum::actingAs($this->warga($mosque));

        $this->getJson('/api/v1/qurban-slots')
            ->assertOk()
            ->assertJsonPath('is_staff', false)
            ->assertJsonPath('animals.0.slots.0.name', 'Abbas')
            ->assertJsonPath('animals.0.slots.0.phone', null);
    }

    #[Test]
    public function a_pengurus_sees_the_phone_number(): void
    {
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');
        $officer = $this->officer($mosque, ['browse-qurban', 'add-qurban']);

        Sanctum::actingAs($officer);
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Abbas'],
            'phone' => '08123456789',
        ])->assertCreated();

        $this->getJson('/api/v1/qurban-slots')
            ->assertOk()
            ->assertJsonPath('is_staff', true)
            ->assertJsonPath('animals.0.slots.0.phone', '08123456789');
    }

    #[Test]
    public function a_warga_can_tell_which_line_is_theirs(): void
    {
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');
        $warga = $this->warga($mosque);

        Sanctum::actingAs($warga);
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Abbas'],
        ])->assertCreated();

        $this->getJson('/api/v1/qurban-slots')
            ->assertOk()
            ->assertJsonPath('animals.0.slots.0.is_mine', true);
    }

    #[Test]
    public function an_animal_not_yet_bought_still_appears(): void
    {
        // "Sapi 1" exists as an idea before it exists as an animal. Waiting for
        // an ear tag would hide the sheet at the exact moment people need to
        // sign it.
        $mosque = $this->mosque();
        $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));

        $this->getJson('/api/v1/qurban-slots')
            ->assertOk()
            ->assertJsonPath('animals.0.ear_tag_code', null)
            ->assertJsonPath('animals.0.weight', null);
    }

    #[Test]
    public function the_price_reaches_the_board_split(): void
    {
        $mosque = $this->mosque();
        $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));

        $this->getJson('/api/v1/qurban-slots')
            ->assertOk()
            ->assertJsonPath('animals.0.base_price', 3000000)
            ->assertJsonPath('animals.0.service_fee', 200000)
            ->assertJsonPath('animals.0.price_per_share', 3200000);
    }

    // ── Taking a line ───────────────────────────────────────────────────────

    #[Test]
    public function lines_are_handed_out_in_order(): void
    {
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Abbas'],
        ])->assertCreated();

        Sanctum::actingAs($this->warga($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['RT Rouf'],
        ])->assertCreated();

        $this->assertDatabaseHas('qurban_animal_allocations', [
            'qurban_animal_id' => $animal->id,
            'share_index' => 1,
            'notes' => 'Abbas',
        ]);
        $this->assertDatabaseHas('qurban_animal_allocations', [
            'qurban_animal_id' => $animal->id,
            'share_index' => 2,
            'notes' => 'RT Rouf',
        ]);
    }

    #[Test]
    public function one_payment_may_fill_several_lines_each_named(): void
    {
        // Someone taking two places — for themselves and a late parent — fills
        // two lines, each carrying its own name. Each share of a cow is offered
        // on behalf of exactly one person.
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));

        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Sukirno', 'Almh. Sukarmani'],
        ])->assertCreated()->assertJsonPath('shares', 2);

        $this->assertDatabaseCount('qurban_orders', 1);
        $this->assertDatabaseHas('qurban_animal_allocations', [
            'share_index' => 2,
            'notes' => 'Almh. Sukarmani',
        ]);
    }

    #[Test]
    public function a_full_animal_is_refused(): void
    {
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Kambing 1', type: 'goat', slots: 1);

        Sanctum::actingAs($this->warga($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Yang pertama'],
        ])->assertCreated();

        Sanctum::actingAs($this->warga($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Yang kedua'],
        ])->assertStatus(422)->assertJsonValidationErrors('names');
    }

    #[Test]
    public function asking_for_more_lines_than_remain_takes_none_of_them(): void
    {
        // Half-filling the request would scatter one person's qurban across
        // two animals without them asking.
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['A', 'B', 'C', 'D', 'E', 'F'],
        ])->assertCreated();

        Sanctum::actingAs($this->warga($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['G', 'H'],
        ])->assertStatus(422);

        $this->assertDatabaseCount('qurban_animal_allocations', 6);
    }

    #[Test]
    public function a_new_slot_is_unpaid_until_someone_says_otherwise(): void
    {
        // Patungan is normally paid off in instalments. A slot marked paid on
        // the day it was taken would have the mosque order an animal it cannot
        // fund.
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->warga($mosque));

        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Abbas'],
        ])->assertCreated()->assertJsonPath('status', 'pending_payment');
    }

    #[Test]
    public function a_pengurus_filling_a_line_is_recorded_as_a_counter_sale(): void
    {
        // Someone walked in with cash and no smartphone. The distinction
        // matters later: an app buyer gets notified, a walk-in was standing
        // there.
        $mosque = $this->mosque();
        $animal = $this->animal($mosque, 'Sapi 1');

        Sanctum::actingAs($this->officer($mosque, ['browse-qurban', 'add-qurban']));

        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Mbah Karso'],
            'is_paid' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('qurban_orders', [
            'customer_name' => 'Mbah Karso',
            'source_type' => 'counter',
            'status' => 'paid',
            'user_id' => null,
        ]);
    }

    #[Test]
    public function an_animal_with_no_programme_cannot_be_joined(): void
    {
        $mosque = $this->mosque();
        $animal = QurbanAnimal::forceCreate([
            'organization_id' => $mosque->id,
            'animal_type' => 'cow',
            'animal_code' => 'Lepas',
            'share_slots' => 7,
            'status' => 'available',
        ]);

        Sanctum::actingAs($this->warga($mosque));

        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Abbas'],
        ])->assertStatus(422)->assertJsonValidationErrors('animal_id');
    }

    #[Test]
    public function another_mosques_board_is_not_served_by_default(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();
        $this->animal($theirs, 'Sapi milik orang lain');

        Sanctum::actingAs($this->warga($mine));

        $this->getJson('/api/v1/qurban-slots')
            ->assertOk()
            ->assertJsonPath('has_board', false);
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

    protected function animal(
        Organization $mosque,
        string $code,
        string $type = 'cow',
        int $slots = 7,
    ): QurbanAnimal {
        $program = QurbanProgram::firstOrCreate(
            ['organization_id' => $mosque->id, 'year' => (int) now()->year],
            [
                'title' => 'Qurban Uji',
                'slug' => 'qurban-' . fake()->unique()->numerify('#####'),
                'status' => 'open',
                'is_public' => true,
            ],
        );

        QurbanProgramPackage::firstOrCreate(
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

        return QurbanAnimal::forceCreate([
            'organization_id' => $mosque->id,
            'qurban_program_id' => $program->id,
            'animal_type' => $type,
            'animal_code' => $code,
            'share_slots' => $slots,
            'status' => 'available',
        ]);
    }
}
