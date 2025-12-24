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
                        <div class="d-flex align-items-center">
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
                        <div class="d-flex align-items-center">
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
                        <div class="d-flex align-items-center">
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
            <div class="mb-3">
                <div class="form-label fw-medium">{{ trans.data_source }} <span class="text-danger">*</span></div>
                <div class="btn-group" role="group" aria-label="Data source">
                    <template v-for="(input, index) in dataSourceInputNames[this.formStore.data.formFields.text]">
                        <input type="radio" class="btn-check" :id="index" v-model="value.data_source" :value="index"
                               autocomplete="off">
                        <label class="btn btn-outline-primary" :for="index">{{ input }}</label>
                    </template>
                </div>
                <div class="invalid-feedback d-block" v-if="errors.data_source">
                    <strong>{{ errors.data_source[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row" v-if="value.data_source === dataSourceInput.text">
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

    <div class="row" v-show="value.data_source === dataSourceInput.current_user">
        <div class="col-md-12">
            <div :class="['mb-3', errors.column_name ? 'select2-is-invalid' : '']">
                <label for="column_name" class="form-label">
                    {{ trans.column_name }}
                    <span class="text-danger">*</span>
                </label>
                <select id="column_name" class="form-control select2" :data-placeholder="trans.please_select"
                        v-model="value.column_name" v-select2>
                    <option v-for="(value, name) in userColumnNames" :key="name" :value="name">
                        {{ value }}
                    </option>
                </select>
                <span class="invalid-feedback d-block" v-if="errors.column_name">
                    <strong>{{ errors.column_name[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row mb-2" v-if="value.data_source === dataSourceInput.url">
        <div class="col-md-12">
            <div class="form-check form-switch mb-3">
                <input type="checkbox" id="use_current_url" v-model="value.use_current_url"
                       :value="value.use_current_url"
                       :class="['form-check-input', errors.use_current_url ? 'is-invalid' : '']">
                <label for="use_current_url" class="form-check-label">
                    <div class="d-flex align-items-center">
                        <span>{{ trans.use_current_url }}</span>
                    </div>
                </label>
                <div class="invalid-feedback" v-if="errors.use_current_url">
                    <strong>{{ errors.use_current_url[0] }}</strong>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="url" class="form-label">
                            {{ trans.url }}
                            <span class="text-danger">*</span>
                            <span class="text-muted fs-12 ms-1" v-if="value.use_current_url">({{ appUrl }})</span>
                        </label>
                        <input type="text" id="url" v-model="value.url"
                               :class="['form-control', errors.url ? 'is-invalid' : '']"
                               autocomplete="off">
                        <span class="invalid-feedback" v-if="errors.url">
                            <strong>{{ errors.url[0] }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <label for="url_value" class="form-label">
                    {{ trans.value }}
                </label>
                <input type="text" id="url_value" v-model="value.url_value"
                       :class="['form-control', errors.url_value ? 'is-invalid' : '']" :placeholder="trans.value_key">
                <div class="form-text">{{ trans.leave_blank_if_the_data_is_returned_as_a_string }}</div>
                <span class="invalid-feedback" v-if="errors.url_value">
                    <strong>{{ errors.url_value[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-check form-switch mb-3">
                <input type="checkbox" :class="['form-check-input', errors.input_group ? 'is-invalid' : '']"
                       id="input_group" v-model="value.input_group" :value="value.input_group">
                <label for="input_group" class="form-check-label">
                    <div class="d-flex align-items-center">
                        <span>{{ trans.input_group }}</span>
                        <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                           :title="trans.enhance_input_by_adding_text_in_front_of_or_behind_the_input_field"></i>
                    </div>
                </label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.input_group">
                <strong>{{ errors.input_group[0] }}</strong>
            </div>
        </div>
    </div>

    <template v-if="value.input_group">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="left_text_input_group" class="form-label">{{ trans.left_text }}</label>
                    <input type="text" id="left_text_input_group" v-model="value.left_text_input_group"
                           :class="['form-control', errors.left_text_input_group ? 'is-invalid' : '']"
                           :maxlength="commonSettingsFormBuilder.input_group_text_max_length">
                    <div class="form-text text-end">{{ leftTextLength }}</div>
                    <div class="invalid-feedback" v-if="errors.left_text_input_group">
                        <strong>{{ errors.left_text_input_group[0] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <label for="right_text_input_group" class="form-label">{{ trans.right_text }}</label>
                    <input type="text" id="right_text_input_group" v-model="value.right_text_input_group"
                           :class="['form-control', errors.right_text_input_group ? 'is-invalid' : '']"
                           :maxlength="commonSettingsFormBuilder.input_group_text_max_length">
                    <div class="form-text text-end">{{ rightTextLength }}</div>
                    <div class="invalid-feedback" v-if="errors.right_text_input_group">
                        <strong>{{ errors.right_text_input_group[0] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" id="display_input_group_text"
                           :class="['form-check-input', errors.display_input_group_text ? 'is-invalid' : '']"
                           v-model="value.display_input_group_text" :value="value.display_input_group_text">
                    <label for="display_input_group_text" class="form-check-label">
                        <div class="d-flex align-items-center">
                            <span>{{ trans.display_input_group_text }}</span>
                            <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                               :title="trans.add_text_to_input_during_submission"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block mb-3" v-if="errors.display_input_group_text">
                    <strong>{{ errors.display_input_group_text[0] }}</strong>
                </div>
            </div>
        </div>
    </template>

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

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="max_char_limit" class="form-label">{{ trans.max_character_limit }} <span
                    class="text-danger">*</span></label>
                <input type="number" id="max_char_limit" v-model.number="value.max_char_limit"
                       :class="['form-control', errors.max_char_limit ? 'is-invalid' : '']">
                <div class="form-text">{{ trans.maximum_character_limit_of_2000 }}</div>
                <span class="invalid-feedback" v-if="errors.max_char_limit">
                    <strong>{{ errors.max_char_limit[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-check form-switch mb-3">
                <input type="checkbox" :class="['form-check-input', errors.show_char_limit ? 'is-invalid' : '']"
                       id="show_char_limit" v-model="value.show_char_limit" :value="value.show_char_limit">
                <label for="show_char_limit" class="form-check-label">
                    <span>{{ trans.always_show_character_limit }}</span>
                </label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.show_char_limit">
                <strong>{{ errors.show_char_limit[0] }}</strong>
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
import {tooltip} from "../../../directives/tooltip";

export default {
    name: "TextField",
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
    directives: {select2, tooltip},
    data() {
        return {
            value: {
                hidden: this.form.hidden,
                required: this.form.required,
                disabled: this.form.disabled,
                label: this.form.label,
                placeholder: this.form.placeholder,
                data_source: this.form.data_source,
                prefill: this.form.prefill,
                column_name: this.form.column_name,
                input_group: this.form.input_group,
                left_text_input_group: this.form.left_text_input_group,
                right_text_input_group: this.form.right_text_input_group,
                display_input_group_text: this.form.display_input_group_text,
                help: this.form.help,
                width: this.form.width,
                max_char_limit: this.form.max_char_limit,
                show_char_limit: this.form.show_char_limit,
                url: this.form.url,
                use_current_url: this.form.use_current_url,
                url_value: this.form.url_value,
                logic: this.form.logic,
            },
            fieldWidthList: fieldWidth,
            actionTypes: actionTypes,
            dataSourceInput: dataSourceInput,
            dataSourceInputNames: dataSourceInputNames,
            userColumnNames: userColumnNames,
            commonSettingsFormBuilder: commonSettingsFormBuilder,
            formStore: useFormStore(),
            appUrl: appUrl,
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
                max_character_limit: trans.max_character_limit,
                maximum_character_limit_of_2000: trans.maximum_character_limit_of_2000,
                always_show_character_limit: trans.always_show_character_limit,
                disabled: trans.disabled,
                disabled_this_field: trans.disabled_this_field,
                data_source: trans.data_source,
                column_name: trans.column_name,
                please_select: trans.please_select,
                use_current_url: trans.use_current_url,
                url: trans.url,
                value: trans.value,
                value_key: trans.value_key,
                leave_blank_if_the_data_is_returned_as_a_string: trans.leave_blank_if_the_data_is_returned_as_a_string,
                input_group: trans.input_group,
                enhance_input_by_adding_text_in_front_of_or_behind_the_input_field: trans.enhance_input_by_adding_text_in_front_of_or_behind_the_input_field,
                left_text: trans.left_text,
                right_text: trans.right_text,
                display_input_group_text: trans.display_input_group_text,
                add_text_to_input_during_submission: trans.add_text_to_input_during_submission,
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

            let actions = collect(fieldActions[this.formStore.data.formFields.text]);
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
        leftTextLength() {
            let length = 0;
            if (this.value.left_text_input_group) {
                length = this.value.left_text_input_group.length;
            }

            return length+"/"+this.commonSettingsFormBuilder.input_group_text_max_length;
        },
        rightTextLength() {
            let length = 0;
            if (this.value.right_text_input_group) {
                length = this.value.right_text_input_group.length;
            }

            return length+"/"+this.commonSettingsFormBuilder.input_group_text_max_length;
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
        'value.input_group'(newValue) {
            // Clear input group field
            if (!newValue) {
                this.value.left_text_input_group = null;
                this.value.right_text_input_group = null;
                this.value.display_input_group_text = false;
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
