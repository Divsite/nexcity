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
                <label for="time_from_label" class="form-label">
                    {{ trans.time_from_label }}
                    <span class="text-danger">*</span>
                </label>
                <input type="text" id="time_from_label" v-model="value.time_from_label"
                       :class="['form-control', errors.time_from_label ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.time_from_label">
                    <strong>{{ errors.time_from_label[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="time_to_label" class="form-label">
                    {{ trans.time_to_label }}
                    <span class="text-danger">*</span>
                </label>
                <input type="text" id="time_to_label" v-model="value.time_to_label"
                       :class="['form-control', errors.time_to_label ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.time_to_label">
                    <strong>{{ errors.time_to_label[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div :class="['mb-3', errors.prefill_from ? 'flatpickr-is-invalid' : '']">
                <div class="form-label fw-medium">{{ trans.prefill_from_value }}</div>
                <div class="input-group">
                    <flat-pickr
                        v-model="value.prefill_from"
                        :config="config"
                        id="prefill_from"
                        key="prefill_from"
                        class="form-control"
                    />
                    <button class="btn btn-outline-primary" type="button" id="prefill_from" data-clear>
                        <i class="ri-close-line fs-13"/>
                    </button>
                </div>
                <span class="invalid-feedback d-block" v-if="errors.prefill_from">
                    <strong>{{ errors.prefill_from[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div :class="['mb-3', errors.prefill_to ? 'flatpickr-is-invalid' : '']">
                <div class="form-label fw-medium">{{ trans.prefill_to_value }}</div>
                <div class="input-group">
                    <flat-pickr
                        v-model="value.prefill_to"
                        :config="configPrefillTo"
                        id="prefill_to"
                        key="prefill_to"
                        class="form-control"
                    />
                    <button class="btn btn-outline-primary" type="button" id="prefill_to" data-clear>
                        <i class="ri-close-line fs-13"/>
                    </button>
                </div>
                <span class="invalid-feedback d-block" v-if="errors.prefill_to">
                    <strong>{{ errors.prefill_to[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div :class="['mb-3', errors.min_time ? 'flatpickr-is-invalid' : '']">
                <div class="form-label fw-medium">{{ trans.min_time }}</div>
                <div class="input-group">
                    <flat-pickr
                        v-model="value.min_time"
                        :config="config"
                        id="min_time"
                        key="min_time"
                        class="form-control"
                    />
                    <button class="btn btn-outline-primary" type="button" id="min_time" data-clear>
                        <i class="ri-close-line fs-13"/>
                    </button>
                </div>
                <span class="invalid-feedback d-block" v-if="errors.min_time">
                    <strong>{{ errors.min_time[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div :class="['mb-3', errors.max_time ? 'flatpickr-is-invalid' : '']">
                <div class="form-label fw-medium">{{ trans.max_time }}</div>
                <div class="input-group">
                    <flat-pickr
                        v-model="value.max_time"
                        :config="config"
                        id="max_time"
                        key="max_time"
                        class="form-control"
                    />
                    <button class="btn btn-outline-primary" type="button" id="max_time" data-clear>
                        <i class="ri-close-line fs-13"/>
                    </button>
                </div>
                <span class="invalid-feedback d-block" v-if="errors.max_time">
                    <strong>{{ errors.max_time[0] }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" :class="['form-check-input', errors.time_24hr ? 'is-invalid' : '']"
                   id="time_24hr" v-model="value.time_24hr" :value="value.time_24hr">
            <label for="time_24hr" class="form-check-label">
                <div class="d-flex align-middle">
                    <span>{{ trans.twenty_four_hour_format }}</span>
                    <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                       :title="trans.displays_time_picker_in_24_hour_mode_without_am_pm_selection_when_enabled"></i>
                </div>
            </label>
            <div class="invalid-feedback" v-if="errors.time_24hr">
                <strong>{{ errors.time_24hr[0] }}</strong>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" :class="['form-check-input', errors.enable_seconds ? 'is-invalid' : '']"
                   id="enable_seconds" v-model="value.enable_seconds" :value="value.enable_seconds">
            <label for="enable_seconds" class="form-check-label">
                <div class="d-flex align-middle">
                    <span>{{ trans.seconds }}</span>
                    <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                       :title="trans.enables_seconds_in_the_time_picker"></i>
                </div>
            </label>
            <div class="invalid-feedback" v-if="errors.enable_seconds">
                <strong>{{ errors.enable_seconds[0] }}</strong>
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
import flatPickr from "vue-flatpickr-component";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";

dayjs.extend(customParseFormat);

export default {
    name: "TimeRangeField",
    components: {ConditionEditor, flatPickr},
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
                time_from_label: this.form.time_from_label,
                time_to_label: this.form.time_to_label,
                prefill_from: this.form.prefill_from,
                prefill_to: this.form.prefill_to,
                min_time: this.form.min_time,
                max_time: this.form.max_time,
                time_24hr: this.form.time_24hr,
                enable_seconds: this.form.enable_seconds,
                help: this.form.help,
                width: this.form.width,
                logic: this.form.logic,
            },
            formStore: useFormStore(),
            fieldWidthList: fieldWidth,
            actionTypes: actionTypes,
            configPrefillTo: {
                wrap: true,
                static: true,
                dateFormat: "h:i K",
                noCalendar: true,
                enableTime: true,
                minTime: null,
            },
            trans: {
                general: trans.general,
                hidden: trans.hidden,
                required: trans.required,
                exclude_this_field: trans.exclude_this_field,
                make_this_field_required: trans.make_this_field_required,
                time_from_label: trans.time_from_label,
                time_to_label: trans.time_to_label,
                prefill_from_value: trans.prefill_from_value,
                prefill_to_value: trans.prefill_to_value,
                help_text: trans.help_text,
                your_help_text_will_be_shown_below_the_field_just_like_this_message: trans.your_help_text_will_be_shown_below_the_field_just_like_this_message,
                width: trans.width,
                min_time: trans.min_time,
                max_time: trans.max_time,
                disabled: trans.disabled,
                disabled_this_field: trans.disabled_this_field,
                twenty_four_hour_format: trans.twenty_four_hour_format,
                displays_time_picker_in_24_hour_mode_without_am_pm_selection_when_enabled: trans.displays_time_picker_in_24_hour_mode_without_am_pm_selection_when_enabled,
                seconds: trans.seconds,
                enables_seconds_in_the_time_picker: trans.enables_seconds_in_the_time_picker,
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

            let actions = collect(fieldActions[this.formStore.data.formFields.time_range]);
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
        config() {
            return {
                wrap: true,
                static: true,
                dateFormat: "h:i K",
                noCalendar: true,
                enableTime: true,
            }
        },
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
        'value.prefill_from'(newValue) {
            let prefillFrom = null;

            if (newValue) {
                prefillFrom = dayjs(newValue, "h:mm A").format("HH:mm");
                this.configPrefillTo.minTime = prefillFrom;
            } else {
                this.configPrefillTo.minTime = null;
            }
        }
    },
    methods: {
        logicUpdate: function (items) {
            this.value.logic = items;
        },
    }
}
</script>

<style>
.flatpickr-wrapper {
    width: 100% !important;
}
</style>
