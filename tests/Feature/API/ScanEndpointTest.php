<?php

namespace Tests\Feature\API;

use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The rule under test: a QR identifies, it does not authorize.
 *
 * The same card is entitled at the mosque that listed the resident and not at
 * the one that did not. There is no "valid token" — only entitled or not, here,
 * now, for this programme.
 */
class ScanEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_listed_resident_is_entitled_at_the_mosque_that_listed_them(): void
    {
        $mosque = $this->mosque('masjid-a');
        $officer = $this->officer($mosque);
        $resident = $this->resident();
        $this->listResident($mosque, $resident);

        Sanctum::actingAs($officer);

        $this->postJson('/api/v1/scan', ['qr_token' => $this->tokenOf($resident)], [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'entitled')
            ->assertJsonPath('resident.name', $resident->name);
    }

    #[Test]
    public function the_same_card_is_not_entitled_at_a_mosque_that_did_not_list_them(): void
    {
        // Masjid A listed them; Masjid B did not. One card, two answers.
        $mosqueA = $this->mosque('masjid-a');
        $mosqueB = $this->mosque('masjid-b');

        $resident = $this->resident();
        $this->listResident($mosqueA, $resident);

        $officerB = $this->officer($mosqueB);
        Sanctum::actingAs($officerB);

        $this->postJson('/api/v1/scan', ['qr_token' => $this->tokenOf($resident)], [
            'X-Organization-Id' => (string) $mosqueB->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'not_entitled');
    }

    #[Test]
    public function one_mosque_never_learns_what_another_already_gave(): void
    {
        // The real cross-organization leak, and not a rare one: a resident can
        // be listed for qurban at two mosques at once.
        $mosqueA = $this->mosque('masjid-a');
        $mosqueB = $this->mosque('masjid-b');

        $resident = $this->resident();
        $recipient = $this->listResident($mosqueA, $resident);
        $recipient->update(['status' => 'distributed', 'distributed_at' => now()]);

        Sanctum::actingAs($this->officer($mosqueB));

        $response = $this->postJson('/api/v1/scan', ['qr_token' => $this->tokenOf($resident)], [
            'X-Organization-Id' => (string) $mosqueB->id,
        ]);

        $response->assertOk()->assertJsonPath('status', 'not_entitled');
        // Masjid B must not see Masjid A's entitlement at all.
        $this->assertNull($response->json('entitlement'));
    }

    #[Test]
    public function identity_is_returned_even_when_the_person_is_not_listed(): void
    {
        // The officer needs to know who is in front of them to explain. This is
        // capped at what the printed card already shows, so it discloses
        // nothing that handing the card over did not.
        $mosque = $this->mosque('masjid-a');
        $resident = $this->resident();

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson('/api/v1/scan', ['qr_token' => $this->tokenOf($resident)], [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'not_entitled')
            ->assertJsonPath('resident.name', $resident->name);
    }

    #[Test]
    public function an_unknown_card_reveals_nothing(): void
    {
        $mosque = $this->mosque('masjid-a');
        Sanctum::actingAs($this->officer($mosque));

        $response = $this->postJson('/api/v1/scan', ['qr_token' => (string) Str::uuid()], [
            'X-Organization-Id' => (string) $mosque->id,
        ]);

        $response->assertOk()->assertJsonPath('status', 'unknown_card');
        $this->assertNull($response->json('resident'));
    }

    #[Test]
    public function a_second_claim_reports_when_and_by_whom(): void
    {
        $mosque = $this->mosque('masjid-a');
        $officer = $this->officer($mosque);
        $resident = $this->resident();
        $recipient = $this->listResident($mosque, $resident);

        Sanctum::actingAs($officer);

        $this->patchJson("/api/v1/distribution-recipients/{$recipient->id}", [
            'status' => 'distributed',
        ], ['X-Organization-Id' => (string) $mosque->id])->assertOk();

        // Scanning again must explain rather than silently refuse.
        $this->postJson('/api/v1/scan', ['qr_token' => $this->tokenOf($resident)], [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'already_claimed')
            ->assertJsonPath('entitlement.distributed_by', $officer->name);

        $this->patchJson("/api/v1/distribution-recipients/{$recipient->id}", [
            'status' => 'distributed',
        ], ['X-Organization-Id' => (string) $mosque->id])->assertStatus(409);
    }

    #[Test]
    public function marking_writes_an_audit_log(): void
    {
        // A lifelong printed card proves nothing on its own; the trail of who
        // scanned what, and when, is the real protection.
        $mosque = $this->mosque('masjid-a');
        $officer = $this->officer($mosque);
        $recipient = $this->listResident($mosque, $this->resident());

        Sanctum::actingAs($officer);

        $this->patchJson("/api/v1/distribution-recipients/{$recipient->id}", [
            'status' => 'distributed',
            'note' => 'Diterima langsung',
        ], ['X-Organization-Id' => (string) $mosque->id])->assertOk();

        $this->assertDatabaseHas('distribution_recipient_status_logs', [
            'distribution_recipient_id' => $recipient->id,
            'from_status' => 'pending',
            'to_status' => 'distributed',
            'created_by' => $officer->id,
        ]);
    }

    #[Test]
    public function a_recipient_of_another_organization_cannot_be_marked(): void
    {
        $mine = $this->mosque('masjid-a');
        $theirs = $this->mosque('masjid-b');

        $officer = $this->officer($mine);
        $foreign = $this->listResident($theirs, $this->resident());

        Sanctum::actingAs($officer);

        $this->patchJson("/api/v1/distribution-recipients/{$foreign->id}", [
            'status' => 'distributed',
        ], ['X-Organization-Id' => (string) $mine->id])->assertForbidden();
    }

    #[Test]
    public function a_level_without_a_scan_capability_is_refused(): void
    {
        $mosque = $this->mosque('masjid-a');
        // A bendahara: charity, no scanning.
        $bendahara = $this->officer($mosque, 'mosque-finance', ['browse-mosque-charity-transactions']);

        Sanctum::actingAs($bendahara);

        $this->postJson('/api/v1/scan', ['qr_token' => 'apa-saja'], [
            'X-Organization-Id' => (string) $mosque->id,
        ])->assertForbidden();
    }

    #[Test]
    public function a_photo_is_stored_against_the_recipient(): void
    {
        Storage::fake('uploads');

        $mosque = $this->mosque('masjid-a');
        $officer = $this->officer($mosque);
        $recipient = $this->listResident($mosque, $this->resident());

        Sanctum::actingAs($officer);

        $this->postJson(
            "/api/v1/distribution-recipients/{$recipient->id}/photos",
            ['photo' => UploadedFile::fake()->image('bukti.jpg')],
            ['X-Organization-Id' => (string) $mosque->id],
        )->assertCreated();

        $this->assertDatabaseHas('distribution_recipient_attachments', [
            'distribution_recipient_id' => $recipient->id,
            'created_by' => $officer->id,
        ]);
    }

    #[Test]
    public function photos_accumulate_rather_than_replacing_each_other(): void
    {
        // An officer photographs the goods, the handover, the house. Replacing
        // on each upload would leave only the last one and quietly destroy the
        // evidence the earlier shots were taken for.
        Storage::fake('uploads');

        $mosque = $this->mosque('masjid-a');
        $recipient = $this->listResident($mosque, $this->resident());

        Sanctum::actingAs($this->officer($mosque));

        foreach (['satu.jpg', 'dua.jpg'] as $name) {
            $this->postJson(
                "/api/v1/distribution-recipients/{$recipient->id}/photos",
                ['photo' => UploadedFile::fake()->image($name)],
                ['X-Organization-Id' => (string) $mosque->id],
            )->assertCreated();
        }

        $this->assertSame(2, $recipient->attachments()->count());
    }

    #[Test]
    public function a_non_image_is_refused(): void
    {
        Storage::fake('uploads');

        $mosque = $this->mosque('masjid-a');
        $recipient = $this->listResident($mosque, $this->resident());

        Sanctum::actingAs($this->officer($mosque));

        $this->postJson(
            "/api/v1/distribution-recipients/{$recipient->id}/photos",
            ['photo' => UploadedFile::fake()->create('laporan.pdf', 100)],
            ['X-Organization-Id' => (string) $mosque->id],
        )->assertStatus(422);
    }

    #[Test]
    public function a_photo_cannot_be_attached_to_another_organizations_recipient(): void
    {
        Storage::fake('uploads');

        $mine = $this->mosque('masjid-a');
        $theirs = $this->mosque('masjid-b');
        $foreign = $this->listResident($theirs, $this->resident());

        Sanctum::actingAs($this->officer($mine));

        $this->postJson(
            "/api/v1/distribution-recipients/{$foreign->id}/photos",
            ['photo' => UploadedFile::fake()->image('bukti.jpg')],
            ['X-Organization-Id' => (string) $mine->id],
        )->assertForbidden();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function mosque(string $slug): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => $slug . '-' . fake()->unique()->numerify('####'),
            'name' => strtoupper($slug),
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(
        Organization $organization,
        string $levelSlug = 'mosque-qurban',
        array $permissions = [
            'scan-qurban-coupon',
            'edit-mosque-charity-distributions',
        ],
    ): User {
        $user = $this->user('Petugas');

        $role = Role::firstOrCreate(
            ['name' => 'mosque_admin', 'guard_name' => 'web'],
            ['display_name' => 'Mosque Admin'],
        );
        $user->assignRole($role);

        $level = UserLevel::firstOrCreate(
            ['organization_id' => null, 'slug' => $levelSlug],
            ['name' => $levelSlug, 'is_global' => true],
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
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function resident(): User
    {
        $user = $this->user('Warga');

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'resident', 'guard_name' => 'web'],
            ['display_name' => 'Resident'],
        ));

        UserResidentProfile::forceCreate(['user_id' => $user->id]);

        return $user->fresh();
    }

    protected function tokenOf(User $resident): string
    {
        return UserResidentProfile::where('user_id', $resident->id)->value('qr_token');
    }

    protected function listResident(Organization $organization, User $resident): DistributionRecipient
    {
        $suffix = fake()->unique()->numerify('###');
        $typeId = DB::table('m_distribution_types')->insertGetId([
            'name' => 'Zakat Fitrah ' . $suffix,
            'slug' => 'zakat-fitrah-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $distribution = Distribution::forceCreate([
            'organization_id' => $organization->id,
            'distribution_type_id' => $typeId,
            'year' => 2026,
            'title' => 'Distribusi Uji',
            'status' => 'ongoing',
        ]);

        return DistributionRecipient::forceCreate([
            'distribution_id' => $distribution->id,
            'resident_id' => $resident->id,
            'status' => 'pending',
        ]);
    }

    protected function user(string $name): User
    {
        return User::forceCreate([
            'name' => $name . ' ' . fake()->unique()->numerify('###'),
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
    }
}
