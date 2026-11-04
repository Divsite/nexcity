<?php

namespace Tests\Feature\API;

use App\Models\Charities\CharityDonationReceipt;
use App\Models\Charities\CharityTransaction;
use App\Models\CharityTypes\CharityType;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesScheme;
use App\Models\Organizations\Organization;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanBeneficiary;
use App\Models\Qurbans\QurbanCoupon;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanProgramPackage;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Aktivitas saya": one warga, records from several mosques and their RT, in
 * one list — the warga is the centre, not the organization.
 */
class MyActivityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function one_list_gathers_every_organization(): void
    {
        $alFalah = $this->organization('Masjid Al-Falah', Organization::TYPE_MOSQUE);
        $baitussalam = $this->organization('Masjid Baitussalam', Organization::TYPE_MOSQUE);
        $rt = $this->organization('RT 05', Organization::TYPE_RT);
        $citra = $this->warga('Citra', $rt);

        $this->coupon($alFalah, $citra);
        $this->infaq($baitussalam, $citra, 750000);
        $this->bill($rt, $citra, 20000, RtDuesBill::STATUS_PENDING);
        $this->bill($rt, $citra, 20000, RtDuesBill::STATUS_PAID, month: 10);
        $this->aid($rt, $citra);

        Sanctum::actingAs($citra);
        $this->postJson('/api/v1/qurban-slots', [
            'animal_id' => $this->cow($baitussalam)->id,
            'names' => ['Citra'],
        ])->assertCreated();

        $response = $this->getJson('/api/v1/me/activity')->assertOk();

        $this->assertEqualsCanonicalizing(
            ['amal', 'qurban', 'hak', 'hak', 'iuran', 'iuran'],
            $response->json('data.*.kind'),
        );
        $this->assertEqualsCanonicalizing(
            ['Masjid Al-Falah', 'Masjid Baitussalam', 'RT 05'],
            array_values(array_unique($response->json('data.*.organization.name'))),
        );

        // Meat to collect, iuran unpaid, a patungan still owed — from three
        // places, in one list.
        $this->assertEqualsCanonicalizing(
            ['hak', 'iuran', 'qurban'],
            $response->json('needs_action.*.kind'),
        );

        $response
            ->assertJsonPath('summary.charity_money', 750000)
            ->assertJsonPath('summary.charity_organizations', 1)
            ->assertJsonPath('summary.dues_paid', 1)
            ->assertJsonPath('summary.dues_total', 2);
    }

    #[Test]
    public function every_row_says_where_its_detail_lives(): void
    {
        $mosque = $this->organization('Masjid Al-Falah', Organization::TYPE_MOSQUE);
        $citra = $this->warga('Citra', $this->organization('RT 05', Organization::TYPE_RT));
        $coupon = $this->coupon($mosque, $citra);

        Sanctum::actingAs($citra);

        $this->getJson('/api/v1/me/activity')
            ->assertOk()
            ->assertJsonPath('data.0.id', "coupon:{$coupon->id}")
            ->assertJsonPath('data.0.status', 'issued')
            ->assertJsonPath('data.0.target.screen', 'entitlements')
            ->assertJsonPath('data.0.organization.type', Organization::TYPE_MOSQUE);
    }

    #[Test]
    public function a_neighbour_sees_none_of_it(): void
    {
        $rt = $this->organization('RT 05', Organization::TYPE_RT);
        $mosque = $this->organization('Masjid Al-Falah', Organization::TYPE_MOSQUE);
        $citra = $this->warga('Citra', $rt);
        $this->coupon($mosque, $citra);
        $this->infaq($mosque, $citra, 50000);
        $this->bill($rt, $citra, 20000, RtDuesBill::STATUS_PENDING);

        // Same RT, same mosque: iuran and zakat are still nobody else's.
        Sanctum::actingAs($this->warga('Tetangga', $rt));

        $this->getJson('/api/v1/me/activity')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonCount(0, 'needs_action');
    }

    #[Test]
    public function claimed_and_paid_need_nothing_more(): void
    {
        $rt = $this->organization('RT 05', Organization::TYPE_RT);
        $mosque = $this->organization('Masjid Al-Falah', Organization::TYPE_MOSQUE);
        $citra = $this->warga('Citra', $rt);
        $this->coupon($mosque, $citra, QurbanCoupon::STATUS_CLAIMED);
        $this->bill($rt, $citra, 20000, RtDuesBill::STATUS_PAID);

        Sanctum::actingAs($citra);

        $this->getJson('/api/v1/me/activity')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonCount(0, 'needs_action');
    }

    #[Test]
    public function anonymous_callers_get_nothing(): void
    {
        $this->getJson('/api/v1/me/activity')->assertUnauthorized();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function organization(string $name, string $type): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'name' => $name,
            'type' => $type,
        ]);
    }

    protected function warga(string $name, Organization $rt): User
    {
        $user = User::forceCreate([
            'name' => $name,
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        UserResidentProfile::forceCreate(['user_id' => $user->id, 'organization_id' => $rt->id]);

        return $user;
    }

    protected function coupon(Organization $mosque, User $resident, string $status = QurbanCoupon::STATUS_ISSUED): QurbanCoupon
    {
        $batch = QurbanDistributionBatch::forceCreate([
            'organization_id' => $mosque->id,
            'title' => 'Daging Qurban',
            'year' => (int) now()->year,
            'status' => 'active',
        ]);

        $beneficiary = QurbanBeneficiary::forceCreate([
            'organization_id' => $mosque->id,
            'resident_id' => $resident->id,
            'name_snapshot' => $resident->name,
        ]);

        $code = 'Q26AAR' . fake()->unique()->numerify('########');

        return QurbanCoupon::forceCreate([
            'qurban_distribution_batch_id' => $batch->id,
            'qurban_beneficiary_id' => $beneficiary->id,
            'coupon_code' => $code,
            'qr_code' => $code,
            'status' => $status,
        ]);
    }

    protected function infaq(Organization $mosque, User $payer, float $amount): CharityTransaction
    {
        $sourceId = DB::table('m_charity_type_sources')->where('slug', 'infaq')->value('id')
            ?? DB::table('m_charity_type_sources')->insertGetId([
                'name' => 'Infaq',
                'slug' => 'infaq',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $type = CharityType::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_source_id' => $sourceId,
            'year' => now()->year,
            'is_rice' => false,
            'is_active' => true,
        ]);

        $transaction = CharityTransaction::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_id' => $type->id,
            'year' => now()->year,
            'payer_name' => $payer->name,
            'payer_user_id' => $payer->id,
            'payment_method' => 'cash',
            'status' => CharityTransaction::STATUS_PAID,
        ]);

        CharityDonationReceipt::create([
            'charity_transaction_id' => $transaction->id,
            'amount_money' => $amount,
        ]);

        return $transaction;
    }

    protected function bill(Organization $rt, User $resident, float $amount, string $status, int $month = 11): RtDuesBill
    {
        $scheme = RtDuesScheme::firstOrCreate(
            ['organization_id' => $rt->id, 'year' => (int) now()->year, 'name' => 'Iuran Bulanan'],
            ['type' => RtDuesScheme::TYPE_MONTHLY],
        );

        $period = RtDuesPeriod::forceCreate([
            'rt_dues_scheme_id' => $scheme->id,
            'organization_id' => $rt->id,
            'year' => (int) now()->year,
            'month' => $month,
        ]);

        return RtDuesBill::forceCreate([
            'rt_dues_period_id' => $period->id,
            'resident_id' => $resident->id,
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    protected function aid(Organization $organization, User $resident): DistributionRecipient
    {
        $typeId = DB::table('m_distribution_types')->insertGetId([
            'name' => 'Bansos',
            'slug' => 'bansos-' . fake()->unique()->numerify('###'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $distribution = Distribution::forceCreate([
            'organization_id' => $organization->id,
            'distribution_type_id' => $typeId,
            'year' => (int) now()->year,
            'title' => 'Bansos sembako',
            'status' => 'ongoing',
        ]);

        return DistributionRecipient::forceCreate([
            'distribution_id' => $distribution->id,
            'resident_id' => $resident->id,
            'status' => 'distributed',
            'distributed_at' => now(),
        ]);
    }

    protected function cow(Organization $mosque): QurbanAnimal
    {
        $program = QurbanProgram::forceCreate([
            'organization_id' => $mosque->id,
            'title' => 'Qurban 1448 H',
            'slug' => 'qurban-' . fake()->unique()->numerify('#####'),
            'year' => (int) now()->year,
            'status' => 'open',
            'is_public' => true,
        ]);

        QurbanProgramPackage::create([
            'qurban_program_id' => $program->id,
            'title' => 'Patungan Sapi 1/7',
            'animal_type' => 'cow',
            'package_type' => 'share',
            'share_count' => 7,
            'base_price' => 3000000,
            'service_fee' => 200000,
            'quota' => 49,
            'remaining_quota' => 49,
            'is_active' => true,
        ]);

        return QurbanAnimal::forceCreate([
            'organization_id' => $mosque->id,
            'qurban_program_id' => $program->id,
            'animal_type' => 'cow',
            'animal_code' => 'Sapi 1',
            'share_slots' => 7,
            'status' => QurbanAnimal::STATUS_READY,
        ]);
    }
}
