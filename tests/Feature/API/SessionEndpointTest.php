<?php

namespace Tests\Feature\API;

use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function me_returns_the_session_the_mobile_client_expects(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeMember($organization, 'mosque-qurban', [
            'browse-qurban',
            'scan-qurban-coupon',
        ], isPrimary: true);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'roles'],
                'memberships' => [
                    ['organization' => ['id', 'slug', 'name', 'type'], 'is_primary', 'level', 'capabilities'],
                ],
                'active_organization_id',
                'global_capabilities',
            ])
            ->assertJsonPath('active_organization_id', $organization->id)
            ->assertJsonPath('memberships.0.level.slug', 'mosque-qurban');

        $this->assertContains(
            'scan-qurban-coupon',
            $response->json('memberships.0.capabilities'),
        );
    }

    #[Test]
    public function me_omits_the_resident_profile_when_there_is_none(): void
    {
        // A mosque officer is not a resident. The key must be absent, not `{}`.
        $organization = $this->makeOrganization();
        $user = $this->makeMember($organization, 'mosque-qurban', ['browse-qurban']);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonMissingPath('user.resident_profile');
    }

    #[Test]
    public function me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[Test]
    public function switching_to_an_organization_you_belong_to_returns_that_session(): void
    {
        $user = $this->makeUser();
        $primary = $this->makeOrganization('primary');
        $other = $this->makeOrganization('other');

        $this->attach($user, $primary, null, isPrimary: true);
        $this->attach($user, $other, null);

        Sanctum::actingAs($user);

        $this->postJson('/api/auth/active-organization', ['organization_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('active_organization_id', $other->id);
    }

    #[Test]
    public function switching_to_an_organization_you_do_not_belong_to_is_rejected(): void
    {
        $user = $this->makeMember($this->makeOrganization('mine'), null, []);
        $foreign = $this->makeOrganization('not-mine');

        Sanctum::actingAs($user);

        $this->postJson('/api/auth/active-organization', ['organization_id' => $foreign->id])
            ->assertForbidden();
    }

    #[Test]
    public function the_organization_header_is_rejected_when_you_are_not_a_member(): void
    {
        // The header is what scopes data on every other endpoint. If it were
        // trusted blindly, changing one number would read another
        // organization's records.
        $user = $this->makeMember($this->makeOrganization('mine'), null, []);
        $foreign = $this->makeOrganization('not-mine');

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me', ['X-Organization-Id' => (string) $foreign->id])
            ->assertForbidden();
    }

    #[Test]
    public function the_organization_header_is_honoured_when_you_are_a_member(): void
    {
        $user = $this->makeUser();
        $primary = $this->makeOrganization('primary');
        $other = $this->makeOrganization('other');

        $this->attach($user, $primary, null, isPrimary: true);
        $this->attach($user, $other, null);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me', ['X-Organization-Id' => (string) $other->id])
            ->assertOk()
            ->assertJsonPath('active_organization_id', $other->id);
    }

    #[Test]
    public function a_malformed_organization_header_is_rejected(): void
    {
        $user = $this->makeMember($this->makeOrganization(), null, []);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me', ['X-Organization-Id' => 'not-a-number'])
            ->assertStatus(400);
    }

    #[Test]
    public function no_organization_header_falls_back_to_the_primary_membership(): void
    {
        $organization = $this->makeOrganization();
        $user = $this->makeMember($organization, null, [], isPrimary: true);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('active_organization_id', $organization->id);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    protected function makeUser(): User
    {
        return User::forceCreate([
            'name' => 'Test User',
            'username' => 'user' . fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('secret'),
        ]);
    }

    protected function makeOrganization(string $slug = 'test-mosque'): Organization
    {
        return Organization::forceCreate([
            'uuid' => (string) Str::uuid(),
            'slug' => $slug . '-' . fake()->unique()->numerify('####'),
            'name' => 'Test Organization',
            'type' => Organization::TYPE_MOSQUE,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function makeMember(
        Organization $organization,
        ?string $levelSlug,
        array $permissions,
        bool $isPrimary = false,
    ): User {
        $user = $this->makeUser();

        if ($levelSlug !== null && $permissions !== []) {
            $level = UserLevel::forceCreate([
                'organization_id' => $organization->id,
                'name' => ucfirst($levelSlug),
                'slug' => $levelSlug,
            ]);

            foreach ($permissions as $permission) {
                UserLevelPermission::forceCreate([
                    'user_level_id' => $level->id,
                    'permission_name' => $permission,
                ]);
            }
        }

        $this->attach($user, $organization, $levelSlug, $isPrimary);

        return $user;
    }

    protected function attach(User $user, Organization $organization, ?string $levelSlug, bool $isPrimary = false): void
    {
        OrganizationUser::forceCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'member',
            'level_slug' => $levelSlug,
            'is_primary' => $isPrimary,
        ]);
    }
}
