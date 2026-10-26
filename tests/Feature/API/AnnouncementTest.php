<?php

namespace Tests\Feature\API;

use App\Models\Announcements\Announcement;
use App\Models\Announcements\AnnouncementCategory;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Announcements are mostly a question about who is allowed to read what, so
 * that is what most of these tests are about. The negative cases are the point:
 * an RT's notice reaching a stranger is the failure that matters.
 */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    // ── The feed ────────────────────────────────────────────────────────────

    #[Test]
    public function the_feed_carries_announcements_from_my_own_organizations(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement($rt, title: 'Kerja bakti Minggu pagi');

        Sanctum::actingAs($resident);

        $this->getJson('/api/v1/announcements')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Kerja bakti Minggu pagi')
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function a_draft_is_not_an_announcement_yet(): void
    {
        // Visible because someone published it, never because someone saved it.
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement($rt, draft: true);

        Sanctum::actingAs($resident);

        $this->getJson('/api/v1/announcements')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function an_announcement_scheduled_for_next_week_stays_hidden(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement($rt, publishedAt: now()->addWeek());

        Sanctum::actingAs($resident);

        $this->getJson('/api/v1/announcements')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function sundays_kerja_bakti_is_not_news_on_monday(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement($rt, expiresAt: now()->subDay());

        Sanctum::actingAs($resident);

        $this->getJson('/api/v1/announcements')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function pinned_comes_first_then_the_newest(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement($rt, title: 'Lama', publishedAt: now()->subDays(3));
        $this->announcement($rt, title: 'Baru', publishedAt: now()->subHour());
        $this->announcement($rt, title: 'Disematkan', publishedAt: now()->subDays(5), pinned: true);

        Sanctum::actingAs($resident);

        $response = $this->getJson('/api/v1/announcements')->assertOk();

        $this->assertSame(
            ['Disematkan', 'Baru', 'Lama'],
            $response->json('data.*.title'),
        );
    }

    // ── Who may read what ───────────────────────────────────────────────────

    #[Test]
    public function another_rts_announcement_never_reaches_me(): void
    {
        $mine = $this->organization(Organization::TYPE_RT);
        $theirs = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($mine);

        $this->announcement($theirs, title: 'Rapat RT sebelah');

        Sanctum::actingAs($resident);

        $this->getJson('/api/v1/announcements')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function an_rt_cannot_address_the_public_even_if_the_row_says_so(): void
    {
        // An RT's business belongs to its own residents — the same rule that
        // makes iuran private. The read path refuses to honour `public` on an
        // RT rather than trusting that whatever wrote the row was correct.
        $rt = $this->organization(Organization::TYPE_RT);
        $outsider = $this->resident($this->organization(Organization::TYPE_RT));

        $this->announcement($rt, title: 'Bocor', audience: Announcement::AUDIENCE_PUBLIC);

        Sanctum::actingAs($outsider);

        $this->getJson('/api/v1/announcements')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/organizations/{$rt->slug}/announcements")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function a_staff_only_notice_is_hidden_from_ordinary_residents(): void
    {
        // Coordination between pengurus is not news. A resident seeing "bawa
        // uang kas ke rumah Pak RT" is a leak, not a feature.
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement($rt, title: 'Rapat pengurus', audience: Announcement::AUDIENCE_STAFF);

        Sanctum::actingAs($resident);

        $this->getJson('/api/v1/announcements')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function a_staff_only_notice_reaches_pengurus(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $officer = $this->officer($rt);

        $this->announcement($rt, title: 'Rapat pengurus', audience: Announcement::AUDIENCE_STAFF);

        Sanctum::actingAs($officer);

        $this->getJson('/api/v1/announcements')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Rapat pengurus');
    }

    // ── A mosque is open; its feed still is not ─────────────────────────────

    #[Test]
    public function a_visitor_sees_a_mosques_public_notice_on_the_mosques_page(): void
    {
        // A kajian welcomes anyone who walks in, including people who live
        // nowhere near the mosque.
        $mosque = $this->organization(Organization::TYPE_MOSQUE);
        $stranger = $this->resident($this->organization(Organization::TYPE_RT));

        $this->announcement($mosque, title: 'Kajian Subuh', audience: Announcement::AUDIENCE_PUBLIC);

        Sanctum::actingAs($stranger);

        $this->getJson("/api/v1/organizations/{$mosque->slug}/announcements")
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Kajian Subuh');
    }

    #[Test]
    public function a_public_notice_does_not_push_itself_into_a_strangers_feed(): void
    {
        // Readable is not the same as delivered. There is no "follow" in this
        // product, and without one an open feed fills with strangers' notices.
        $mosque = $this->organization(Organization::TYPE_MOSQUE);
        $stranger = $this->resident($this->organization(Organization::TYPE_RT));

        $this->announcement($mosque, audience: Announcement::AUDIENCE_PUBLIC);

        Sanctum::actingAs($stranger);

        $this->getJson('/api/v1/announcements')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function a_mosques_members_only_notice_stays_with_its_jamaah(): void
    {
        $mosque = $this->organization(Organization::TYPE_MOSQUE);
        $stranger = $this->resident($this->organization(Organization::TYPE_RT));

        $this->announcement($mosque, title: 'Berita duka', audience: Announcement::AUDIENCE_MEMBERS);

        Sanctum::actingAs($stranger);

        $this->getJson("/api/v1/organizations/{$mosque->slug}/announcements")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── Opening one directly ────────────────────────────────────────────────

    #[Test]
    public function opening_someone_elses_announcement_is_a_404_not_a_403(): void
    {
        // 403 would confirm that the RT announced something, which is itself
        // the information being withheld.
        $rt = $this->organization(Organization::TYPE_RT);
        $outsider = $this->resident($this->organization(Organization::TYPE_RT));

        $announcement = $this->announcement($rt);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/announcements/{$announcement->id}")->assertNotFound();
    }

    #[Test]
    public function a_resident_can_open_their_own_rts_announcement(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $announcement = $this->announcement($rt, title: 'Siskamling');

        Sanctum::actingAs($resident);

        $this->getJson("/api/v1/announcements/{$announcement->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Siskamling');
    }

    #[Test]
    public function anonymous_callers_get_nothing(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $this->announcement($rt);

        $this->getJson('/api/v1/announcements')->assertUnauthorized();
    }

    // ── The payload ─────────────────────────────────────────────────────────

    #[Test]
    public function an_event_carries_when_and_where(): void
    {
        // Kerja bakti, sholat jenazah and kajian all answer the same two
        // questions. An announcement that only says "ada kerja bakti" is the
        // loudspeaker problem restated.
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement(
            $rt,
            eventAt: Carbon::parse('2026-08-16 06:00:00'),
            eventLocation: 'Balai RT 05',
        );

        Sanctum::actingAs($resident);

        $response = $this->getJson('/api/v1/announcements')->assertOk();

        $this->assertNotNull($response->json('data.0.event_at'));
        $this->assertSame('Balai RT 05', $response->json('data.0.event_location'));
    }

    #[Test]
    public function urgency_comes_from_the_category_so_the_app_keeps_no_list_of_its_own(): void
    {
        $rt = $this->organization(Organization::TYPE_RT);
        $resident = $this->resident($rt);

        $this->announcement($rt, categorySlug: 'berita-duka');

        Sanctum::actingAs($resident);

        $this->getJson('/api/v1/announcements')
            ->assertOk()
            ->assertJsonPath('data.0.category.slug', 'berita-duka')
            ->assertJsonPath('data.0.category.is_urgent', true);
    }

    // ── Categories ──────────────────────────────────────────────────────────

    #[Test]
    public function an_rt_is_not_offered_categories_that_belong_to_a_mosque(): void
    {
        $this->seed(\Database\Seeders\AnnouncementCategorySeeder::class);

        $slugs = AnnouncementCategory::forOrganizationType('rt')->pluck('slug')->all();

        $this->assertContains('kerja-bakti', $slugs);
        $this->assertContains('keamanan', $slugs);
        $this->assertNotContains('jadwal-kajian', $slugs);
        $this->assertNotContains('laporan-keuangan', $slugs);
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

    /**
     * A plain member: belongs to the organization, holds no level, therefore
     * no capabilities — which is exactly what makes them not pengurus.
     */
    protected function resident(Organization $organization): User
    {
        $user = $this->user();

        OrganizationUser::forceCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'resident',
            'level_slug' => null,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function officer(Organization $organization): User
    {
        $user = $this->user();

        $levelSlug = 'rt-secretary';
        $level = UserLevel::firstOrCreate(
            ['organization_id' => null, 'slug' => $levelSlug],
            ['name' => 'Sekretaris', 'is_global' => true],
        );

        Permission::firstOrCreate(
            ['name' => 'browse-residents', 'guard_name' => 'web'],
            ['display_name' => 'Browse residents'],
        );
        UserLevelPermission::firstOrCreate([
            'user_level_id' => $level->id,
            'permission_name' => 'browse-residents',
        ]);

        OrganizationUser::forceCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'rt_admin',
            'level_slug' => $levelSlug,
            'is_primary' => true,
        ]);

        return $user->fresh();
    }

    protected function user(): User
    {
        $user = User::forceCreate([
            'name' => 'Warga ' . fake()->unique()->numerify('###'),
            'username' => 'u' . fake()->unique()->numerify('######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);

        $user->assignRole(Role::firstOrCreate(
            ['name' => 'resident', 'guard_name' => 'web'],
            ['display_name' => 'Resident'],
        ));

        return $user;
    }

    protected function category(string $slug = 'umum'): AnnouncementCategory
    {
        return AnnouncementCategory::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => Str::headline($slug),
                'applies_to' => 'both',
                'default_audience' => 'members',
                'is_urgent' => $slug === 'berita-duka',
            ],
        );
    }

    protected function announcement(
        Organization $organization,
        string $title = 'Pengumuman',
        string $audience = Announcement::AUDIENCE_MEMBERS,
        string $categorySlug = 'umum',
        ?Carbon $publishedAt = null,
        ?Carbon $expiresAt = null,
        ?Carbon $eventAt = null,
        ?string $eventLocation = null,
        bool $pinned = false,
        bool $draft = false,
    ): Announcement {
        return Announcement::forceCreate([
            'organization_id' => $organization->id,
            'announcement_category_id' => $this->category($categorySlug)->id,
            'title' => $title,
            'body' => 'Isi pengumuman.',
            'audience' => $audience,
            'published_at' => $draft ? null : ($publishedAt ?? now()->subMinute()),
            'expires_at' => $expiresAt,
            'event_at' => $eventAt,
            'event_location' => $eventLocation,
            'is_pinned' => $pinned,
        ]);
    }
}
