<?php

namespace Database\Seeders;

use App\Models\Menus\UserMenu;
use Illuminate\Database\Seeder;

class UserMenuSeeder extends Seeder
{
    public function run(): void
    {
        UserMenu::truncate();

        $menus = array_merge(
            $this->adminMenus(),
            $this->mosqueMenus(),
            $this->rtMenus(),
            $this->residentMenus()
        );

        foreach ($menus as $menu) {
            UserMenu::create($menu);
        }
    }

    protected function adminMenus(): array
    {
        return [
            [
                'context' => 'admin',
                'section' => 'menu.admin.sections.core',
                'label' => 'menu.admin.items.organizations',
                'icon' => 'ri-community-line',
                'route_name' => 'organizations.index',
                'order' => 10,
                'visibility_rules' => [
                    'permissions' => ['browse-organizations'],
                ],
            ],
            [
                'context' => 'admin',
                'section' => 'menu.admin.sections.core',
                'label' => 'menu.admin.items.residents',
                'icon' => 'ri-user-4-line',
                'route_name' => 'residents.index',
                'order' => 20,
                'visibility_rules' => [
                    'permissions' => ['browse-rt-residents'],
                ],
            ],
        ];
    }

    protected function mosqueMenus(): array
    {
        return [
            $this->placeholderMenu(
                context: 'mosque',
                section: 'menu.mosque.sections.features',
                label: 'menu.mosque.items.charity',
                icon: 'ri-heart-2-line',
                slug: 'mosque-charity',
                order: 20,
                rules: [
                    'organization_types' => ['mosque'],
                    'permissions' => ['browse-zakat'],
                ]
            ),
            $this->placeholderMenu('mosque', 'menu.mosque.sections.features', 'menu.mosque.items.qurban', 'ri-government-line', 'mosque-qurban', 30, [
                'organization_types' => ['mosque'],
                'permissions' => ['browse-qurban'],
            ]),
            $this->placeholderMenu('mosque', 'menu.mosque.sections.features', 'menu.mosque.items.distribution', 'ri-hand-coin-line', 'mosque-distribution', 40, [
                'organization_types' => ['mosque'],
                'permissions' => ['browse-mosque-distributions'],
            ]),
            $this->placeholderMenu('mosque', 'menu.mosque.sections.features', 'menu.mosque.items.scan', 'ri-qr-code-line', 'mosque-scan', 50, [
                'organization_types' => ['mosque'],
                'permissions' => ['scan-qurban-coupon', 'scan-zakat-coupon'],
            ]),
            $this->placeholderMenu('mosque', 'menu.mosque.sections.features', 'menu.mosque.items.inventory', 'ri-archive-drawer-line', 'mosque-inventory', 60, [
                'organization_types' => ['mosque'],
                'permissions' => ['browse-mosque-inventory'],
            ]),
            $this->placeholderMenu('mosque', 'menu.mosque.sections.features', 'menu.mosque.items.crm', 'ri-contacts-book-2-line', 'mosque-crm', 70, [
                'organization_types' => ['mosque'],
                'permissions' => ['browse-mosque-crm'],
            ]),
            $this->placeholderMenu('mosque', 'menu.mosque.sections.reports', 'menu.mosque.items.charity_report', 'ri-file-chart-line', 'mosque-report-charity', 80, [
                'organization_types' => ['mosque'],
                'permissions' => ['browse-mosque-charity-reports'],
            ]),
            $this->placeholderMenu('mosque', 'menu.mosque.sections.reports', 'menu.mosque.items.distribution_report', 'ri-file-chart-line', 'mosque-report-distribution', 90, [
                'organization_types' => ['mosque'],
                'permissions' => ['browse-mosque-distribution-reports'],
            ]),
        ];
    }

    protected function rtMenus(): array
    {
        return [
            $this->placeholderMenu('rt', 'menu.rt.sections.population', 'menu.rt.items.citizen_data', 'ri-team-line', 'rt-citizen-data', 10, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-residents'],
            ]),
            $this->placeholderMenu('rt', 'menu.rt.sections.programs', 'menu.rt.items.inventory', 'ri-archive-drawer-line', 'rt-inventory', 20, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-inventory'],
            ]),
            $this->placeholderMenu('rt', 'menu.rt.sections.programs', 'menu.rt.items.community_events', 'ri-calendar-event-line', 'rt-events', 30, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-events'],
            ]),
            $this->placeholderMenu('rt', 'menu.rt.sections.programs', 'menu.rt.items.citizen_dues', 'ri-money-dollar-circle-line', 'rt-dues', 40, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-dues'],
            ]),
            $this->placeholderMenu('rt', 'menu.rt.sections.communication', 'menu.rt.items.news', 'ri-newspaper-line', 'rt-news', 50, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-news'],
            ]),
            $this->placeholderMenu('rt', 'menu.rt.sections.communication', 'menu.rt.items.feedback', 'ri-mail-line', 'rt-feedback', 60, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-feedback'],
            ]),
            $this->placeholderMenu('rt', 'menu.rt.sections.organization', 'menu.rt.items.membership', 'ri-group-line', 'rt-membership', 70, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-membership'],
            ]),
            $this->placeholderMenu('rt', 'menu.rt.sections.reports', 'menu.rt.items.reports', 'ri-file-list-3-line', 'rt-reports', 80, [
                'organization_types' => ['rt'],
                'permissions' => ['browse-rt-reports'],
            ]),
        ];
    }

    protected function residentMenus(): array
    {
        return [
            $this->placeholderMenu('resident', 'menu.resident.sections.portal', 'menu.resident.items.dues', 'ri-coins-line', 'resident-dues', 20, [
                'organization_required' => true,
            ]),
            $this->placeholderMenu('resident', 'menu.resident.sections.portal', 'menu.resident.items.information', 'ri-information-line', 'resident-information', 30, [
                'organization_required' => true,
            ]),
            $this->placeholderMenu('resident', 'menu.resident.sections.portal', 'menu.resident.items.complaints', 'ri-chat-3-line', 'resident-complaints', 40, [
                'organization_required' => true,
            ]),
            $this->placeholderMenu('resident', 'menu.resident.sections.portal', 'menu.resident.items.events', 'ri-calendar-event-line', 'resident-events', 50, [
                'organization_required' => true,
            ]),
        ];
    }

    protected function placeholderMenu(
        string $context,
        ?string $section,
        string $label,
        string $icon,
        string $slug,
        int $order,
        array $rules = []
    ): array {
        $routeName = str_replace('-', '.', $slug);

        return [
            'context' => $context,
            'section' => $section,
            'label' => $label,
            'icon' => $icon,
            'route_name' => $routeName,
            'order' => $order,
            'visibility_rules' => $rules,
            'is_active' => true,
        ];
    }
}
