<?php

namespace Tests\Feature\API;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Profiles\ResidentDataCorrection;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResidentDataTest extends TestCase
{
    use RefreshDatabase;

    // ── RT resident list ────────────────────────────────────────────────────

    #[Test]
    public function an_rt_officer_sees_their_own_warga_and_what_each_lacks(): void
    {
        $rt = $this->rt();
        $this->resident($rt, ['name' => 'Ahmad Lengkap']);
        $this->resident($rt, ['name' => 'Budi Tanpa KK', 'family_card_number' => null]);
        $this->resident($this->rt(), ['name' => 'Warga RT Lain']);

        $this->actingAsOfficer($rt, ['browse-rt-residents']);

        $response = $this->getJson('/api/v1/rt/residents', $this->in($rt))->assertOk();

        $this->assertSame(['Ahmad Lengkap', 'Budi Tanpa KK'], $response->json('data.*.name'));
        $this->assertSame([], $response->json('data.0.missing'));
        $this->assertSame(['family_card_number'], $response->json('data.1.missing'));
        $this->assertSame(2, $response->json('summary.residents'));
        $this->assertSame(1, $response->json('summary.incomplete'));
    }

    #[Test]
    public function the_list_never_carries_nik_full_kk_or_phone(): void
    {
        $rt = $this->rt();
        $this->resident($rt, ['national_id_number' => '3201000000000001']);
        $this->actingAsOfficer($rt, ['browse-rt-residents']);

        $body = $this->getJson('/api/v1/rt/residents', $this->in($rt))->assertOk()->getContent();

        $this->assertStringNotContainsString('3201000000000001', $body);
        $this->assertStringNotContainsString('3201999988887777', $body);
        $this->assertStringNotContainsString('6281234', $body);
        $this->assertStringContainsString('7777', $body);
    }

    #[Test]
    public function an_unmatchable_phone_counts_as_missing(): void
    {
        // The WhatsApp login cannot find a landline, so it is as good as none.
        $rt = $this->rt();
        $this->resident($rt, ['phone' => '021 765 4321']);
        $this->actingAsOfficer($rt, ['browse-rt-residents']);

        $this->getJson('/api/v1/rt/residents?filter=incomplete', $this->in($rt))
            ->assertOk()
            ->assertJsonPath('data.0.missing', ['phone']);
    }

    #[Test]
    public function the_incomplete_filter_leaves_out_complete_records(): void
    {
        $rt = $this->rt();
        $this->resident($rt, ['name' => 'Lengkap']);
        $this->resident($rt, ['name' => 'Tanpa alamat', 'address_line' => '']);
        $this->actingAsOfficer($rt, ['browse-rt-residents']);

        $this->getJson('/api/v1/rt/residents?filter=incomplete', $this->in($rt))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Tanpa alamat']);
    }

    #[Test]
    public function a_level_without_browse_rt_residents_is_refused(): void
    {
        $rt = $this->rt();
        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $this->getJson('/api/v1/rt/residents', $this->in($rt))->assertForbidden();
    }

    // ── Data saya ───────────────────────────────────────────────────────────

    #[Test]
    public function a_warga_reads_their_own_record(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt, ['family_card_number' => null]);
        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/profile')
            ->assertOk()
            ->assertJsonPath('data.name', $me->name)
            ->assertJsonPath('data.family_card', null)
            ->assertJsonPath('data.missing', ['family_card_number'])
            ->assertJsonPath('fields.family_card_number', 'Nomor KK');
    }

    #[Test]
    public function an_account_with_no_resident_record_gets_a_clear_404(): void
    {
        Sanctum::actingAs($this->user());

        $this->getJson('/api/v1/me/profile')->assertNotFound();
    }

    #[Test]
    public function a_correction_is_a_request_and_does_not_touch_the_record(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt);
        Sanctum::actingAs($me);

        $this->postJson('/api/v1/me/profile/corrections', [
            'field' => 'address_line',
            'proposed_value' => 'Jl. Kenanga No. 9',
        ])->assertCreated()->assertJsonPath('data.status', 'pending');

        // The mosque builds zakat lists from this; it changes only when the RT
        // changes it.
        $this->assertSame('Jl. Melati No. 3', $me->residentProfile()->first()->address_line);

        $this->assertDatabaseHas('resident_data_corrections', [
            'user_id' => $me->id,
            'organization_id' => $rt->id,
            'field' => 'address_line',
        ]);
    }

    #[Test]
    public function the_same_field_cannot_be_queued_twice(): void
    {
        $me = $this->resident($this->rt());
        Sanctum::actingAs($me);
        $payload = ['field' => 'phone', 'proposed_value' => '081299990000'];

        $this->postJson('/api/v1/me/profile/corrections', $payload)->assertCreated();
        $this->postJson('/api/v1/me/profile/corrections', $payload)->assertStatus(409);
    }

    #[Test]
    public function nik_is_not_a_correctable_field(): void
    {
        Sanctum::actingAs($this->resident($this->rt()));

        $this->postJson('/api/v1/me/profile/corrections', [
            'field' => 'national_id_number',
            'proposed_value' => '3201000000000002',
        ])->assertStatus(422)->assertJsonValidationErrors('field');
    }

    #[Test]
    public function other_needs_an_explanation(): void
    {
        Sanctum::actingAs($this->resident($this->rt()));

        $this->postJson('/api/v1/me/profile/corrections', ['field' => 'other'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('note');
    }

    // ── RT correction queue ─────────────────────────────────────────────────

    #[Test]
    public function the_rt_sees_and_resolves_its_own_queue(): void
    {
        $rt = $this->rt();
        $correction = $this->correctionFrom($this->resident($rt, ['name' => 'Citra']));
        $this->correctionFrom($this->resident($this->rt()));

        $officer = $this->actingAsOfficer($rt, ['browse-rt-residents', 'edit-rt-residents']);

        $this->getJson('/api/v1/rt/corrections', $this->in($rt))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.resident_name', 'Citra');

        $this->postJson("/api/v1/rt/corrections/{$correction->id}/resolve", ['status' => 'applied'], $this->in($rt))
            ->assertOk();

        $this->assertSame('applied', $correction->fresh()->status);
        $this->assertSame($officer->id, $correction->fresh()->reviewed_by);

        // Resolving twice is refused, not silently re-stamped.
        $this->postJson("/api/v1/rt/corrections/{$correction->id}/resolve", ['status' => 'rejected'], $this->in($rt))
            ->assertStatus(409);
    }

    #[Test]
    public function another_rts_request_reads_as_not_found(): void
    {
        $rt = $this->rt();
        $foreign = $this->correctionFrom($this->resident($this->rt()));
        $this->actingAsOfficer($rt, ['browse-rt-residents', 'edit-rt-residents']);

        $this->postJson("/api/v1/rt/corrections/{$foreign->id}/resolve", ['status' => 'applied'], $this->in($rt))
            ->assertNotFound();

        $this->assertSame('pending', $foreign->fresh()->status);
    }

    #[Test]
    public function reading_the_queue_does_not_allow_resolving_it(): void
    {
        $rt = $this->rt();
        $correction = $this->correctionFrom($this->resident($rt));
        $this->actingAsOfficer($rt, ['browse-rt-residents']);

        $this->postJson("/api/v1/rt/corrections/{$correction->id}/resolve", ['status' => 'applied'], $this->in($rt))
            ->assertForbidden();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    protected function in(Organization $organization): array
    {
        return ['X-Organization-Id' => (string) $organization->id];
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

    protected function user(array $attributes = []): User
    {
        return User::forceCreate($attributes + [
            'name' => 'Warga ' . fake()->unique()->numerify('###'),
            'username' => 'w' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
    }

    /**
     * A complete resident unless $overrides blank something out.
     */
    protected function resident(Organization $rt, array $overrides = []): User
    {
        $user = $this->user([
            'name' => $overrides['name'] ?? 'Warga ' . fake()->unique()->numerify('###'),
            'phone' => array_key_exists('phone', $overrides) ? $overrides['phone'] : '0812 3456 ' . fake()->unique()->numerify('####'),
        ]);

        UserResidentProfile::forceCreate([
            'user_id' => $user->id,
            'organization_id' => $rt->id,
            'family_card_number' => array_key_exists('family_card_number', $overrides)
                ? $overrides['family_card_number'] : '3201999988887777',
            'address_line' => array_key_exists('address_line', $overrides)
                ? $overrides['address_line'] : 'Jl. Melati No. 3',
            'national_id_number' => $overrides['national_id_number'] ?? null,
        ]);

        OrganizationUser::forceCreate([
            'organization_id' => $rt->id,
            'user_id' => $user->id,
            'role' => 'resident',
            'is_primary' => true,
        ]);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function actingAsOfficer(Organization $rt, array $permissions): User
    {
        $slug = 'rt-uji-' . fake()->unique()->numerify('####');
        $level = UserLevel::forceCreate([
            'organization_id' => $rt->id,
            'name' => 'Pengurus Uji',
            'slug' => $slug,
        ]);

        foreach ($permissions as $permission) {
            UserLevelPermission::forceCreate(['user_level_id' => $level->id, 'permission_name' => $permission]);
        }

        $officer = $this->user();
        OrganizationUser::forceCreate([
            'organization_id' => $rt->id,
            'user_id' => $officer->id,
            'role' => 'rt_admin',
            'level_slug' => $slug,
            'is_primary' => true,
        ]);

        Sanctum::actingAs($officer);

        return $officer;
    }

    protected function correctionFrom(User $resident): ResidentDataCorrection
    {
        return ResidentDataCorrection::create([
            'user_id' => $resident->id,
            'organization_id' => $resident->residentProfile()->first()->organization_id,
            'field' => 'address_line',
            'proposed_value' => 'Jl. Baru',
        ]);
    }
}
