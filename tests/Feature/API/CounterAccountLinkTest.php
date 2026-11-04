<?php

namespace Tests\Feature\API;

use App\Models\CharityTypes\CharityType;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanProgramPackage;
use App\Models\Users\User;
use App\Support\AccountRef;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Akun warga" on the counter forms: a payment a pengurus records for someone
 * reaches that person's own app — and the lookup that makes it possible gives
 * away as little as it can.
 */
class CounterAccountLinkTest extends TestCase
{
    use RefreshDatabase;

    // ── lookup ──────────────────────────────────────────────────────────────

    #[Test]
    public function a_phone_number_finds_the_account_with_a_shortened_name(): void
    {
        $mosque = $this->mosque();
        $this->warga('Citra Lestari Putri', '0812-3456-7890');

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->postJson('/api/v1/accounts/lookup', ['phone' => '+62 812 3456 7890'], $this->in($mosque))
            ->assertOk()
            ->assertJsonPath('status', 'found')
            ->assertJsonPath('account.name', 'Citra L. P.')
            ->assertJsonPath('account.phone_hint', '7890')
            ->assertJsonPath('account.is_self', false);

        // No id, no full name: typing a number is not a directory.
        $this->assertArrayNotHasKey('id', $response->json('account'));
        $this->assertStringNotContainsString('Lestari', $response->getContent());
    }

    #[Test]
    public function an_unknown_or_shared_number_finds_nobody(): void
    {
        $mosque = $this->mosque();
        $this->warga('Budi', '081299990000');
        $this->warga('Budi Kakak', '081299990000');

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson('/api/v1/accounts/lookup', ['phone' => '081211112222'], $this->in($mosque))
            ->assertOk()->assertJsonPath('status', 'not_found');

        $this->postJson('/api/v1/accounts/lookup', ['phone' => '081299990000'], $this->in($mosque))
            ->assertOk()->assertJsonPath('status', 'ambiguous')->assertJsonMissingPath('account');

        $this->postJson('/api/v1/accounts/lookup', ['phone' => '12345'], $this->in($mosque))
            ->assertUnprocessable();
    }

    #[Test]
    public function a_kartu_warga_finds_its_owner(): void
    {
        $mosque = $this->mosque();
        $warga = $this->warga('Citra', '081234567890');
        $token = UserResidentProfile::where('user_id', $warga->id)->value('qr_token');

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson('/api/v1/accounts/lookup', ['qr_token' => $token], $this->in($mosque))
            ->assertOk()->assertJsonPath('account.name', 'Citra');

        $this->postJson('/api/v1/accounts/lookup', ['qr_token' => (string) Str::uuid()], $this->in($mosque))
            ->assertOk()->assertJsonPath('status', 'not_found');
    }

    #[Test]
    public function a_warga_cannot_look_anyone_up(): void
    {
        $mosque = $this->mosque();
        $this->warga('Citra', '081234567890');

        Sanctum::actingAs($this->warga('Tetangga', '081200001111'));

        $this->postJson('/api/v1/accounts/lookup', ['phone' => '081234567890'], $this->in($mosque))
            ->assertForbidden();
    }

    // ── charity ─────────────────────────────────────────────────────────────

    #[Test]
    public function linked_charity_appears_in_the_payers_history_and_nobody_elses(): void
    {
        $mosque = $this->mosque();
        $type = $this->infaq($mosque);
        $warga = $this->warga('Citra Lestari', '081234567890');

        Sanctum::actingAs($this->officer($mosque));
        $ref = $this->lookup($mosque, ['phone' => '081234567890']);

        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_ref' => $ref,
            'total_money' => 100000,
            'payment_method' => 'cash',
        ], $this->in($mosque))
            ->assertCreated()
            ->assertJsonPath('is_linked', true)
            // The officer saw "Citra L."; the record keeps the real name.
            ->assertJsonPath('payer_name', 'Citra Lestari');

        Sanctum::actingAs($warga);
        $this->getJson('/api/v1/me/charity')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'Infaq')
            ->assertJsonPath('data.0.organization', $mosque->name)
            ->assertJsonPath('data.0.total_money', 100000);

        Sanctum::actingAs($this->warga('Tetangga', '081200001111'));
        $this->getJson('/api/v1/me/charity')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function a_reference_from_another_mosque_or_a_forged_one_is_refused(): void
    {
        $mosque = $this->mosque();
        $other = $this->mosque();
        $type = $this->infaq($mosque);
        $warga = $this->warga('Citra', '081234567890');

        Sanctum::actingAs($this->officer($mosque));

        foreach ([AccountRef::issue($warga, $other->id), 'not-a-reference', (string) $warga->id] as $ref) {
            $this->postJson('/api/v1/charity-transactions', [
                'charity_type_id' => $type->id,
                'payer_ref' => $ref,
                'total_money' => 50000,
                'payment_method' => 'cash',
            ], $this->in($mosque))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('payer_ref');
        }

        $this->assertDatabaseCount('charity_transactions', 0);
    }

    #[Test]
    public function an_expired_reference_is_refused(): void
    {
        $mosque = $this->mosque();
        $type = $this->infaq($mosque);
        $warga = $this->warga('Citra', '081234567890');
        $ref = AccountRef::issue($warga, $mosque->id);

        $this->travel(AccountRef::TTL_MINUTES + 1)->minutes();

        Sanctum::actingAs($this->officer($mosque));
        $this->postJson('/api/v1/charity-transactions', [
            'charity_type_id' => $type->id,
            'payer_ref' => $ref,
            'total_money' => 50000,
            'payment_method' => 'cash',
        ], $this->in($mosque))->assertJsonValidationErrors('payer_ref');
    }

    // ── qurban ──────────────────────────────────────────────────────────────

    #[Test]
    public function a_line_filled_at_the_counter_reaches_the_warga(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque);
        $warga = $this->warga('Citra', '081234567890');

        Sanctum::actingAs($this->officer($mosque));
        $ref = $this->lookup($mosque, ['phone' => '081234567890']);

        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Citra', 'Alm. Bapak Citra'],
            'owner_ref' => $ref,
        ], $this->in($mosque))->assertCreated();

        $this->assertDatabaseHas('qurban_orders', [
            'user_id' => $warga->id,
            'source_type' => 'counter',
            'customer_phone' => '6281234567890',
        ]);

        Sanctum::actingAs($warga);
        $this->getJson('/api/v1/me/qurban')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.shares.1.name', 'Alm. Bapak Citra');
    }

    #[Test]
    public function a_pengurus_taking_a_line_for_themselves_owns_it(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque);
        $officer = $this->officer($mosque);

        Sanctum::actingAs($officer);
        $ref = $this->lookup($mosque, ['self' => true], isSelf: true);

        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Amil'],
            'owner_ref' => $ref,
        ], $this->in($mosque))->assertCreated();

        $this->assertDatabaseHas('qurban_orders', ['user_id' => $officer->id]);
    }

    #[Test]
    public function without_a_lookup_a_counter_line_stays_unowned(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque);

        Sanctum::actingAs($this->officer($mosque));
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Pak Haji'],
        ], $this->in($mosque))->assertCreated();

        $this->assertSame(null, QurbanOrder::query()->value('user_id'));
    }

    #[Test]
    public function a_warga_cannot_hand_their_line_to_someone_else(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque);
        $other = $this->warga('Tetangga', '081200001111');
        $warga = $this->warga('Citra', '081234567890');

        Sanctum::actingAs($warga);
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $animal->id,
            'names' => ['Citra'],
            'owner_ref' => AccountRef::issue($other, $mosque->id),
        ])->assertCreated();

        $this->assertDatabaseHas('qurban_orders', ['user_id' => $warga->id]);
    }

    #[Test]
    public function a_participant_recorded_for_an_account_takes_its_name(): void
    {
        $mosque = $this->mosque();
        $animal = $this->cow($mosque);
        $warga = $this->warga('Citra Lestari', '081234567890');

        Sanctum::actingAs($this->officer($mosque));
        $ref = $this->lookup($mosque, ['phone' => '081234567890']);

        $this->postJson('/api/v1/qurban-participants', [
            'package_id' => QurbanProgramPackage::where('qurban_program_id', $animal->qurban_program_id)->value('id'),
            'owner_ref' => $ref,
            'shares' => 1,
        ], $this->in($mosque))->assertCreated();

        $this->assertDatabaseHas('qurban_orders', [
            'user_id' => $warga->id,
            'customer_name' => 'Citra Lestari',
        ]);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $query
     */
    protected function lookup(Organization $mosque, array $query, bool $isSelf = false): string
    {
        return $this->postJson('/api/v1/accounts/lookup', $query, $this->in($mosque))
            ->assertOk()
            ->assertJsonPath('status', 'found')
            ->assertJsonPath('account.is_self', $isSelf)
            ->json('account.ref');
    }

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

    protected function warga(string $name, string $phone): User
    {
        $user = User::forceCreate([
            'name' => $name,
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone,
            'password' => bcrypt('secret'),
        ]);

        UserResidentProfile::forceCreate(['user_id' => $user->id]);

        return $user;
    }

    protected function officer(Organization $mosque): User
    {
        $user = User::forceCreate([
            'name' => 'Amil',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'mosque_admin', 'guard_name' => 'web'],
            ['display_name' => 'Mosque Admin'],
        ));

        $level = UserLevel::firstOrCreate(
            ['organization_id' => null, 'slug' => 'mosque-officer'],
            ['name' => 'Petugas', 'is_global' => true],
        );

        foreach ([
            'browse-mosque-charity-transactions',
            'add-mosque-charity-transactions',
            'browse-qurban',
            'add-qurban',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'], ['display_name' => $permission]);
            UserLevelPermission::firstOrCreate(['user_level_id' => $level->id, 'permission_name' => $permission]);
        }

        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => 'mosque-officer',
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function infaq(Organization $mosque): CharityType
    {
        $sourceId = DB::table('m_charity_type_sources')->where('slug', 'infaq')->value('id')
            ?? DB::table('m_charity_type_sources')->insertGetId([
                'name' => 'Infaq',
                'slug' => 'infaq',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return CharityType::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_source_id' => $sourceId,
            'year' => now()->year,
            'is_rice' => false,
            'is_active' => true,
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
}
