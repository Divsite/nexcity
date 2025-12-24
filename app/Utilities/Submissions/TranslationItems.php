<?php

namespace App\Utilities\Submissions;

class TranslationItems
{
    public static function get(): array
    {
        return [
            "additional_info" => __('messages.additional_info'),
            "comment" => __('messages.comment'),
            "created_at" => __('messages.created_at'),
            "created_by" => __('messages.created_by'),
            "edit" => __('messages.edit'),
            "id" => __('messages.id'),
            "latest_status" => __('messages.latest_status'),
            "loading" => __('messages.loading'),
            "please_select" => __('messages.please_select'),
            "process" => __('messages.process'),
            "process_list" => __('messages.process_list'),
            "save" => __('messages.save'),
            "status" => __('messages.status'),
            "status_list" => __('messages.status_list'),
            "submission_information" => __('messages.submission_information'),
            "updated_at" => __('messages.updated_at'),
            "updated_by" => __('messages.updated_by'),
            "view" => __('messages.view'),
        ];
    }
}
