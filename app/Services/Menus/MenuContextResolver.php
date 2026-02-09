<?php

namespace App\Services\Menus;

use App\Models\Organizations\Organization;
use App\Models\Users\User;

class MenuContextResolver
{
    /**
     * @return array{string, \App\Models\Organizations\Organization|null}
     */
    public function resolve(User $user): array
    {
        if ($user->hasRole('superadmin')) {
            return ['admin', null];
        }

        if (
            $user->hasRole('rt_admin') ||
            $user->hasRole('mosque_admin') ||
            $user->hasRole('umkm_admin') ||
            $user->hasRole('corporate_admin') ||
            $user->hasRole('institusi_admin')
        ) {
            $membership = $user->organizationMemberships()
                ->with('organization')
                ->where('is_primary', true)
                ->first() ?? $user->organizationMemberships()->with('organization')->first();

            if ($membership && $membership->organization) {
                return [$this->contextFromOrganization($membership->organization), $membership->organization];
            }
        }

        if ($user->hasRole('resident')) {
            $organization = $user->residentProfile?->organization;

            return ['resident', $organization];
        }

        return ['admin', null];
    }

    protected function contextFromOrganization(Organization $organization): string
    {
        return match ($organization->type) {
            Organization::TYPE_RT => 'rt',
            Organization::TYPE_MOSQUE => 'mosque',
            Organization::TYPE_UMKM,
            Organization::TYPE_INSTITUTION => 'partner',
            default => 'admin',
        };
    }
}
