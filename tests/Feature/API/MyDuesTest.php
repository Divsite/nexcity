<?php

namespace Tests\Feature\API;

use App\Actions\Dues\IssueDuesBills;
use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesRate;
use App\Models\Dues\RtDuesScheme;
use App\Models\Organizations\Organization;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MyDuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_resident_sees_only_their_own_bills(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt);
        $neighbour = $this->resident($rt);

        $period = $this->period($rt, 2026, 7, 25000);
        $this->issue($period);

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/v1/me/dues')->assertOk();

        // Two residents were billed; this one may see exactly one row.
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(
            $me->id,
            RtDuesBill::query()->find($response->json('data.0.id'))->resident_id
        );
        $this->assertNotSame($neighbour->id, $response->json('data.0.id'));
    }

    #[Test]
    public function arrears_add_up_across_months(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt);

        $this->issue($this->period($rt, 2026, 5, 25000));
        $this->issue($this->period($rt, 2026, 6, 25000));
        $this->issue($this->period($rt, 2026, 7, 30000));

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/dues')
            ->assertOk()
            ->assertJsonPath('summary.outstanding_count', 3)
            ->assertJsonPath('summary.outstanding_amount', 80000);
    }

    #[Test]
    public function a_waived_bill_is_not_counted_as_arrears(): void
    {
        // Hardship is forgiven, not owed. Counting it would show a widow a debt
        // the RT has already decided she does not have.
        $rt = $this->rt();
        $me = $this->resident($rt);

        $this->issue($this->period($rt, 2026, 5, 25000));
        $this->issue($this->period($rt, 2026, 6, 25000));

        RtDuesBill::query()
            ->where('resident_id', $me->id)
            ->first()
            ->update(['status' => RtDuesBill::STATUS_WAIVED]);

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/dues')
            ->assertOk()
            ->assertJsonPath('summary.outstanding_count', 1)
            ->assertJsonPath('summary.outstanding_amount', 25000);
    }

    #[Test]
    public function a_paid_bill_is_not_counted_as_arrears(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt);

        $this->issue($this->period($rt, 2026, 5, 25000));

        RtDuesBill::query()->where('resident_id', $me->id)->update([
            'status' => RtDuesBill::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => 'cash',
        ]);

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/dues')
            ->assertOk()
            ->assertJsonPath('summary.outstanding_count', 0)
            ->assertJsonPath('summary.outstanding_amount', 0)
            ->assertJsonPath('data.0.status', 'paid')
            ->assertJsonPath('data.0.payment_method', 'cash');
    }

    #[Test]
    public function the_current_month_is_surfaced_on_its_own(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt);

        $this->issue($this->period($rt, (int) now()->year, (int) now()->month, 27500));

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/dues')
            ->assertOk()
            ->assertJsonPath('summary.current.amount', 27500)
            ->assertJsonPath('summary.current.status', 'pending');
    }

    #[Test]
    public function the_current_month_is_null_when_the_rt_has_not_set_a_rate(): void
    {
        // Not an error and not a debt: an RT that has not opened this month yet
        // simply has nothing to show.
        $rt = $this->rt();
        $me = $this->resident($rt);

        $this->issue($this->period($rt, 2025, 1, 25000));

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/dues')
            ->assertOk()
            ->assertJsonPath('summary.current', null)
            ->assertJsonPath('summary.outstanding_count', 1);
    }

    #[Test]
    public function months_come_back_newest_first(): void
    {
        $rt = $this->rt();
        $me = $this->resident($rt);

        $this->issue($this->period($rt, 2025, 12, 20000));
        $this->issue($this->period($rt, 2026, 1, 25000));

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/dues')
            ->assertOk()
            ->assertJsonPath('data.0.period', 'Januari 2026')
            ->assertJsonPath('data.1.period', 'Desember 2025');
    }

    #[Test]
    public function a_user_without_the_resident_capability_is_refused(): void
    {
        $user = User::forceCreate([
            'name' => 'Bukan Warga',
            'username' => 'x' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/dues')->assertForbidden();
    }





    // ── helpers ─────────────────────────────────────────────────────────────

    protected function rt(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'rt-' . fake()->unique()->numerify('#####'),
            'name' => 'RT Uji',
            'type' => Organization::TYPE_RT,
        ]);
    }

    protected function resident(Organization $rt): User
    {
        $user = User::forceCreate([
            'name' => 'Warga ' . fake()->unique()->numerify('###'),
            'username' => 'w' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        Permission::firstOrCreate(
            ['name' => 'browse-resident-dues', 'guard_name' => 'web'],
            ['display_name' => 'Browse Resident Dues'],
        );

        $role = Role::firstOrCreate(
            ['name' => 'resident', 'guard_name' => 'web'],
            ['display_name' => 'Resident'],
        );
        $role->givePermissionTo('browse-resident-dues');

        $user->assignRole($role);

        UserResidentProfile::forceCreate([
            'user_id' => $user->id,
            'organization_id' => $rt->id,
        ]);

        return $user->fresh();
    }

    /**
     * One month of a monthly scheme, at a single flat rate.
     *
     * A scheme per amount keeps each test's figures independent; the real world
     * has one scheme per year, which DuesSchemeTest covers.
     */
    protected function period(Organization $rt, int $year, int $month, float $amount): RtDuesPeriod
    {
        $scheme = RtDuesScheme::firstOrCreate(
            [
                'organization_id' => $rt->id,
                'year' => $year,
                'name' => 'Iuran Bulanan ' . $amount,
            ],
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

    protected function issue(RtDuesPeriod $period): int
    {
        return app(IssueDuesBills::class)->handle($period->load('scheme.rates'));
    }
}
