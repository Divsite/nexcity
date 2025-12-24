<template>
    <div class="row">
        <div class="col-md-12">
            <div class="mb-1">
                <div class="form-label fw-medium">{{ trans.general }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" :class="['form-check-input', errors.hidden ? 'is-invalid' : '']" id="hidden"
                           v-model="value.hidden" :value="value.hidden">
                    <label for="hidden" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.hidden }}</span>
                            <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                               :title="trans.exclude_this_field"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block mb-3" v-if="errors.hidden">
                    <strong>{{ errors.hidden[0] }}</strong>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" :class="['form-check-input', errors.required ? 'is-invalid' : '']"
                           id="required" v-model="value.required" :value="value.required">
                    <label for="required" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.required }}</span>
                            <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                               :title="trans.make_this_field_required"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block mb-3" v-if="errors.required">
                    <strong>{{ errors.required[0] }}</strong>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" :class="['form-check-input', errors.disabled ? 'is-invalid' : '']"
                           id="disabled" v-model="value.disabled" :value="value.disabled">
                    <label for="disabled" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.disabled }}</span>
                            <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                               :title="trans.disabled_this_field"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block mb-3" v-if="errors.disabled">
                    <strong>{{ errors.disabled[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="label" class="form-label">{{ trans.label }} <span class="text-danger">*</span></label>
                <input type="text" id="label" v-model="value.label"
                       :class="['form-control', errors.label ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.label"><strong>{{ errors.label[0] }}</strong></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="placeholder" class="form-label">{{ trans.placeholder }}</label>
                <input type="text" id="placeholder" v-model="value.placeholder"
                       :class="['form-control', errors.placeholder ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.placeholder">
                    <strong>{{ errors.placeholder[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div :class="['mb-3', errors.date_format ? 'select2-is-invalid' : '']">
                <label for="date_format" class="form-label">
                    {{ trans.date_format }}
                    <span class="text-danger">*</span>
                </label>
                <select id="date_format" class="form-control select2" :data-placeholder="trans.please_select"
                        v-model="value.date_format" v-select2>
                    <option v-for="(date, index) in dateFormat" :key="index" :value="date.format">
                        {{ date.label }}
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.date_format">
                    <strong>{{ errors.date_format[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div :class="prefillFormClass">
                <div class="form-label fw-medium">{{ trans.prefill_value }}</div>
                <flat-pickr
                    v-model="rangePrefill"
                    :config="configPrefill"
                    id="prefill"
                    class="form-control"
                />
                <span class="invalid-feedback d-block" v-if="errors['prefill.0']">
                    <strong>{{ errors['prefill.0'][0] }}</strong>
                </span>
                <span class="invalid-feedback d-block" v-if="errors['prefill.1']">
                    <strong>{{ errors['prefill.1'][0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" :class="['form-check-input', errors.disable_past_dates ? 'is-invalid' : '']"
                           id="disable_past_dates" v-model="value.disable_past_dates" :value="value.disable_past_dates">
                    <label for="disable_past_dates" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.disable_past_dates }}</span>
                            <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                               :title="trans.dates_before_today_cannot_be_selected"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block" v-if="errors.disable_past_dates">
                    <strong>{{ errors.disable_past_dates[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" :class="['form-check-input', errors.inline ? 'is-invalid' : '']"
                           id="inline" v-model="value.inline" :value="value.inline">
                    <label for="inline" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.inline }}</span>
                            <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                               :title="trans.displays_the_calendar_inline"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block" v-if="errors.inline">
                    <strong>{{ errors.inline[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" :class="['form-check-input', errors.week_numbers ? 'is-invalid' : '']"
                           id="week_numbers" v-model="value.week_numbers" :value="value.week_numbers">
                    <label for="week_numbers" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.week_numbers }}</span>
                            <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                               :title="trans.enables_display_of_week_numbers_in_calendar"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block" v-if="errors.week_numbers">
                    <strong>{{ errors.week_numbers[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="mb-3">
            <div class="form-label fw-medium">
                {{ trans.minimum_and_maximum_date_options }}
            </div>
            <div class="btn-group" role="group" aria-label="Min & max options">
                <template v-for="(option, index) in minMaxOptions">
                    <input type="radio" class="btn-check" :id="`min_max_option_`+index" v-model="value.min_max_options"
                           :value="option.value" autocomplete="off">
                    <label class="btn btn-outline-primary" :for="`min_max_option_`+index">{{ option.label }}</label>
                </template>
            </div>
            <div class="invalid-feedback d-block" v-if="errors.min_max_options">
                <strong>{{ errors.min_max_options[0] }}</strong>
            </div>
        </div>
    </div>

    <template v-if="value.min_max_options === minMaxOptionList.specific_date">
        <div class="row" v-if="!value.disable_past_dates">
            <div class="col-md-12">
                <div :class="['mb-3', errors.min_date ? 'flatpickr-is-invalid' : '']">
                    <div class="form-label fw-medium">{{ trans.min_date }}</div>
                    <flat-pickr
                        v-model="value.min_date"
                        :config="configMinDate"
                        id="min_date"
                        class="form-control"
                    />
                    <span class="invalid-feedback d-block" v-if="errors.min_date">
                        <strong>{{ errors.min_date[0] }}</strong>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div :class="['mb-3', errors.max_date ? 'flatpickr-is-invalid' : '']">
                    <div class="form-label fw-medium">{{ trans.max_date }}</div>
                    <flat-pickr
                        v-model="value.max_date"
                        :config="configMaxDate"
                        id="max_date"
                        class="form-control"
                    />
                    <span class="invalid-feedback d-block" v-if="errors.max_date">
                        <strong>{{ errors.max_date[0] }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </template>

    <template v-if="value.min_max_options === minMaxOptionList.number_days">
        <div class="row" v-if="!value.disable_past_dates">
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="min_number_days" class="form-label">{{ trans.min_number_days }}</label>
                    <input type="number" id="min_number_days" v-model="value.min_number_days"
                           :class="['form-control', errors.min_number_days ? 'is-invalid' : '']"
                           :min="minMaxNumberDays.min" :max="minMaxNumberDays.max">
                    <span class="invalid-feedback" v-if="errors.min_number_days">
                        <strong>{{ errors.min_number_days[0] }}</strong>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="max_number_days" class="form-label">{{ trans.max_number_days }}</label>
                    <input type="number" id="max_number_days" v-model="value.max_number_days"
                           :class="['form-control', errors.max_number_days ? 'is-invalid' : '']"
                           :min="minMaxNumberDays.min" :max="minMaxNumberDays.max">
                    <span class="invalid-feedback" v-if="errors.max_number_days">
                        <strong>{{ errors.max_number_days[0] }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </template>

    <div class="row">
        <div class="col-md-12">
            <div :class="['mb-3', disableDatesErrors.status ? 'flatpickr-is-invalid' : '']">
                <div class="form-label fw-medium">{{ trans.disabling_dates }}</div>
                <flat-pickr
                    v-model="disableDates"
                    :config="configDisableDates"
                    id="disable_dates"
                    class="form-control"
                />
                <span class="invalid-feedback d-block" v-if="disableDatesErrors.status">
                    <strong>{{ disableDatesErrors.message }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="help" class="form-label">{{ trans.help_text }}</label>
                <input type="text" id="help" v-model="value.help"
                       :class="['form-control', errors.help ? 'is-invalid' : '']">
                <div class="form-text">
                    {{ trans.your_help_text_will_be_shown_below_the_field_just_like_this_message }}
                </div>
                <span class="invalid-feedback" v-if="errors.help"><strong>{{ errors.help[0] }}</strong></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="width" class="form-label">{{ trans.width }} <span class="text-danger">*</span></label>
                <select id="width" :class="['form-select', errors.width ? 'is-invalid' : '']" v-model="value.width">
                    <option v-for="(fieldWidth, index) in fieldWidthList" :value="index">{{ fieldWidth }}</option>
                </select>
                <span class="invalid-feedback" v-if="errors.width"><strong>{{ errors.width[0] }}</strong></span>
            </div>
        </div>
    </div>

    <condition-editor :logic="value.logic" :action-options="actionOptions" :errors="errors"
                      @logic-updated="logicUpdate"></condition-editor>
</template>

<script>
import ConditionEditor from "../Rules/ConditionEditor.vue";
import {useFormStore} from "../../../stores/FormStore";
import {collect} from "collect.js";
import {tooltip} from "../../../directives/tooltip";
import {select2} from "../../../directives/select2";
import flatPickr from "vue-flatpickr-component";

export default {
    name: "DateRangeField",
    components: {flatPickr, ConditionEditor},
    emits: ['value-updated', 'action-options'],
    props: {
        form: {
            type: Object,
            required: false,
        },
        errors: {
            type: Object,
            required: false,
        },
    },
    directives: {tooltip, select2},
    data() {
        return {
            rangePrefill: null,
            disableDates: null,
            value: {
                hidden: this.form.hidden,
                required: this.form.required,
                disabled: this.form.disabled,
                label: this.form.label,
                placeholder: this.form.placeholder,
                date_format: this.form.date_format,
                prefill: this.form.prefill,
                disable_past_dates: this.form.disable_past_dates,
                inline: this.form.inline,
                week_numbers: this.form.week_numbers,
                min_max_options: this.form.min_max_options,
                min_date: this.form.min_date,
                max_date: this.form.max_date,
                min_number_days: this.form.min_number_days,
                max_number_days: this.form.max_number_days,
                disable_dates: this.form.disable_dates,
                help: this.form.help,
                width: this.form.width,
                logic: this.form.logic,
            },
            fieldWidthList: fieldWidth,
            actionTypes: actionTypes,
            dateFormat: dateFormat,
            minMaxOptionList: minMaxOptionList,
            minMaxOptions: minMaxOptions,
            minMaxNumberDays: minMaxNumberDays,
            formStore: useFormStore(),
            trans: {
                general: trans.general,
                hidden: trans.hidden,
                required: trans.required,
                exclude_this_field: trans.exclude_this_field,
                make_this_field_required: trans.make_this_field_required,
                label: trans.label,
                placeholder: trans.placeholder,
                prefill_value: trans.prefill_value,
                help_text: trans.help_text,
                your_help_text_will_be_shown_below_the_field_just_like_this_message: trans.your_help_text_will_be_shown_below_the_field_just_like_this_message,
                width: trans.width,
                min_date: trans.min_date,
                max_date: trans.max_date,
                disabled: trans.disabled,
                disabled_this_field: trans.disabled_this_field,
                date_format: trans.date_format,
                disable_past_dates: trans.disable_past_dates,
                dates_before_today_cannot_be_selected: trans.dates_before_today_cannot_be_selected,
                minimum_and_maximum_date_options: trans.minimum_and_maximum_date_options,
                min_number_days: trans.min_number_days,
                max_number_days: trans.max_number_days,
                disabling_dates: trans.disabling_dates,
                inline: trans.inline,
                displays_the_calendar_inline: trans.displays_the_calendar_inline,
                week_numbers: trans.week_numbers,
                enables_display_of_week_numbers_in_calendar: trans.enables_display_of_week_numbers_in_calendar,
            }
        }
    },
    mounted() {
        $(".select2").select2({
            dropdownParent: $('#edit-field-modal'),
            language: {
                noResults: function () {
                    return messages.no_results_found;
                }
            }
        });
    },
    computed: {
        actionOptions() {
            let items = collect();

            let actions = collect(fieldActions[this.formStore.data.formFields.date]);
            actions.each((trans, action) => {
                if (this.value.hidden) {
                    if (action === this.actionTypes.show) {
                        items.push({value: action, name: trans});
                    }
                } else {
                    if (action === this.actionTypes.hide) {
                        items.push({value: action, name: trans});
                    }
                }

                if (this.value.required) {
                    if (action === this.actionTypes.optional) {
                        items.push({value: action, name: trans});
                    }
                } else {
                    if (action === this.actionTypes.require) {
                        items.push({value: action, name: trans});
                    }
                }

                if (this.value.disabled) {
                    if (action === this.actionTypes.enable) {
                        items.push({value: action, name: trans});
                    }
                } else {
                    if (action === this.actionTypes.disable) {
                        items.push({value: action, name: trans});
                    }
                }
            });

            this.$emit('action-options', items.all());
            return items.all();
        },
        configPrefill() {
            return {
                mode: "range",
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                defaultDate: this.value.prefill,
                locale: flatpickrLocale,
            };
        },
        configMinDate() {
            return {
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                maxDate: this.value.max_date,
                locale: flatpickrLocale,
            };
        },
        configMaxDate() {
            let minDate;
            if (this.value.disable_past_dates) {
                minDate = "today";
            } else {
                minDate = this.value.min_date;
            }

            return {
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                minDate: minDate,
                locale: flatpickrLocale,
            };
        },
        configDisableDates() {
            return {
                mode: "multiple",
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                defaultDate: this.value.disable_dates,
                locale: flatpickrLocale,
            };
        },
        prefillFormClass() {
            return {
                'mb-3': true,
                'flatpickr-is-invalid': this.errors.hasOwnProperty('prefill.0') || this.errors.hasOwnProperty('prefill.0'),
            }
        },
        disableDatesErrors() {
            let values = {
                status: false,
                message: null,
            };

            let items = collect(this.value.disable_dates);
            items.each((item, index) => {
                if (this.errors.hasOwnProperty('disable_dates.' + index)) {
                    values.status = true;
                    values.message = this.errors['disable_dates.' + index][0];
                }
            });

            return values;
        },
    },
    watch: {
        value: {
            handler() {
                this.$emit('value-updated', this.value);
            },
            deep: true
        },
        rangePrefill(newValue) {
            if (newValue) {
                this.value.prefill = newValue.split(' - ');
            } else {
                this.value.prefill = null;
            }
        },
        disableDates(newValue) {
            if (newValue) {
                this.value.disable_dates = newValue.split(',').map(item => item.trim());
            } else {
                this.value.disable_dates = null;
            }
        },
        'value.hidden'(newValue) {
            if (newValue) {
                this.value.required = false;
                this.value.disabled = false;
            }
        },
        'value.required'(newValue) {
            if (newValue) {
                this.value.hidden = false;
                this.value.disabled = false;
            }
        },
        'value.disabled'(newValue) {
            if (newValue) {
                this.value.hidden = false;
                this.value.required = false;
                this.value.inline = false;
            }
        },
        'value.inline'(newValue) {
            if (newValue) {
                this.value.disabled = false;
            }
        },
        'value.disable_past_dates'() {
            // Clear value if checked disable past dates
            this.value.min_date = null;
            this.value.max_date = null;
            this.value.min_number_days = null;
            this.value.max_number_days = null;
        },
        'value.min_max_options'(newVal) {
            if (newVal === this.minMaxOptionList.specific_date) {
                this.value.min_number_days = null;
                this.value.max_number_days = null;
            }

            if (newVal === this.minMaxOptionList.number_days) {
                this.value.min_date = null;
                this.value.max_date = null;
            }
        },
        'value.min_number_days'(newVal) {
            if (newVal && this.value.min_max_options === this.minMaxOptionList.number_days) {
                if (newVal <= this.minMaxNumberDays.min) {
                    this.value.min_number_days = this.minMaxNumberDays.min;
                }

                if (newVal >= this.minMaxNumberDays.max) {
                    this.value.min_number_days = this.minMaxNumberDays.max;
                }
            }
        },
        'value.max_number_days'(newVal) {
            if (newVal && this.value.min_max_options === this.minMaxOptionList.number_days) {
                if (newVal <= this.minMaxNumberDays.min) {
                    this.value.max_number_days = this.minMaxNumberDays.min;
                }

                if (newVal >= this.minMaxNumberDays.max) {
                    this.value.max_number_days = this.minMaxNumberDays.max;
                }
            }
        },
        actionOptions: {
            handler() {
                this.$emit('action-options', this.actionOptions);
            },
            deep: true
        },
    },
    methods: {
        logicUpdate: function (items) {
            this.value.logic = items;
        }
    }
}
</script>
