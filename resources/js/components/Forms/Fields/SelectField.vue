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

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <div class="form-label fw-medium">{{ trans.data_source }} <span class="text-danger">*</span></div>
                <div class="btn-group" role="group" aria-label="Data source">
                    <template v-for="(input, index) in dataSourceInputNames[this.formStore.data.formFields.select]">
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

    <div class="row mb-2" v-if="value.data_source === dataSourceInput.list">
        <div class="col-md-12">
            <div class="mb-3">
                <label class="form-label">{{ trans.options }} <span class="text-danger">*</span></label>
                <template v-if="value.options.length > 0">
                    <div v-for="(option, index) in value.options" class="border rounded mb-3 p-3" :key="index">
                        <div class="row">
                            <div class="col-lg-12 text-end">
                                <button type="button" class="btn-close text-end" aria-label="Close"
                                        @click="deleteField(index)"></button>
                            </div>

                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label :for="`label_`+index" class="form-label">
                                        {{ trans.label }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" :id="`label_`+index" v-model="option.label"
                                           :class="['form-control', errors['options.'+index+'.label'] ? 'is-invalid' : '']">
                                    <span class="invalid-feedback" v-if="errors['options.'+index+'.label']">
                                            <strong>{{ errors['options.' + index + '.label'][0] }}</strong>
                                        </span>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label :for="`value_`+index" class="form-label">
                                        {{ trans.value }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" :id="`value_`+index" v-model="option.value"
                                           :class="['form-control', errors['options.'+index+'.value'] ? 'is-invalid' : '']">
                                    <span class="invalid-feedback" v-if="errors['options.'+index+'.value']">
                                            <strong>{{ errors['options.' + index + '.value'][0] }}</strong>
                                        </span>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-check mb-3">
                                    <input type="checkbox" :id="`active_`+index" v-model="option.active"
                                           :value="option.active"
                                           :class="['form-check-input', errors['options.'+index+'.active'] ? 'is-invalid' : '']">
                                    <label :for="`active_`+index" class="form-check-label">
                                        <div class="d-flex align-middle">{{ trans.active }}</div>
                                    </label>
                                </div>
                                <div class="invalid-feedback d-block mb-3"
                                     v-if="errors['options.'+index+'.active']">
                                    <strong>{{ errors['options.' + index + '.active'][0] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <p class="text-muted border border-dotted rounded p-2 text-center">
                        {{ trans.the_option_list_is_empty }}
                    </p>
                </template>
            </div>

            <button type="button" class="btn btn-info" @click="addOption">
                <i class="ri-add-line align-bottom me-1"></i> {{ trans.add_option }}
            </button>

            <span class="invalid-feedback d-block mt-3" v-if="errors.options">
                <strong>{{ errors.options[0] }}</strong>
            </span>
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

        <div class="col-md-6">
            <div class="mb-3">
                <label for="url_value" class="form-label">
                    {{ trans.value }}
                    <span class="text-danger">*</span>
                </label>
                <input type="text" id="url_value" v-model="value.url_value"
                       :class="['form-control', errors.url_value ? 'is-invalid' : '']" :placeholder="trans.value_key">
                <span class="invalid-feedback" v-if="errors.url_value">
                    <strong>{{ errors.url_value[0] }}</strong>
                </span>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="url_label" class="form-label">
                    {{ trans.label }}
                    <span class="text-danger">*</span>
                </label>
                <input type="text" id="url_label" v-model="value.url_label"
                       :class="['form-control', errors.url_label ? 'is-invalid' : '']" :placeholder="trans.label_key">
                <span class="invalid-feedback" v-if="errors.url_label">
                    <strong>{{ errors.url_label[0] }}</strong>
                </span>
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
    name: "SelectField",
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
            inputDropdownValue: '',
            selectedOption: null,
            value: {
                hidden: this.form.hidden,
                required: this.form.required,
                disabled: this.form.disabled,
                label: this.form.label,
                placeholder: this.form.placeholder,
                help: this.form.help,
                width: this.form.width,
                data_source: this.form.data_source,
                options: this.form.options,
                url: this.form.url,
                use_current_url: this.form.use_current_url,
                url_value: this.form.url_value,
                url_label: this.form.url_label,
                logic: this.form.logic,
            },
            fieldWidthList: fieldWidth,
            actionTypes: actionTypes,
            dataSourceInput: dataSourceInput,
            dataSourceInputNames: dataSourceInputNames,
            formStore: useFormStore(),
            appUrl: appUrl,
            trans: {
                general: trans.general,
                hidden: trans.hidden,
                required: trans.required,
                exclude_this_field: trans.exclude_this_field,
                make_this_field_required: trans.make_this_field_required,
                disabled_this_field: trans.disabled_this_field,
                label: trans.label,
                placeholder: trans.placeholder,
                help_text: trans.help_text,
                your_help_text_will_be_shown_below_the_field_just_like_this_message: trans.your_help_text_will_be_shown_below_the_field_just_like_this_message,
                width: trans.width,
                disabled: trans.disabled,
                options: trans.options,
                add_option: trans.add_option,
                the_option_list_is_empty: trans.the_option_list_is_empty,
                value: trans.value,
                active: trans.active,
                data_source: trans.data_source,
                please_select: trans.please_select,
                use_current_url: trans.use_current_url,
                url: trans.url,
                value_key: trans.value_key,
                label_key: trans.label_key,
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

            let actions = collect(fieldActions[this.formStore.data.formFields.select]);
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
        addOption() {
            const options = {
                value: null,
                label: null,
                active: true,
            };

            this.value.options.push(options);
        },
        deleteField(index) {
            this.value.options.splice(index, 1);
        },
        logicUpdate: function (items) {
            this.value.logic = items;
        },
    }
}
</script>
