<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;

class MasterDataController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (! $user) {
                abort(403);
            }

            if ($user->can('read-data-master') || $this->isOrganizationSuperadmin($user)) {
                return $next($request);
            }

            abort(403);
        });
    }

    public function index()
    {
        $masterItems = [
            // SUPERADMIN BOLEH AKSES SEMUANYA
            ['key' => 'residence-statuses', 'label' => __('messages.residence_status'), 'icon' => 'ri-home-heart-line', 'roles' => ['superadmin']],
            ['key' => 'marital-statuses', 'label' => __('messages.marital_status'), 'icon' => 'ri-user-heart-line', 'roles' => ['superadmin']],
            ['key' => 'educations', 'label' => __('messages.education'), 'icon' => 'ri-graduation-cap-line', 'roles' => ['superadmin']],
            ['key' => 'education-majors', 'label' => __('messages.education_major'), 'icon' => 'ri-book-open-line', 'roles' => ['superadmin']],
            ['key' => 'religions', 'label' => __('messages.religion'), 'icon' => 'ri-ancient-pavilion-line', 'roles' => ['superadmin']],
            ['key' => 'ownership-statuses', 'label' => __('messages.ownership_status'), 'icon' => 'ri-home-4-line', 'roles' => ['superadmin']],
            ['key' => 'work-statuses', 'label' => __('messages.work_status'), 'icon' => 'ri-briefcase-4-line', 'roles' => ['superadmin']],
            ['key' => 'resident-statuses', 'label' => __('messages.resident_status'), 'icon' => 'ri-user-location-line', 'roles' => ['superadmin']],
            ['key' => 'banks', 'label' => __('messages.banks'), 'icon' => 'ri-bank-line', 'roles' => ['superadmin']],
            ['key' => 'distribution-types', 'label' => __('messages.distribution_types'), 'icon' => 'ri-hand-heart-line', 'roles' => ['superadmin']],
            ['key' => 'distribution-class-sources', 'label' => __('messages.distribution_class_sources'), 'icon' => 'ri-book-mark-line', 'roles' => ['superadmin']],
            ['key' => 'charity-type-sources', 'label' => __('messages.charity_type_sources'), 'icon' => 'ri-book-mark-line', 'roles' => ['superadmin']],

            // MASJID ROLE ACCESS
            ['key' => 'distribution-classes', 'label' => __('messages.distribution_classes'), 'icon' => 'ri-layout-grid-line', 'roles' => ['mosque_admin']],
            ['key' => 'charity-types', 'label' => __('messages.charity_types'), 'icon' => 'ri-hand-coin-line', 'roles' => ['mosque_admin']],
            ['key' => 'charity-payments', 'label' => __('messages.charity_payments'), 'icon' => 'ri-bank-card-2-line', 'roles' => ['mosque_admin']],
        ];

        // only admin access
        $locationItems = [
            ['key' => 'countries', 'label' => __('messages.countries'), 'icon' => 'ri-earth-line'],
            ['key' => 'provinces', 'label' => __('messages.provinces'), 'icon' => 'ri-map-pin-2-line'],
            ['key' => 'cities', 'label' => __('messages.cities'), 'icon' => 'ri-building-2-line'],
            ['key' => 'districts', 'label' => __('messages.districts'), 'icon' => 'ri-road-map-line'],
            ['key' => 'villages', 'label' => __('messages.villages'), 'icon' => 'ri-community-line'],
            ['key' => 'citizens-associations', 'label' => __('messages.citizens_associations'), 'icon' => 'ri-team-line'],
            ['key' => 'neighborhood-associations', 'label' => __('messages.neighborhood_associations'), 'icon' => 'ri-group-line'],
        ];

        $user = auth()->user();
        $userRoles = $user ? $user->getRoleNames()->toArray() : [];
        $isSuperadmin = in_array('superadmin', $userRoles, true);
        $isOrgSuperadmin = $user ? $this->isOrganizationSuperadmin($user) : false;

        if (! $isSuperadmin && ! $isOrgSuperadmin) {
            $masterItems = [];
        } else {
            $masterItems = collect($masterItems)
                ->filter(function (array $item) use ($userRoles) {
                    $allowedRoles = $item['roles'] ?? [];

                    return count(array_intersect($allowedRoles, $userRoles)) > 0;
                })
                ->values()
                ->toArray();
        }

        if (! $isSuperadmin) {
            $locationItems = [];
        }

        return view('master-data.index', [
            'masterItems' => $masterItems,
            'locationItems' => $locationItems,
        ]);
    }

    private function isOrganizationSuperadmin($user): bool
    {
        return $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', '%-superadmin')
            ->exists();
    }
}
