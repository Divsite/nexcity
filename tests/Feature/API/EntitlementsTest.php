<?php

namespace Tests\Feature\API;

use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Qurbans\QurbanBeneficiary;
use App\Models\Qurbans\QurbanCoupon;
use App\Models\Qurbans\QurbanCouponClaim;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntitlementsTest extends TestCase
{
    use RefreshDatabase;

    // ── Hak saya ────────────────────────────────────────────────────────────

    #[Test]
    public function a_warga_sees_their_own_coupons_and_aid(): void
    {
        $this->travelTo(now()->setDate(2026, 5, 20));
        $mosque = $this->mosque();
        $me = $this->resident('Citra');
        $neighbour = $this->resident('Tetangga');

        $mine = $this->coupon($mosque, $me, 'Q26AAR00200001');
        $this->coupon($mosque, $neighbour, 'Q26AAR00200002');
        $this->aid($mosque, $me, 'Zakat Fitrah 2026');
        $this->aid($mosque, $neighbour, 'Zakat Fitrah 2026');

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/entitlements')
            ->assertOk()
            ->assertJsonCount(1, 'qurban_coupons')
            ->assertJsonPath('qurban_coupons.0.code', 'Q26AAR00200001')
            ->assertJsonPath('qurban_coupons.0.qr', $mine->qr_code)
            ->assertJsonPath('qurban_coupons.0.status', 'issued')
            ->assertJsonPath('qurban_coupons.0.organization', $mosque->name)
            ->assertJsonCount(1, 'aid')
            ->assertJsonPath('aid.0.title', 'Zakat Fitrah 2026')
            ->assertJsonPath('aid.0.status', 'pending');
    }

    #[Test]
    public function a_cancelled_coupon_or_batch_is_not_shown(): void
    {
        // Showing it would send someone to collect meat that is not there.
        $this->travelTo(now()->setDate(2026, 5, 20));
        $mosque = $this->mosque();
        $me = $this->resident('Citra');

        $this->coupon($mosque, $me, 'Q26AAR00200001', couponStatus: 'cancelled');
        $this->coupon($mosque, $me, 'Q26AAR00200002', batchStatus: 'cancelled');

        Sanctum::actingAs($me);

        $this->getJson('/api/v1/me/entitlements')
            ->assertOk()
            ->assertJsonCount(0, 'qurban_coupons');
    }

    #[Test]
    public function nothing_listed_is_two_empty_lists(): void
    {
        Sanctum::actingAs($this->resident('Citra'));

        $this->getJson('/api/v1/me/entitlements')
            ->assertOk()
            ->assertExactJson(['qurban_coupons' => [], 'aid' => []]);
    }

    // ── Claiming a coupon from the officer's phone ──────────────────────────

    #[Test]
    public function the_officer_claims_a_coupon_and_a_second_scan_is_refused(): void
    {
        $mosque = $this->mosque();
        $coupon = $this->coupon($mosque, $this->resident('Citra'), 'Q26AAR00200001');
        $officer = $this->actingAsOfficer($mosque, ['scan-qurban-coupon']);

        $this->postJson('/api/v1/qurban/coupons/scan', ['code' => 'Q26AAR00200001'], $this->in($mosque))
            ->assertOk()
            ->assertJsonPath('result', 'success')
            ->assertJsonPath('coupon.name', 'Citra');

        $this->assertSame('claimed', $coupon->fresh()->status);
        $this->assertDatabaseHas('qurban_coupon_claims', [
            'qurban_coupon_id' => $coupon->id,
            'scan_result' => QurbanCouponClaim::RESULT_SUCCESS,
            'scanner_user_id' => $officer->id,
        ]);

        $this->postJson('/api/v1/qurban/coupons/scan', ['code' => 'Q26AAR00200001'], $this->in($mosque))
            ->assertOk()
            ->assertJsonPath('result', 'already_claimed');
    }

    #[Test]
    public function another_mosques_coupon_is_not_claimed_here(): void
    {
        $here = $this->mosque();
        $there = $this->mosque();
        $coupon = $this->coupon($there, $this->resident('Citra'), 'Q26BBR00200001');
        $this->actingAsOfficer($here, ['scan-qurban-coupon']);

        $this->postJson('/api/v1/qurban/coupons/scan', ['code' => 'Q26BBR00200001'], $this->in($here))
            ->assertOk()
            ->assertJsonPath('result', 'invalid')
            ->assertJsonPath('coupon', null);

        $this->assertSame('issued', $coupon->fresh()->status);
    }

    #[Test]
    public function it_claims_for_the_mosque_being_worked_in_not_the_first_one(): void
    {
        // An officer of two mosques, standing in the second: the web would
        // pick their first membership and refuse this coupon.
        $first = $this->mosque();
        $second = $this->mosque();
        $coupon = $this->coupon($second, $this->resident('Citra'), 'Q26CCR00200001');

        $officer = $this->actingAsOfficer($first, ['scan-qurban-coupon']);
        $this->addMembership($officer, $second, ['scan-qurban-coupon']);

        $this->postJson('/api/v1/qurban/coupons/scan', ['code' => 'Q26CCR00200001'], $this->in($second))
            ->assertOk()
            ->assertJsonPath('result', 'success');

        $this->assertSame('claimed', $coupon->fresh()->status);
    }

    #[Test]
    public function a_level_without_scan_qurban_coupon_is_refused(): void
    {
        $mosque = $this->mosque();
        $this->coupon($mosque, $this->resident('Citra'), 'Q26AAR00200001');
        $this->actingAsOfficer($mosque, ['browse-mosque-charity-transactions']);

        $this->postJson('/api/v1/qurban/coupons/scan', ['code' => 'Q26AAR00200001'], $this->in($mosque))
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

    protected function mosque(): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => 'masjid-' . fake()->unique()->numerify('#####'),
            'name' => 'Masjid ' . fake()->unique()->numerify('###'),
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    protected function user(string $name): User
    {
        return User::forceCreate([
            'name' => $name,
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
    }

    protected function resident(string $name): User
    {
        $user = $this->user($name);
        UserResidentProfile::forceCreate(['user_id' => $user->id]);

        return $user;
    }

    protected function coupon(
        Organization $mosque,
        User $resident,
        string $code,
        string $couponStatus = 'issued',
        string $batchStatus = 'active',
    ): QurbanCoupon {
        $batch = QurbanDistributionBatch::forceCreate([
            'organization_id' => $mosque->id,
            'title' => 'Daging Qurban RT 02',
            'year' => 2026,
            'status' => $batchStatus,
        ]);

        $beneficiary = QurbanBeneficiary::forceCreate([
            'organization_id' => $mosque->id,
            'resident_id' => $resident->id,
            'name_snapshot' => $resident->name,
        ]);

        return QurbanCoupon::forceCreate([
            'qurban_distribution_batch_id' => $batch->id,
            'qurban_beneficiary_id' => $beneficiary->id,
            'coupon_code' => $code,
            'qr_code' => $code,
            'status' => $couponStatus,
        ]);
    }

    protected function aid(Organization $organization, User $resident, string $title): DistributionRecipient
    {
        $suffix = fake()->unique()->numerify('###');
        $typeId = DB::table('m_distribution_types')->insertGetId([
            'name' => 'Zakat ' . $suffix,
            'slug' => 'zakat-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $distribution = Distribution::forceCreate([
            'organization_id' => $organization->id,
            'distribution_type_id' => $typeId,
            'year' => 2026,
            'title' => $title,
            'status' => 'ongoing',
        ]);

        return DistributionRecipient::forceCreate([
            'distribution_id' => $distribution->id,
            'resident_id' => $resident->id,
            'status' => 'pending',
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function actingAsOfficer(Organization $mosque, array $permissions): User
    {
        $officer = $this->user('Petugas');
        $this->addMembership($officer, $mosque, $permissions, primary: true);

        Sanctum::actingAs($officer);

        return $officer;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function addMembership(User $user, Organization $mosque, array $permissions, bool $primary = false): void
    {
        $slug = 'mosque-uji-' . fake()->unique()->numerify('####');
        $level = UserLevel::forceCreate(['organization_id' => $mosque->id, 'name' => 'Petugas', 'slug' => $slug]);

        foreach ($permissions as $permission) {
            UserLevelPermission::forceCreate(['user_level_id' => $level->id, 'permission_name' => $permission]);
        }

        OrganizationUser::forceCreate([
            'organization_id' => $mosque->id,
            'user_id' => $user->id,
            'role' => 'mosque_admin',
            'level_slug' => $slug,
            'is_primary' => $primary,
        ]);
    }
}
