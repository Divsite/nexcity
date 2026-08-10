<?php

namespace Tests\Feature\API;

use App\Models\Distributions\Distribution;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MosqueSummaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reports_the_officers_own_mosque(): void
    {
        $mosque = $this->mosque();
        $officer = $this->officer($mosque);

        Sanctum::actingAs($officer);

        $this->getJson('/api/v1/mosque/summary', [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('organization.id', $mosque->id)
            ->assertJsonPath('level', 'mosque-qurban');
    }

    #[Test]
    public function open_distributions_are_counted_apart_from_finished_ones(): void
    {
        // The actionable number: what an officer might have to go and do
        // something about today, not the lifetime tally.
        $mosque = $this->mosque();

        $this->distribution($mosque, 'pending');
        $this->distribution($mosque, 'pending');
        $this->distribution($mosque, 'completed');

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/mosque/summary', [
            'X-Organization-Id' => (string) $mosque->id,
        ])
            ->assertOk()
            ->assertJsonPath('stats.distributions_open', 2)
            ->assertJsonPath('stats.distributions_total', 3);
    }

    #[Test]
    public function another_mosques_figures_are_never_included(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();

        $this->distribution($mine, 'pending');
        $this->distribution($theirs, 'pending');
        $this->distribution($theirs, 'pending');

        Sanctum::actingAs($this->officer($mine));

        $this->getJson('/api/v1/mosque/summary', [
            'X-Organization-Id' => (string) $mine->id,
        ])
            ->assertOk()
            ->assertJsonPath('stats.distributions_total', 1);
    }

    #[Test]
    public function a_level_with_no_mosque_capability_is_refused(): void
    {
        $mosque = $this->mosque();
        $outsider = $this->officer($mosque, 'mosque-humas', ['browse-mosque-crm']);

        Sanctum::actingAs($outsider);

        $this->getJson('/api/v1/mosque/summary', [
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
        Organization $mosque,
        string $levelSlug = 'mosque-qurban',
        array $permissions = ['browse-qurban'],
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
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function distribution(Organization $mosque, string $status): Distribution
    {
        $suffix = fake()->unique()->numerify('####');
        $typeId = DB::table('m_distribution_types')->insertGetId([
            'name' => 'Tipe ' . $suffix,
            'slug' => 'tipe-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Distribution::forceCreate([
            'organization_id' => $mosque->id,
            'distribution_type_id' => $typeId,
            'year' => 2026,
            'title' => 'Distribusi Uji',
            'status' => $status,
        ]);
    }
}
