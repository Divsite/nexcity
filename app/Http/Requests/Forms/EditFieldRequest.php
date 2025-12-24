<?php

namespace App\Http\Requests\Forms;

use App\Rules\ValidRegex;
use Cknow\Money\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EditFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $fields = form_fields();
        $dataSourceInput = data_source_input();
        $commonSettings = common_settings_form_builder();

        $rules['name'] = ['required', 'string', 'max:255'];
        $rules['type'] = ['required', 'string', Rule::in($fields)];

        if ($this->request->get('type') == $fields['heading']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['text'] = ['required', 'string'];
            $rules['tag'] = ['required', Rule::in(heading_list())];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['text']) {
            $dataSource = collect(data_source_input_names()[$this->request->get('type')]);

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['data_source'] = ['required', Rule::in($dataSource->keys())];
            $rules['prefill'] = ['nullable', 'string'];
            $rules['column_name'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['current_user']),
                Rule::in(user_columns()),
            ];

            $urlType = 'url';
            if ($this->use_current_url) {
                $urlType = 'string';
            }

            $rules['url'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                $urlType,
            ];
            $rules['use_current_url'] = [
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'boolean'
            ];
            $rules['url_value'] = ['nullable', 'string'];

            $rules['input_group'] = ['required', 'boolean'];
            $rules['left_text_input_group'] = [
                'nullable',
                'string',
                "max:{$commonSettings['input_group_text_max_length']}"
            ];
            $rules['right_text_input_group'] = [
                'nullable',
                'string',
                "max:{$commonSettings['input_group_text_max_length']}"
            ];
            $rules['display_input_group_text'] = ['required', 'boolean'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules['max_char_limit'] = ['required', 'integer', 'min:0', 'max:2000'];
            $rules['show_char_limit'] = ['required', 'boolean'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['email']) {
            $dataSource = collect(data_source_input_names()[$this->request->get('type')]);

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['data_source'] = ['required', Rule::in($dataSource->keys())];
            $rules['prefill'] = ['nullable', 'string'];
            $rules['column_name'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['current_user']),
                Rule::in(user_columns()),
            ];

            $urlType = 'url';
            if ($this->use_current_url) {
                $urlType = 'string';
            }

            $rules['url'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                $urlType,
            ];
            $rules['use_current_url'] = [
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'boolean'
            ];
            $rules['url_value'] = ['nullable', 'string'];

            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules['max_char_limit'] = ['required', 'integer', 'min:0', 'max:2000'];
            $rules['show_char_limit'] = ['required', 'boolean'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['textarea']) {
            $dataSource = collect(data_source_input_names()[$this->request->get('type')]);

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['data_source'] = ['required', Rule::in($dataSource->keys())];
            $rules['prefill'] = ['nullable', 'string'];
            $rules['column_name'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['current_user']),
                Rule::in(user_columns()),
            ];

            $urlType = 'url';
            if ($this->use_current_url) {
                $urlType = 'string';
            }

            $rules['url'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                $urlType,
            ];
            $rules['use_current_url'] = [
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'boolean'
            ];
            $rules['url_value'] = ['nullable', 'string'];

            $rules['help'] = ['nullable', 'string'];
            $rules['size'] = ['required', 'numeric', 'min:1', 'max:50'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules['max_char_limit'] = ['required', 'integer', 'min:0', 'max:2000'];
            $rules['show_char_limit'] = ['required', 'boolean'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['hidden']) {
            $dataSource = collect(data_source_input_names()[$this->request->get('type')]);

            $rules['data_source'] = ['required', Rule::in($dataSource->keys())];
            $rules['prefill'] = ['nullable', 'string'];
            $rules['column_name'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['current_user']),
                Rule::in(user_columns()),
            ];
        }

        if ($this->request->get('type') == $fields['url']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['prefill'] = ['nullable', 'url'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules['max_char_limit'] = ['required', 'integer', 'min:0', 'max:2000'];
            $rules['show_char_limit'] = ['required', 'boolean'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['number'] ) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['prefill'] = ['nullable', 'integer'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules['min_number'] = ['nullable', 'integer'];
            $rules['max_number'] = ['nullable', 'integer'];
            $rules['step_number'] = ['nullable', 'integer'];
            $rules['number_pattern'] = ['nullable', 'string'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['date']) {
            $minMaxNumberDays = min_max_number_days();

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['date_format'] = ['required', Rule::in(date_format_list()->pluck('format'))];
            $rules['prefill'] = ['nullable', "date_format:Y-m-d"];
            $rules['disable_past_dates'] = ['required', 'boolean'];
            $rules['inline'] = ['required', 'boolean'];
            $rules['week_numbers'] = ['required', 'boolean'];
            $rules['min_max_options'] = ['nullable', Rule::in(min_max_option_list())];
            $rules['min_date'] = ['nullable', "date_format:Y-m-d"];
            $rules['max_date'] = ['nullable', "date_format:Y-m-d"];
            $rules['min_number_days'] = [
                'nullable', 'numeric', "min:{$minMaxNumberDays['min']}", "max:{$minMaxNumberDays['max']}"
            ];
            $rules['max_number_days'] = [
                'nullable', 'numeric', "min:{$minMaxNumberDays['min']}", "max:{$minMaxNumberDays['max']}"
            ];
            $rules['disable_dates.*'] = ['nullable', "date_format:Y-m-d"];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['paragraph']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['text'] = ['required', 'string'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['page_break']) {
            $rules['next_btn_text'] = ['nullable', 'string', 'max:255'];
            $rules['previous_btn_text'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->request->get('type') == $fields['divider']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['thickness'] = ['required', 'string'];
            $rules['color'] = ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['snippet']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['content'] = ['nullable', 'string'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['phone']) {
            $dataSource = collect(data_source_input_names()[$this->request->get('type')]);

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['data_source'] = ['required', Rule::in($dataSource->keys())];
            $rules['prefill'] = ['nullable', 'string'];
            $rules['column_name'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['current_user']),
                Rule::in(user_columns()),
            ];

            $urlType = 'url';
            if ($this->use_current_url) {
                $urlType = 'string';
            }

            $rules['url'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                $urlType,
            ];
            $rules['use_current_url'] = [
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'boolean'
            ];
            $rules['url_value'] = ['nullable', 'string'];

            $rules['minlength'] = ['nullable', 'integer'];
            $rules['maxlength'] = ['nullable', 'integer'];
            $rules['pattern'] = ['nullable', 'string', new ValidRegex];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['radio']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['help'] = ['nullable', 'string'];
            $rules['horizontal'] = ['required', 'boolean'];
            $rules['outline'] = ['required', 'boolean'];
            $rules['options'] = ['required', 'array'];
            $rules['options.*.label'] = ['required', 'string', 'max:255'];
            $rules['options.*.value'] = ['required', 'string', 'distinct', 'max:255'];
            $rules['options.*.disabled'] = ['required', 'boolean'];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['checkbox']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['prefill'] = ['required', 'boolean'];
            $rules['help'] = ['nullable', 'string'];
            $rules['toggle_switch'] = ['required', 'boolean'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['select']) {
            $dataSource = collect(data_source_input_names()[$this->request->get('type')]);

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules['data_source'] = ['required', Rule::in($dataSource->keys())];
            $rules['options'] = ['required', 'array'];
            $rules['options.*.label'] = ['required', 'string', 'max:255'];
            $rules['options.*.value'] = ['required', 'string', 'distinct', 'max:255'];
            $rules['options.*.active'] = ['required', 'boolean'];

            $urlType = 'url';
            if ($this->use_current_url) {
                $urlType = 'string';
            }

            $rules['url'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                $urlType,
            ];
            $rules['use_current_url'] = [
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'boolean'
            ];
            $rules['url_value'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'string',
            ];
            $rules['url_label'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'string',
            ];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['file']) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['accept_files'] = ['nullable', 'string'];
            $rules['multiple_files'] = ['required', 'boolean'];
            $rules['max_files'] = [
                'nullable',
                Rule::requiredIf($this->multiple_files == true),
                'numeric',
                'min:2',
                'max:15',
            ];
            $rules['max_file_size'] = ['required', 'numeric', 'min:1', 'max:100'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['currency']) {
            $currencies = Money::getISOCurrencies();

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['prefill'] = ['nullable', 'string'];
            $rules['currency'] = ['required', 'string', Rule::in(collect($currencies)->keys())];
            $rules['precision'] = ['required', 'integer', Rule::in(range(1, 15))];
            $rules['min_value'] = ['nullable', 'integer'];
            $rules['max_value'] = ['nullable', 'integer'];
            $rules['auto_decimal_digits'] = ['required', 'boolean'];
            $rules['hide_currency_symbol_on_focus'] = ['required', 'boolean'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['time'] ) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['prefill'] = ['nullable', 'date_format:g:i A'];
            $rules['min_time'] = ['nullable', 'date_format:g:i A'];
            $rules['max_time'] = ['nullable', 'date_format:g:i A'];
            $rules['time_24hr'] = ['required', 'boolean'];
            $rules['enable_seconds'] = ['required', 'boolean'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['time_range'] ) {
            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['time_from_label'] = ['required', 'string'];
            $rules['time_to_label'] = ['required', 'string'];
            $rules['prefill_from'] = ['nullable', 'date_format:g:i A'];
            $rules['prefill_to'] = ['nullable', 'date_format:g:i A', "after:$this->prefill_from"];
            $rules['min_time'] = ['nullable', 'date_format:g:i A'];
            $rules['max_time'] = ['nullable', 'date_format:g:i A'];
            $rules['time_24hr'] = ['required', 'boolean'];
            $rules['enable_seconds'] = ['required', 'boolean'];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['checkbox_group']) {
            $dataSource = collect(data_source_input_names()[$this->request->get('type')]);

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['help'] = ['nullable', 'string'];
            $rules['horizontal'] = ['required', 'boolean'];
            $rules['outline'] = ['required', 'boolean'];
            $rules['toggle_switch'] = ['required', 'boolean'];
            $rules['data_source'] = ['required', Rule::in($dataSource->keys())];
            $rules['options'] = ['required', 'array'];
            $rules['options.*.label'] = ['required', 'string', 'max:255'];
            $rules['options.*.value'] = ['required', 'string', 'distinct', 'max:255'];
            $rules['options.*.tooltip'] = ['nullable', 'string', 'distinct', 'max:255'];
            $rules['options.*.disabled'] = ['required', 'boolean'];

            $urlType = 'url';
            if ($this->use_current_url) {
                $urlType = 'string';
            }

            $rules['url'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                $urlType,
            ];
            $rules['use_current_url'] = [
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'boolean'
            ];
            $rules['url_value'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'string',
            ];
            $rules['url_label'] = [
                'nullable',
                Rule::requiredIf(fn() => $this->data_source == $dataSourceInput['url']),
                'string',
            ];
            $rules['url_tooltip'] = [
                'nullable',
                'string',
            ];

            $rules = $this->getLogicRules($rules);
        }

        if ($this->request->get('type') == $fields['date_range']) {
            $minMaxNumberDays = min_max_number_days();

            $rules['hidden'] = ['required', 'boolean'];
            $rules['required'] = ['required', 'boolean'];
            $rules['disabled'] = ['required', 'boolean'];
            $rules['label'] = ['required', 'string'];
            $rules['placeholder'] = ['nullable', 'string'];
            $rules['date_format'] = ['required', Rule::in(date_format_list()->pluck('format'))];
            $rules['prefill.0'] = ['nullable', "date_format:Y-m-d", "before_or_equal:prefill.1"];
            $rules['prefill.1'] = ['nullable', "date_format:Y-m-d", "after_or_equal:prefill.0"];
            $rules['disable_past_dates'] = ['required', 'boolean'];
            $rules['inline'] = ['required', 'boolean'];
            $rules['week_numbers'] = ['required', 'boolean'];
            $rules['min_max_options'] = ['nullable', Rule::in(min_max_option_list())];
            $rules['min_date'] = ['nullable', "date_format:Y-m-d"];
            $rules['max_date'] = ['nullable', "date_format:Y-m-d"];
            $rules['min_number_days'] = [
                'nullable', 'numeric', "min:{$minMaxNumberDays['min']}", "max:{$minMaxNumberDays['max']}"
            ];
            $rules['max_number_days'] = [
                'nullable', 'numeric', "min:{$minMaxNumberDays['min']}", "max:{$minMaxNumberDays['max']}"
            ];
            $rules['disable_dates.*'] = ['nullable', "date_format:Y-m-d"];
            $rules['help'] = ['nullable', 'string'];
            $rules['width'] = ['required', Rule::in(array_keys(field_width()))];
            $rules = $this->getLogicRules($rules);
        }

        return $rules;
    }

    public function attributes(): array
    {
        $fields = form_fields();

        $attributes['name'] = Str::lower(__('messages.field_name'));
        $attributes['type'] = Str::lower(__('messages.type'));

        if ($this->request->get('type') == $fields['heading']) {
            $attributes['text'] = Str::lower(__('messages.text'));
            $attributes['tag'] = Str::lower(__('messages.tags'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if (
            $this->request->get('type') == $fields['text'] ||
            $this->request->get('type') == $fields['email']
        ) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['data_source'] = Str::lower(__('messages.data_source'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['column_name'] = Str::lower(__('messages.column_name'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes['max_char_limit'] = Str::lower(__('messages.max_character_limit'));
            $attributes['show_char_limit'] = Str::lower(__('messages.always_show_character_limit'));
            $attributes['url'] = Str::lower(__('messages.url'));
            $attributes['use_current_url'] = Str::lower(__('messages.use_current_url'));
            $attributes['url_value'] = Str::lower(__('messages.value'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['textarea']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['data_source'] = Str::lower(__('messages.data_source'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['column_name'] = Str::lower(__('messages.column_name'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['size'] = Str::lower(__('messages.field_size'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes['max_char_limit'] = Str::lower(__('messages.max_character_limit'));
            $attributes['show_char_limit'] = Str::lower(__('messages.always_show_character_limit'));
            $attributes['url'] = Str::lower(__('messages.url'));
            $attributes['use_current_url'] = Str::lower(__('messages.use_current_url'));
            $attributes['url_value'] = Str::lower(__('messages.value'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['hidden']) {
            $attributes['data_source'] = Str::lower(__('messages.data_source'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['column_name'] = Str::lower(__('messages.column_name'));
        }

        if ($this->request->get('type') == $fields['url']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes['max_char_limit'] = Str::lower(__('messages.max_character_limit'));
            $attributes['show_char_limit'] = Str::lower(__('messages.always_show_character_limit'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['number']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes['min_number'] = Str::lower(__('messages.min_number'));
            $attributes['max_number'] = Str::lower(__('messages.max_number'));
            $attributes['step_number'] = Str::lower(__('messages.step_number'));
            $attributes['number_pattern'] = Str::lower(__('messages.number_pattern'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['date']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['disable_past_dates'] = Str::lower(__('messages.disable_past_dates'));
            $attributes['inline'] = Str::lower(__('messages.inline'));
            $attributes['week_numbers'] = Str::lower(__('messages.week_numbers'));
            $attributes['min_max_options'] = Str::lower(__('messages.minimum_and_maximum_date_options'));
            $attributes['min_date'] = Str::lower(__('messages.min_date'));
            $attributes['max_date'] = Str::lower(__('messages.max_date'));
            $attributes['min_number_days'] = Str::lower(__('messages.min_number_days'));
            $attributes['max_number_days'] = Str::lower(__('messages.max_number_days'));
            $attributes['disable_dates.*'] = Str::lower(__('messages.disabling_dates'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['paragraph']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['text'] = Str::lower(__('messages.text'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['page_break']) {
            $attributes['next_btn_text'] = Str::lower(__('messages.text_of_next_button'));
            $attributes['previous_btn_text'] = Str::lower(__('messages.text_of_previous_button'));
        }

        if ($this->request->get('type') == $fields['divider']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['thickness'] = Str::lower(__('messages.thickness'));
            $attributes['color'] = Str::lower(__('messages.color'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['snippet']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['content'] = Str::lower(__('messages.content'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['phone']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['data_source'] = Str::lower(__('messages.data_source'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['column_name'] = Str::lower(__('messages.column_name'));
            $attributes['minlength'] = Str::lower(__('messages.min_number'));
            $attributes['maxlength'] = Str::lower(__('messages.max_number'));
            $attributes['pattern'] = Str::lower(__('messages.number_pattern'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes['max_char_limit'] = Str::lower(__('messages.max_character_limit'));
            $attributes['show_char_limit'] = Str::lower(__('messages.always_show_character_limit'));
            $attributes['url'] = Str::lower(__('messages.url'));
            $attributes['use_current_url'] = Str::lower(__('messages.use_current_url'));
            $attributes['url_value'] = Str::lower(__('messages.value'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['radio']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['horizontal'] = Str::lower(__('messages.horizontal'));
            $attributes['outline'] = Str::lower(__('messages.outline'));
            $attributes['options'] = Str::lower(__('messages.options'));
            $attributes['options.*.label'] = Str::lower(__('messages.label'));
            $attributes['options.*.value'] = Str::lower(__('messages.value'));
            $attributes['options.*.disabled'] = Str::lower(__('messages.disabled'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['checkbox']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['toggle_switch'] = Str::lower(__('messages.toggle_switch'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['select']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes['data_source'] = Str::lower(__('messages.data_source'));
            $attributes['options'] = Str::lower(__('messages.options'));
            $attributes['options.*.label'] = Str::lower(__('messages.label'));
            $attributes['options.*.value'] = Str::lower(__('messages.value'));
            $attributes['options.*.active'] = Str::lower(__('messages.active'));
            $attributes['url'] = Str::lower(__('messages.url'));
            $attributes['use_current_url'] = Str::lower(__('messages.use_current_url'));
            $attributes['url_value'] = Str::lower(__('messages.value'));
            $attributes['url_label'] = Str::lower(__('messages.label'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['file']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['accept_files'] = Str::lower(__('messages.allowed_file_types'));
            $attributes['multiple_files'] = Str::lower(__('messages.multiple_files'));
            $attributes['max_files'] = Str::lower(__('messages.maximum_files'));
            $attributes['max_file_size'] = Str::lower(__('messages.maximum_file_size'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['currency']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['currency'] = Str::lower(__('messages.currency'));
            $attributes['precision'] = Str::lower(__('messages.precision'));
            $attributes['min_value'] = Str::lower(__('messages.min_number'));
            $attributes['max_value'] = Str::lower(__('messages.max_number'));
            $attributes['auto_decimal_digits'] = Str::lower(__('messages.auto_decimal_digits'));
            $attributes['hide_currency_symbol_on_focus'] = Str::lower(__('messages.hide_currency_symbol'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['time']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['prefill'] = Str::lower(__('messages.prefill_value'));
            $attributes['min_time'] = Str::lower(__('messages.min_time'));
            $attributes['max_time'] = Str::lower(__('messages.max_time'));
            $attributes['time_24hr'] = Str::lower(__('messages.twenty_four_hour_format'));
            $attributes['enable_seconds'] = Str::lower(__('messages.seconds'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['time_range']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['time_from_label'] = Str::lower(__('messages.time_from_label'));
            $attributes['time_to_label'] = Str::lower(__('messages.time_to_label'));
            $attributes['prefill_from'] = Str::lower(__('messages.prefill_from_value'));
            $attributes['prefill_to'] = Str::lower(__('messages.prefill_to_value'));
            $attributes['min_time'] = Str::lower(__('messages.min_time'));
            $attributes['max_time'] = Str::lower(__('messages.max_time'));
            $attributes['time_24hr'] = Str::lower(__('messages.twenty_four_hour_format'));
            $attributes['enable_seconds'] = Str::lower(__('messages.seconds'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['checkbox_group']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['horizontal'] = Str::lower(__('messages.horizontal'));
            $attributes['outline'] = Str::lower(__('messages.outline'));
            $attributes['toggle_switch'] = Str::lower(__('messages.toggle_switch'));
            $attributes['data_source'] = Str::lower(__('messages.data_source'));
            $attributes['options'] = Str::lower(__('messages.options'));
            $attributes['options.*.label'] = Str::lower(__('messages.label'));
            $attributes['options.*.value'] = Str::lower(__('messages.value'));
            $attributes['options.*.tooltip'] = Str::lower(__('messages.tooltips'));
            $attributes['options.*.disabled'] = Str::lower(__('messages.disabled'));
            $attributes['url'] = Str::lower(__('messages.url'));
            $attributes['use_current_url'] = Str::lower(__('messages.use_current_url'));
            $attributes['url_value'] = Str::lower(__('messages.value'));
            $attributes['url_label'] = Str::lower(__('messages.label'));
            $attributes['url_tooltip'] = Str::lower(__('messages.tooltips'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        if ($this->request->get('type') == $fields['date_range']) {
            $attributes['hidden'] = Str::lower(__('messages.hidden'));
            $attributes['required'] = Str::lower(__('messages.required'));
            $attributes['disabled'] = Str::lower(__('messages.disabled'));
            $attributes['label'] = Str::lower(__('messages.label'));
            $attributes['placeholder'] = Str::lower(__('messages.placeholder'));
            $attributes['prefill.0'] = Str::lower(__('messages.prefill_from_value'));
            $attributes['prefill.1'] = Str::lower(__('messages.prefill_to_value'));
            $attributes['disable_past_dates'] = Str::lower(__('messages.disable_past_dates'));
            $attributes['inline'] = Str::lower(__('messages.inline'));
            $attributes['week_numbers'] = Str::lower(__('messages.week_numbers'));
            $attributes['min_max_options'] = Str::lower(__('messages.minimum_and_maximum_date_options'));
            $attributes['min_date'] = Str::lower(__('messages.min_date'));
            $attributes['max_date'] = Str::lower(__('messages.max_date'));
            $attributes['min_number_days'] = Str::lower(__('messages.min_number_days'));
            $attributes['max_number_days'] = Str::lower(__('messages.max_number_days'));
            $attributes['disable_dates.*'] = Str::lower(__('messages.disabling_dates'));
            $attributes['help'] = Str::lower(__('messages.help_text'));
            $attributes['width'] = Str::lower(__('messages.width'));
            $attributes = $this->getLogicAttributes($attributes);
        }

        return $attributes;
    }

    public function messages(): array
    {
        $fields = form_fields();

        $messages = [];

        if ($this->request->get('type') == $fields['time_range']) {
            $messages['prefill_to.after'] = __('messages.time_after');
        }

        return $messages;
    }

    private function getLogicRules(array $rules): array
    {
        $properties = collect($this->properties);

        // Validate properties ID
        $rules['properties_dropdown'] = ['nullable', 'array'];
        $propertiesDropdown = collect($this->properties_dropdown);

        $rules['logic'] = ['nullable', 'array'];
        $rules['logic.enabled'] = ['required', 'boolean'];
        $rules['logic.conditions.operator'] = ['required_if:logic.enabled,true', Rule::in(conditional_rule_operator())];
        $rules['logic.conditions.group.*.name'] = ['nullable', 'string', 'max:255'];
        $rules['logic.conditions.group.*.operator'] = ['required', Rule::in(conditional_rule_operator())];
        $rules['logic.conditions.group.*.rules'] = ['nullable', 'array'];
        $rules['logic.conditions.group.*.rules.*.property_id'] = ['required', 'uuid', Rule::in($propertiesDropdown->pluck('id'))];

        if ($this->logic) {
            if (isset($this->logic['conditions']) && !empty($this->logic['conditions'])) {
                foreach ($this->logic['conditions']['group'] as $key => $group) {
                    foreach ($group['rules'] as $index => $rule) {
                        $rules["logic.conditions.group.$key.rules.$index.compare"][] = 'required';

                        if ($rule['property_id'] && $rule['compare']) {
                            $property = $properties->firstWhere('id', $rule['property_id']);
                            if ($property) {
                                $comparisonOperator = comparison_operator_details()[$property['type']];

                                $rules["logic.conditions.group.$key.rules.$index.compare"][] = Rule::in($comparisonOperator->keys());

                                if ($comparisonOperator->has($rule['compare'])) {
                                    $compare = $comparisonOperator->get($rule['compare']);

                                    $rules["logic.conditions.group.$key.rules.$index.value"] = [
                                        Rule::requiredIf($compare['required']), $compare['type']
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        $actionOptions = collect($this->action_options);

        $rules['logic.actions'] = ['nullable', 'array'];
        $rules['logic.actions.*.type'] = ['required', Rule::in($actionOptions->pluck('value'))];

        return $rules;
    }

    private function getLogicAttributes(array $attributes): array
    {
        $properties = collect($this->properties);

        $attributes['logic.conditions.operator'] = Str::lower(__('messages.condition_type'));
        $attributes['logic.conditions.group.*.name'] = Str::lower(__('messages.name'));
        $attributes['logic.conditions.group.*.operator'] = Str::lower(__('messages.condition_type'));
        $attributes['logic.conditions.group.*.rules.*.property_id'] = '';

        if ($this->logic) {
            if (isset($this->logic['conditions']) && !empty($this->logic['conditions'])) {
                foreach ($this->logic['conditions']['group'] as $key => $group) {
                    foreach ($group['rules'] as $index => $rule) {
                        $attributes["logic.conditions.group.$key.rules.$index.compare"] = Str::lower(__('messages.compare'));

                        if ($rule['property_id'] && $rule['compare']) {
                            $property = $properties->firstWhere('id', $rule['property_id']);
                            if ($property) {
                                $comparisonOperator = comparison_operator_details()[$property['type']];
                                if ($comparisonOperator->has($rule['compare'])) {
                                    $attributes["logic.conditions.group.$key.rules.$index.value"] = Str::lower(__('messages.value'));
                                }
                            }
                        }
                    }
                }
            }
        }

        $attributes['logic.actions.*.type'] = Str::lower(__('messages.action'));

        return $attributes;
    }
}
