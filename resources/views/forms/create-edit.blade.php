@extends('layouts.app')

@isset($model)
    @section('title', __('messages.edit_forms'))
    @section('breadcrumbs', Breadcrumbs::render('forms.edit', $model))
@else
    @section('title', __('messages.create_forms'))
    @section('breadcrumbs', Breadcrumbs::render('forms.create'))
@endisset

@section('content')
    <div v-cloak id="form-builder">
        <form-builder></form-builder>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        let formTypes = {{ \Illuminate\Support\Js::from($formTypes) }};
        let model = null;

        @if(!empty($model))
            model = {{ \Illuminate\Support\Js::from($model) }}
        @endif
    </script>
    <script>
        let formFields = {{ \Illuminate\Support\Js::from(form_fields()) }};
        let formTypeNames = {{ \Illuminate\Support\Js::from(form_type_names()) }};
        let formFieldsIcon = {{ \Illuminate\Support\Js::from(form_fields_with_icon()) }};
        let headingList = {{ \Illuminate\Support\Js::from(heading_list()) }};
        let fieldWidth = {{ \Illuminate\Support\Js::from(field_width()) }};
        let defaultOperator = '{{ conditional_rule_operator()['all'] }}';
        let operatorName = {{ \Illuminate\Support\Js::from(operator_name()) }};
        let comparisonOperator = {{ \Illuminate\Support\Js::from(comparison_operator()) }};
        let comparisonOperatorDetails = {{ \Illuminate\Support\Js::from(comparison_operator_details()) }};
        let translatedActionTypes = {{ \Illuminate\Support\Js::from(translated_action_types()) }};
        let conditionalRuleOperator = {{ \Illuminate\Support\Js::from(conditional_rule_operator()) }};
        let fieldActions = {{ \Illuminate\Support\Js::from(field_actions()) }};
        let actionTypes = {{ \Illuminate\Support\Js::from(action_types()) }};
        let thicknessList = {{ \Illuminate\Support\Js::from(thickness_list())}};
        let appUrl = "{{ config('app.url') }}";
        let dataSourceInput = {{ \Illuminate\Support\Js::from(data_source_input())}};
        let dataSourceInputNames = {{ \Illuminate\Support\Js::from(data_source_input_names())}};
        let userColumnNames = {{ \Illuminate\Support\Js::from(user_column_names())}};
        let userInfo = {{ \Illuminate\Support\Js::from(user_info())}};
        let currencies = {{ \Illuminate\Support\Js::from($currencies)}};
        let dateFormat = {{ \Illuminate\Support\Js::from(date_format_list())}};
        let flatpickrLocale = {{ \Illuminate\Support\Js::from(flatpickr_locale()) }};
        let minMaxOptionList = {{ \Illuminate\Support\Js::from(min_max_option_list()) }};
        let minMaxOptions = {{ \Illuminate\Support\Js::from(min_max_options()) }};
        let minMaxNumberDays = {{ \Illuminate\Support\Js::from(min_max_number_days()) }};
        let commonSettingsFormBuilder = {{ \Illuminate\Support\Js::from(common_settings_form_builder()) }};
        <!-- Translation -->
        let trans = {
            'accept_file_support': '{{ __('messages.accept_file_support') }}',
            'action': '{{ __('messages.action') }}',
            'action_triggerred_when_all_conditions_are_met': '{{ __('messages.action_triggerred_when_all_conditions_are_met') }}',
            'action_triggerred_when_any_conditions_are_met': '{{ __('messages.action_triggerred_when_any_conditions_are_met') }}',
            'actions': '{{ __('messages.actions') }}',
            'active': '{{ __('messages.active')}}',
            'add_action': '{{ __('messages.add_action') }}',
            'add_condition': '{{ __('messages.add_condition') }}',
            'add_field': '{{ __('messages.add_field') }}',
            'add_group': '{{ __('messages.add_group') }}',
            'add_input_field_to_use_conditional_rules': '{{ __('messages.add_input_field_to_use_conditional_rules') }}',
            'add_option': '{{ __('messages.add_option') }}',
            'add_rule': '{{ __('messages.add_rule') }}',
            'add_text_to_input_during_submission': '{{ __('messages.add_text_to_input_during_submission') }}',
            'allowed_file_types': '{{ __('messages.allowed_file_types') }}',
            'always_show_character_limit': '{{ __('messages.always_show_character_limit') }}',
            'are_you_sure_you_want_to_delete_this_field': '{{ __('messages.are_you_sure_you_want_to_delete_this_field') }}',
            'auto_decimal_digits': '{{ __('messages.auto_decimal_digits') }}',
            'change_checkboxes_to_outlined_styles': '{{ __('messages.change_checkboxes_to_outlined_styles') }}',
            'change_radio_buttons_to_outlined_styles': '{{ __('messages.change_radio_buttons_to_outlined_styles') }}',
            'close': '{{ __('messages.close') }}',
            'color': '{{ __('messages.color')}}',
            'column_name': '{{ __('messages.column_name') }}',
            'comma_separated_values_leave_blank_to_allow_all_file_types': '{{ __('messages.comma_separated_values_leave_blank_to_allow_all_file_types') }}',
            'compare': '{{ __('messages.compare') }}',
            'condition_type': '{{ __('messages.condition_type') }}',
            'conditional_rules': '{{ __('messages.conditional_rules') }}',
            'conditions': '{{ __('messages.conditions') }}',
            'currency': '{{ __('messages.currency') }}',
            'data_source': '{{ __('messages.data_source') }}',
            'date_format': '{{ __('messages.date_format') }}',
            'dates_before_today_cannot_be_selected': '{{ __('messages.dates_before_today_cannot_be_selected') }}',
            'delete': '{{ __('messages.delete') }}',
            'disable_past_dates': '{{ __('messages.disable_past_dates') }}',
            'disabled': '{{ __('messages.disabled') }}',
            'disabled_this_field': '{{ __('messages.disabled_this_field') }}',
            'disabling_dates': '{{ __('messages.disabling_dates') }}',
            'display_input_group_text': '{{ __('messages.display_input_group_text') }}',
            'displays_the_calendar_inline': '{{ __('messages.displays_the_calendar_inline') }}',
            'displays_time_picker_in_24_hour_mode_without_am_pm_selection_when_enabled': '{{ __('messages.displays_time_picker_in_24_hour_mode_without_am_pm_selection_when_enabled') }}',
            'drag_and_drop_your_files_or_browse': "{!! __('messages.drag_and_drop_your_files_or_browse') !!}",
            'duplicate': '{{ __('messages.duplicate') }}',
            'enables_display_of_week_numbers_in_calendar': '{{ __('messages.enables_display_of_week_numbers_in_calendar') }}',
            'enables_seconds_in_the_time_picker': '{{ __('messages.enables_seconds_in_the_time_picker') }}',
            'enhance_input_by_adding_text_in_front_of_or_behind_the_input_field': '{{ __('messages.enhance_input_by_adding_text_in_front_of_or_behind_the_input_field') }}',
            'error_during_remove': '{{ __('messages.error_during_remove') }}',
            'error_during_upload': '{{ __('messages.error_during_upload') }}',
            'exclude_this_field': '{{ __('messages.exclude_this_field') }}',
            'expects': '{{ __('messages.expects') }}',
            'field': '{{ __('messages.field') }}',
            'field_name': '{{ __('messages.field_name') }}',
            'field_size': '{{ __('messages.field_size') }}',
            'file_is_too_large': '{{ __('messages.file_is_too_large') }}',
            'file_of_invalid_type': '{{ __('messages.file_of_invalid_type') }}',
            'form_name': '{{ __('messages.form_name') }}',
            'form_structure': '{{ __('messages.form_structure') }}',
            'form_type': '{{ __('messages.form_type') }}',
            'general': '{{ __('messages.general') }}',
            'heading': '{{ __('messages.heading') }}',
            'help_text': '{{ __('messages.help_text') }}',
            'hidden': '{{ __('messages.hidden') }}',
            'hidden_field': '{{ __('messages.hidden_field') }}',
            'hide_currency_symbol': '{{ __('messages.hide_currency_symbol') }}',
            'horizontal': '{{ __('messages.horizontal') }}',
            'html_content': '{{ __('messages.html_content') }}',
            'if_you_do_any_data_associated_with_this_field_will_be_deleted_too_if_this_form_has_at_least_one_form_submission_you_should_export_your_data_first': '{{ __('messages.if_you_do_any_data_associated_with_this_field_will_be_deleted_too_if_this_form_has_at_least_one_form_submission_you_should_export_your_data_first') }}',
            'information': '{{ __('messages.information') }}',
            'inline': '{{ __('messages.inline') }}',
            'input_group': '{{ __('messages.input_group') }}',
            'integer_pattern': '{{ __('messages.integer_pattern') }}',
            'integration': '{{ __('messages.integration') }}',
            'label': '{{ __('messages.label') }}',
            'label_key': '{{ __('messages.label_key') }}',
            'leave_blank_if_the_data_is_returned_as_a_string': '{{ __('messages.leave_blank_if_the_data_is_returned_as_a_string') }}',
            'left_text': '{{ __('messages.left_text') }}',
            'list_of_iso_4217_currency_codes': '{{ __('messages.list_of_iso_4217_currency_codes') }}',
            'loading': '{{ __('messages.loading') }}',
            'make_checkboxes_horizontally_in_a_single_row': '{{ __('messages.make_checkboxes_horizontally_in_a_single_row') }}',
            'make_radio_buttons_horizontally_in_a_single_row': '{{ __('messages.make_radio_buttons_horizontally_in_a_single_row') }}',
            'make_this_field_required': '{{ __('messages.make_this_field_required') }}',
            'max_character_limit': '{{ __('messages.max_character_limit') }}',
            'max_date': '{{ __('messages.max_date') }}',
            'max_number': '{{ __('messages.max_number') }}',
            'max_number_days': '{{ __('messages.max_number_days') }}',
            'max_time': '{{ __('messages.max_time') }}',
            'maximum_character_limit_of_2000': '{{ __('messages.maximum_character_limit_of_2000') }}',
            'maximum_file_size': '{{ __('messages.maximum_file_size') }}',
            'maximum_file_size_is': '{{ __('messages.maximum_file_size_is') }}',
            'maximum_files': '{{ __('messages.maximum_files') }}',
            'min_date': '{{ __('messages.min_date') }}',
            'min_number': '{{ __('messages.min_number') }}',
            'min_number_days': '{{ __('messages.min_number_days') }}',
            'min_time': '{{ __('messages.min_time') }}',
            'minimum_and_maximum_date_options': '{{ __('messages.minimum_and_maximum_date_options') }}',
            'multiple_files': '{{ __('messages.multiple_files') }}',
            'name': '{{ __('messages.name') }}',
            'next': '{{ __('messages.next') }}',
            'no_conditions': '{{ __('messages.no_conditions') }}',
            'no_fields_created': '{{ __('messages.no_fields_created') }}',
            'no_input_fields': '{{ __('messages.no_input_fields') }}',
            'number_pattern': '{{ __('messages.number_pattern') }}',
            'options': '{{ __('messages.options') }}',
            'outline': '{{ __('messages.outline') }}',
            'page_break': '{{ __('messages.page_break') }}',
            'phone': '{{ __('messages.phone') }}',
            'placeholder': '{{ __('messages.placeholder') }}',
            'please_select': '{{ __('messages.please_select') }}',
            'please_wait_a_moment': '{{ __('messages.please_wait_a_moment') }}',
            'precision': '{{ __('messages.precision') }}',
            'prefill_from_value': '{{ __('messages.prefill_from_value') }}',
            'prefill_to_value': '{{ __('messages.prefill_to_value') }}',
            'prefill_value': '{{ __('messages.prefill_value') }}',
            'preview': '{{ __('messages.preview') }}',
            'previous': '{{ __('messages.previous') }}',
            'references': '{{ __('messages.references') }}',
            'required': '{{ __('messages.required') }}',
            'right_text': '{{ __('messages.right_text') }}',
            'rules': '{{ __('messages.rules') }}',
            'save': '{{ __('messages.save') }}',
            'save_changes': '{{ __('messages.save_changes') }}',
            'seconds': '{{ __('messages.seconds') }}',
            'start_building_your_form_its_fast_easy_and_fun': "{!! __('messages.start_building_your_form_its_fast_easy_and_fun') !!}",
            'start_by_adding_some_conditions_and_then_add_some_actions': '{{ __('messages.start_by_adding_some_conditions_and_then_add_some_actions') }}',
            'status_code': '{{ __('messages.status_code') }}',
            'step_number': '{{ __('messages.step_number') }}',
            'style': '{{ __('messages.style') }}',
            'submit': '{{ __('messages.submit') }}',
            'tags': '{{ __('messages.tags') }}',
            'tap_to_cancel': '{{ __('messages.tap_to_cancel') }}',
            'tap_to_retry': '{{ __('messages.tap_to_retry') }}',
            'tap_to_undo': '{{ __('messages.tap_to_undo') }}',
            'text': '{{ __('messages.text') }}',
            'text_of_next_button': '{{ __('messages.text_of_next_button') }}',
            'text_of_previous_button': '{{ __('messages.text_of_previous_button') }}',
            'the_action_list_is_empty': '{{ __('messages.the_action_list_is_empty') }}',
            'the_condition_list_is_empty': '{{ __('messages.the_condition_list_is_empty') }}',
            'the_option_list_is_empty': '{{ __('messages.the_option_list_is_empty') }}',
            'the_rule_list_is_empty': '{{ __('messages.the_rule_list_is_empty') }}',
            'the_type_specification_of_your_form': '{{ __('messages.the_type_specification_of_your_form') }}',
            'thickness': '{{ __('messages.thickness')}}',
            'time_from_label': '{{ __('messages.time_from_label') }}',
            'time_to_label': '{{ __('messages.time_to_label') }}',
            'toggle_switch': '{{ __('messages.toggle_switch')}}',
            'toggle_switch_mode': '{{ __('messages.toggle_switch_mode')}}',
            'tooltip_key': '{{ __('messages.tooltip_key') }}',
            'tooltips': '{{ __('messages.tooltips') }}',
            'tooltips_is_small_pop_up_box_that_appears_when_the_user_moves_the_mouse_pointer_over_an_element': '{{ __('messages.tooltips_is_small_pop_up_box_that_appears_when_the_user_moves_the_mouse_pointer_over_an_element') }}',
            'twenty_four_hour_format': '{{ __('messages.twenty_four_hour_format') }}',
            'untitled_form': '{{ __('messages.untitled_form') }}',
            'upload_cancelled': '{{ __('messages.upload_cancelled') }}',
            'upload_complete': '{{ __('messages.upload_complete') }}',
            'uploading': '{{ __('messages.uploading') }}',
            'url': '{{ __('messages.url') }}',
            'use_current_url': '{{ __('messages.use_current_url') }}',
            'value': '{{ __('messages.value') }}',
            'value_and_label_key_not_found_in_the_list': '{{ __('messages.value_and_label_key_not_found_in_the_list') }}',
            'value_key': '{{ __('messages.value_key') }}',
            'value_key_not_found_in_the_data': '{{ __('messages.value_key_not_found_in_the_data') }}',
            'we_will_post_form_submissions_to_this_url': '{{ __('messages.we_will_post_form_submissions_to_this_url') }}',
            'webhook_url': '{{ __('messages.webhook_url') }}',
            'week_numbers': '{{ __('messages.week_numbers') }}',
            'whether_the_decimal_symbol_is_inserted_automatically_using_the_last_inputted_digits_as_decimal_digits': '{{ __('messages.whether_the_decimal_symbol_is_inserted_automatically_using_the_last_inputted_digits_as_decimal_digits') }}',
            'whether_to_hide_the_currency_symbol_on_focus': '{{ __('messages.whether_to_hide_the_currency_symbol_on_focus') }}',
            'width': '{{ __('messages.width') }}',
            'you_can_upload_more_than_one': '{{ __('messages.you_can_upload_more_than_one') }}',
            'your_help_text_will_be_shown_below_the_field_just_like_this_message': '{{ __('messages.your_help_text_will_be_shown_below_the_field_just_like_this_message') }}',
        };
    </script>
    <!-- vue -->
    @vite('resources/js/views/forms/builder.js')
@endpush
