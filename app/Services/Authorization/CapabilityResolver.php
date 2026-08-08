<?php

namespace App\Services\Authorization;

use App\Models\Organizations\OrganizationUser;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Support\Collection;

/**
 * Resolves what a user may actually do, per organization.
 *
 * Every mosque staff member carries the same Spatie role (`mosque_admin`), and
 * the same is true for RT. The only thing separating a bendahara from a
 * pengurus qurban is their `level_slug` in `organization_user`. So for
 * organization-scoped permissions the **level is authoritative**, not the role
 * — otherwise levels would be decorative and every officer could do everything.
 *
 * Account-level permissions (my-account, change-password, the resident portal)
 * are not tied to an organization and come from the role.
 *
 * This is deliberately stricter than `MenuBuilder::isVisibleForUser()`, which
 * grants a permission when *either* the role or the level allows it. That OR
 * means a level can never withhold anything.
 */
class CapabilityResolver
{
    /**
     * Permission prefixes that only make sense inside an organization.
     *
     * Anything matching these is granted by the level, never by the role alone.
     */
    protected const ORGANIZATION_SCOPED_PREFIXES = [
        'browse-mosque-', 'read-mosque-', 'edit-mosque-', 'add-mosque-', 'delete-mosque-',
        'print-mosque-',
        'browse-rt-', 'read-rt-', 'edit-rt-', 'add-rt-', 'delete-rt-',
        'browse-qurban', 'read-qurban', 'edit-qurban', 'add-qurban', 'delete-qurban',
        'scan-',
    ];

    /**
     * Capabilities that hold regardless of which organization is active:
     * the user's own account and the resident portal.
     *
     * @return list<string>
     */
    public function globalCapabilities(User $user): array
    {
        return $user->getAllPermissions()
            ->pluck('name')
            ->reject(fn (string $name) => $this->isOrganizationScoped($name))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Capabilities and level name per organization, keyed by organization id.
     *
     * Resolved in a bounded number of queries rather than one per permission,
     * because this runs on every session load.
     *
     * @param  Collection<int, OrganizationUser>  $memberships
     * @return array<int, array{capabilities: list<string>, level_name: string|null}>
     */
    public function resolveByOrganization(User $user, Collection $memberships): array
    {
        if ($memberships->isEmpty()) {
            return [];
        }

        // Superadmin is not scoped by level; it holds everything, everywhere.
        if ($user->hasRole('superadmin')) {
            $all = $user->getAllPermissions()->pluck('name')->unique()->sort()->values()->all();

            return $memberships
                ->mapWithKeys(fn (OrganizationUser $m) => [
                    $m->organization_id => ['capabilities' => $all, 'level_name' => null],
                ])
                ->all();
        }

        $levels = $this->loadLevels($memberships);

        $permissionsByLevel = UserLevelPermission::query()
            ->whereIn('user_level_id', $levels->pluck('id'))
            ->get()
            ->groupBy('user_level_id')
            ->map(fn (Collection $rows) => $rows->pluck('permission_name')->unique()->sort()->values()->all());

        return $memberships
            ->mapWithKeys(function (OrganizationUser $membership) use ($levels, $permissionsByLevel) {
                $matching = $levels->where('slug', $membership->level_slug);

                // An organization's own definition wins over the global one.
                $level = $matching->firstWhere(
                    'organization_id',
                    $membership->organization_id,
                ) ?? $matching->firstWhere('organization_id', null);

                return [
                    $membership->organization_id => [
                        'capabilities' => $level ? ($permissionsByLevel[$level->id] ?? []) : [],
                        'level_name' => $level?->name,
                    ],
                ];
            })
            ->all();
    }

    /**
     * The organization a session starts in: the primary membership, else the
     * first one, else the resident's own RT.
     *
     * Mirrors MenuContextResolver so web and mobile land in the same place.
     *
     * @param  Collection<int, OrganizationUser>  $memberships
     */
    public function defaultOrganizationId(User $user, Collection $memberships): ?int
    {
        $primary = $memberships->firstWhere('is_primary', true);

        return $primary?->organization_id
            ?? $memberships->first()?->organization_id
            ?? $user->residentProfile?->organization_id;
    }

    /**
     * @param  Collection<int, OrganizationUser>  $memberships
     * @return Collection<int, UserLevel>
     */
    protected function loadLevels(Collection $memberships): Collection
    {
        $withLevel = $memberships->filter(fn (OrganizationUser $m) => filled($m->level_slug));

        if ($withLevel->isEmpty()) {
            return collect();
        }

        // Levels are defined once, globally (organization_id null). An
        // organization-specific row is still honoured if one exists, so a
        // future per-partner override would not need this rewritten.
        return UserLevel::query()
            ->whereIn('slug', $withLevel->pluck('level_slug')->unique())
            ->where(function ($query) use ($withLevel) {
                $query->whereNull('organization_id')
                    ->orWhereIn('organization_id', $withLevel->pluck('organization_id')->unique());
            })
            ->get();
    }

    protected function isOrganizationScoped(string $permission): bool
    {
        foreach (self::ORGANIZATION_SCOPED_PREFIXES as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
