<?php

namespace App\Http\Requests\FormSubmissions;

use App\Models\FormSubmissions\FormSubmission;
use App\Rules\DateRange;
use App\Service\Forms\ConditionalRule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateFormSubmissionRequest extends FormRequest
{
    public FormSubmission $formSubmission;
    public array $formField;
    public array $actionTypes;
    public bool $isLastPage = false;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $this->formSubmission = FormSubmission::findOrFail($this->route('submission'));
        $this->formField = form_fields();
        $this->actionTypes = action_types();

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [];

        if ($this->formSubmission->form->properties) {
            $pages = $this->fieldPages();

            // Get last element
            $last = $pages->last();

            // Check if this page is last
            $this->isLastPage = $last === $pages[$this->current_page_index];

            // If last page then validate all field
            if ($this->isLastPage) {
                if ($pages->isNotEmpty()) {
                    foreach ($pages as $properties) {
                        foreach ($properties as $property) {
                            $rules = $this->validatePropertyRule($property, $rules);
                        }
                    }
                }
            }
            // Validate current page only
            else {
                if ($pages[$this->current_page_index]->isNotEmpty()) {
                    foreach ($pages[$this->current_page_index] as $property) {
                        $rules = $this->validatePropertyRule($property, $rules);
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        if ($this->formSubmission->form->properties) {
            foreach ($this->formSubmission->form->prepareFields as $property) {

                if (
                    $property['type'] == $this->formField['text'] ||
                    $property['type'] == $this->formField['email'] ||
                    $property['type'] == $this->formField['textarea'] ||
                    $property['type'] == $this->formField['date'] ||
                    $property['type'] == $this->formField['phone'] ||
                    $property['type'] == $this->formField['number'] ||
                    $property['type'] == $this->formField['url'] ||
                    $property['type'] == $this->formField['radio'] ||
                    $property['type'] == $this->formField['checkbox'] ||
                    $property['type'] == $this->formField['select'] ||
                    $property['type'] == $this->formField['file'] ||
                    $property['type'] == $this->formField['currency'] ||
                    $property['type'] == $this->formField['time'] ||
                    $property['type'] == $this->formField['checkbox_group'] ||
                    $property['type'] == $this->formField['date_range']
                ) {
                    $attributes[$property['id']] = Str::lower($property['label']);
                }

                if ($property['type'] == $this->formField['time_range']) {
                    $fromLabel = "{$property['id']}.from";
                    $toLabel = "{$property['id']}.to";

                    $attributes[$fromLabel] = Str::lower($property['time_from_label']);
                    $attributes[$toLabel] = Str::lower($property['time_to_label']);
                }

            }
        }

        return $attributes;
    }

    public function messages(): array
    {
        $messages = [];

        if ($this->formSubmission->form->properties) {
            foreach ($this->formSubmission->form->prepareFields as $property) {

                if ($property['type'] == $this->formField['date']) {
                    $minMaxOptionList = min_max_option_list();

                    if ($property['disable_past_dates']) {
                        $now = Carbon::now()->translatedFormat($property['date_format']);
                        $propertyId = "{$property['id']}.after_or_equal";
                        $messages[$propertyId] = __('validation.after_or_equal', ['date' => $now]);
                    }

                    if ($property['min_max_options'] == $minMaxOptionList['specific_date']) {
                        if ($property['min_date']) {
                            $minDate = Carbon::createFromFormat('Y-m-d', $property['min_date'])
                                ->translatedFormat($property['date_format']);
                            $propertyId = "{$property['id']}.after_or_equal";
                            $messages[$propertyId] = __('validation.after_or_equal', ['date' => $minDate]);
                        }

                        if ($property['max_date']) {
                            $maxDate = Carbon::createFromFormat('Y-m-d', $property['max_date'])
                                ->translatedFormat($property['date_format']);
                            $propertyId = "{$property['id']}.before_or_equal";
                            $messages[$propertyId] = __('validation.before_or_equal', ['date' => $maxDate]);
                        }
                    }

                    if ($property['min_max_options'] == $minMaxOptionList['number_days']) {
                        if ($property['min_number_days']) {
                            $minNumberDate = Carbon::now()->addDays($property['min_number_days'])
                                ->translatedFormat($property['date_format']);
                            $propertyId = "{$property['id']}.after_or_equal";
                            $messages[$propertyId] = __('validation.after_or_equal', ['date' => $minNumberDate]);
                        }

                        if ($property['max_number_days']) {
                            $maxNumberDate = Carbon::now()->addDays($property['max_number_days'])
                                ->translatedFormat($property['date_format']);
                            $propertyId = "{$property['id']}.before_or_equal";
                            $messages[$propertyId] = __('validation.before_or_equal', ['date' => $maxNumberDate]);
                        }
                    }
                }

                if ($property['type'] == $this->formField['time_range']) {
                    $propertyId = "{$property['id']}.to.after";
                    $messages[$propertyId] = __('messages.time_after');
                }

            }
        }

        return $messages;
    }

    private function logicRule(array $property): array
    {
        $items = [
            'required' => $property['required'],
            'hidden' => $property['hidden'],
        ];

        // Check each field has logic
        if (!empty($property['logic'])) {
            if ($property['logic']['enabled']) {
                $conditionRule = new ConditionalRule($property, $this->all());
                if ($conditionRule->conditionMet()) {
                    if (!empty($property['logic']['actions'])) {
                        foreach ($property['logic']['actions'] as $action) {

                            if ($action['type'] == $this->actionTypes['show']) {
                                // use property value
                            }

                            if ($action['type'] == $this->actionTypes['hide']) {
                                $items['required'] = false;
                                $items['hidden'] = true;
                            }

                            if ($action['type'] == $this->actionTypes['require']) {
                                $items['required'] = true;
                                $items['hidden'] = false;
                            }

                            if ($action['type'] == $this->actionTypes['optional']) {
                                $items['required'] = false;
                            }

                            if ($action['type'] == $this->actionTypes['enable']) {
                                // use property value
                            }

                            if ($action['type'] == $this->actionTypes['disable']) {
                                $items['required'] = false;
                            }

                        }
                    }
                }
            }
        }

        return $items;
    }

    private function validatePropertyRule(mixed $property, array $rules): array
    {
        $max = "max:2000"; // Default max

        $dataSourceInput = data_source_input();

        if (
            $property['type'] == $this->formField['text'] ||
            $property['type'] == $this->formField['textarea']
        ) {
            if ($property['max_char_limit']) {
                $max = "max:{$property['max_char_limit']}";
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'string', $max];
            } else {
                $rules[$property['id']] = ['nullable', 'string', $max];
            }
        }

        if ($property['type'] == $this->formField['email']) {
            if ($property['max_char_limit']) {
                $max = "max:{$property['max_char_limit']}";
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'string', 'email', $max];
            } else {
                $rules[$property['id']] = ['nullable', 'string', 'email', $max];
            }
        }

        if ($property['type'] == $this->formField['date']) {
            $minMaxOptionList = min_max_option_list();

            $minDate = null;
            $maxDate = null;

            if ($property['disable_past_dates']) {
                $now = Carbon::now()->format('Y-m-d');
                $minDate = "after_or_equal:$now";
            }

            if ($property['min_max_options'] == $minMaxOptionList['specific_date']) {
                if ($property['min_date']) {
                    $minDate = "after_or_equal:{$property['min_date']}";
                }

                if ($property['max_date']) {
                    $maxDate = "before_or_equal:{$property['max_date']}";
                }
            }

            if ($property['min_max_options'] == $minMaxOptionList['number_days']) {
                if ($property['min_number_days']) {
                    $minNumberDate = Carbon::now()->addDays($property['min_number_days'])->format('Y-m-d');
                    $minDate = "after_or_equal:$minNumberDate";
                }

                if ($property['max_number_days']) {
                    $maxNumberDate = Carbon::now()->addDays($property['max_number_days'])->format('Y-m-d');
                    $maxDate = "before_or_equal:$maxNumberDate";
                }
            }

            $disableDates = null;
            if ($property['disable_dates']) {
                $disableDates = Rule::notIn($property['disable_dates']);
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = [
                    'required',
                    'date_format:Y-m-d',
                    $minDate,
                    $maxDate,
                    $disableDates,
                ];
            } else {
                $rules[$property['id']] = [
                    'nullable',
                    'date_format:Y-m-d',
                    $minDate,
                    $maxDate,
                    $disableDates,
                ];
            }
        }

        if ($property['type'] == $this->formField['phone']) {
            $minLength = null;
            if ($property['minlength']) {
                $minLength = "min:{$property['minlength']}";
            }

            $maxLength = null;
            if ($property['maxlength']) {
                $maxLength = "max:{$property['maxlength']}";
            }

            $pattern = null;
            if ($property['pattern']) {
                $pattern = "regex:{$property['pattern']}";
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'string', $minLength, $maxLength, $pattern];
            } else {
                $rules[$property['id']] = ['nullable', 'string', $minLength, $maxLength, $pattern];
            }
        }

        if ($property['type'] == $this->formField['number']) {
            $minNo = null;
            if ($property['min_number']) {
                $minNo = "min:{$property['min_number']}";
            }

            $maxNo = null;
            if ($property['max_number']) {
                $maxNo = "max:{$property['max_number']}";
            }

            $pattern = null;
            if ($property['number_pattern']) {
                $pattern = "regex:{$property['number_pattern']}";
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'integer', $minNo, $maxNo, $pattern];
            } else {
                $rules[$property['id']] = ['nullable', 'integer', $minNo, $maxNo, $pattern];
            }
        }

        if ($property['type'] == $this->formField['url']) {
            if ($property['max_char_limit']) {
                $max = "max:{$property['max_char_limit']}";
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'url', $max];
            } else {
                $rules[$property['id']] = ['nullable', 'url', $max];
            }
        }

        if ($property['type'] == $this->formField['hidden']) {
            $rules[$property['id']] = ['nullable', 'string'];
        }

        if ($property['type'] == $this->formField['radio']) {
            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'string'];
            } else {
                $rules[$property['id']] = ['nullable', 'string'];
            }
        }

        if ($property['type'] == $this->formField['checkbox']) {
            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'boolean', 'accepted'];
            } else {
                $rules[$property['id']] = ['nullable', 'boolean'];
            }
        }

        if ($property['type'] == $this->formField['select']) {
            $logic = $this->logicRule($property);

            if ($property['data_source'] == $dataSourceInput['list']) {
                $options = collect($property['options']);
                $active = $options->where('active', true)->pluck('value');

                if ($logic['required'] && !$logic['hidden']) {
                    $rules[$property['id']] = ['required', Rule::in($active)];
                } else {
                    $rules[$property['id']] = ['nullable', Rule::in($active)];
                }
            }

            if ($property['data_source'] == $dataSourceInput['url']) {
                if ($logic['required'] && !$logic['hidden']) {
                    $rules[$property['id']] = ['required'];
                } else {
                    $rules[$property['id']] = ['nullable'];
                }
            }
        }

        if ($property['type'] == $this->formField['file']) {
            $logic = $this->logicRule($property);

            $maxFiles = null;
            if ($property['max_files']) {
                $maxFiles = "max:{$property['max_files']}";
            }

            if ($logic['required'] && !$logic['hidden']) {
                // If files exist on current submission, can ignore if user not upload file
                $required = 'required';
                if ($this->formSubmission->files()->exists()) {
                    $required = 'nullable';
                }

                $rules[$property['id']] = [$required, 'array', $maxFiles];
            } else {
                $rules[$property['id']] = ['nullable', 'array', $maxFiles];
            }
        }

        if ($property['type'] == $this->formField['currency']) {
            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'numeric'];
            } else {
                $rules[$property['id']] = ['nullable', 'numeric'];
            }
        }

        if ($property['type'] == $this->formField['time']) {
            $timeFormat = "g:i A";

            if ($property['time_24hr']) {
                $timeFormat = "H:i";

                if ($property['enable_seconds']) {
                    $timeFormat = "H:i:s";
                }
            } else {
                if ($property['enable_seconds']) {
                    $timeFormat = "g:i:s A";
                }
            }

            $minTime = null;
            if ($property['min_time']) {
                if ($property['time_24hr']) {
                    $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("H:i");

                    if ($property['enable_seconds']) {
                        $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("H:i:s");
                    }
                } else {
                    $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("g:i A");

                    if ($property['enable_seconds']) {
                        $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("g:i:s A");
                    }
                }

                $minTime = "after_or_equal:$valueMinDate";
            }

            $maxTime = null;
            if ($property['max_time']) {
                if ($property['time_24hr']) {
                    $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("H:i");

                    if ($property['enable_seconds']) {
                        $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("H:i:s");
                    }
                } else {
                    $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("g:i A");

                    if ($property['enable_seconds']) {
                        $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("g:i:s A");
                    }
                }

                $maxTime = "before_or_equal:$valueMaxDate";
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', "date_format:$timeFormat", $minTime, $maxTime];
            } else {
                $rules[$property['id']] = ['nullable', "date_format:$timeFormat", $minTime, $maxTime];
            }
        }

        if ($property['type'] == $this->formField['time_range']) {
            $timeFormat = "g:i A";

            if ($property['time_24hr']) {
                $timeFormat = "H:i";

                if ($property['enable_seconds']) {
                    $timeFormat = "H:i:s";
                }
            } else {
                if ($property['enable_seconds']) {
                    $timeFormat = "g:i:s A";
                }
            }

            $minTime = null;
            if ($property['min_time']) {
                if ($property['time_24hr']) {
                    $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("H:i");

                    if ($property['enable_seconds']) {
                        $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("H:i:s");
                    }
                } else {
                    $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("g:i A");

                    if ($property['enable_seconds']) {
                        $valueMinDate = Carbon::createFromFormat("g:i A", $property['min_time'])->format("g:i:s A");
                    }
                }

                $minTime = "after_or_equal:$valueMinDate";
            }

            $maxTime = null;
            if ($property['max_time']) {
                if ($property['time_24hr']) {
                    $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("H:i");

                    if ($property['enable_seconds']) {
                        $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("H:i:s");
                    }
                } else {
                    $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("g:i A");

                    if ($property['enable_seconds']) {
                        $valueMaxDate = Carbon::createFromFormat("g:i A", $property['max_time'])->format("g:i:s A");
                    }
                }

                $maxTime = "before_or_equal:$valueMaxDate";
            }

            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules["{$property['id']}.from"] = ['required', "date_format:$timeFormat", $minTime, $maxTime];

                $fromValue = $this->get("{$property['id']}");
                $rules["{$property['id']}.to"] = ['required', "date_format:$timeFormat", "after:{$fromValue['from']}", $maxTime];
            } else {
                $rules["{$property['id']}.from"] = ['nullable', "date_format:$timeFormat", $minTime, $maxTime];

                $fromValue = $this->get("{$property['id']}");
                $rules["{$property['id']}.to"] = ['nullable', "date_format:$timeFormat", "after:{$fromValue['from']}", $maxTime];
            }
        }

        if ($property['type'] == $this->formField['checkbox_group']) {
            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', 'array'];
            } else {
                $rules[$property['id']] = ['nullable', 'array'];
            }
        }

        if ($property['type'] == $this->formField['date_range']) {
            $logic = $this->logicRule($property);

            if ($logic['required'] && !$logic['hidden']) {
                $rules[$property['id']] = ['required', new DateRange($property)];
            } else {
                $rules[$property['id']] = ['nullable', new DateRange($property)];
            }
        }

        return $rules;
    }

    private function fieldPages(): Collection
    {
        $pages = collect();
        $currentPage = collect();

        foreach ($this->formSubmission->form->prepare_fields as $field) {
            $currentPage->push($field);

            if ($field['type'] == $this->formField['page_break']) {
                $pages->push($currentPage);
                $currentPage = collect();
            }
        }

        $pages->push($currentPage);

        return $pages;
    }
}
