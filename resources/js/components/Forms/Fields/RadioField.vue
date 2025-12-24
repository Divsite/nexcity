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

        <div class="col-md-12">
            <div class="mb-1">
                <label for="style" class="form-label">{{ trans.style }}</label>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" :class="['form-check-input', errors.horizontal ? 'is-invalid' : '']"
                           id="horizontal" v-model="value.horizontal" :value="value.horizontal">
                    <label for="horizontal" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.horizontal }}</span>
                            <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                               :title="trans.make_radio_buttons_horizontally_in_a_single_row"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block mb-3" v-if="errors.horizontal">
                    <strong>{{ errors.horizontal[0] }}</strong>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" :class="['form-check-input', errors.outline ? 'is-invalid' : '']"
                           id="outline" v-model="value.outline" :value="value.outline">
                    <label for="outline" class="form-check-label">
                        <div class="d-flex align-middle">
                            <span>{{ trans.outline }}</span>
                            <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                               :title="trans.change_radio_buttons_to_outlined_styles"></i>
                        </div>
                    </label>
                </div>
                <div class="invalid-feedback d-block mb-3" v-if="errors.outline">
                    <strong>{{ errors.outline[0] }}</strong>
                </div>
            </div>
        </div>

        <div class="row mb-2">
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
                                        <input type="checkbox" :id="`disabled_`+index" v-model="option.disabled"
                                               :value="option.disabled"
                                               :class="['form-check-input', errors['options.'+index+'.disabled'] ? 'is-invalid' : '']">
                                        <label :for="`disabled_`+index" class="form-check-label">
                                            <div class="d-flex align-middle">{{ trans.disabled }}</div>
                                        </label>
                                    </div>
                                    <div class="invalid-feedback d-block mb-3"
                                         v-if="errors['options.'+index+'.disabled']">
                                        <strong>{{ errors['options.' + index + '.disabled'][0] }}</strong>
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
    </div>

    <condition-editor :logic="value.logic" :action-options="actionOptions" :errors="errors"
                      @logic-updated="logicUpdate"></condition-editor>
</template>

<script>
import {useFormStore} from "../../../stores/FormStore";
import {collect} from "collect.js";
import ConditionEditor from "../Rules/ConditionEditor.vue";


export default {
    name: "RadioField",
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
                label: this.form.label,
                help: this.form.help,
                horizontal: this.form.horizontal,
                outline: this.form.outline,
                options: this.form.options,
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
                style: trans.style,
                horizontal: trans.horizontal,
                outline: trans.outline,
                make_radio_buttons_horizontally_in_a_single_row: trans.make_radio_buttons_horizontally_in_a_single_row,
                change_radio_buttons_to_outlined_styles: trans.change_radio_buttons_to_outlined_styles,
                options: trans.options,
                add_option: trans.add_option,
                the_option_list_is_empty: trans.the_option_list_is_empty,
                value: trans.value,
                disabled: trans.disabled,
            }

        };
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    computed: {
        actionOptions() {
            let items = collect();

            let actions = collect(fieldActions[this.formStore.data.formFields.radio]);
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
            }
        },
        'value.required'(newValue) {
            if (newValue) {
                this.value.hidden = false;
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
                disabled: false,
            };

            this.value.options.push(options);
        },
        deleteField(index) {
            this.value.options.splice(index, 1);
        },
        logicUpdate: function (items) {
            this.value.logic = items;
        }
    },
};
</script>
