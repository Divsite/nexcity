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
            <div class="form-check mb-3">
                <input type="checkbox" :class="['form-check-input', errors.prefill ? 'is-invalid' : '']" id="prefill"
                       v-model="value.prefill" :value="value.hidden">
                <label for="prefill" class="form-check-label">{{ trans.prefill_value }}</label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.prefill">
                <strong>{{ errors.prefill[0] }}</strong>
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
            <div class="form-check form-switch mb-3">
                <input type="checkbox" :class="['form-check-input', errors.toggle_switch ? 'is-invalid' : '']"
                       id="toggle_switch" v-model="value.toggle_switch" :value="value.toggle_switch">
                <label for="toggle_switch" class="form-check-label">
                    <div class="d-flex align-middle">
                        <span>{{ trans.toggle_switch }}</span>
                        <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                           :title="trans.toggle_switch_mode"></i>
                    </div>
                </label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.toggle_switch">
                <strong>{{ errors.toggle_switch[0] }}</strong>
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

export default {
    name: "CheckboxField",
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
    data() {
        return {
            value: {
                hidden: this.form.hidden,
                required: this.form.required,
                disabled: this.form.disabled,
                label: this.form.label,
                prefill: this.form.prefill,
                help: this.form.help,
                toggle_switch: this.form.toggle_switch,
                width: this.form.width,
                logic: this.form.logic,
            },
            fieldWidthList: fieldWidth,
            actionTypes: actionTypes,
            formStore: useFormStore(),
            trans: {
                general: trans.general,
                hidden: trans.hidden,
                required: trans.required,
                disabled: trans.disabled,
                exclude_this_field: trans.exclude_this_field,
                make_this_field_required: trans.make_this_field_required,
                disabled_this_field: trans.disabled_this_field,
                label: trans.label,
                prefill_value: trans.prefill_value,
                help_text: trans.help_text,
                your_help_text_will_be_shown_below_the_field_just_like_this_message: trans.your_help_text_will_be_shown_below_the_field_just_like_this_message,
                width: trans.width,
                toggle_switch: trans.toggle_switch,
                toggle_switch_mode: trans.toggle_switch_mode,
            }
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    computed: {
        actionOptions() {
            let items = collect();

            let actions = collect(fieldActions[this.formStore.data.formFields.checkbox]);
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
