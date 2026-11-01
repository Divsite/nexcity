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
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RtDuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_treasurer_sees_who_owes_gathered_per_warga(): void
    {
        $rt = $this->rt();
        $this->resident($rt, 'Budi', '0812 1111 2222');
        $citra = $this->resident($rt, 'Citra');

        $this->issue($this->period($rt, 2026, 9, 25000));
        $this->issue($this->period($rt, 2026, 10, 25000));
        $this->payAll($citra);

        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $response = $this->getJson('/api/v1/rt/dues/arrears', $this->in($rt))->assertOk();

        // Budi owes two months: one row, not two.
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Budi', $response->json('data.0.name'));
        $this->assertSame(2, $response->json('data.0.bills_count'));
        $this->assertEquals(50000, $response->json('data.0.amount'));
        // Normalized, ready for a wa.me link.
        $this->assertSame('6281211112222', $response->json('data.0.phone'));
        $this->assertSame(['September 2026', 'Oktober 2026'], $response->json('data.0.bills.*.period'));
    }

    #[Test]
    public function the_year_overview_counts_billed_and_collected(): void
    {
        $rt = $this->rt();
        $this->resident($rt, 'Budi');
        $citra = $this->resident($rt, 'Citra');
        $this->issue($this->period($rt, 2026, 10, 25000));
        $this->payAll($citra);

        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $this->getJson('/api/v1/rt/dues?year=2026', $this->in($rt))
            ->assertOk()
            ->assertJsonPath('periods.0.label', 'Oktober 2026')
            ->assertJsonPath('periods.0.bills_count', 2)
            ->assertJsonPath('periods.0.paid_count', 1)
            ->assertJsonPath('periods.0.collected_amount', 25000)
            ->assertJsonPath('summary.residents_in_arrears', 1)
            ->assertJsonPath('summary.outstanding_amount', 25000);
    }

    #[Test]
    public function another_rts_dues_never_appear(): void
    {
        $rt = $this->rt();
        $other = $this->rt();
        $this->resident($other, 'Tetangga RT');
        $this->issue($this->period($other, 2026, 10, 25000));

        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $this->getJson('/api/v1/rt/dues/arrears', $this->in($rt))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $foreignPeriod = RtDuesPeriod::where('organization_id', $other->id)->first();
        $this->getJson("/api/v1/rt/dues/periods/{$foreignPeriod->id}/bills", $this->in($rt))
            ->assertNotFound();
    }

    #[Test]
    public function marking_paid_from_the_phone_matches_the_web(): void
    {
        $rt = $this->rt();
        $this->resident($rt, 'Budi');
        $this->issue($this->period($rt, 2026, 10, 25000));
        $bill = RtDuesBill::first();

        $officer = $this->actingAsOfficer($rt, ['browse-rt-dues', 'edit-rt-dues']);

        $this->patchJson("/api/v1/rt/dues/bills/{$bill->id}", [
            'status' => 'paid',
            'payment_method' => 'transfer',
        ], $this->in($rt))
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_method', 'transfer');

        $bill->refresh();
        $this->assertNotNull($bill->paid_at);
        $this->assertSame($officer->id, $bill->recorded_by);

        // Undoing clears the payment, as the web does.
        $this->patchJson("/api/v1/rt/dues/bills/{$bill->id}", ['status' => 'pending'], $this->in($rt))
            ->assertOk();

        $bill->refresh();
        $this->assertNull($bill->paid_at);
        $this->assertNull($bill->payment_method);
    }

    #[Test]
    public function a_level_that_may_read_dues_cannot_mark_them(): void
    {
        $rt = $this->rt();
        $this->resident($rt, 'Budi');
        $this->issue($this->period($rt, 2026, 10, 25000));
        $bill = RtDuesBill::first();

        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $this->patchJson("/api/v1/rt/dues/bills/{$bill->id}", ['status' => 'paid'], $this->in($rt))
            ->assertForbidden();

        $this->assertSame('pending', $bill->fresh()->status);
    }

    #[Test]
    public function another_rts_bill_cannot_be_marked(): void
    {
        $rt = $this->rt();
        $other = $this->rt();
        $this->resident($other, 'Tetangga');
        $this->issue($this->period($other, 2026, 10, 25000));
        $foreign = RtDuesBill::first();

        $this->actingAsOfficer($rt, ['browse-rt-dues', 'edit-rt-dues']);

        $this->patchJson("/api/v1/rt/dues/bills/{$foreign->id}", ['status' => 'paid'], $this->in($rt))
            ->assertNotFound();

        $this->assertSame('pending', $foreign->fresh()->status);
    }

    #[Test]
    public function a_level_without_dues_is_refused_entirely(): void
    {
        $rt = $this->rt();
        $this->actingAsOfficer($rt, ['browse-rt-residents']);

        $this->getJson('/api/v1/rt/dues/arrears', $this->in($rt))->assertForbidden();
    }

    #[Test]
    public function one_periods_bills_can_be_filtered_to_the_unpaid(): void
    {
        $rt = $this->rt();
        $this->resident($rt, 'Budi');
        $citra = $this->resident($rt, 'Citra');
        $period = $this->period($rt, 2026, 10, 25000);
        $this->issue($period);
        $this->payAll($citra);

        $this->actingAsOfficer($rt, ['browse-rt-dues']);

        $this->getJson("/api/v1/rt/dues/periods/{$period->id}/bills?status=pending", $this->in($rt))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Budi'])
            ->assertJsonPath('period.label', 'Oktober 2026');
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
