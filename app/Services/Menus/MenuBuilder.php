<?php

namespace App\Services\Menus;

use App\Models\Menus\UserMenu;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuBuilder
{
    public const DEFAULT_SECTION = '__default__';

    public function __construct(protected CapabilityResolver $capabilities)
    {
    }

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
            // One rule, one implementation: the same answer `capability:`
            // middleware gives. Previously this was `can() || level`, and
            // because every RT officer carries the same `rt_admin` role, that
            // OR meant a level could never withhold anything — a bendahara saw
            // Kependudukan and Keorganisasian in the sidebar, then hit a 403 on
            // opening them. The menu described the role; the door obeyed the
            // level.
            $allowed = collect((array) $rules['permissions'])
                ->some(fn ($permission) => $this->capabilities->holds(
                    $user,
                    $permission,
                    $organization?->id,
                ));

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

}
