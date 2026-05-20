<?php

use App\Models\FormSubmissions\FormSubmission;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

if (!function_exists('route_is_active')) {
    function route_is_active(array $patterns): bool
    {
        $current = Route::currentRouteName();
        foreach ($patterns as $p) {
            if (Str::is($p, $current)) return true;
        }
        return false;
    }
}

if (!function_exists('menu_node_active')) {
    function menu_node_active(array $node): bool
    {
        // leaf
        if (!isset($node['children']) || empty($node['children'])) {
            if (isset($node['active_patterns'])) {
                return route_is_active((array)$node['active_patterns']);
            }
            if (isset($node['route'])) {
                return route_is_active([$node['route']]);
            }
            return false;
        }

        foreach ($node['children'] as $child) {
            if (menu_node_active($child)) return true;
        }
        return false;
    }
}

if (!function_exists('menu_can_show')) {
    function menu_can_show(?string $ability): bool
    {
        if (!$ability) return true;
        try {
            return Gate::allows($ability);
        } catch (Throwable $e) {
            return true;
        }
    }
}

if ( ! function_exists('set_active'))
{
    function set_active(string $routeName): ?string
    {
        return Str::startsWith(Route::currentRouteName(), $routeName) ? 'active' : null;
    }
}

if ( ! function_exists('show_menu_dropdown'))
{
    function show_menu_dropdown(string $routeName): ?string
    {
        return Str::startsWith(Route::currentRouteName(), $routeName) ? 'show' : null;
    }
}

if ( ! function_exists('menu_manage'))
{
    function menu_manage($dropdown = false): ?string
    {
        $routeName = [
            'users',
            'roles',
            'internal-roles',
            'permissions',
            'menus',
            'groups',
            'form-types',
        ];

        $value = 'active';

        if ($dropdown) {
            $value = 'show';
        }

        if (Str::startsWith(Route::currentRouteName(), $routeName)) {
            return $value;
        }

        return null;
    }
}

if ( ! function_exists('menu_ss_manage_person'))
{
    function menu_ss_manage_person($dropdown = false): ?string
    {
        $routeName = [
            'abouts',
            'events',
            'articles',
        ];

        $value = 'active';

        if ($dropdown) {
            $value = 'show';
        }

        if (Str::startsWith(Route::currentRouteName(), $routeName)) {
            return $value;
        }

        return null;
    }
}

if ( ! function_exists('menu_ss_manage_work'))
{
    function menu_ss_manage_work($dropdown = false): ?string
    {
        $routeName = [
            'services',
            'projects',
            'testimonies',
            'tools',
        ];

        $value = 'active';

        if ($dropdown) {
            $value = 'show';
        }

        if (Str::startsWith(Route::currentRouteName(), $routeName)) {
            return $value;
        }

        return null;
    }
}

if ( ! function_exists('menu_ss_manage_learn'))
{
    function menu_ss_manage_learn($dropdown = false): ?string
    {
        $routeName = [
            'courses',
            'contents',
            'podcasts',
            'newsletters',
        ];

        $value = 'active';

        if ($dropdown) {
            $value = 'show';
        }

        if (Str::startsWith(Route::currentRouteName(), $routeName)) {
            return $value;
        }

        return null;
    }
}

if ( ! function_exists('to_options'))
{
    function to_options(array $array): array
    {
        return array_merge([
            '' => __('messages.all'),
        ], $array);
    }
}

if ( ! function_exists('greeting'))
{
    function greeting(string $name): string
    {
        $hour = Carbon::now()->format('H');
        if ($hour < 12) {
            return __('messages.good_morning', ['name' => $name]);
        }
        if ($hour < 17) {
            return __('messages.good_afternoon', ['name' => $name]);
        }
        return __('messages.good_evening', ['name' => $name]);
    }
}

if ( ! function_exists('to_boolean')) {

    /**
     * Convert to boolean
     *
     * @param $booleable
     * @return boolean
     */
    function to_boolean($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}


/**
 * All fields on form builder
 */
if ( ! function_exists('form_fields'))
{
    function form_fields(): array
    {
        $fields = [
            "heading",
            "text",
            "email",
            "textarea",
            "hidden",
            "paragraph",
            "url",
            "number",
            "date",
            "page_break",
            "divider",
            "snippet",
            "phone",
            "radio",
            "checkbox",
            "select",
            "file",
            "currency",
            "time",
            "time_range",
            "checkbox_group",
            "date_range",
        ];

        // Return same value for key and value
        return array_combine($fields, $fields);
    }
}

/**
 * Group fields
 */
if ( ! function_exists('group_fields'))
{
    function group_fields(): array
    {
        return [
            "input_field" => __('messages.input_field'),
            "layout_field" => __('messages.layout_field'),
        ];
    }
}

/**
 * Properties for each fields
 */
if ( ! function_exists('form_properties'))
{
    function form_properties(string $field): array
    {
        $formField = form_fields();
        $dataSourceInput = data_source_input();

        $data = [];

        $logic = [
            'enabled' => false,
            'conditions' => null,
            'actions' => null,
        ];

        if ($field == $formField['heading']) {
            $data['hidden'] = false;
            $data['tag'] = 'h3';
            $data['text'] = __('messages.heading');
            $data['logic'] = $logic;
        }

        if ($field == $formField['text']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['data_source'] = $dataSourceInput['text'];
            $data['prefill'] = null;
            $data['column_name'] = null;
            $data['input_group'] = false;
            $data['left_text_input_group'] = null;
            $data['right_text_input_group'] = null;
            $data['display_input_group_text'] = false;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['max_char_limit'] = 2000;
            $data['show_char_limit'] = false;
            $data['url'] = null;
            $data['use_current_url'] = false;
            $data['url_value'] = null;
            $data['logic'] = $logic;
        }

        if ($field == $formField['email']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['data_source'] = $dataSourceInput['text'];
            $data['prefill'] = null;
            $data['column_name'] = null;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['max_char_limit'] = 2000;
            $data['show_char_limit'] = false;
            $data['url'] = null;
            $data['use_current_url'] = false;
            $data['url_value'] = null;
            $data['logic'] = $logic;
        }

        if ($field == $formField['textarea']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['data_source'] = $dataSourceInput['text'];
            $data['prefill'] = null;
            $data['column_name'] = null;
            $data['help'] = null;
            $data['size'] = 3;
            $data['width'] = 'col-md-12';
            $data['max_char_limit'] = 2000;
            $data['show_char_limit'] = false;
            $data['url'] = null;
            $data['use_current_url'] = false;
            $data['url_value'] = null;
            $data['logic'] = $logic;
        }

        if ($field == $formField['hidden']) {
            $data['data_source'] = $dataSourceInput['text'];
            $data['prefill'] = null;
            $data['column_name'] = null;
        }

        if ($field == $formField['paragraph']) {
            $data['hidden'] = false;
            $data['text'] = __('messages.paragraph');
            $data['logic'] = $logic;
        }

        if ($field == $formField['url']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['prefill'] = null;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['max_char_limit'] = 2000;
            $data['show_char_limit'] = false;
            $data['logic'] = $logic;
        }

        if ($field == $formField['number'] ) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['prefill'] = null;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['min_number'] = null;
            $data['max_number'] = null;
            $data['step_number'] = null;
            $data['number_pattern'] = null;
            $data['logic'] = $logic;
        }

        if ($field == $formField['date'] ) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['date_format'] = 'd-m-Y';
            $data['prefill'] = null;
            $data['disable_past_dates'] = false;
            $data['week_numbers'] = false;
            $data['inline'] = false;
            $data['min_max_options'] = null;
            $data['min_date'] = null;
            $data['max_date'] = null;
            $data['min_number_days'] = null;
            $data['max_number_days'] = null;
            $data['disable_dates'] = null;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['logic'] = $logic;
        }

        if ($field == $formField['page_break']) {
            $data['next_btn_text'] = null;
            $data['previous_btn_text'] = null;
        }

        if ($field == $formField['divider']) {
            $data['hidden'] = false;
            $data['thickness'] = '1px';
            $data['color'] = '#ECF0F1';
            $data['logic'] = $logic;
        }

        if ($field == $formField['snippet']) {
            $data['hidden'] = false;
            $data['content'] = __('messages.replace_this_code_tag_with_your_html_snippet');
            $data['logic'] = $logic;
        }

        if ($field == $formField['phone']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['data_source'] = $dataSourceInput['text'];
            $data['prefill'] = null;
            $data['column_name'] = null;
            $data['minlength'] = null;
            $data['maxlength'] = null;
            $data['pattern'] = null;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['url'] = null;
            $data['use_current_url'] = false;
            $data['url_value'] = null;
            $data['logic'] = $logic;
        }

        if ($field == $formField['radio']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['label'] = __('messages.select_a_choice');
            $data['help'] = null;
            $data['horizontal'] = false;
            $data['outline'] = false;
            $data['options'] = [
                [
                    'value' => __('messages.first'),
                    'label' => __('messages.radio_choice', ['number' => 1]),
                    'disabled' => false
                ],
                [
                    'value' => __('messages.second'),
                    'label' => __('messages.radio_choice', ['number' => 2]),
                    'disabled' => false
                ],
                [
                    'value' => __('messages.third'),
                    'label' => __('messages.radio_choice', ['number' => 3]),
                    'disabled' => false
                ]
            ];
            $data['logic'] = $logic;
        }

        if ($field == $formField['checkbox']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.checkbox');
            $data['prefill'] = false;
            $data['help'] = null;
            $data['toggle_switch'] = false;
            $data['width'] = 'col-md-12';
            $data['logic'] = $logic;
        }

        if ($field == $formField['select']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.select');
            $data['placeholder'] = __('messages.please_select');
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['data_source'] = $dataSourceInput['list'];
            $data['options'] = [
                [
                    'value' => __('messages.first'),
                    'label' => __('messages.option_number', ['number' => 1]),
                    'active' => true,
                ],
                [
                    'value' => __('messages.second'),
                    'label' => __('messages.option_number', ['number' => 2]),
                    'active' => true,
                ],
                [
                    'value' => __('messages.third'),
                    'label' => __('messages.option_number', ['number' => 3]),
                    'active' => true,
                ]
            ];
            $data['url'] = null;
            $data['use_current_url'] = false;
            $data['url_value'] = null;
            $data['url_label'] = null;
            $data['logic'] = $logic;
        }

        if ($field == $formField['file']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.attach_a_file');
            $data['accept_files'] = null;
            $data['multiple_files'] = false;
            $data['max_files'] = 3;
            $data['max_file_size'] = 25;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['logic'] = $logic;
        }

        if ($field == $formField['currency']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['prefill'] = null;
            $data['currency'] = 'MYR';
            $data['precision'] = 2;
            $data['min_value'] = null;
            $data['max_value'] = null;
            $data['auto_decimal_digits'] = false;
            $data['hide_currency_symbol_on_focus'] = true;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['logic'] = $logic;
        }

        if ($field == $formField['time']) {
            /**
             * Flatpickr inline not supported at this time for vue3
             */

            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.time');
            $data['prefill'] = null;
            $data['min_time'] = null;
            $data['max_time'] = null;
            $data['time_24hr'] = false;
            $data['enable_seconds'] = false;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['logic'] = $logic;
        }

        if ($field == $formField['time_range']) {
            /**
             * Flatpickr inline not supported at this time for vue3
             */

            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['time_from_label'] = __('messages.time_from');
            $data['time_to_label'] = __('messages.time_to');
            $data['prefill_from'] = null;
            $data['prefill_to'] = null;
            $data['min_time'] = null;
            $data['max_time'] = null;
            $data['time_24hr'] = false;
            $data['enable_seconds'] = false;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['logic'] = $logic;
        }

        if ($field == $formField['checkbox_group']) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['label'] = __('messages.checkbox_group');
            $data['help'] = null;
            $data['horizontal'] = false;
            $data['outline'] = false;
            $data['toggle_switch'] = false;
            $data['data_source'] = $dataSourceInput['list'];
            $data['options'] = [
                [
                    'value' => __('messages.first'),
                    'label' => __('messages.label_number', ['number' => 1]),
                    'tooltip' => null,
                    'disabled' => false,
                ],
                [
                    'value' => __('messages.second'),
                    'label' => __('messages.label_number', ['number' => 2]),
                    'tooltip' => null,
                    'disabled' => false,
                ],
                [
                    'value' => __('messages.third'),
                    'label' => __('messages.label_number', ['number' => 3]),
                    'tooltip' => null,
                    'disabled' => false,
                ]
            ];
            $data['url'] = null;
            $data['use_current_url'] = false;
            $data['url_value'] = null;
            $data['url_label'] = null;
            $data['url_tooltip'] = null;
            $data['logic'] = $logic;
        }

        if ($field == $formField['date_range'] ) {
            $data['hidden'] = false;
            $data['required'] = false;
            $data['disabled'] = false;
            $data['label'] = __('messages.text');
            $data['placeholder'] = null;
            $data['date_format'] = 'd/m/Y';
            $data['prefill'] = null;
            $data['disable_past_dates'] = false;
            $data['week_numbers'] = false;
            $data['inline'] = false;
            $data['min_max_options'] = null;
            $data['min_date'] = null;
            $data['max_date'] = null;
            $data['min_number_days'] = null;
            $data['max_number_days'] = null;
            $data['disable_dates'] = null;
            $data['help'] = null;
            $data['width'] = 'col-md-12';
            $data['logic'] = $logic;
        }

        return $data;
    }
}

/**
 * Fields type name
 */
if ( ! function_exists('form_type_names'))
{
    function form_type_names(): array
    {
        $formField = form_fields();

        return [
            $formField['heading'] => __('messages.heading'),
            $formField['text'] => __('messages.text_field'),
            $formField['email'] => __('messages.email_field'),
            $formField['textarea'] => __('messages.text_area'),
            $formField['hidden'] => __('messages.hidden_field'),
            $formField['paragraph'] => __('messages.paragraph'),
            $formField['url'] => __('messages.url_field'),
            $formField['number'] => __('messages.number_field'),
            $formField['date'] => __('messages.date_field'),
            $formField['page_break'] => __('messages.page_break'),
            $formField['divider'] => __('messages.divider'),
            $formField['snippet'] => __('messages.snippet'),
            $formField['phone'] => __('messages.phone_field'),
            $formField['radio'] => __('messages.radio_buttons'),
            $formField['checkbox'] => __('messages.checkbox'),
            $formField['select'] => __('messages.select'),
            $formField['file'] => __('messages.file_upload'),
            $formField['currency'] => __('messages.currency_field'),
            $formField['time'] => __('messages.time_field'),
            $formField['time_range'] => __('messages.time_range'),
            $formField['checkbox_group'] => __('messages.checkbox_group'),
            $formField['date_range'] => __('messages.date_range'),
        ];
    }
}

/**
 * Fields type with icon (remix icon)
 */
if ( ! function_exists('form_fields_with_icon'))
{
    function form_fields_with_icon(): array
    {
        $formField = form_fields();
        $groupField = group_fields();

        $items = collect([
            [
                'type' => $formField['heading'], 'name' => __('messages.heading'),
                'group' => $groupField['layout_field'], 'icon' => 'ri-heading'
            ],
            [
                'type' => $formField['text'], 'name' => __('messages.text_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-text'
            ],
            [
                'type' => $formField['email'], 'name' => __('messages.email_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-mail-line'
            ],
            [
                'type' => $formField['textarea'], 'name' => __('messages.text_area'),
                'group' => $groupField['input_field'], 'icon' => 'ri-edit-box-line'
            ],
            [
                'type' => $formField['hidden'], 'name' => __('messages.hidden_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-eye-close-line'
            ],
            [
                'type' => $formField['paragraph'], 'name' => __('messages.paragraph'),
                'group' => $groupField['layout_field'], 'icon' => 'ri-align-justify'
            ],
            [
                'type' => $formField['url'], 'name' => __('messages.url_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-links-line'
            ],
            [
                'type' => $formField['number'], 'name' => __('messages.number_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-hashtag'
            ],
            [
                'type' => $formField['date'], 'name' => __('messages.date_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-calendar-line'
            ],
            [
                'type' => $formField['page_break'], 'name' => __('messages.page_break'),
                'group' => $groupField['layout_field'], 'icon' => 'ri-page-separator'
            ],
            [
                'type' => $formField['divider'], 'name' => __('messages.divider'),
                'group' => $groupField['layout_field'], 'icon' => 'ri-subtract-line'
            ],
            [
                'type' => $formField['snippet'], 'name' => __('messages.snippet'),
                'group' => $groupField['layout_field'], 'icon' => 'ri-code-s-slash-line'
            ],
            [
                'type' => $formField['phone'], 'name' => __('messages.phone_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-phone-line'
            ],
            [   'type' => $formField['radio'], 'name' => __('messages.radio_buttons'),
                'group' => $groupField['input_field'],'icon' => 'ri-radio-button-line'
            ],
            [
                'type' => $formField['checkbox'], 'name' => __('messages.checkbox'),
                'group' => $groupField['input_field'], 'icon' => 'ri-checkbox-line'
            ],
            [
                'type' => $formField['select'], 'name' => __('messages.select'),
                'group' => $groupField['input_field'], 'icon' => 'ri-arrow-down-circle-line'
            ],
            [
                'type' => $formField['file'], 'name' => __('messages.file_upload'),
                'group' => $groupField['input_field'], 'icon' => 'ri-upload-cloud-line'
            ],
            [
                'type' => $formField['currency'], 'name' => __('messages.currency_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-currency-line'
            ],
            [
                'type' => $formField['time'], 'name' => __('messages.time_field'),
                'group' => $groupField['input_field'], 'icon' => 'ri-time-line'
            ],
            [
                'type' => $formField['time_range'], 'name' => __('messages.time_range'),
                'group' => $groupField['input_field'], 'icon' => 'ri-history-line'
            ],
            [
                'type' => $formField['checkbox_group'], 'name' => __('messages.checkbox_group'),
                'group' => $groupField['input_field'], 'icon' => 'ri-checkbox-multiple-line'
            ],
            [
                'type' => $formField['date_range'], 'name' => __('messages.date_range'),
                'group' => $groupField['input_field'], 'icon' => 'ri-calendar-todo-line'
            ],
        ]);

        return $items->sortBy('name')->groupBy('group')->all();
    }
}

/**
 * Heading list
 */
if ( ! function_exists('heading_list'))
{
    function heading_list(): array
    {
        return [
            "h1",
            "h2",
            "h3",
            "h4",
            "h5",
            "h6",
        ];
    }
}

/**
 * Field width
 */
if ( ! function_exists('field_width'))
{
    function field_width(): array
    {
        return [
            "col-md-12" => __('messages.full'),
            "col-md-6" => __('messages.1_2_half_width'),
            "col-md-4" => __('messages.1_3_a_third_of_the_width'),
        ];
    }
}

/**
 * Available condition rules operator
 */
if ( ! function_exists('conditional_rule_operator'))
{
    function conditional_rule_operator(): array
    {
        $operators =  [
            "all",
            "any",
        ];

        return array_combine($operators, $operators);
    }
}

/**
 * Translated condition rules operator
 */
if ( ! function_exists('operator_name'))
{
    function operator_name(): array
    {
        $operator = conditional_rule_operator();

        return [
            $operator['all'] => __('messages.all'),
            $operator['any'] => __('messages.any'),
        ];
    }
}

/**
 * Comparison operator
 */
if ( ! function_exists('comparison_operator'))
{
    function comparison_operator(): array
    {
        $compare = [
            // General
            "is_present",
            "is_blank",
            "is",
            "is_not",
            "contains",
            "does_not_contains",
            "starts_with",
            "ends_with",
            // Date
            "is_before",
            "is_after",
            // Single checkbox
            "is_checked",
            "is_not_checked",
            // File
            "has_file_selected",
            "has_no_file_selected",
        ];

        return array_combine($compare, $compare);
    }
}

/**
 * Comparison operator details (only input field)
 */
if ( ! function_exists('comparison_operator_details'))
{
    function comparison_operator_details(): Collection
    {
        $formFields = form_fields();
        $comparisonOperator = comparison_operator();

        $items = collect();

        $items[$formFields['text']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['email']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['textarea']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['date']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "date_format:Y-m-d",
                "required" => true,
                "value" => null,
                "input_type" => "date",
            ],

            $comparisonOperator["is_before"] => [
                "name" => __('messages.is_before'),
                "type" => "date_format:Y-m-d",
                "required" => true,
                "value" => null,
                "input_type" => "date",
            ],

            $comparisonOperator["is_after"] => [
                "name" => __('messages.is_after'),
                "type" => "date_format:Y-m-d",
                "required" => true,
                "value" => null,
                "input_type" => "date",
            ],
        ]);

        $items[$formFields['phone']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['number']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "integer",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "integer",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "integer",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "integer",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "integer",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "integer",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
        ]);

        $items[$formFields['url']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "url",
                "required" => true,
                "value" => null,
                "input_type" => "url",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "url",
                "required" => true,
                "value" => null,
                "input_type" => "url",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "url",
                "required" => true,
                "value" => null,
                "input_type" => "url",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "url",
                "required" => true,
                "value" => null,
                "input_type" => "url",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "url",
                "required" => true,
                "value" => null,
                "input_type" => "url",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "url",
                "required" => true,
                "value" => null,
                "input_type" => "url",
            ],
        ]);

        $items[$formFields['hidden']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['radio']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['checkbox']] = collect([
            $comparisonOperator["is_checked"] => [
                "name" => __('messages.is_checked'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_not_checked"] => [
                "name" => __('messages.is_not_checked'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
        ]);

        $items[$formFields['select']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['file']] = collect([
            $comparisonOperator["has_file_selected"] => [
                "name" => __('messages.has_file_selected'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["has_no_file_selected"] => [
                "name" => __('messages.has_no_file_selected'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
        ]);

        $items[$formFields['currency']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "numeric",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "numeric",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "numeric",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "numeric",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "numeric",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "numeric",
                "required" => true,
                "value" => null,
                "input_type" => "number",
            ],
        ]);

        $items[$formFields['time']] = collect([
            $comparisonOperator["is_present"] => [
                "name" => __('messages.is_present'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is_blank"] => [
                "name" => __('messages.is_blank'),
                "type" => "boolean",
                "required" => false,
                "value" => true,
                "input_type" => null,
            ],
            $comparisonOperator["is"] => [
                "name" => __('messages.is'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not"] => [
                "name" => __('messages.is_not'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["contains"] => [
                "name" => __('messages.contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["does_not_contains"] => [
                "name" => __('messages.does_not_contains'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["starts_with"] => [
                "name" => __('messages.starts_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["ends_with"] => [
                "name" => __('messages.ends_with'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        $items[$formFields['checkbox_group']] = collect([
            $comparisonOperator["is_checked"] => [
                "name" => __('messages.is_checked'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
            $comparisonOperator["is_not_checked"] => [
                "name" => __('messages.is_not_checked'),
                "type" => "string",
                "required" => true,
                "value" => null,
                "input_type" => "text",
            ],
        ]);

        return $items;
    }
}

/**
 * Action Types
 */
if ( ! function_exists('action_types'))
{
    function action_types(): array
    {
        $actionTypes =  [
            "show",
            "hide",
            "require",
            "optional",
            "enable",
            "disable",
        ];

        return array_combine($actionTypes, $actionTypes);
    }
}

/**
 * Translated action types
 */
if ( ! function_exists('translated_action_types'))
{
    function translated_action_types(): array
    {
        $actions = action_types();

        $items[$actions['show']] = __('messages.show');
        $items[$actions['hide']] = __('messages.hide');
        $items[$actions['enable']] = __('messages.enable');
        $items[$actions['disable']] = __('messages.disable');
        $items[$actions['require']] = __('messages.require');
        $items[$actions['optional']] = __('messages.optional');

        return $items;
    }
}

/**
 * Field actions
 */
if ( ! function_exists('field_actions'))
{
    function field_actions(): array
    {
        $formFields = form_fields();
        $actions = action_types();
        $trans = translated_action_types();

        $items[$formFields['heading']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
        ];

        $items[$formFields['text']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['email']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['textarea']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['divider']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
        ];

        $items[$formFields['snippet']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
        ];

        $items[$formFields['date']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['phone']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['number']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['url']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['paragraph']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
        ];

        $items[$formFields['radio']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
        ];

        $items[$formFields['checkbox']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['select']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['file']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['currency']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['time']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['time_range']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        $items[$formFields['checkbox_group']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
        ];

        $items[$formFields['date_range']] = [
            $actions['show'] => $trans[$actions['show']],
            $actions['hide'] => $trans[$actions['hide']],
            $actions['require'] => $trans[$actions['require']],
            $actions['optional'] => $trans[$actions['optional']],
            $actions['enable'] => $trans[$actions['enable']],
            $actions['disable'] => $trans[$actions['disable']],
        ];

        return $items;
    }
}

/**
 * Thickness list
 */
if ( ! function_exists('thickness_list'))
{
    function thickness_list(): array
    {
        return [
            "1px",
            "2px",
            "3px",
            "4px",
            "5px",
            "6px",
        ];
    }
}

/**
 * Data Source for input field
 */
if ( ! function_exists('data_source_input'))
{
    function data_source_input(): array
    {
        $data = [
            "text",
            "current_user",
            "list",
            "url",
        ];

        return array_combine($data, $data);
    }
}

/**
 * Data Source for input field names
 */
if ( ! function_exists('data_source_input_names'))
{
    function data_source_input_names(): array
    {
        $formFields = form_fields();
        $dataSourceInput = data_source_input();

        $items[$formFields['text']] = [
            $dataSourceInput['text'] => __('messages.text'),
            $dataSourceInput['current_user'] => __('messages.current_user'),
            $dataSourceInput['url'] => __('messages.url'),
        ];

        $items[$formFields['email']] = [
            $dataSourceInput['text'] => __('messages.text'),
            $dataSourceInput['current_user'] => __('messages.current_user'),
            $dataSourceInput['url'] => __('messages.url'),
        ];

        $items[$formFields['textarea']] = [
            $dataSourceInput['text'] => __('messages.text'),
            $dataSourceInput['current_user'] => __('messages.current_user'),
            $dataSourceInput['url'] => __('messages.url'),
        ];

        $items[$formFields['hidden']] = [
            $dataSourceInput['text'] => __('messages.text'),
            $dataSourceInput['current_user'] => __('messages.current_user'),
        ];

        $items[$formFields['phone']] = [
            $dataSourceInput['text'] => __('messages.text'),
            $dataSourceInput['current_user'] => __('messages.current_user'),
            $dataSourceInput['url'] => __('messages.url'),
        ];

        $items[$formFields['select']] = [
            $dataSourceInput['list'] => __('messages.list'),
            $dataSourceInput['url'] => __('messages.url'),
        ];

        $items[$formFields['checkbox_group']] = [
            $dataSourceInput['list'] => __('messages.list'),
            $dataSourceInput['url'] => __('messages.url'),
        ];

        return $items;
    }
}

/**
 * User column
 */
if ( ! function_exists('user_columns'))
{
    function user_columns(): array
    {
        $column = [
            "name",
            "username",
            "email",
            "phone",
            "role",
        ];

        return array_combine($column, $column);
    }
}

/**
 * User column names
 */
if ( ! function_exists('user_column_names'))
{
    function user_column_names(): array
    {
        $userColumn = user_columns();

        return [
            $userColumn['name'] => __('messages.name'),
            $userColumn['username'] => __('messages.username'),
            $userColumn['email'] => __('messages.email'),
            $userColumn['phone'] => __('messages.phone_number'),
            $userColumn['role'] => __('messages.role'),
        ];

    }
}

/**
 * User info
 */
if ( ! function_exists('user_info'))
{
    function user_info(): array
    {
        $userColumn = user_columns();

        $item = [
            $userColumn['name'] => null,
            $userColumn['username'] => null,
            $userColumn['email'] => null,
            $userColumn['phone'] => null,
            $userColumn['role'] => null,
        ];

        if (auth()->check()) {
            $user = User::findOrFail(auth()->id());

            $item[$userColumn['name']] = $user->name;
            $item[$userColumn['username']] = $user->username;
            $item[$userColumn['email']] = $user->email;
            $item[$userColumn['phone']] = $user->phone;

            if ($user->roles()->exists()) {
                // support 1 role only
                $item[$userColumn['role']] = $user->roles[0]->display_name;
            }
        }

        return $item;
    }
}

/**
 * Format Bytes
 */
if ( ! function_exists('format_bytes'))
{
    function format_bytes($size): string
    {
        $unitDecimalsByFactor = [
            ['B', 0],
            ['kB', 0],
            ['MB', 2],
            ['GB', 2],
            ['TB', 3],
            ['PB', 3]
        ];

        $factor = $size ? floor(log($size, 1024)) : 0;
        $factor = min($factor, count($unitDecimalsByFactor) - 1);

        $value = round($size / pow(1024, $factor), $unitDecimalsByFactor[$factor][1]);
        $units = $unitDecimalsByFactor[$factor][0];

        return $value.' '.$units;
    }
}

/**
 * Flatpickr config locale based on current language
 */
if ( ! function_exists('flatpickr_locale'))
{
    function flatpickr_locale(): array
    {
        return [
            'firstDayOfWeek' => 1,
            'rangeSeparator' => ' - ',
            'weekdays' => [
                'shorthand' => [
                    __('messages.sun'),
                    __('messages.mon'),
                    __('messages.tue'),
                    __('messages.wed'),
                    __('messages.thu'),
                    __('messages.fri'),
                    __('messages.sat'),
                ],
                'longhand' => [
                    __('messages.sunday'),
                    __('messages.monday'),
                    __('messages.tuesday'),
                    __('messages.wednesday'),
                    __('messages.thursday'),
                    __('messages.friday'),
                    __('messages.saturday'),
                ],
            ],
            'months' => [
                'shorthand' => [
                    __('messages.jan'),
                    __('messages.feb'),
                    __('messages.mar'),
                    __('messages.apr'),
                    __('messages.may'),
                    __('messages.jun'),
                    __('messages.jul'),
                    __('messages.aug'),
                    __('messages.sep'),
                    __('messages.oct'),
                    __('messages.nov'),
                    __('messages.dec'),
                ],
                'longhand' => [
                    __('messages.january'),
                    __('messages.february'),
                    __('messages.march'),
                    __('messages.april'),
                    __('messages.may'),
                    __('messages.june'),
                    __('messages.july'),
                    __('messages.august'),
                    __('messages.september'),
                    __('messages.october'),
                    __('messages.november'),
                    __('messages.december'),
                ],
            ]
        ];
    }
}

/**
 * Common middleware (auth and profile completed)
 */
if ( ! function_exists('common_middleware'))
{
    function common_middleware(): array
    {
        return ['auth', 'profile_completed'];
    }
}

/**
 * For dev purposes
 * Auto populate path based on env
 */
if ( ! function_exists('build_modules_path'))
{
    function build_modules_path($module, $path): string
    {
        if (app()->env === 'production') {
            return $path;
        }

        return "Modules/{$module}/{$path}";
    }
}

/**
 * Date format
 */
if ( ! function_exists('date_format_list'))
{
    function date_format_list(): Collection
    {
        $now = now();

        return collect([
            [
                "format" => "d-m-Y",
                "label" => "d-m-Y ({$now->format('d-m-Y')})",
            ],
            [
                "format" => "m-d-Y",
                "label" => "m-d-Y ({$now->format('m-d-Y')})",
            ],
            [
                "format" => "Y-m-d",
                "label" => "Y-m-d ({$now->format('Y-m-d')})",
            ],
            [
                "format" => "d.m.Y",
                "label" => "d.m.Y ({$now->format('d.m.Y')})",
            ],
            [
                "format" => "m.d.Y",
                "label" => "m.d.Y ({$now->format('m.d.Y')})",
            ],
            [
                "format" => "Y.m.d",
                "label" => "Y.m.d ({$now->format('Y.m.d')})",
            ],
            [
                "format" => "d/m/Y",
                "label" => "d/m/Y ({$now->format('d/m/Y')})",
            ],
            [
                "format" => "m/d/Y",
                "label" => "m/d/Y ({$now->format('m/d/Y')})",
            ],
            [
                "format" => "Y/m/d",
                "label" => "Y/m/d ({$now->format('Y/m/d')})",
            ],
            [
                "format" => "d-M-Y",
                "label" => "d-M-Y ({$now->translatedFormat('d-M-Y')})",
            ],
            [
                "format" => "d/M/Y",
                "label" => "d/M/Y ({$now->translatedFormat('d/M/Y')})",
            ],
            [
                "format" => "d.M.Y",
                "label" => "d.M.Y ({$now->translatedFormat('d.M.Y')})",
            ],
            [
                "format" => "d-M-Y",
                "label" => "d-M-Y ({$now->translatedFormat('d-M-Y')})",
            ],
            [
                "format" => "d M Y",
                "label" => "d M Y ({$now->translatedFormat('d M Y')})",
            ],
            [
                "format" => "d F, Y",
                "label" => "d F, Y ({$now->translatedFormat('d F, Y')})",
            ],
            [
                "format" => "D/M/Y",
                "label" => "D/M/Y ({$now->translatedFormat('D/M/Y')})",
            ],
            [
                "format" => "D.M.Y",
                "label" => "D.M.Y ({$now->translatedFormat('D.M.Y')})",
            ],
            [
                "format" => "D-M-Y",
                "label" => "D-M-Y ({$now->translatedFormat('D-M-Y')})",
            ],
            [
                "format" => "D M Y",
                "label" => "D M Y ({$now->translatedFormat('D M Y')})",
            ],
            [
                "format" => "d D M Y",
                "label" => "d D M Y ({$now->translatedFormat('d D M Y')})",
            ],
            [
                "format" => "D d M Y",
                "label" => "D d M Y ({$now->translatedFormat('D d M Y')})",
            ],
        ]);
    }
}

if ( ! function_exists('min_max_option_list'))
{
    function min_max_option_list(): Collection
    {
        $items = [
            'specific_date',
            'number_days',
        ];

        return collect(array_combine($items, $items));
    }
}

if ( ! function_exists('min_max_options'))
{
    function min_max_options(): Collection
    {
        $minMax = min_max_option_list();

        return collect([
            [
                'value' => $minMax['specific_date'],
                'label' => __('messages.specific_date'),
            ],
            [
                'value' => $minMax['number_days'],
                'label' => __('messages.number_of_days_from_today'),
            ]
        ]);
    }
}

if ( ! function_exists('min_max_number_days'))
{
    function min_max_number_days(): Collection
    {
        return collect([
            'min' => -10000,
            'max' => 10000,
        ]);
    }
}

if ( ! function_exists('common_settings_form_builder'))
{
    function common_settings_form_builder(): Collection
    {
        return collect([
            'input_group_text_max_length' => 50,
        ]);
    }
}

if ( ! function_exists('password_lang'))
{
    function password_lang(): Collection
    {
        $passwordRule = config('core.password_rules');

        return collect([
            'at_least_min_characters' => __('messages.at_least_min_characters', ['min' => $passwordRule['min']]),
            'one_letter' => __('messages.one_letter'),
            'one_lowercase_letter_and_one_uppercase_letter' => __('messages.one_lowercase_letter_and_one_uppercase_letter'),
            'one_number' => __('messages.one_number'),
            'one_symbol' => __('messages.one_symbol'),
        ]);
    }
}

if ( ! function_exists('menu_submissions'))
{
    function menu_submissions($dropdown = false): ?string
    {
        $routeName = [
            'submission.show',
            'submission.edit',
            'my-submissions.index',
            'submission.list',
            'tasks.current',
            'tasks.completed',
        ];

        if (request()->routeIs($routeName) && $dropdown) {
            return 'show';
        }

        if (request()->routeIs($routeName)) {
            return 'active';
        }

        return null;
    }
}

if ( ! function_exists('total_current_task'))
{
    function total_current_task()
    {
        return FormSubmission::whereHas('currentTasks', function (Builder $query) {
            $query->where('user_id', '=', auth()->user()->id);
        })->count();
    }
}

if ( ! function_exists('menu_forms'))
{
    function menu_forms($dropdown = false): ?string
    {
        $routeName = [
            'forms.index',
            'forms.create',
            'forms.show',
            'forms.edit',
            'forms.submissions.index',
            'forms.submissions.create',
            'submissions.show',
            'submissions.edit',
            'forms.processes.index',
            'fill.forms',
        ];

        if (request()->routeIs($routeName) && $dropdown) {
            return 'show';
        }

        if (request()->routeIs($routeName)) {
            return 'active';
        }

        return null;
    }
}

if ( ! function_exists('forms_link'))
{
    function forms_link(): ?string
    {
        $routeName = [
            'forms.index',
            'forms.create',
            'forms.show',
            'forms.edit',
            'forms.processes.index',
        ];

        if (request()->routeIs($routeName)) {
            return 'active';
        }

        return null;
    }
}

if (!function_exists('task_type'))
{
    function task_type(): array
    {
        $type = [
            'all',
            'current',
            'completed',
        ];

        return array_combine($type, $type);
    }
}

if (!function_exists('submission_show'))
{
    function submission_show(): Collection
    {
        $type = task_type();

        return collect([
            null => 'my-submissions.index',
            $type['all'] => 'submission.list',
            $type['current'] => 'tasks.current',
            $type['completed'] => 'tasks.completed',
        ]);
    }
}

if ( ! function_exists('set_env'))
{
    function set_env($key, $value, $isString = false): void
    {
        if ($isString) {
            file_put_contents(app()->environmentFilePath(), str_replace(
                $key.'="'.env($key).'"',
                $key.'="'.$value.'"',
                file_get_contents(app()->environmentFilePath())
            ));
        } else {
            file_put_contents(app()->environmentFilePath(), str_replace(
                $key.'='.env($value),
                $key.'='.$value,
                file_get_contents(app()->environmentFilePath())
            ));
        }
    }
}

// Compatibility shims for PageBuilder module (mirrors rawdee-glampings helpers)

if (! function_exists('site_setting')) {
    function site_setting(string $key, $default = null): mixed
    {
        try {
            $row = \App\Models\SiteSetting::where('key', $key)->first();
            return $row ? $row->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (! function_exists('site_setting_forget_cache')) {
    function site_setting_forget_cache(): void
    {
        // no-op: nexcity does not cache site_setting reads
    }
}

if (! function_exists('uploads_url')) {
    function uploads_url(string $path): string
    {
        return \Illuminate\Support\Facades\Storage::url($path);
    }
}
