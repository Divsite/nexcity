<template>
    <div class="row">
        <div class="col-md-12">
            <div class="mb-1">
                <div id="general" class="form-label fw-medium">{{ trans.general }}</div>
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
                        <div class="d-flex align-items-center">
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
                        <div class="d-flex align-items-center">
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
                <label for="accept_files" class="form-label">
                    {{ trans.allowed_file_types }}
                    <span class="text-muted">
                        <span>({{ trans.references }}: </span>
                        <a href="https://mimetype.io/all-types" target="_blank"
                           class="link-info link-offset-2 text-decoration-underline link-underline-opacity-25 link-underline-opacity-100-hover">All MIME types</a>
                        <span>)</span>
                    </span>
                </label>
                <input type="text" id="accept_files" v-model="value.accept_files"
                       :placeholder="trans.accept_file_support"
                       :class="['form-control', errors.accept_files ? 'is-invalid' : '']">
                <div class="form-text">{{ trans.comma_separated_values_leave_blank_to_allow_all_file_types }}</div>
                <span class="invalid-feedback" v-if="errors.accept_files"><strong>{{ errors.accept_files[0] }}</strong></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-check form-switch mb-3">
                <input type="checkbox" :class="['form-check-input', errors.multiple_files ? 'is-invalid' : '']"
                       id="multiple_files" v-model="value.multiple_files" :value="value.multiple_files">
                <label for="multiple_files" class="form-check-label">
                    <div class="d-flex align-middle">
                        <span>{{ trans.multiple_files }}</span>
                        <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                           :title="trans.you_can_upload_more_than_one"></i>
                    </div>
                </label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.multiple_files">
                <strong>{{ errors.multiple_files[0] }}</strong>
            </div>
        </div>

        <div class="col-md-12" v-if="value.multiple_files">
            <div class="mb-3">
                <label for="max_files" class="form-label">
                    {{ trans.maximum_files }}
                    <span class="text-danger">*</span>
                </label>
                <input type="number" id="max_files" v-model="value.max_files"
                       :class="['form-control', errors.max_files ? 'is-invalid' : '']">
                <div class="invalid-feedback d-block mb-3" v-if="errors.max_files">
                    <strong>{{ errors.max_files[0] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="max_file_size" class="form-label">
                    {{ trans.maximum_file_size }}
                    <span class="text-danger">*</span>
                </label>
                <input type="number" id="max_file_size" v-model="value.max_file_size"
                       :class="['form-control', errors.max_file_size ? 'is-invalid' : '']">
                <div class="invalid-feedback d-block mb-3" v-if="errors.max_file_size">
                    <strong>{{ errors.max_file_size[0] }}</strong>
                </div>
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

export default {
    name: "FileField",
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
                accept_files: this.form.accept_files,
                multiple_files: this.form.multiple_files,
                max_files: this.form.max_files,
                max_file_size: this.form.max_file_size,
                help: this.form.help,
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
                exclude_this_field: trans.exclude_this_field,
                make_this_field_required: trans.make_this_field_required,
                label: trans.label,
                help_text: trans.help_text,
                your_help_text_will_be_shown_below_the_field_just_like_this_message: trans.your_help_text_will_be_shown_below_the_field_just_like_this_message,
                width: trans.width,
                disabled: trans.disabled,
                disabled_this_field: trans.disabled_this_field,
                allowed_file_types: trans.allowed_file_types,
                comma_separated_values_leave_blank_to_allow_all_file_types: trans.comma_separated_values_leave_blank_to_allow_all_file_types,
                accept_file_support: trans.accept_file_support,
                multiple_files: trans.multiple_files,
                you_can_upload_more_than_one: trans.you_can_upload_more_than_one,
                maximum_files: trans.maximum_files,
                maximum_file_size: trans.maximum_file_size,
                references: trans.references,
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

            let actions = collect(fieldActions[this.formStore.data.formFields.file]);
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
        },
    }
}
</script>
