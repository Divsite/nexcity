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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DistributionListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_the_active_organizations_distributions(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();

        $this->distribution($mine, 'Zakat Kita');
        $this->distribution($theirs, 'Zakat Mereka');

        Sanctum::actingAs($this->officer($mine));

        $response = $this->getJson('/api/v1/distributions', [
            'X-Organization-Id' => (string) $mine->id,
        ]);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Zakat Kita', $response->json('data.0.title'));
    }

    #[Test]
    public function it_counts_progress_so_the_officer_knows_what_is_left(): void
    {
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque);

        $this->recipient($distribution, $this->resident());
        $this->recipient($distribution, $this->resident())
            ->update(['status' => 'distributed']);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/distributions', [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.recipients_count', 2)
            ->assertJsonPath('data.0.distributed_count', 1);
    }

    #[Test]
    public function it_returns_the_recipient_list(): void
    {
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque);
        $this->recipient($distribution, $this->resident());

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson("/api/v1/distributions/{$distribution->id}/recipients", [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.has_card', true);
    }

    #[Test]
    public function a_recipient_without_an_account_is_listed_and_flagged_cardless(): void
    {
        // Someone living in a kontrakan without local papers. They have no
        // account and therefore no QR card, so scanning does not apply to them
        // at all — the app has to offer marking from the list instead.
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque);

        DistributionRecipient::forceCreate([
            'distribution_id' => $distribution->id,
            'resident_id' => null,
            'recipient_name' => 'Pak Umar (kontrakan)',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson("/api/v1/distributions/{$distribution->id}/recipients", [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Pak Umar (kontrakan)')
            ->assertJsonPath('data.0.has_card', false);
    }

    #[Test]
    public function it_works_without_an_organization_header(): void
    {
        // The mobile client does not send X-Organization-Id. Reading the header
        // alone gave `(int) null` = 0, so the query filtered on
        // organization_id = 0 — no error, no rows, and a screen that looked
        // like a mosque with nothing scheduled.
        $mosque = $this->mosque();
        $this->distribution($mosque, 'Zakat Kita');

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/distributions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function a_recipient_inherits_the_entitlement_of_their_golongan(): void
    {
        // 200 of 207 real rows leave the per-recipient amounts null and take
        // the figure from their class. An officer holding a sack of rice needs
        // to know what to hand over, not merely that someone qualifies.
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque);

        $classId = $this->distributionClass($mosque, 'Fakir Miskin Gol 2', 100000, 4);

        $this->recipient($distribution, $this->resident())
            ->update(['distribution_class_id' => $classId]);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson("/api/v1/distributions/{$distribution->id}/recipients", [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.class_name', 'Fakir Miskin Gol 2')
            // Numeric, not a decimal string: sqlite and MySQL disagree on how
            // the cast surfaces, and the live response sends a number.
            ->assertJsonPath('data.0.amount_money', fn ($v) => (float) $v === 100000.0)
            ->assertJsonPath('data.0.amount_rice', fn ($v) => (float) $v === 4.0);
    }

    #[Test]
    public function a_per_recipient_amount_overrides_the_golongan(): void
    {
        // The columns exist as an override for the exceptional case — someone
        // given more or less than their class by decision of the committee.
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque);

        $classId = $this->distributionClass($mosque, 'Fakir Miskin Gol 2', 100000, 4);

        $this->recipient($distribution, $this->resident())->update([
            'distribution_class_id' => $classId,
            'amount_money' => 250000,
        ]);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson("/api/v1/distributions/{$distribution->id}/recipients", [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.amount_money', fn ($v) => (float) $v === 250000.0);
    }

    #[Test]
    public function only_distributed_counts_towards_progress(): void
    {
        // The web's own summary tallies redirected with failed. Two systems
        // reporting different progress for one distribution is worse than
        // either figure being wrong.
        $mosque = $this->mosque();
        $distribution = $this->distribution($mosque);

        $this->recipient($distribution, $this->resident())
            ->update(['status' => 'distributed']);
        $this->recipient($distribution, $this->resident())
            ->update(['status' => 'redirected']);
        $this->recipient($distribution, $this->resident())
            ->update(['status' => 'failed']);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/distributions', [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.recipients_count', 3)
            ->assertJsonPath('data.0.distributed_count', 1);
    }

    #[Test]
    public function another_organizations_recipient_list_is_refused(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();
        $foreign = $this->distribution($theirs);

        Sanctum::actingAs($this->officer($mine));

        $this->getJson("/api/v1/distributions/{$foreign->id}/recipients", [
            'X-Organization-Id' => (string) $mine->id,
        ])->assertForbidden();
    }

    #[Test]
    public function a_level_without_the_browse_capability_is_refused(): void
    {
        $mosque = $this->mosque();
        $humas = $this->officer($mosque, 'mosque-humas', ['browse-mosque-crm']);

        Sanctum::actingAs($humas);

        $this->getJson('/api/v1/distributions', [
            'X-Organization-Id' => (string) $mosque->id,
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
        Organization $organization,
        string $levelSlug = 'mosque-qurban',
        array $permissions = ['browse-mosque-charity-distributions'],
    ): User {
        $user = User::forceCreate([
            'name' => 'Petugas',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'mosque_admin', 'guard_name' => 'web'],
            ['display_name' => 'Mosque Admin'],
        ));

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
        $user = User::forceCreate([
            'name' => 'Warga ' . fake()->unique()->numerify('###'),
            'username' => 'w' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        UserResidentProfile::forceCreate(['user_id' => $user->id]);

        return $user;
    }

    protected function distribution(Organization $organization, string $title = 'Distribusi Uji'): Distribution
    {
        $suffix = fake()->unique()->numerify('####');
        $typeId = DB::table('m_distribution_types')->insertGetId([
            'name' => 'Tipe ' . $suffix,
            'slug' => 'tipe-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Distribution::forceCreate([
            'organization_id' => $organization->id,
            'distribution_type_id' => $typeId,
            'year' => 2026,
            'title' => $title,
            'status' => 'ongoing',
        ]);
    }

    /** A golongan and the entitlement it carries. */
    protected function distributionClass(
        Organization $mosque,
        string $name,
        float $money,
        float $rice,
    ): int {
        $sourceId = DB::table('m_distribution_class_sources')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('distribution_classes')->insertGetId([
            'organization_id' => $mosque->id,
            'distribution_class_source_id' => $sourceId,
            'year' => 2026,
            'get_money' => $money,
            'get_rice' => $rice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function recipient(Distribution $distribution, User $resident): DistributionRecipient
    {
        return DistributionRecipient::forceCreate([
            'distribution_id' => $distribution->id,
            'resident_id' => $resident->id,
            'status' => 'pending',
        ]);
    }
}
