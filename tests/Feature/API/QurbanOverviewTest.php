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
 * The mosque's own qurban season.
 *
 * Everything in this module hangs off a programme — packages, orders, animals,
 * coupons, beneficiaries all reference one — so the first thing this endpoint
 * has to get right is telling "no programme yet" apart from "a programme with
 * nothing in it".
 */
class QurbanOverviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function no_programme_is_reported_as_no_programme(): void
    {
        // Not as a row of zeroes. A mosque that has not opened a season yet
        // has nothing to report, and pretending otherwise would have an
        // officer looking for animals nobody bought.
        $mosque = $this->mosque();

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/qurban-overview')
            ->assertOk()
            ->assertJsonPath('has_program', false)
            ->assertJsonCount(0, 'programs');
    }

    #[Test]
    public function it_lists_the_programme_and_what_is_left_of_each_package(): void
    {
        // Remaining quota, not sold count: an officer at a table needs to know
        // whether they can still take an order.
        $mosque = $this->mosque();
        $program = $this->program($mosque);

        $this->package($program, 'Patungan Sapi 1/7', quota: 49, remaining: 12);
        $this->package($program, 'Kambing Reguler', quota: 30, remaining: 30);

        Sanctum::actingAs($this->officer($mosque));

        $response = $this->getJson('/api/v1/qurban-overview')->assertOk();

        $response->assertJsonPath('has_program', true)
            ->assertJsonCount(1, 'programs')
            ->assertJsonCount(2, 'programs.0.packages');

        $this->assertSame(12, $response->json('programs.0.packages.0.remaining_quota'));
    }

    #[Test]
    public function inactive_packages_are_left_out(): void
    {
        $mosque = $this->mosque();
        $program = $this->program($mosque);

        $this->package($program, 'Masih dijual', quota: 10, remaining: 10);
        $this->package($program, 'Sudah ditutup', quota: 10, remaining: 0, active: false);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/qurban-overview')
            ->assertOk()
            ->assertJsonCount(1, 'programs.0.packages');
    }

    #[Test]
    public function livestock_is_grouped_by_where_it_has_got_to(): void
    {
        // The stages come from the animals themselves, so one added later
        // shows up without anyone remembering to list it here.
        $mosque = $this->mosque();
        $this->program($mosque);

        $this->animal($mosque, 'available');
        $this->animal($mosque, 'available');
        $this->animal($mosque, 'slaughtered');

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/qurban-overview')
            ->assertOk()
            ->assertJsonPath('animals.total', 3)
            ->assertJsonPath('animals.by_status.available', 2)
            ->assertJsonPath('animals.by_status.slaughtered', 1);
    }

    #[Test]
    public function another_years_programme_is_not_shown(): void
    {
        $mosque = $this->mosque();
        $this->program($mosque, year: 2024);

        Sanctum::actingAs($this->officer($mosque));

        $this->getJson('/api/v1/qurban-overview')
            ->assertOk()
            ->assertJsonPath('has_program', false);

        $this->getJson('/api/v1/qurban-overview?year=2024')
            ->assertOk()
            ->assertJsonPath('has_program', true);
    }

    #[Test]
    public function another_mosques_programme_is_never_included(): void
    {
        $mine = $this->mosque();
        $theirs = $this->mosque();
        $this->program($theirs);

        Sanctum::actingAs($this->officer($mine));

        $this->getJson('/api/v1/qurban-overview')
            ->assertOk()
            ->assertJsonPath('has_program', false);
    }

    #[Test]
    public function a_level_without_the_qurban_capability_is_refused(): void
    {
        $mosque = $this->mosque();

        Sanctum::actingAs($this->officer($mosque, ['browse-mosque-charity-transactions']));

        $this->getJson('/api/v1/qurban-overview')->assertForbidden();
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

    protected function program(Organization $mosque, ?int $year = null): QurbanProgram
    {
        return QurbanProgram::forceCreate([
            'organization_id' => $mosque->id,
            'title' => 'Qurban Uji',
            'slug' => 'qurban-' . fake()->unique()->numerify('#####'),
            'year' => $year ?? (int) now()->year,
            'period_start_at' => now()->subDay(),
            'period_end_at' => now()->addMonth(),
            'status' => 'open',
            'is_public' => true,
        ]);
    }

    protected function package(
        QurbanProgram $program,
        string $title,
        int $quota,
        int $remaining,
        bool $active = true,
    ): QurbanProgramPackage {
        return QurbanProgramPackage::forceCreate([
            'qurban_program_id' => $program->id,
            'animal_type' => 'cow',
            'package_type' => 'share',
            'share_count' => 7,
            'title' => $title,
            'price' => 3000000,
            'quota' => $quota,
            'remaining_quota' => $remaining,
            'is_active' => $active,
        ]);
    }

    protected function animal(Organization $mosque, string $status): QurbanAnimal
    {
        return QurbanAnimal::forceCreate([
            'organization_id' => $mosque->id,
            'animal_type' => 'cow',
            'animal_code' => 'SAPI-' . fake()->unique()->numerify('####'),
            'gender' => 'male',
            'weight' => 280,
            'health_status' => 'healthy',
            'status' => $status,
        ]);
    }
}
