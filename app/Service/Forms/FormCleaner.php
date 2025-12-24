<?php

namespace App\Service\Forms;

class FormCleaner
{
    /**
     * Clean data based on each field data type
     */
    public function getData(array $validated): array
    {
        $formFields = form_fields();
        $dataSourceInput = data_source_input();

        $properties = form_properties($validated['type']);

        if ($validated['type'] == $formFields['heading']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['tag'] = $validated['tag'] ?? $properties['tag'];
            $properties['text'] = $validated['text'] ?? $properties['text'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['text']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['data_source'] = $validated['data_source'] ?? $properties['data_source'];

            if ($properties['data_source'] == $dataSourceInput['text']) {
                $properties['prefill'] = $validated['prefill'] ?? $properties['prefill'];
                $properties['column_name'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['current_user']) {
                $properties['column_name'] = $validated['column_name'] ?? $properties['column_name'];
                $properties['prefill'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['url']) {
                $properties['url'] = $validated['url'] ?? $properties['url'];
                $properties['use_current_url'] = $validated['use_current_url'] ?? $properties['use_current_url'];
                $properties['url_value'] = $validated['url_value'] ?? $properties['url_value'];
            }

            $properties['input_group'] = $validated['input_group'] ?? $properties['input_group'];
            $properties['left_text_input_group'] = $validated['left_text_input_group'] ?? null;
            $properties['right_text_input_group'] = $validated['right_text_input_group'] ?? null;
            $properties['display_input_group_text'] = $validated['display_input_group_text'] ?? $properties['display_input_group_text'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['max_char_limit'] = (int) $validated['max_char_limit'] ?? (int) $properties['max_char_limit'];
            $properties['show_char_limit'] = $validated['show_char_limit'] ?? $properties['show_char_limit'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['email']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['data_source'] = $validated['data_source'] ?? $properties['data_source'];

            if ($properties['data_source'] == $dataSourceInput['text']) {
                $properties['prefill'] = $validated['prefill'] ?? $properties['prefill'];
                $properties['column_name'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['current_user']) {
                $properties['column_name'] = $validated['column_name'] ?? $properties['column_name'];
                $properties['prefill'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['url']) {
                $properties['url'] = $validated['url'] ?? $properties['url'];
                $properties['use_current_url'] = $validated['use_current_url'] ?? $properties['use_current_url'];
                $properties['url_value'] = $validated['url_value'] ?? $properties['url_value'];
            }

            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['max_char_limit'] = (int) $validated['max_char_limit'] ?? (int) $properties['max_char_limit'];
            $properties['show_char_limit'] = $validated['show_char_limit'] ?? $properties['show_char_limit'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['textarea']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['data_source'] = $validated['data_source'] ?? $properties['data_source'];

            if ($properties['data_source'] == $dataSourceInput['text']) {
                $properties['prefill'] = $validated['prefill'] ?? $properties['prefill'];
                $properties['column_name'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['current_user']) {
                $properties['column_name'] = $validated['column_name'] ?? $properties['column_name'];
                $properties['prefill'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['url']) {
                $properties['url'] = $validated['url'] ?? $properties['url'];
                $properties['use_current_url'] = $validated['use_current_url'] ?? $properties['use_current_url'];
                $properties['url_value'] = $validated['url_value'] ?? $properties['url_value'];
            }

            $properties['help'] = $validated['help'] ?? null;
            $properties['size'] = (int) $validated['size'] ?? (int) $properties['size'];
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['max_char_limit'] = (int) $validated['max_char_limit'] ?? (int) $properties['max_char_limit'];
            $properties['show_char_limit'] = $validated['show_char_limit'] ?? $properties['show_char_limit'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['hidden']) {
            $properties['data_source'] = $validated['data_source'] ?? $properties['data_source'];

            if ($properties['data_source'] == $dataSourceInput['text']) {
                $properties['prefill'] = $validated['prefill'] ?? $properties['prefill'];
                $properties['column_name'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['current_user']) {
                $properties['column_name'] = $validated['column_name'] ?? $properties['column_name'];
                $properties['prefill'] = null;
            }
        }

        if ($validated['type'] == $formFields['url']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['prefill'] = $validated['prefill'] ?? null;
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['max_char_limit'] = (int) $validated['max_char_limit'] ?? (int) $properties['max_char_limit'];
            $properties['show_char_limit'] = $validated['show_char_limit'] ?? $properties['show_char_limit'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['number']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['prefill'] = $validated['prefill'] ?? null;
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['min_number'] = $validated['min_number'] ?? $properties['min_number'];
            $properties['max_number'] = $validated['max_number'] ?? $properties['max_number'];
            $properties['step_number'] = $validated['step_number'] ?? $properties['step_number'];
            $properties['number_pattern'] = $validated['number_pattern'] ?? $properties['number_pattern'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['date']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['date_format'] = $validated['date_format'] ?? $properties['date_format'];
            $properties['prefill'] = $validated['prefill'] ?? null;
            $properties['disable_past_dates'] = $validated['disable_past_dates'] ?? $properties['disable_past_dates'];
            $properties['inline'] = $validated['inline'] ?? $properties['inline'];
            $properties['week_numbers'] = $validated['week_numbers'] ?? $properties['week_numbers'];
            $properties['min_max_options'] = $validated['min_max_options'] ?? $properties['min_max_options'];
            $properties['min_date'] = $validated['min_date'] ?? null;
            $properties['max_date'] = $validated['max_date'] ?? null;
            $properties['min_number_days'] = $validated['min_number_days'] ?? null;
            $properties['max_number_days'] = $validated['max_number_days'] ?? null;
            $properties['disable_dates'] = $validated['disable_dates'] ?? null;
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['paragraph']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['text'] = $validated['text'] ?? $properties['text'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['page_break']) {
            $properties['next_btn_text'] = $validated['next_btn_text'] ?? $properties['next_btn_text'];
            $properties['previous_btn_text'] = $validated['previous_btn_text'] ?? $properties['previous_btn_text'];
        }

        if ($validated['type'] == $formFields['divider']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['thickness'] = $validated['thickness'] ?? $properties['thickness'];
            $properties['color'] = $validated['color'] ?? $properties['color'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['snippet']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['content'] = $validated['content'] ?? $properties['content'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['phone']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['data_source'] = $validated['data_source'] ?? $properties['data_source'];

            if ($properties['data_source'] == $dataSourceInput['text']) {
                $properties['prefill'] = $validated['prefill'] ?? $properties['prefill'];
                $properties['column_name'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['current_user']) {
                $properties['column_name'] = $validated['column_name'] ?? $properties['column_name'];
                $properties['prefill'] = null;
            }

            if ($properties['data_source'] == $dataSourceInput['url']) {
                $properties['url'] = $validated['url'] ?? $properties['url'];
                $properties['use_current_url'] = $validated['use_current_url'] ?? $properties['use_current_url'];
                $properties['url_value'] = $validated['url_value'] ?? $properties['url_value'];
            }

            $properties['minlength'] = $validated['minlength'] ?? $properties['minlength'];
            $properties['maxlength'] = $validated['maxlength'] ?? $properties['maxlength'];
            $properties['pattern'] = $validated['pattern'] ?? $properties['pattern'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['radio']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['label'] = $validated['label'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['horizontal'] = $validated['horizontal'] ?? $properties['horizontal'];
            $properties['outline'] = $validated['outline'] ?? $properties['outline'];
            $properties['options'] = $validated['options'] ?? $properties['options'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['checkbox']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['prefill'] = $validated['prefill'] ?? $properties['prefill'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['toggle_switch'] = $validated['toggle_switch'] ?? $properties['toggle_switch'];
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['select']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? $properties['placeholder'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['data_source'] = $validated['data_source'] ?? $properties['data_source'];
            $properties['options'] = $validated['options'] ?? $properties['options'];
            $properties['url'] = $validated['url'] ?? $properties['url'];
            $properties['use_current_url'] = $validated['use_current_url'] ?? $properties['use_current_url'];
            $properties['url_value'] = $validated['url_value'] ?? $properties['url_value'];
            $properties['url_label'] = $validated['url_label'] ?? $properties['url_label'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['file']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['accept_files'] = $validated['accept_files'] ?? $properties['accept_files'];
            $properties['multiple_files'] = $validated['multiple_files'] ?? $properties['multiple_files'];
            $properties['max_files'] = $validated['max_files'] ?? $properties['max_files'];
            $properties['max_file_size'] = $validated['max_file_size'] ?? $properties['max_file_size'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['currency']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['prefill'] = $validated['prefill'] ?? null;
            $properties['currency'] = $validated['currency'] ?? $properties['currency'];
            $properties['precision'] = $validated['precision'] ?? $properties['precision'];
            $properties['min_value'] = $validated['min_value'] ?? $properties['min_value'];
            $properties['max_value'] = $validated['max_value'] ?? $properties['max_value'];
            $properties['auto_decimal_digits'] = $validated['auto_decimal_digits'] ?? $properties['auto_decimal_digits'];
            $properties['hide_currency_symbol_on_focus'] = $validated['hide_currency_symbol_on_focus'] ?? $properties['hide_currency_symbol_on_focus'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['time']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['prefill'] = $validated['prefill'] ?? null;
            $properties['min_time'] = $validated['min_time'] ?? null;
            $properties['max_time'] = $validated['max_time'] ?? null;
            $properties['time_24hr'] = $validated['time_24hr'] ?? $properties['time_24hr'];
            $properties['enable_seconds'] = $validated['enable_seconds'] ?? $properties['enable_seconds'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['time_range']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['time_from_label'] = $validated['time_from_label'];
            $properties['time_to_label'] = $validated['time_to_label'];
            $properties['prefill_from'] = $validated['prefill_from'] ?? null;
            $properties['prefill_to'] = $validated['prefill_to'] ?? null;
            $properties['min_time'] = $validated['min_time'] ?? null;
            $properties['max_time'] = $validated['max_time'] ?? null;
            $properties['time_24hr'] = $validated['time_24hr'] ?? $properties['time_24hr'];
            $properties['enable_seconds'] = $validated['enable_seconds'] ?? $properties['enable_seconds'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['checkbox_group']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['label'] = $validated['label'];
            $properties['help'] = $validated['help'] ?? null;
            $properties['horizontal'] = $validated['horizontal'] ?? $properties['horizontal'];
            $properties['outline'] = $validated['outline'] ?? $properties['outline'];
            $properties['toggle_switch'] = $validated['toggle_switch'] ?? $properties['toggle_switch'];
            $properties['data_source'] = $validated['data_source'] ?? $properties['data_source'];
            $properties['options'] = $validated['options'] ?? $properties['options'];
            $properties['url'] = $validated['url'] ?? $properties['url'];
            $properties['use_current_url'] = $validated['use_current_url'] ?? $properties['use_current_url'];
            $properties['url_value'] = $validated['url_value'] ?? $properties['url_value'];
            $properties['url_label'] = $validated['url_label'] ?? $properties['url_label'];
            $properties['url_tooltip'] = $validated['url_tooltip'] ?? $properties['url_tooltip'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        if ($validated['type'] == $formFields['date_range']) {
            $properties['hidden'] = $validated['hidden'] ?? $properties['hidden'];
            $properties['required'] = $validated['required'] ?? $properties['required'];
            $properties['disabled'] = $validated['disabled'] ?? $properties['disabled'];
            $properties['label'] = $validated['label'];
            $properties['placeholder'] = $validated['placeholder'] ?? null;
            $properties['date_format'] = $validated['date_format'] ?? $properties['date_format'];
            $properties['prefill'] = $validated['prefill'] ?? null;
            $properties['disable_past_dates'] = $validated['disable_past_dates'] ?? $properties['disable_past_dates'];
            $properties['inline'] = $validated['inline'] ?? $properties['inline'];
            $properties['week_numbers'] = $validated['week_numbers'] ?? $properties['week_numbers'];
            $properties['min_max_options'] = $validated['min_max_options'] ?? $properties['min_max_options'];
            $properties['min_date'] = $validated['min_date'] ?? null;
            $properties['max_date'] = $validated['max_date'] ?? null;
            $properties['min_number_days'] = $validated['min_number_days'] ?? null;
            $properties['max_number_days'] = $validated['max_number_days'] ?? null;
            $properties['disable_dates'] = $validated['disable_dates'] ?? null;
            $properties['help'] = $validated['help'] ?? null;
            $properties['width'] = $validated['width'] ?? $properties['width'];
            $properties['logic'] = $this->getLogicAttr($validated);
        }

        return $properties;
    }

    private function getLogicAttr($validated): array
    {
        $properties = form_properties($validated['type']);

        $properties['logic']['enabled'] = $validated['logic']['enabled'] ?? $properties['logic']['enabled'];
        $properties['logic']['conditions'] = $validated['logic']['conditions'] ?? $properties['logic']['conditions'];
        $properties['logic']['actions'] = $validated['logic']['actions'] ?? $properties['logic']['actions'];

        return $properties['logic'];
    }
}
