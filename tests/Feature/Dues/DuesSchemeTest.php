<?php

namespace Tests\Feature\Dues;

use App\Actions\Dues\OpenDuesScheme;
use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesRate;
use App\Models\Dues\RtDuesScheme;
use App\Models\Organizations\Organization;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Modelled on a real printed card: RT 04/03 Pondok Jati Utara, 2026.
 * Ber KK Rp 20.000, Tidak Ber KK Rp 15.000, twelve months printed at once.
 */
class DuesSchemeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function opening_a_monthly_scheme_creates_the_whole_year(): void
    {
        // The card is printed for the year, not a month at a time.
        $rt = $this->rt();
        $this->resident($rt);

        $scheme = $this->monthlyScheme($rt);

        $result = app(OpenDuesScheme::class)->handle($scheme);

        $this->assertSame(12, $result['periods']);
        $this->assertSame(12, $result['bills']);
        $this->assertSame(12, RtDuesPeriod::query()->count());
    }

    #[Test]
    public function each_household_is_billed_at_its_own_golongan(): void
    {
        $rt = $this->rt();
        $withCard = $this->resident($rt, RtDuesRate::TIER_WITH_CARD);
        $withoutCard = $this->resident($rt, RtDuesRate::TIER_WITHOUT_CARD);

        app(OpenDuesScheme::class)->handle($this->monthlyScheme($rt));

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
    public function a_household_with_no_golongan_falls_back_to_the_default_rate(): void
    {
        // This is the common case, not the exception: only a handful of the 207
        // real profiles carry a family card number, so most residents have no
        // golongan. Skipping them would silently under-bill almost the whole RT.
        $rt = $this->rt();
        $unclassified = $this->resident($rt);

        app(OpenDuesScheme::class)->handle($this->monthlyScheme($rt));

        $bill = RtDuesBill::query()->where('resident_id', $unclassified->id)->first();

        $this->assertNotNull($bill);
        $this->assertSame('15000.00', $bill->amount);
        $this->assertSame(RtDuesRate::TIER_WITHOUT_CARD, $bill->tier);
    }

    #[Test]
    public function the_golongan_is_recorded_on_the_bill(): void
    {
        // "Why was I charged 20.000?" has to stay answerable after the rate
        // table changes.
        $rt = $this->rt();
        $resident = $this->resident($rt, RtDuesRate::TIER_WITH_CARD);

        app(OpenDuesScheme::class)->handle($this->monthlyScheme($rt));

        $bill = RtDuesBill::query()->where('resident_id', $resident->id)->first();

        $this->assertSame(RtDuesRate::TIER_WITH_CARD, $bill->tier);
        $this->assertSame('Ber KK', $bill->tierLabel());
    }

    #[Test]
    public function a_seasonal_scheme_creates_one_period_with_one_flat_rate(): void
    {
        // HUT RI: everyone pays the same, no golongan involved.
        $rt = $this->rt();
        $this->resident($rt, RtDuesRate::TIER_WITH_CARD);
        $this->resident($rt, RtDuesRate::TIER_WITHOUT_CARD);

        $scheme = RtDuesScheme::forceCreate([
            'organization_id' => $rt->id,
            'name' => 'Iuran HUT RI',
            'type' => RtDuesScheme::TYPE_SEASONAL,
            'year' => 2026,
        ]);

        RtDuesRate::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'tier' => null,
            'label' => 'Semua warga',
            'amount' => 50000,
            'is_default' => true,
        ]);

        $result = app(OpenDuesScheme::class)->handle($scheme);

        $this->assertSame(1, $result['periods']);
        $this->assertSame(2, $result['bills']);
        $this->assertSame(
            ['50000.00'],
            RtDuesBill::query()->pluck('amount')->unique()->values()->all()
        );
    }

    #[Test]
    public function a_seasonal_scheme_can_target_only_some_residents(): void
    {
        // Not every collection covers the whole block.
        $rt = $this->rt();
        $included = $this->resident($rt);
        $this->resident($rt);

        $scheme = RtDuesScheme::forceCreate([
            'organization_id' => $rt->id,
            'name' => 'Renovasi Pos Ronda',
            'type' => RtDuesScheme::TYPE_SEASONAL,
            'year' => 2026,
        ]);

        RtDuesRate::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'label' => 'Semua warga',
            'amount' => 100000,
            'is_default' => true,
        ]);

        $result = app(OpenDuesScheme::class)->handle($scheme, residentIds: [$included->id]);

        $this->assertSame(1, $result['bills']);
        $this->assertSame($included->id, RtDuesBill::query()->value('resident_id'));
    }

    #[Test]
    public function reopening_a_year_adds_nothing_and_duplicates_nothing(): void
    {
        // The treasurer will press it twice.
        $rt = $this->rt();
        $this->resident($rt);

        $scheme = $this->monthlyScheme($rt);

        app(OpenDuesScheme::class)->handle($scheme);
        $second = app(OpenDuesScheme::class)->handle($scheme);

        $this->assertSame(12, $second['periods']);
        $this->assertSame(0, $second['bills']);
        $this->assertSame(12, RtDuesBill::query()->count());
    }

    #[Test]
    public function someone_who_moves_in_later_is_billed_on_the_next_run(): void
    {
        $rt = $this->rt();
        $this->resident($rt);

        $scheme = $this->monthlyScheme($rt);
        app(OpenDuesScheme::class)->handle($scheme);

        $this->resident($rt);

        $this->assertSame(12, app(OpenDuesScheme::class)->handle($scheme)['bills']);
    }

    #[Test]
    public function correcting_a_rate_does_not_rewrite_bills_already_issued(): void
    {
        $rt = $this->rt();
        $resident = $this->resident($rt, RtDuesRate::TIER_WITH_CARD);

        $scheme = $this->monthlyScheme($rt);
        app(OpenDuesScheme::class)->handle($scheme);

        $scheme->rates()->where('tier', RtDuesRate::TIER_WITH_CARD)->update(['amount' => 35000]);

        $this->assertSame(
            '20000.00',
            RtDuesBill::query()->where('resident_id', $resident->id)->value('amount')
        );
    }

    #[Test]
    public function another_rts_residents_are_never_billed(): void
    {
        $mine = $this->rt();
        $theirs = $this->rt();

        $this->resident($mine);
        $this->resident($theirs);

        $result = app(OpenDuesScheme::class)->handle($this->monthlyScheme($mine));

        $this->assertSame(12, $result['bills']);
    }

    #[Test]
    public function a_scheme_with_no_rates_bills_nobody(): void
    {
        // Better to issue nothing than to issue a bill for an amount nobody set.
        $rt = $this->rt();
        $this->resident($rt);

        $scheme = RtDuesScheme::forceCreate([
            'organization_id' => $rt->id,
            'name' => 'Belum diisi tarifnya',
            'type' => RtDuesScheme::TYPE_MONTHLY,
            'year' => 2026,
        ]);

        $this->assertSame(0, app(OpenDuesScheme::class)->handle($scheme)['bills']);
    }

    #[Test]
    public function the_programmes_a_scheme_funds_are_listed_one_per_line(): void
    {
        // Straight off the card: "NB. Program RT: Santunan Sosial, Kain Kafan
        // Gratis, Pengadaan Hansip…"
        $scheme = new RtDuesScheme([
            'programs' => "Santunan Sosial\nKain Kafan Gratis\n\n  Pengadaan Hansip  \n",
        ]);

        $this->assertSame(
            ['Santunan Sosial', 'Kain Kafan Gratis', 'Pengadaan Hansip'],
            $scheme->programList()
        );
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

    /** The printed card's shape: two golongan, non-KK as the default. */
    protected function monthlyScheme(Organization $rt): RtDuesScheme
    {
        $scheme = RtDuesScheme::forceCreate([
            'organization_id' => $rt->id,
            'name' => 'Iuran Bulanan',
            'type' => RtDuesScheme::TYPE_MONTHLY,
            'year' => 2026,
            'programs' => "Santunan Sosial\nKain Kafan Gratis",
        ]);

        RtDuesRate::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'tier' => RtDuesRate::TIER_WITH_CARD,
            'label' => 'Ber KK',
            'amount' => 20000,
            'is_default' => false,
        ]);

        RtDuesRate::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'tier' => RtDuesRate::TIER_WITHOUT_CARD,
            'label' => 'Tidak Ber KK',
            'amount' => 15000,
            'is_default' => true,
        ]);

        return $scheme->load('rates');
    }
}
