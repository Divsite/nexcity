<?php

namespace Tests\Feature\API;

use App\Actions\Dues\IssueDuesBills;
use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesRate;
use App\Models\Dues\RtDuesScheme;
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

class RtSummaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_ketua_sees_warga_iuran_and_distribusi(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15));
        $rt = $this->rt();
        $budi = $this->resident($rt, 'Budi', '081211112222');
        $this->resident($rt, 'Citra');
        $this->issue($this->period($rt, 2026, 10, 25000));
        $this->payAll($budi);
        ResidentDataCorrection::create([
            'user_id' => $budi->id, 'organization_id' => $rt->id,
            'field' => 'address_line', 'proposed_value' => 'Jl. Baru',
        ]);

        $this->actingAsOfficer($rt, ['browse-rt-residents', 'browse-rt-dues']);

        $this->getJson('/api/v1/rt/summary', $this->in($rt))
            ->assertOk()
            ->assertJsonPath('residents.total', 2)
            // Citra has no phone, and neither has a KK in this fixture.
            ->assertJsonPath('residents.incomplete', 2)
            ->assertJsonPath('residents.pending_corrections', 1)
            ->assertJsonPath('dues.residents_in_arrears', 1)
            ->assertJsonPath('dues.outstanding_amount', 25000)
            ->assertJsonPath('dues.this_month.label', 'Oktober 2026')
            ->assertJsonPath('dues.this_month.paid_count', 1)
            ->assertJsonPath('dues.this_month.bills_count', 2)
            ->assertJsonPath('distributions.recipients_pending', 0);
    }

    #[Test]
    public function the_bendahara_sees_iuran_and_nothing_about_warga(): void
    {
        $rt = $this->rt();
        $this->resident($rt, 'Budi');
        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $this->getJson('/api/v1/rt/summary', $this->in($rt))
            ->assertOk()
            ->assertJsonPath('residents', null)
            ->assertJsonPath('distributions', null)
            ->assertJsonPath('dues.residents_in_arrears', 0);
    }

    #[Test]
    public function a_month_not_yet_opened_is_null_not_zero(): void
    {
        // "0 dari 0 lunas" would read as nobody paying; the truth is the
        // treasurer has not opened the month yet.
        $this->travelTo(now()->setDate(2026, 11, 2));
        $rt = $this->rt();
        $this->resident($rt, 'Budi');
        $this->issue($this->period($rt, 2026, 10, 25000));
        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $this->getJson('/api/v1/rt/summary', $this->in($rt))
            ->assertOk()
            ->assertJsonPath('dues.this_month', null)
            ->assertJsonPath('dues.residents_in_arrears', 1);
    }

    #[Test]
    public function another_rts_figures_never_leak_in(): void
    {
        $rt = $this->rt();
        $other = $this->rt();
        $this->resident($other, 'Tetangga');
        $this->issue($this->period($other, 2026, 10, 25000));

        $this->actingAsOfficer($rt, ['browse-rt-residents', 'browse-rt-dues']);

        $this->getJson('/api/v1/rt/summary', $this->in($rt))
            ->assertOk()
            ->assertJsonPath('residents.total', 0)
            ->assertJsonPath('dues.outstanding_amount', 0);
    }

    #[Test]
    public function an_officer_without_any_rt_capability_is_refused(): void
    {
        $rt = $this->rt();
        $this->actingAsOfficer($rt, ['browse-rt-events']);

        $this->getJson('/api/v1/rt/summary', $this->in($rt))->assertForbidden();
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

    protected function user(string $name, ?string $phone = null): User
    {
        return User::forceCreate([
            'name' => $name,
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone,
            'password' => bcrypt('secret'),
        ]);
    }

    protected function resident(Organization $rt, string $name, ?string $phone = null): User
    {
        $user = $this->user($name, $phone);

        UserResidentProfile::forceCreate(['user_id' => $user->id, 'organization_id' => $rt->id]);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function actingAsOfficer(Organization $rt, array $permissions): User
    {
        $slug = 'rt-uji-' . fake()->unique()->numerify('####');
        $level = UserLevel::forceCreate(['organization_id' => $rt->id, 'name' => 'Bendahara Uji', 'slug' => $slug]);

        foreach ($permissions as $permission) {
            UserLevelPermission::forceCreate(['user_level_id' => $level->id, 'permission_name' => $permission]);
        }

        $officer = $this->user('Bendahara');
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

    protected function period(Organization $rt, int $year, int $month, float $amount): RtDuesPeriod
    {
        $scheme = RtDuesScheme::firstOrCreate(
            ['organization_id' => $rt->id, 'year' => $year, 'name' => 'Iuran Bulanan'],
            ['type' => RtDuesScheme::TYPE_MONTHLY],
        );

        RtDuesRate::firstOrCreate(
            ['rt_dues_scheme_id' => $scheme->id, 'tier' => null],
            ['label' => 'Semua warga', 'amount' => $amount, 'is_default' => true],
        );

        return RtDuesPeriod::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'organization_id' => $rt->id,
            'year' => $year,
            'month' => $month,
        ]);
    }

    protected function issue(RtDuesPeriod $period): void
    {
        app(IssueDuesBills::class)->handle($period->load('scheme.rates'));
    }

    protected function payAll(User $resident): void
    {
        RtDuesBill::where('resident_id', $resident->id)->update(['status' => RtDuesBill::STATUS_PAID, 'paid_at' => now()]);
    }
}
