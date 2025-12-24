<template>
    <div class="row">
        <div class="col-md-12">
            <div class="mb-1">
                <label for="general" class="form-label">{{ trans.general }}</label>
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
                            <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
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
                            <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
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
                            <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
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
            <div class="mb-3">
                <label for="prefill" class="form-label">{{ trans.prefill_value }}</label>
                <input type="text" id="prefill" v-model="value.prefill"
                       :class="['form-control', errors.prefill ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.prefill">
                <strong>{{ errors.prefill[0] }}</strong>
            </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div :class="['mb-3', errors.currency ? 'select2-is-invalid' : '']">
                <label for="currency" class="form-label">
                    {{ trans.currency }}
                    <span class="text-danger me-1">*</span>
                    <span class="text-muted">
                    <span>({{ trans.references }}: </span>
                    <a href="https://www.newbridgefx.com/currency-codes-symbols/" target="_blank"
                       class="link-info link-offset-2 text-decoration-underline link-underline-opacity-25 link-underline-opacity-100-hover">
                        {{ trans.list_of_iso_4217_currency_codes }}
                    </a>
                    <span>)</span>
                </span>
                </label>
                <select id="currency" class="form-control select2" :data-placeholder="trans.please_select"
                        v-model="value.currency" v-select2>
                    <option v-for="(currency, index) in currencies" :key="index" :value="index">
                        {{ index }}
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.currency">
                    <strong>{{ errors.currency[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="precision" class="form-label">{{ trans.precision }} <span
                    class="text-danger">*</span></label>
                <select id="precision" :class="['form-select', errors.precision ? 'is-invalid' : '']"
                        v-model="value.precision">
                    <option v-for="index in 15" :key="index" :value="index">{{ index }}</option>
                </select>
                <span class="invalid-feedback" v-if="errors.precision"><strong>{{ errors.precision[0] }}</strong></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="min_value" class="form-label">{{ trans.min_number }}</label>
                <input type="number" id="min_value" v-model="value.min_value"
                       :class="['form-control', errors.min_value ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.min_value"><strong>{{ errors.min_value[0] }}</strong></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="max_value" class="form-label">{{ trans.max_number }}</label>
                <input type="number" id="max_value" v-model="value.max_value"
                       :class="['form-control', errors.max_value ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.max_value">
                    <strong>{{ errors.max_value[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-check form-switch mb-3">
                <input type="checkbox" :class="['form-check-input', errors.auto_decimal_digits ? 'is-invalid' : '']"
                       id="auto_decimal_digits" v-model="value.auto_decimal_digits" :value="value.auto_decimal_digits">
                <label for="auto_decimal_digits" class="form-check-label">
                    <div class="d-flex align-middle">
                        <span>{{ trans.auto_decimal_digits }}</span>
                        <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                           :title="trans.whether_the_decimal_symbol_is_inserted_automatically_using_the_last_inputted_digits_as_decimal_digits"></i>
                    </div>
                </label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.auto_decimal_digits">
                <strong>{{ errors.auto_decimal_digits[0] }}</strong>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-check form-switch mb-3">
                <input type="checkbox"
                       :class="['form-check-input', errors.hide_currency_symbol_on_focus ? 'is-invalid' : '']"
                       id="hide_currency_symbol_on_focus" v-model="value.hide_currency_symbol_on_focus"
                       :value="value.hide_currency_symbol_on_focus">
                <label for="hide_currency_symbol_on_focus" class="form-check-label">
                    <div class="d-flex align-middle">
                        <span>{{ trans.hide_currency_symbol }}</span>
                        <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                           :title="trans.whether_to_hide_the_currency_symbol_on_focus"></i>
                    </div>
                </label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.hide_currency_symbol_on_focus">
                <strong>{{ errors.hide_currency_symbol_on_focus[0] }}</strong>
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
                <select :class="['form-select', errors.width ? 'is-invalid' : '']" v-model="value.width">
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
import {select2} from "../../../directives/select2";

export default {
    name: "CurrencyField",
    components: {ConditionEditor},
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
    directives: {select2},
    data() {
        return {
            value: {
                hidden: this.form.hidden,
                required: this.form.required,
                disabled: this.form.disabled,
                label: this.form.label,
                placeholder: this.form.placeholder,
                prefill: this.form.prefill,
                currency: this.form.currency,
                precision: this.form.precision,
                min_value: this.form.min_value,
                max_value: this.form.max_value,
                auto_decimal_digits: this.form.auto_decimal_digits,
                hide_currency_symbol_on_focus: this.form.hide_currency_symbol_on_focus,
                help: this.form.help,
                width: this.form.width,
                logic: this.form.logic,
            },
            fieldWidthList: fieldWidth,
            actionTypes: actionTypes,
            formStore: useFormStore(),
            currencies: currencies,
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
                field_size: trans.field_size,
                your_help_text_will_be_shown_below_the_field_just_like_this_message: trans.your_help_text_will_be_shown_below_the_field_just_like_this_message,
                width: trans.width,
                disabled: trans.disabled,
                disabled_this_field: trans.disabled_this_field,
                please_select: trans.please_select,
                currency: trans.currency,
                references: trans.references,
                list_of_iso_4217_currency_codes: trans.list_of_iso_4217_currency_codes,
                precision: trans.precision,
                min_number: trans.min_number,
                max_number: trans.max_number,
                auto_decimal_digits: trans.auto_decimal_digits,
                whether_the_decimal_symbol_is_inserted_automatically_using_the_last_inputted_digits_as_decimal_digits: trans.whether_the_decimal_symbol_is_inserted_automatically_using_the_last_inputted_digits_as_decimal_digits,
                hide_currency_symbol: trans.hide_currency_symbol,
                whether_to_hide_the_currency_symbol_on_focus: trans.whether_to_hide_the_currency_symbol_on_focus,
            }
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

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

            let actions = collect(fieldActions[this.formStore.data.formFields.currency]);
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
        }
    },
    watch: {
        value: {
            handler() {
                this.$emit('value-updated', this.value);
            },
            deep: true
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
