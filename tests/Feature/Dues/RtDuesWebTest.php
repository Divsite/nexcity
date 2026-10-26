<?php

namespace Tests\Feature\Dues;

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
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RtDuesWebTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function opening_a_monthly_scheme_bills_the_whole_year(): void
    {
        // Mirrors the printed card: twelve months issued at once, not opened
        // one at a time.
        $rt = $this->rt();
        $this->resident($rt);
        $this->resident($rt);

        $this->actingAs($this->officer($rt))
            ->post(route('rt.dues.store'), $this->schemePayload())
            ->assertRedirect();

        $this->assertSame(12, RtDuesPeriod::query()->count());
        $this->assertSame(24, RtDuesBill::query()->count());
    }

    #[Test]
    public function the_two_golongan_from_the_printed_card_are_billed_apart(): void
    {
        $rt = $this->rt();
        $withCard = $this->resident($rt, RtDuesRate::TIER_WITH_CARD);
        $withoutCard = $this->resident($rt, RtDuesRate::TIER_WITHOUT_CARD);

        $this->actingAs($this->officer($rt))
            ->post(route('rt.dues.store'), $this->schemePayload());

        $this->assertSame(
            '20000.00',
            RtDuesBill::query()->where('resident_id', $withCard->id)->value('amount')
        );
        $this->assertSame(
            '15000.00',
            RtDuesBill::query()->where('resident_id', $withoutCard->id)->value('amount')
        );
    }

    #[Test]
    public function a_scheme_with_no_rates_is_rejected(): void
    {
        // Better a validation error than a scheme that quietly bills nobody.
        $rt = $this->rt();

        $this->actingAs($this->officer($rt))
            ->post(route('rt.dues.store'), [...$this->schemePayload(), 'rates' => []])
            ->assertSessionHasErrors('rates');
    }

    #[Test]
    public function a_treasurer_can_reclassify_residents_in_bulk(): void
    {
        // The answer to "where do I set Ber KK?": a screen of its own, because
        // golongan is household data that outlives any one month.
        $rt = $this->rt();
        $a = $this->resident($rt);
        $b = $this->resident($rt);

        $this->actingAs($this->officer($rt))
            ->patch(route('rt.dues.tiers.update'), [
                'tier' => RtDuesRate::TIER_WITH_CARD,
                'resident_ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertSame(
            2,
            UserResidentProfile::query()
                ->where('dues_tier', RtDuesRate::TIER_WITH_CARD)
                ->count()
        );
    }

    #[Test]
    public function another_rts_resident_cannot_be_reclassified(): void
    {
        $mine = $this->rt();
        $theirs = $this->rt();
        $foreign = $this->resident($theirs);

        $this->actingAs($this->officer($mine))
            ->patch(route('rt.dues.tiers.update'), [
                'tier' => RtDuesRate::TIER_WITH_CARD,
                'resident_ids' => [$foreign->id],
            ]);

        $this->assertNull(
            UserResidentProfile::query()->where('user_id', $foreign->id)->value('dues_tier')
        );
    }

    #[Test]
    public function the_same_year_cannot_be_opened_twice(): void
    {
        // Opening 2026 a second time would bill every household twice, and
        // nobody would notice until a resident complained.
        $rt = $this->rt();
        $this->resident($rt);

        $officer = $this->officer($rt);
        $payload = $this->schemePayload();

        $this->actingAs($officer)->post(route('rt.dues.store'), $payload);

        $this->actingAs($officer)
            ->post(route('rt.dues.store'), $payload)
            ->assertSessionHasErrors('year');

        $this->assertSame(1, RtDuesScheme::query()->count());
        $this->assertSame(12, RtDuesBill::query()->count());
    }

    #[Test]
    public function a_year_that_has_not_started_is_refused(): void
    {
        // Otherwise next year's twelve bills appear on every resident's phone
        // today, reading as arrears.
        $rt = $this->rt();

        $this->actingAs($this->officer($rt))
            ->post(route('rt.dues.store'), [
                ...$this->schemePayload(),
                'year' => now()->year + 1,
            ])
            ->assertSessionHasErrors('year');

        $this->assertSame(0, RtDuesScheme::query()->count());
    }

    #[Test]
    public function two_rates_for_the_same_golongan_are_refused(): void
    {
        // Which of the two applies? Not a state anyone could resolve later.
        $rt = $this->rt();

        $payload = $this->schemePayload();
        $payload['rates'][1]['tier'] = $payload['rates'][0]['tier'];

        $this->actingAs($this->officer($rt))
            ->post(route('rt.dues.store'), $payload)
            ->assertSessionHasErrors('rates');
    }

    #[Test]
    public function correcting_rates_goes_through_update_not_a_second_create(): void
    {
        // A treasurer must still be able to fix a wrong figure — just not by
        // creating the year again.
        $rt = $this->rt();
        $this->resident($rt, RtDuesRate::TIER_WITH_CARD);

        $officer = $this->officer($rt);
        $this->actingAs($officer)->post(route('rt.dues.store'), $this->schemePayload());

        $scheme = RtDuesScheme::query()->firstOrFail();

        $corrected = $this->schemePayload();
        $corrected['rates'][0]['amount'] = 22000;

        $this->actingAs($officer)
            ->patch(route('rt.dues.update', $scheme), $corrected)
            ->assertRedirect();

        $this->assertSame(
            '22000.00',
            $scheme->rates()->where('tier', RtDuesRate::TIER_WITH_CARD)->value('amount')
        );

        // Bills already issued keep the figure the household was told.
        $this->assertSame(
            ['20000.00'],
            RtDuesBill::query()->pluck('amount')->unique()->values()->all()
        );
    }

    #[Test]
    public function another_rts_scheme_cannot_be_edited(): void
    {
        $mine = $this->rt();
        $theirs = $this->rt();

        $foreign = RtDuesScheme::forceCreate([
            'organization_id' => $theirs->id,
            'name' => 'Iuran Bulanan',
            'type' => RtDuesScheme::TYPE_MONTHLY,
            'year' => 2026,
        ]);

        $this->actingAs($this->officer($mine))
            ->patch(route('rt.dues.update', $foreign), $this->schemePayload())
            ->assertForbidden();
    }

    #[Test]
    public function marking_a_bill_paid_stamps_who_and_when(): void
    {
        $rt = $this->rt();
        $resident = $this->resident($rt);
        $officer = $this->officer($rt);

        $bill = $this->bill($this->period($rt), $resident);

        $this->actingAs($officer)
            ->patch(route('rt.dues.bills.update', $bill), [
                'status' => 'paid',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $bill->refresh();

        $this->assertSame('paid', $bill->status);
        $this->assertNotNull($bill->paid_at);
        $this->assertSame($officer->id, $bill->recorded_by);
    }

    #[Test]
    public function undoing_a_payment_clears_the_payment_details(): void
    {
        // Otherwise a corrected mistake leaves a payment date and a method on a
        // bill nobody paid, and the next person reading it believes them.
        $rt = $this->rt();
        $resident = $this->resident($rt);
        $officer = $this->officer($rt);

        $bill = $this->bill($this->period($rt), $resident);

        $this->actingAs($officer)->patch(route('rt.dues.bills.update', $bill), [
            'status' => 'paid',
            'payment_method' => 'transfer',
        ]);

        $this->actingAs($officer)->patch(route('rt.dues.bills.update', $bill), [
            'status' => 'pending',
        ]);

        $bill->refresh();

        $this->assertSame('pending', $bill->status);
        $this->assertNull($bill->paid_at);
        $this->assertNull($bill->payment_method);
    }

    #[Test]
    public function the_bill_list_renders_with_its_counts(): void
    {
        $rt = $this->rt();
        $period = $this->period($rt);

        $this->bill($period, $this->resident($rt));
        $this->bill($period, $this->resident($rt))->update(['status' => 'paid']);
        $this->bill($period, $this->resident($rt))->update(['status' => 'waived']);

        $this->actingAs($this->officer($rt))
            ->get(route('rt.dues.period', $period))
            ->assertOk()
            ->assertSee('Agustus 2026')
            // Paid and waived are counted apart: one is money in the box, the
            // other is money the RT decided not to collect.
            ->assertSee(__('messages.dues_waived'));
    }

    #[Test]
    public function another_rts_period_cannot_be_opened_by_url(): void
    {
        $mine = $this->rt();
        $theirs = $this->rt();

        $foreign = $this->period($theirs);

        $this->actingAs($this->officer($mine))
            ->get(route('rt.dues.period', $foreign))
            ->assertForbidden();
    }

    #[Test]
    public function another_rts_bill_cannot_be_marked_paid(): void
    {
        $mine = $this->rt();
        $theirs = $this->rt();

        $foreign = $this->bill($this->period($theirs), $this->resident($theirs));

        $this->actingAs($this->officer($mine))
            ->patch(route('rt.dues.bills.update', $foreign), ['status' => 'paid'])
            ->assertForbidden();

        $this->assertSame('pending', $foreign->fresh()->status);
    }

    #[Test]
    public function a_level_without_the_dues_capability_is_refused(): void
    {
        $rt = $this->rt();
        $humas = $this->officer($rt, 'rt-humas', ['browse-rt-residents']);

        $this->actingAs($humas)->get(route('rt.dues'))->assertForbidden();
    }

    #[Test]
    public function a_level_that_may_read_but_not_record_cannot_record(): void
    {
        // browse without edit is a real combination — a secretary reading the
        // month should not be able to mark money received.
        $rt = $this->rt();
        $resident = $this->resident($rt);
        $secretary = $this->officer($rt, 'rt-secretary', ['browse-rt-dues']);

        $bill = $this->bill($this->period($rt), $resident);

        $this->actingAs($secretary)->get(route('rt.dues'))->assertOk();

        $this->actingAs($secretary)
            ->patch(route('rt.dues.bills.update', $bill), ['status' => 'paid'])
            ->assertForbidden();
    }

    #[Test]
    public function an_invalid_status_is_rejected(): void
    {
        $rt = $this->rt();
        $resident = $this->resident($rt);
        $bill = $this->bill($this->period($rt), $resident);

        $this->actingAs($this->officer($rt))
            ->patch(route('rt.dues.bills.update', $bill), ['status' => 'lunas'])
            ->assertSessionHasErrors('status');
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

    /**
     * @param  list<string>  $permissions
     */
    protected function officer(
        Organization $rt,
        string $levelSlug = 'rt-finance',
        array $permissions = ['browse-rt-dues', 'add-rt-dues', 'edit-rt-dues'],
    ): User {
        $user = User::forceCreate([
            'name' => 'Bendahara',
            'username' => 'o' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'rt_admin', 'guard_name' => 'web'],
            ['display_name' => 'RT Admin'],
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
            'organization_id' => $rt->id,
            'user_id' => $user->id,
            'role' => 'rt_admin',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function resident(Organization $rt, ?string $tier = null): User
    {
        $user = User::forceCreate([
            'name' => 'Warga ' . fake()->unique()->numerify('###'),
            'username' => 'w' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        UserResidentProfile::forceCreate([
            'user_id' => $user->id,
            'organization_id' => $rt->id,
            'dues_tier' => $tier,
        ]);

        return $user;
    }

    protected function period(Organization $rt): RtDuesPeriod
    {
        $scheme = RtDuesScheme::forceCreate([
            'organization_id' => $rt->id,
            'name' => 'Iuran Bulanan',
            'type' => RtDuesScheme::TYPE_MONTHLY,
            'year' => 2026,
        ]);

        RtDuesRate::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'label' => 'Semua warga',
            'amount' => 25000,
            'is_default' => true,
        ]);

        return RtDuesPeriod::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'organization_id' => $rt->id,
            'year' => 2026,
            'month' => 8,
        ]);
    }

    /** The printed card's shape, as the form submits it. */
    protected function schemePayload(): array
    {
        return [
            'name' => 'Iuran Bulanan',
            'type' => RtDuesScheme::TYPE_MONTHLY,
            'year' => 2026,
            'programs' => "Santunan Sosial\nKain Kafan Gratis",
            'rates' => [
                ['label' => 'Ber KK', 'tier' => RtDuesRate::TIER_WITH_CARD, 'amount' => 20000],
                ['label' => 'Tidak Ber KK', 'tier' => RtDuesRate::TIER_WITHOUT_CARD, 'amount' => 15000],
            ],
            'default_rate' => 1,
        ];
    }

    protected function bill(RtDuesPeriod $period, User $resident): RtDuesBill
    {
        return RtDuesBill::forceCreate([
            'rt_dues_period_id' => $period->id,
            'resident_id' => $resident->id,
            'amount' => 25000,
            'status' => RtDuesBill::STATUS_PENDING,
        ]);
    }
}
