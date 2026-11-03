<?php

namespace Tests\Feature\API;

use App\Models\Charities\CharityDonationReceipt;
use App\Models\Charities\CharityTransaction;
use App\Models\CharityTypes\CharityType;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A mosque's charity report, open to anyone signed in — in totals that never
 * name a payer or a recipient.
 */
class PublicCharityReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function anyone_sees_what_came_in_and_what_went_out(): void
    {
        $mosque = $this->organization(Organization::TYPE_MOSQUE);
        $infaq = $this->type($mosque, 'infaq', 'Infaq');
        foreach ([100000, 200000, 300000] as $amount) {
            $this->give($mosque, $infaq, $amount, payer: 'Pak Budi');
        }
        $this->distribute($mosque, 150000);

        Sanctum::actingAs($this->user());

        $response = $this->getJson("/api/v1/organizations/{$mosque->slug}/charity-report")
            ->assertOk()
            ->assertJsonPath('income.money', 600000)
            ->assertJsonPath('income.transactions', 3)
            ->assertJsonPath('income.by_type.0.name', 'Infaq')
            ->assertJsonPath('distributed.money', 150000)
            ->assertJsonPath('distributed.recipients', 1);

        // Totals only: no payer, no recipient anywhere in the answer.
        $this->assertStringNotContainsString('Pak Budi', $response->getContent());
        $this->assertStringNotContainsString('Penerima', $response->getContent());
    }

    #[Test]
    public function a_type_too_small_to_hide_a_payer_is_not_broken_out(): void
    {
        $mosque = $this->organization(Organization::TYPE_MOSQUE);
        $infaq = $this->type($mosque, 'infaq', 'Infaq');
        $waqf = $this->type($mosque, 'waqf', 'Wakaf');
        foreach ([1, 2, 3] as $i) {
            $this->give($mosque, $infaq, 50000);
        }
        // One wakaf: its own row would be one person's gift.
        $this->give($mosque, $waqf, 5000000);

        Sanctum::actingAs($this->user());

        $this->getJson("/api/v1/organizations/{$mosque->slug}/charity-report")
            ->assertOk()
            ->assertJsonPath('income.money', 5150000)
            ->assertJsonCount(1, 'income.by_type')
            ->assertJsonPath('income.by_type.0.name', 'Infaq');
    }

    #[Test]
    public function an_rts_money_stays_internal(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);

        Sanctum::actingAs($this->user());

        $this->getJson("/api/v1/organizations/{$rt->slug}/charity-report")->assertNotFound();
    }

    #[Test]
    public function anonymous_callers_get_nothing(): void
    {
        $mosque = $this->organization(Organization::TYPE_MOSQUE);

        $this->getJson("/api/v1/organizations/{$mosque->slug}/charity-report")->assertUnauthorized();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function organization(string $type): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => $type . '-' . fake()->unique()->numerify('#####'),
            'name' => 'Organisasi Uji',
            'type' => $type,
        ]);
    }

    protected function user(): User
    {
        return User::forceCreate([
            'name' => 'Jamaah',
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
    }

    protected function type(Organization $mosque, string $slug, string $name): CharityType
    {
        $sourceId = DB::table('m_charity_type_sources')->where('slug', $slug)->value('id')
            ?? DB::table('m_charity_type_sources')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return CharityType::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_source_id' => $sourceId,
            'year' => now()->year,
            'is_rice' => false,
            'is_active' => true,
        ]);
    }

    protected function give(Organization $mosque, CharityType $type, float $amount, string $payer = 'Hamba Allah'): void
    {
        $transaction = CharityTransaction::forceCreate([
            'organization_id' => $mosque->id,
            'charity_type_id' => $type->id,
            'year' => now()->year,
            'payer_name' => $payer,
            'payment_method' => 'cash',
            'status' => CharityTransaction::STATUS_PAID,
        ]);

        // Wakaf and infaq both land on a money receipt here; the receipt
        // table does not change what the report adds up.
        CharityDonationReceipt::create([
            'charity_transaction_id' => $transaction->id,
            'amount_money' => $amount,
        ]);
    }

    protected function distribute(Organization $mosque, float $amount): void
    {
        $typeId = DB::table('m_distribution_types')->insertGetId([
            'name' => 'Zakat',
            'slug' => 'zakat-' . fake()->unique()->numerify('###'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $distribution = Distribution::forceCreate([
            'organization_id' => $mosque->id,
            'distribution_type_id' => $typeId,
            'year' => (int) now()->year,
            'title' => 'Penyaluran zakat',
            'status' => 'ongoing',
        ]);

        DistributionRecipient::forceCreate([
            'distribution_id' => $distribution->id,
            'resident_id' => $this->user()->id,
            'amount_money' => $amount,
            'status' => 'distributed',
            'distributed_at' => now(),
        ]);
    }
}
