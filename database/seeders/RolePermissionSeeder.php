<?php

namespace Database\Seeders;

use App\Models\Permissions\Permission;
use App\Models\Roles\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * NOTE: If delete permission, make sure delete also at `syncRolesPermissions` method
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createRoleIfNotExist();
        $this->createAndDeletePermission();
        $this->syncRolesPermissions();
    }

    private function createRoleIfNotExist()
    {
        $roles = collect([
            [
                'name' => 'superadmin',
                'display_name' => 'Superadmin',
                'description' => 'Control and monitor all system functionality',
            ],
            [
                'name' => 'rt_admin',
                'display_name' => 'RT Admin',
                'description' => 'Manage RT data, residents, and community programs.',
            ],
            [
                'name' => 'mosque_admin',
                'display_name' => 'Mosque Admin',
                'description' => 'Manage mosque programs, zakat distribution, and officers.',
            ],
            [
                'name' => 'umkm_admin',
                'display_name' => 'UMKM Admin',
                'description' => 'Manage UMKM operations and partner data.',
            ],
            [
                'name' => 'corporate_admin',
                'display_name' => 'Corporate Admin',
                'description' => 'Manage corporate HR and organization settings.',
            ],
            [
                'name' => 'institusi_admin',
                'display_name' => 'Institusi Admin',
                'description' => 'Manage institution operations and academic data.',
            ],
            [
                'name' => 'resident',
                'display_name' => 'Resident',
                'description' => 'Citizen who accesses announcements and personal services.',
            ],
        ]);

        // Create role if not exist yet
        foreach ($roles as $role) {
            if (!Role::where('name', $role['name'])->exists()) {
                Role::create([
                    'name' => $role['name'],
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                ]);
            }
        }

        Role::whereNotIn('name', $roles->pluck('name'))->delete();
    }

    private function createAndDeletePermission()
    {
        $permissions = $this->permissions();

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'group' => $permission['group'],
                    'scope' => $permission['scope'] ?? 'global',
                    'context' => $permission['context'] ?? null,
                    'description' => $permission['description']
                ]
            );
        }

        // Delete permission not in seeder array
        $unusedPermissions = Permission::whereNotIn('name', $permissions->pluck('name'))->get();
        foreach ($unusedPermissions as $unusedPermission) {
            $unusedPermission->delete();
        }
    }

    private function syncRolesPermissions()
    {
        $roles_permissions = [
            'superadmin' => [
                'read-data-master',
                'read-settings-summary',
                'read-settings-user-managements',
                'read-settings-security',
                'read-settings-billing',
                'manage-internal-roles',
                'my-account',
                'change-password',
                'change-email',
                'change-username',
                'my-activities',
                'recent-sessions',
                'all-user-activities',
                'all-user-sessions',
                'browse-roles',
                'read-roles',
                'edit-roles',
                'add-roles',
                'delete-roles',
                'browse-permissions',
                'read-permissions',
                'browse-users',
                'read-users',
                'edit-users',
                'add-users',
                'delete-users',
                'browse-notifications',
                'read-notifications',
                'edit-notifications',
                'delete-notifications',
                'process-submissions',
                'manage-workflow-processes',
                'browse-settings',
                'edit-settings',
                // organizations
                'browse-organizations',
                'read-organizations',
                'edit-organizations',
                'add-organizations',
                'delete-organizations',
                // residents
                'browse-rt-residents',
                'read-rt-residents',
                'edit-rt-residents',
                'add-rt-residents',
                'delete-rt-residents',
                'scan-resident-qr',
                // menus
                'browse-user-menus',
                'edit-user-menus',
                // mosque charity modules
                'browse-mosque-charity-transactions',
                'read-mosque-charity-transactions',
                'edit-mosque-charity-transactions',
                'add-mosque-charity-transactions',
                'delete-mosque-charity-transactions',
                'browse-mosque-charity-types',
                'read-mosque-charity-types',
                'edit-mosque-charity-types',
                'add-mosque-charity-types',
                'delete-mosque-charity-types',
                'browse-mosque-charity-type-sources',
                'read-mosque-charity-type-sources',
                'edit-mosque-charity-type-sources',
                'add-mosque-charity-type-sources',
                'delete-mosque-charity-type-sources',
                'browse-mosque-charity-payments',
                'read-mosque-charity-payments',
                'edit-mosque-charity-payments',
                'add-mosque-charity-payments',
                'delete-mosque-charity-payments',
                // 'browse-forms',
                // 'read-forms',
                // 'edit-forms',
                // 'add-forms',
                // 'delete-forms',
                // 'browse-groups',
                // 'read-groups',
                // 'edit-groups',
                // 'add-groups',
                // 'delete-groups',
                // 'browse-form-types',
                // 'read-form-types',
                // 'edit-form-types',
                // 'add-form-types',
                // 'delete-form-types',
                // 'browse-submissions',
                // 'read-submissions',
                // 'edit-submissions',
                // 'add-submissions',
                // 'delete-submissions',
            ],
            'rt_admin' => [
                'my-account',
                'change-password',
                'change-email',
                'change-username',
                'my-activities',
                'recent-sessions',

                // residents
                'browse-rt-residents',
                'read-rt-residents',
                'edit-rt-residents',
                'add-rt-residents',
                'delete-rt-residents',
                'scan-resident-qr',

                // internal users
                'browse-users',
                'read-users',

                // memberships
                'browse-rt-membership'
            ],
            'mosque_admin' => [
                'my-account',
                'change-password',
                'change-email',
                'change-username',
                'my-activities',
                'recent-sessions',

                // internal users
                'read-users',

                // charity transactions
                'browse-mosque-charity-transactions',
                'read-mosque-charity-transactions',
                'edit-mosque-charity-transactions',
                'add-mosque-charity-transactions',
                'delete-mosque-charity-transactions',

                // charity settings
                'browse-mosque-charity-types',
                'read-mosque-charity-types',
                'edit-mosque-charity-types',
                'add-mosque-charity-types',
                'delete-mosque-charity-types',
                'browse-mosque-charity-type-sources',
                'read-mosque-charity-type-sources',
                'edit-mosque-charity-type-sources',
                'add-mosque-charity-type-sources',
                'delete-mosque-charity-type-sources',
                'browse-mosque-charity-payments',
                'read-mosque-charity-payments',
                'edit-mosque-charity-payments',
                'add-mosque-charity-payments',
                'delete-mosque-charity-payments',
            ],
            'resident' => [
                'my-account',
                'change-password',
                'change-email',
                'change-username',
                'my-activities',
                'recent-sessions',
                'browse-resident-dues',
                'browse-resident-informations',
                'browse-resident-complaints',
                'browse-resident-events',
            ],
            'umkm_admin' => [
                'my-account',
                'change-password',
                'change-email',
                'change-username',
                'my-activities',
                'recent-sessions',
                'read-users',
            ],
            'corporate_admin' => [
                'my-account',
                'change-password',
                'change-email',
                'change-username',
                'my-activities',
                'recent-sessions',
                'read-users',
            ],
            'institusi_admin' => [
                'my-account',
                'change-password',
                'change-email',
                'change-username',
                'my-activities',
                'recent-sessions',
                'read-users',
            ],
        ];

        foreach ($roles_permissions as $name => $permissions) {
            $role = Role::whereName($name)->first();
            if ($role) {
                $role->syncPermissions($permissions);
            }
        }
    }

    public function groups(): Collection
    {
        return collect([
            'profile' => 'profile',
            'roles' => 'roles',
            'permissions' => 'permissions',
            'activity_log' => 'activity_log',
            'authentication_log' => 'authentication_log',
            'users' => 'users',
            'forms' => 'forms',
            'groups' => 'groups',
            'form_types' => 'form_types',
            'notifications' => 'notifications',
            'form_submissions' => 'form_submissions',
            'workflow_processes' => 'workflow_processes',
            'settings' => 'settings',
            'organizations' => 'organizations',
            'menus' => 'menus',

            // rt/rw permissions
            'rt_population' => 'rt_population',
            'rt_dues' => 'rt_dues',
            'rt_events' => 'rt_events',
            'rt_news' => 'rt_news',
            'rt_feedback' => 'rt_feedback',
            'rt_inventory' => 'rt_inventory',
            'rt_membership' => 'rt_membership',
            'rt_reports' => 'rt_reports',

            // mosque permissions
            'mosque_charity' => 'mosque_charity',
            'mosque_qurban' => 'mosque_qurban',
            'mosque_distributions' => 'mosque_distributions',
            'mosque_reports' => 'mosque_reports',
            'mosque_inventory' => 'mosque_inventory',
            'mosque_crm' => 'mosque_crm',

            // resident permission
            'resident_dues' => 'resident_dues',
            'resident_informations' => 'resident_informations',
            'resident_complaints' => 'resident_complaints',
            'resident_events' => 'resident_events',

            'master_data' => 'master_data',
        ]);
    }

    public function permissions(): Collection
    {
        $groups = $this->groups();

        return collect([
            [
                'name' => 'read-data-master',
                'display_name' => 'read_data_master',
                'group' => $groups['master_data'],
                'description' => 'view_master_data',
            ],
            [
                'name' => 'read-settings-summary',
                'display_name' => 'read_settings_summary',
                'group' => $groups['settings'],
                'description' => 'view_settings_summary',
            ],
            [
                'name' => 'read-settings-user-managements',
                'display_name' => 'read_settings_user_managements',
                'group' => $groups['settings'],
                'description' => 'view_settings_user_management',
            ],
            [
                'name' => 'read-settings-security',
                'display_name' => 'read_settings_security',
                'group' => $groups['settings'],
                'description' => 'view_settings_security',
            ],
            [
                'name' => 'read-settings-billing',
                'display_name' => 'read_settings_billing',
                'group' => $groups['settings'],
                'description' => 'view_settings_billing',
            ],
            [
                'name' => 'my-account',
                'display_name' => 'my_account',
                'group' => $groups['profile'],
                'description' => 'view_profile_information',
            ],
            [
                'name' => 'change-password',
                'display_name' => 'change_password',
                'group' => $groups['profile'],
                'description' => 'change_user_password',
            ],
            [
                'name' => 'change-email',
                'display_name' => 'change_email',
                'group' => $groups['profile'],
                'description' => 'change_user_email',
            ],
            [
                'name' => 'change-username',
                'display_name' => 'change_username',
                'group' => $groups['profile'],
                'description' => 'change_current_username',
            ],
            [
                'name' => 'my-activities',
                'display_name' => 'my_activities',
                'group' => $groups['profile'],
                'description' => 'view_all_current_user_activity',
            ],
            [
                'name' => 'recent-sessions',
                'display_name' => 'recent_sessions',
                'group' => $groups['profile'],
                'description' => 'view_all_recent_sessions_for_the_current_user',
            ],
            [
                'name' => 'all-user-activities',
                'display_name' => 'all_user_activities',
                'group' => $groups['activity_log'],
                'description' => 'list_of_all_user_activities',
            ],
            [
                'name' => 'all-user-sessions',
                'display_name' => 'all_user_sessions',
                'group' => $groups['authentication_log'],
                'description' => 'list_of_all_user_sessions',
            ],
            [
                'name' => 'browse-roles',
                'display_name' => 'browse_roles',
                'group' => $groups['roles'],
                'description' => 'list_of_all_role',
            ],
            [
                'name' => 'manage-internal-roles',
                'display_name' => 'manage_internal_roles',
                'group' => $groups['roles'],
                'description' => 'manage_internal_role_permissions',
            ],
            [
                'name' => 'read-roles',
                'display_name' => 'read_roles',
                'group' => $groups['roles'],
                'description' => 'read_the_selected_role',
            ],
            [
                'name' => 'edit-roles',
                'display_name' => 'edit_roles',
                'group' => $groups['roles'],
                'description' => 'edit_the_selected_role',
            ],
            [
                'name' => 'add-roles',
                'display_name' => 'add_roles',
                'group' => $groups['roles'],
                'description' => 'add_specific_role',
            ],
            [
                'name' => 'delete-roles',
                'display_name' => 'delete_roles',
                'group' => $groups['roles'],
                'description' => 'delete_the_selected_role',
            ],
            [
                'name' => 'browse-permissions',
                'display_name' => 'browse_permissions',
                'group' => $groups['permissions'],
                'description' => 'list_of_all_permission',
            ],
            [
                'name' => 'read-permissions',
                'display_name' => 'read_permissions',
                'group' => $groups['permissions'],
                'description' => 'read_the_selected_permission',
            ],
            [
                'name' => 'browse-users',
                'display_name' => 'browse_users',
                'group' => $groups['users'],
                'description' => 'list_of_all_user',
            ],
            [
                'name' => 'read-users',
                'display_name' => 'read_users',
                'group' => $groups['users'],
                'description' => 'read_the_selected_user',
            ],
            [
                'name' => 'edit-users',
                'display_name' => 'edit_users',
                'group' => $groups['users'],
                'description' => 'edit_account_for_selected_user',
            ],
            [
                'name' => 'add-users',
                'display_name' => 'add_users',
                'group' => $groups['users'],
                'description' => 'add_account_for_user',
            ],
            [
                'name' => 'delete-users',
                'display_name' => 'delete_users',
                'group' => $groups['users'],
                'description' => 'delete_account_for_selected_user',
            ],
            [
                'name' => 'browse-forms',
                'display_name' => 'browse_forms',
                'group' => $groups['forms'],
                'description' => 'list_of_all_form',
            ],
            [
                'name' => 'read-forms',
                'display_name' => 'read_forms',
                'group' => $groups['forms'],
                'description' => 'read_the_selected_form',
            ],
            [
                'name' => 'edit-forms',
                'display_name' => 'edit_forms',
                'group' => $groups['forms'],
                'description' => 'edit_the_selected_form',
            ],
            [
                'name' => 'add-forms',
                'display_name' => 'add_forms',
                'group' => $groups['forms'],
                'description' => 'add_new_form',
            ],
            [
                'name' => 'delete-forms',
                'display_name' => 'delete_forms',
                'group' => $groups['forms'],
                'description' => 'delete_the_selected_form',
            ],
            [
                'name' => 'browse-groups',
                'display_name' => 'browse_groups',
                'group' => $groups['groups'],
                'description' => 'list_of_all_group',
            ],
            [
                'name' => 'read-groups',
                'display_name' => 'read_group',
                'group' => $groups['groups'],
                'description' => 'read_the_selected_group',
            ],
            [
                'name' => 'edit-groups',
                'display_name' => 'edit_group',
                'group' => $groups['groups'],
                'description' => 'edit_the_selected_group',
            ],
            [
                'name' => 'add-groups',
                'display_name' => 'add_groups',
                'group' => $groups['groups'],
                'description' => 'add_new_group',
            ],
            [
                'name' => 'delete-groups',
                'display_name' => 'delete_groups',
                'group' => $groups['groups'],
                'description' => 'delete_the_selected_group',
            ],
            [
                'name' => 'browse-form-types',
                'display_name' => 'browse_form_types',
                'group' => $groups['form_types'],
                'description' => 'list_of_all_form_type',
            ],
            [
                'name' => 'read-form-types',
                'display_name' => 'read_form_types',
                'group' => $groups['form_types'],
                'description' => 'read_the_selected_form_type',
            ],
            [
                'name' => 'edit-form-types',
                'display_name' => 'edit_form_types',
                'group' => $groups['form_types'],
                'description' => 'edit_the_selected_form_type',
            ],
            [
                'name' => 'add-form-types',
                'display_name' => 'add_form_types',
                'group' => $groups['form_types'],
                'description' => 'add_new_form_type',
            ],
            [
                'name' => 'delete-form-types',
                'display_name' => 'delete_form_types',
                'group' => $groups['form_types'],
                'description' => 'delete_the_selected_form_type',
            ],
            [
                'name' => 'browse-notifications',
                'display_name' => 'browse_notifications',
                'group' => $groups['notifications'],
                'description' => 'list_of_all_notification',
            ],
            [
                'name' => 'read-notifications',
                'display_name' => 'read_notifications',
                'group' => $groups['notifications'],
                'description' => 'read_the_selected_notification',
            ],
            [
                'name' => 'edit-notifications',
                'display_name' => 'edit_notifications',
                'group' => $groups['notifications'],
                'description' => 'edit_the_selected_notification',
            ],
            [
                'name' => 'delete-notifications',
                'display_name' => 'delete_notifications',
                'group' => $groups['notifications'],
                'description' => 'delete_the_selected_notification',
            ],
            [
                'name' => 'browse-submissions',
                'display_name' => 'browse_submissions',
                'group' => $groups['form_submissions'],
                'description' => 'list_of_all_form_submission',
            ],
            [
                'name' => 'read-submissions',
                'display_name' => 'read_submissions',
                'group' => $groups['form_submissions'],
                'description' => 'read_the_selected_form_submission',
            ],
            [
                'name' => 'edit-submissions',
                'display_name' => 'edit_submissions',
                'group' => $groups['form_submissions'],
                'description' => 'edit_the_selected_form_submission',
            ],
            [
                'name' => 'add-submissions',
                'display_name' => 'add_submissions',
                'group' => $groups['form_submissions'],
                'description' => 'add_new_form_submission',
            ],
            [
                'name' => 'delete-submissions',
                'display_name' => 'delete_submissions',
                'group' => $groups['form_submissions'],
                'description' => 'delete_the_selected_form_submission',
            ],
            [
                'name' => 'process-submissions',
                'display_name' => 'process_submissions',
                'group' => $groups['form_submissions'],
                'description' => 'list_of_users_who_can_manage_the_submission_process',
            ],
            [
                'name' => 'manage-workflow-processes',
                'display_name' => 'manage_workflow_processes',
                'group' => $groups['workflow_processes'],
                'description' => 'manage_workflow_process_for_the_form',
            ],
            [
                'name' => 'browse-settings',
                'display_name' => 'browse_settings',
                'group' => $groups['settings'],
                'description' => 'list_of_all_setting',
            ],
            [
                'name' => 'edit-settings',
                'display_name' => 'edit_settings',
                'group' => $groups['settings'],
                'description' => 'allows_managing_and_updating_system_settings',
            ],

            /* NEXCITY PERMISSIONS */

            // organizations
            [
                'name' => 'browse-organizations',
                'display_name' => 'browse_organizations',
                'group' => $groups['organizations'],
                'description' => 'list_of_all_organizations',
            ],
            [
                'name' => 'read-organizations',
                'display_name' => 'read_organizations',
                'group' => $groups['organizations'],
                'description' => 'read_selected_organization',
            ],
            [
                'name' => 'edit-organizations',
                'display_name' => 'edit_organizations',
                'group' => $groups['organizations'],
                'description' => 'edit_selected_organization',
            ],
            [
                'name' => 'add-organizations',
                'display_name' => 'add_organizations',
                'group' => $groups['organizations'],
                'description' => 'add_new_organization',
            ],
            [
                'name' => 'delete-organizations',
                'display_name' => 'delete_organizations',
                'group' => $groups['organizations'],
                'description' => 'delete_selected_organization',
            ],

            // residents
            [
                'name' => 'browse-resident-dues',
                'display_name' => 'browse_resident_dues',
                'group' => $groups['resident_dues'],
                'scope' => 'partner',
                'context' => 'resident',
                'description' => 'browse_resident_dues',
            ],
            [
                'name' => 'browse-resident-informations',
                'display_name' => 'browse_resident_informations',
                'group' => $groups['resident_informations'],
                'scope' => 'partner',
                'context' => 'resident',
                'description' => 'browse_resident_informations',
            ],
            [
                'name' => 'browse-resident-complaints',
                'display_name' => 'browse_resident_complaints',
                'group' => $groups['resident_complaints'],
                'scope' => 'partner',
                'context' => 'resident',
                'description' => 'browse_resident_complaints',
            ],
            [
                'name' => 'browse-resident-events',
                'display_name' => 'browse_resident_events',
                'group' => $groups['resident_events'],
                'scope' => 'partner',
                'context' => 'resident',
                'description' => 'browse_resident_events',
            ],
            [
                'name' => 'scan-resident-qr',
                'display_name' => 'scan_resident_qr',
                'group' => $groups['rt_population'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'scan_resident_qr_or_barcode',
            ],
            [
                'name' => 'browse-rt-residents',
                'display_name' => 'browse_rt_residents',
                'group' => $groups['rt_population'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_residents',
            ],
            [
                'name' => 'read-rt-residents',
                'display_name' => 'read_rt_residents',
                'group' => $groups['rt_population'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'read_rt_residents',
            ],
            [
                'name' => 'edit-rt-residents',
                'display_name' => 'edit_rt_residents',
                'group' => $groups['rt_population'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'edit_rt_residents',
            ],
            [
                'name' => 'add-rt-residents',
                'display_name' => 'add_rt_residents',
                'group' => $groups['rt_population'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'add_rt_residents',
            ],
            [
                'name' => 'delete-rt-residents',
                'display_name' => 'delete_rt_residents',
                'group' => $groups['rt_population'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'delete_rt_residents',
            ],
            [
                'name' => 'browse-rt-dues',
                'display_name' => 'browse_rt_dues',
                'group' => $groups['rt_dues'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_dues',
            ],
            [
                'name' => 'read-rt-dues',
                'display_name' => 'read_rt_dues',
                'group' => $groups['rt_dues'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'read_rt_dues',
            ],
            [
                'name' => 'edit-rt-dues',
                'display_name' => 'edit_rt_dues',
                'group' => $groups['rt_dues'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'edit_rt_dues',
            ],
            [
                'name' => 'add-rt-dues',
                'display_name' => 'add_rt_dues',
                'group' => $groups['rt_dues'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'add_rt_dues',
            ],
            [
                'name' => 'delete-rt-dues',
                'display_name' => 'delete_rt_dues',
                'group' => $groups['rt_dues'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'delete_rt_dues',
            ],
            [
                'name' => 'browse-rt-reports',
                'display_name' => 'browse_rt_reports',
                'group' => $groups['rt_reports'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_reports',
            ],
            [
                'name' => 'browse-mosque-charity-reports',
                'display_name' => 'browse_mosque_charity_reports',
                'group' => $groups['mosque_reports'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_charity_reports',
            ],
            [
                'name' => 'browse-mosque-distribution-reports',
                'display_name' => 'browse_mosque_distribution_reports',
                'group' => $groups['mosque_reports'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_distribution_reports',
            ],
            [
                'name' => 'browse-rt-events',
                'display_name' => 'browse_rt_events',
                'group' => $groups['rt_events'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_events',
            ],
            [
                'name' => 'browse-rt-news',
                'display_name' => 'browse_rt_news',
                'group' => $groups['rt_news'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_news',
            ],
            [
                'name' => 'browse-rt-feedback',
                'display_name' => 'browse_rt_feedback',
                'group' => $groups['rt_feedback'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_feedback',
            ],
            [
                'name' => 'browse-rt-inventory',
                'display_name' => 'browse_rt_inventory',
                'group' => $groups['rt_inventory'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_inventory',
            ],
            [
                'name' => 'browse-rt-membership',
                'display_name' => 'browse_rt_membership',
                'group' => $groups['rt_membership'],
                'scope' => 'partner',
                'context' => 'rt',
                'description' => 'browse_rt_membership',
            ],
            [
                'name' => 'browse-mosque-charity-transactions',
                'display_name' => 'browse_mosque_charity_transactions',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_charity_transactions',
            ],
            [
                'name' => 'read-mosque-charity-transactions',
                'display_name' => 'read_mosque_charity_transactions',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_mosque_charity_transactions',
            ],
            [
                'name' => 'edit-mosque-charity-transactions',
                'display_name' => 'edit_mosque_charity_transactions',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_mosque_charity_transactions',
            ],
            [
                'name' => 'add-mosque-charity-transactions',
                'display_name' => 'add_mosque_charity_transactions',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_mosque_charity_transactions',
            ],
            [
                'name' => 'delete-mosque-charity-transactions',
                'display_name' => 'delete_mosque_charity_transactions',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_mosque_charity_transactions',
            ],
            [
                'name' => 'browse-mosque-charity-types',
                'display_name' => 'browse_mosque_charity_types',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_charity_types',
            ],
            [
                'name' => 'read-mosque-charity-types',
                'display_name' => 'read_mosque_charity_types',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_mosque_charity_types',
            ],
            [
                'name' => 'edit-mosque-charity-types',
                'display_name' => 'edit_mosque_charity_types',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_mosque_charity_types',
            ],
            [
                'name' => 'add-mosque-charity-types',
                'display_name' => 'add_mosque_charity_types',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_mosque_charity_types',
            ],
            [
                'name' => 'delete-mosque-charity-types',
                'display_name' => 'delete_mosque_charity_types',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_mosque_charity_types',
            ],
            [
                'name' => 'browse-mosque-charity-type-sources',
                'display_name' => 'browse_mosque_charity_type_sources',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_charity_type_sources',
            ],
            [
                'name' => 'read-mosque-charity-type-sources',
                'display_name' => 'read_mosque_charity_type_sources',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_mosque_charity_type_sources',
            ],
            [
                'name' => 'edit-mosque-charity-type-sources',
                'display_name' => 'edit_mosque_charity_type_sources',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_mosque_charity_type_sources',
            ],
            [
                'name' => 'add-mosque-charity-type-sources',
                'display_name' => 'add_mosque_charity_type_sources',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_mosque_charity_type_sources',
            ],
            [
                'name' => 'delete-mosque-charity-type-sources',
                'display_name' => 'delete_mosque_charity_type_sources',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_mosque_charity_type_sources',
            ],
            [
                'name' => 'browse-mosque-charity-payments',
                'display_name' => 'browse_mosque_charity_payments',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_charity_payments',
            ],
            [
                'name' => 'read-mosque-charity-payments',
                'display_name' => 'read_mosque_charity_payments',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_mosque_charity_payments',
            ],
            [
                'name' => 'edit-mosque-charity-payments',
                'display_name' => 'edit_mosque_charity_payments',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_mosque_charity_payments',
            ],
            [
                'name' => 'add-mosque-charity-payments',
                'display_name' => 'add_mosque_charity_payments',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_mosque_charity_payments',
            ],
            [
                'name' => 'delete-mosque-charity-payments',
                'display_name' => 'delete_mosque_charity_payments',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_mosque_charity_payments',
            ],
            [
                'name' => 'browse-qurban',
                'display_name' => 'browse_qurban',
                'group' => $groups['mosque_qurban'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_qurban',
            ],
            [
                'name' => 'read-qurban',
                'display_name' => 'read_qurban',
                'group' => $groups['mosque_qurban'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_qurban',
            ],
            [
                'name' => 'edit-qurban',
                'display_name' => 'edit_qurban',
                'group' => $groups['mosque_qurban'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_qurban',
            ],
            [
                'name' => 'add-qurban',
                'display_name' => 'add_qurban',
                'group' => $groups['mosque_qurban'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_qurban',
            ],
            [
                'name' => 'delete-qurban',
                'display_name' => 'delete_qurban',
                'group' => $groups['mosque_qurban'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_qurban',
            ],
            [
                'name' => 'browse-mosque-distributions',
                'display_name' => 'browse_mosque_distributions',
                'group' => $groups['mosque_distributions'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_distributions',
            ],
            [
                'name' => 'read-mosque-distributions',
                'display_name' => 'read_mosque_distributions',
                'group' => $groups['mosque_distributions'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_mosque_distributions',
            ],
            [
                'name' => 'edit-mosque-distributions',
                'display_name' => 'edit_mosque_distributions',
                'group' => $groups['mosque_distributions'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_mosque_distributions',
            ],
            [
                'name' => 'add-mosque-distributions',
                'display_name' => 'add_mosque_distributions',
                'group' => $groups['mosque_distributions'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_mosque_distributions',
            ],
            [
                'name' => 'delete-mosque-distributions',
                'display_name' => 'delete_mosque_distributions',
                'group' => $groups['mosque_distributions'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_mosque_distributions',
            ],
            [
                'name' => 'browse-mosque-inventory',
                'display_name' => 'browse_mosque_inventory',
                'group' => $groups['mosque_inventory'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_inventory',
            ],
            [
                'name' => 'read-mosque-inventory',
                'display_name' => 'read_mosque_inventory',
                'group' => $groups['mosque_inventory'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_mosque_inventory',
            ],
            [
                'name' => 'edit-mosque-inventory',
                'display_name' => 'edit_mosque_inventory',
                'group' => $groups['mosque_inventory'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_mosque_inventory',
            ],
            [
                'name' => 'add-mosque-inventory',
                'display_name' => 'add_mosque_inventory',
                'group' => $groups['mosque_inventory'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_mosque_inventory',
            ],
            [
                'name' => 'delete-mosque-inventory',
                'display_name' => 'delete_mosque_inventory',
                'group' => $groups['mosque_inventory'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_mosque_inventory',
            ],
            [
                'name' => 'browse-mosque-crm',
                'display_name' => 'browse_mosque_crm',
                'group' => $groups['mosque_crm'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'browse_mosque_crm',
            ],
            [
                'name' => 'read-mosque-crm',
                'display_name' => 'read_mosque_crm',
                'group' => $groups['mosque_crm'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'read_mosque_crm',
            ],
            [
                'name' => 'edit-mosque-crm',
                'display_name' => 'edit_mosque_crm',
                'group' => $groups['mosque_crm'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'edit_mosque_crm',
            ],
            [
                'name' => 'add-mosque-crm',
                'display_name' => 'add_mosque_crm',
                'group' => $groups['mosque_crm'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'add_mosque_crm',
            ],
            [
                'name' => 'delete-mosque-crm',
                'display_name' => 'delete_mosque_crm',
                'group' => $groups['mosque_crm'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'delete_mosque_crm',
            ],
            [
                'name' => 'scan-qurban-coupon',
                'display_name' => 'scan_qurban_coupon',
                'group' => $groups['mosque_qurban'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'scan_qurban_coupon',
            ],
            [
                'name' => 'scan-zakat-coupon',
                'display_name' => 'scan_zakat_coupon',
                'group' => $groups['mosque_charity'],
                'scope' => 'partner',
                'context' => 'mosque',
                'description' => 'scan_zakat_coupon',
            ],

            // menus
            [
                'name' => 'browse-user-menus',
                'display_name' => 'browse_user_menus',
                'group' => $groups['menus'],
                'description' => 'list_of_all_menu_entries',
            ],
            [
                'name' => 'edit-user-menus',
                'display_name' => 'edit_user_menus',
                'group' => $groups['menus'],
                'description' => 'edit_and_reorder_menu_entries',
            ],
        ]);
    }
}
