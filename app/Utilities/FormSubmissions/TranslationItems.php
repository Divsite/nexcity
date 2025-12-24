<?php

namespace App\Utilities\FormSubmissions;

class TranslationItems
{
    public static function get(): array
    {
        return [
            'drag_and_drop_your_files_or_browse' => __('messages.drag_and_drop_your_files_or_browse'),
            'error_during_remove' => __('messages.error_during_remove'),
            'error_during_upload' => __('messages.error_during_upload'),
            'expects' => __('messages.expects'),
            'file_is_too_large' => __('messages.file_is_too_large'),
            'file_of_invalid_type' => __('messages.file_of_invalid_type'),
            'label_key' => __('messages.label_key'),
            'loading' => __('messages.loading'),
            'maximum_file_size_is' => __('messages.maximum_file_size_is'),
            'next' => __('messages.next'),
            'please_wait_a_moment' => __('messages.please_wait_a_moment'),
            'previous' => __('messages.previous'),
            'start_building_your_form_its_fast_easy_and_fun' => __('messages.start_building_your_form_its_fast_easy_and_fun'),
            'status_code' => __('messages.status_code'),
            'submit' => __('messages.submit'),
            'tap_to_cancel' => __('messages.tap_to_cancel'),
            'tap_to_retry' => __('messages.tap_to_retry'),
            'tap_to_undo' => __('messages.tap_to_undo'),
            'thank_you' => __('messages.thank_you'),
            'upload_cancelled' => __('messages.upload_cancelled'),
            'upload_complete' => __('messages.upload_complete'),
            'uploading' => __('messages.uploading'),
            'url' => __('messages.url'),
            'value_and_label_key_not_found_in_the_list' => __('messages.value_and_label_key_not_found_in_the_list'),
            'value_key' => __('messages.value_key'),
            'value_key_not_found_in_the_data' => __('messages.value_key_not_found_in_the_data'),
            'your_form_has_been_successfully_submitted' => __('messages.your_form_has_been_successfully_submitted'),
        ];
    }
}
