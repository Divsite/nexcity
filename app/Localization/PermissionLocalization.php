<?php

namespace App\Localization;

class PermissionLocalization
{
    public static function displayName()
    {
        return collect([
            'my_account' => __('messages.my_account'),
            'change_password' => __('messages.change_password'),
            'change_email' => __('messages.change_email'),
            'change_username' => __('messages.change_username'),
            'my_activities' => __('messages.my_activities'),
            'recent_sessions' => __('messages.recent_sessions'),
            'all_user_activities' => __('messages.all_user_activities'),
            'all_user_sessions' => __('messages.all_user_sessions'),
            'browse_roles' => __('messages.browse_roles'),
            'read_roles' => __('messages.read_roles'),
            'edit_roles' => __('messages.edit_roles'),
            'add_roles' => __('messages.add_roles'),
            'delete_roles' => __('messages.delete_roles'),
            'browse_permissions' => __('messages.browse_permissions'),
            'read_permissions' => __('messages.read_permissions'),
            'browse_users' => __('messages.browse_users'),
            'read_users' => __('messages.read_users'),
            'edit_users' => __('messages.edit_users'),
            'add_users' => __('messages.add_users'),
            'delete_users' => __('messages.delete_users'),
            'browse_forms' => __('messages.browse_forms'),
            'read_forms' => __('messages.read_forms'),
            'edit_forms' => __('messages.edit_forms'),
            'add_forms' => __('messages.add_forms'),
            'delete_forms' => __('messages.delete_forms'),
            'browse_groups' => __('messages.browse_groups'),
            'read_groups' => __('messages.read_groups'),
            'edit_groups' => __('messages.edit_groups'),
            'add_groups' => __('messages.add_groups'),
            'delete_groups' => __('messages.delete_groups'),
            'browse_form_types' => __('messages.browse_form_types'),
            'read_form_types' => __('messages.read_form_types'),
            'edit_form_types' => __('messages.edit_form_types'),
            'add_form_types' => __('messages.add_form_types'),
            'delete_form_types' => __('messages.delete_form_types'),
            'browse_notifications' => __('messages.browse_notifications'),
            'read_notifications' => __('messages.read_notifications'),
            'edit_notifications' => __('messages.edit_notifications'),
            'delete_notifications' => __('messages.delete_notifications'),
        ]);
    }

    public static function description()
    {
        return collect([
            'view_profile_information' => __('messages.view_profile_information'),
            'change_user_password' => __('messages.change_user_password'),
            'change_user_email' => __('messages.change_user_email'),
            'change_current_username' => __('messages.change_current_username'),
            'view_all_current_user_activity' => __('messages.view_all_current_user_activity'),
            'view_all_recent_sessions_for_the_current_user' => __('messages.view_all_recent_sessions_for_the_current_user'),
            'list_of_all_user_activities' => __('messages.list_of_all_user_activities'),
            'list_of_all_user_sessions' => __('messages.list_of_all_user_sessions'),
            'list_of_all_role' => __('messages.list_of_all_role'),
            'read_the_selected_role' => __('messages.read_the_selected_role'),
            'edit_the_selected_role' => __('messages.edit_the_selected_role'),
            'add_specific_role' => __('messages.add_specific_role'),
            'delete_the_selected_role' => __('messages.delete_the_selected_role'),
            'list_of_all_permission' => __('messages.list_of_all_permission'),
            'read_the_selected_permission' => __('messages.read_the_selected_permission'),
            'list_of_all_user' => __('messages.list_of_all_user'),
            'read_the_selected_user' => __('messages.read_the_selected_user'),
            'edit_account_for_selected_user' => __('messages.edit_account_for_selected_user'),
            'add_account_for_user' => __('messages.add_account_for_user'),
            'delete_account_for_selected_user' => __('messages.delete_account_for_selected_user'),
            'list_of_all_form' => __('messages.list_of_all_form'),
            'read_the_selected_form' => __('messages.read_the_selected_form'),
            'edit_the_selected_form' => __('messages.edit_the_selected_form'),
            'add_new_form' => __('messages.add_new_form'),
            'delete_the_selected_form' => __('messages.delete_the_selected_form'),
            'list_of_all_group' => __('messages.list_of_all_group'),
            'read_the_selected_group' => __('messages.read_the_selected_group'),
            'edit_the_selected_group' => __('messages.edit_the_selected_group'),
            'add_new_group' => __('messages.add_new_group'),
            'delete_the_selected_group' => __('messages.delete_the_selected_group'),
            'list_of_all_form_type' => __('messages.list_of_all_form_type'),
            'read_the_selected_form_type' => __('messages.read_the_selected_form_type'),
            'edit_the_selected_form_type' => __('messages.edit_the_selected_form_type'),
            'add_new_form_type' => __('messages.add_new_form_type'),
            'delete_the_selected_form_type' => __('messages.delete_the_selected_form_type'),
            'list_of_all_notification' => __('messages.list_of_all_notification'),
            'read_the_selected_notification' => __('messages.read_the_selected_notification'),
            'edit_the_selected_notification' => __('messages.edit_the_selected_notification'),
            'delete_the_selected_notification' => __('messages.delete_the_selected_notification'),
        ]);
    }
}

