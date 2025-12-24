<?php

namespace App\Utilities\FormProcesses;

class TranslationItems
{
    public static function get(): array
    {
        return [
            "actions" => __('messages.actions'),
            "add" => __('messages.add'),
            "add_process" => __('messages.add_process'),
            "add_process_to_use_workflow_process" => __('messages.add_process_to_use_workflow_process'),
            "add_status" => __('messages.add_status'),
            "close" => __('messages.close'),
            "comment" => __('messages.comment'),
            "decision_maker" => __('messages.decision_maker'),
            "default" => __('messages.default'),
            "default_submission_status" => __('messages.default_submission_status'),
            "delete" => __('messages.delete'),
            "edit" => __('messages.edit'),
            "edit_process" => __('messages.edit_process'),
            "edit_status" => __('messages.edit_status'),
            "email" => __('messages.email'),
            "enabled" => __('messages.enabled'),
            "end_process" => __('messages.end_process'),
            "general" => __('messages.general'),
            "loading" => __('messages.loading'),
            "name" => __('messages.name'),
            "next" => __('messages.next'),
            "no_items_found" => __('messages.no_items_found'),
            "no_process_found" => __('messages.no_process_found'),
            "off_sort" => __('messages.off_sort'),
            "on_sort" => __('messages.on_sort'),
            "percentage" => __('messages.percentage'),
            "person_in_charge" => __('messages.person_in_charge'),
            "please_select" => __('messages.please_select'),
            "please_set_your_default_status_to_use_the_workflow_processes" => __('messages.please_set_your_default_status_to_use_the_workflow_processes'),
            "previous_btn" => __('messages.previous_btn'),
            "process" => __('messages.process'),
            "process_builder" => __('messages.process_builder'),
            "processor_roles" => __('messages.processor_roles'),
            "processor_users" => __('messages.processor_users'),
            "required" => __('messages.required'),
            "save" => __('messages.save'),
            "search_user" => __('messages.search_user'),
            "set_as_default_status" => __('messages.set_as_default_status'),
            "status" => __('messages.status'),
            "the_decision_of_the_person_in_charge_will_be_taken_for_the_next_process_action" => __('messages.the_decision_of_the_person_in_charge_will_be_taken_for_the_next_process_action'),
            "user" => __('messages.user'),
            "username" => __('messages.username'),
        ];
    }
}
