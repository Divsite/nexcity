<?php

namespace App\Services\Menus;

use App\Models\Menus\UserMenu;
use App\Models\Organizations\Organization;
use App\Models\Organizations\UserLevel;
use App\Models\Organizations\UserLevelPermission;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuBuilder
{
    public const DEFAULT_SECTION = '__default__';

    /**
     * Build menu collection for the given user/context.
     */
    public function forUser(User $user, string $context = 'admin', ?Organization $organization = null): Collection
    {
        return $this->loadMenus($context, $organization)
            ->filter(fn (UserMenu $menu) => $this->isVisibleForUser($menu, $user, $organization))
            ->groupBy(fn (UserMenu $menu) => $menu->section ?: self::DEFAULT_SECTION);
    }

    protected function loadMenus(string $context, ?Organization $organization): Collection
    {
        $cacheKey = sprintf('menu:%s:%s', $context, $organization?->id ?? 'global');

        return Cache::rememberForever($cacheKey, function () use ($context, $organization) {
            return UserMenu::query()
                ->where('context', $context)
                ->where('is_active', true)
                ->where(function ($query) use ($organization) {
                    $query->whereNull('organization_id');
                    if ($organization) {
                        $query->orWhere('organization_id', $organization->id);
                    }
                })
                ->orderByRaw('COALESCE(section, "zzzz")')
                ->orderBy('order')
                ->get();
        });
    }

    protected function isVisibleForUser(UserMenu $menu, User $user, ?Organization $organization): bool
    {
        $rules = $menu->visibility_rules ?? [];

        if (isset($rules['permissions'])) {
            $allowed = collect((array) $rules['permissions'])
                ->some(fn ($permission) => $user->can($permission) || $this->hasLevelPermission($user, $organization, $permission));

            if (! $allowed) {
                return false;
            }
        }

        if (isset($rules['roles'])) {
            $allowed = collect((array) $rules['roles'])
                ->some(fn ($role) => $user->hasRole($role));

            if (! $allowed) {
                return false;
            }
        }

        if (isset($rules['organization_types'])) {
            if (! $organization) {
                return false;
            }

            if (! in_array($organization->type, (array) $rules['organization_types'], true)) {
                return false;
            }
        }

        if (($rules['organization_required'] ?? false) && ! $organization) {
            return false;
        }

        if (isset($rules['level_slugs'])) {
            if (! $organization) {
                return false;
            }

            $allowedSlugs = (array) $rules['level_slugs'];
            $hasLevel = $user->organizationMemberships()
                ->where('organization_id', $organization->id)
                ->whereIn('level_slug', $allowedSlugs)
                ->exists();

            if (! $hasLevel) {
                return false;
            }
        } elseif ($menu->user_level_id && $organization) {
            $levelSlug = $menu->userLevel?->slug;
            if ($levelSlug) {
                $hasLevel = $user->organizationMemberships()
                    ->where('organization_id', $organization->id)
                    ->where('level_slug', $levelSlug)
                    ->exists();

                if (! $hasLevel) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function hasLevelPermission(User $user, ?Organization $organization, string $permission): bool
    {
        if (! $organization) {
            return false;
        }

        $membership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->whereNotNull('level_slug')
            ->first();

        if (! $membership) {
            return false;
        }

        $level = UserLevel::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $membership->level_slug)
            ->first();

        if (! $level) {
            return false;
        }

        return UserLevelPermission::query()
            ->where('user_level_id', $level->id)
            ->where('permission_name', $permission)
            ->exists();
    }
}
