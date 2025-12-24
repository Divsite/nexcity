<template>
    <div :class="field.width">
        <div class="mb-3">
            <div class="form-label fw-medium">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </div>
            <div v-if="!field.inline && !field.week_numbers">
                <flat-pickr
                    v-model="value[field.id]"
                    :config="config"
                    :id="field.id"
                    :key="field.id"
                    :class="['form-control', disableField() ? 'flatpickr-disabled-input' : '']"
                    :placeholder="field.placeholder"
                    :disabled="disableField()"
                />
            </div>
            <div v-if="field.inline && field.week_numbers">
                <flat-pickr
                    v-model="value[field.id]"
                    :config="configInlineWeek"
                    :id="field.id"
                    :key="field.id"
                    :class="['form-control d-none', disableField() ? 'flatpickr-disabled-input' : '']"
                    :placeholder="field.placeholder"
                    :disabled="disableField()"
                />
            </div>
            <div v-if="field.inline && !field.week_numbers">
                <flat-pickr
                    v-model="value[field.id]"
                    :config="configInlineNotWeek"
                    :id="field.id"
                    :key="field.id"
                    :class="['form-control d-none', disableField() ? 'flatpickr-disabled-input' : '']"
                    :placeholder="field.placeholder"
                    :disabled="disableField()"
                />
            </div>
            <div v-if="!field.inline && field.week_numbers">
                <flat-pickr
                    v-model="value[field.id]"
                    :config="configNotInlineWeek"
                    :id="field.id"
                    :key="field.id"
                    :class="['form-control', disableField() ? 'flatpickr-disabled-input' : '']"
                    :placeholder="field.placeholder"
                    :disabled="disableField()"
                />
            </div>
            <div class="form-text mt-1" v-if="field.help">
                {{ field.help }}
            </div>
        </div>
    </div>
</template>

<script>
import {collect} from "collect.js";
import {conditionMet} from "../../../../helpers/conditional-rules";
import flatPickr from "vue-flatpickr-component";

export default {
    name: "DateView",
    components: {flatPickr},
    props: {
        field: {
            type: Object,
            required: false,
        },
        formData: {
            type: Object,
            required: false,
        },
    },
    data() {
        return {
            value: this.formData,
            actionTypes: actionTypes,
            minMaxOptionList: minMaxOptionList,
            config: {
                altInput: true,
                altFormat: this.field.date_format,
                dateFormat: "Y-m-d",
                minDate: null,
                maxDate: null,
                disable: [],
                inline: false,
                weekNumbers: false,
                locale: flatpickrLocale,
            },
            // Need separate inline config
            configInlineWeek: {
                altInput: true,
                altFormat: this.field.date_format,
                dateFormat: "Y-m-d",
                minDate: null,
                maxDate: null,
                disable: [],
                inline: true,
                weekNumbers: true,
                locale: flatpickrLocale,
            },
            configInlineNotWeek: {
                altInput: true,
                altFormat: this.field.date_format,
                dateFormat: "Y-m-d",
                minDate: null,
                maxDate: null,
                disable: [],
                inline: true,
                weekNumbers: false,
                locale: flatpickrLocale,
            },
            configNotInlineWeek: {
                altInput: true,
                altFormat: this.field.date_format,
                dateFormat: "Y-m-d",
                minDate: null,
                maxDate: null,
                disable: [],
                inline: false,
                weekNumbers: true,
                locale: flatpickrLocale,
            },
        }
    },
    mounted() {
        this.updateConfig();
    },
    watch: {
        field: {
            handler() {
                this.updateConfig();
            },
            deep: true
        },
    },
    methods: {
        requiredField: function () {
            let val = this.field.required;

            if (this.field.logic.enabled) {
                if (conditionMet(this.field, this.value)) {
                    if (this.field.logic.actions && this.field.logic.actions.length > 0) {
                        let actions = collect(this.field.logic.actions);

                        actions.each((action) => {
                            if (action.type === this.actionTypes.require) {
                                val = true;
                            }

                            if (action.type === this.actionTypes.optional) {
                                val = false;
                            }
                        });
                    }
                }
            }

            return val;
        },
        disableField: function () {
            let val = this.field.disabled;

            if (this.field.logic.enabled) {
                if (conditionMet(this.field, this.value)) {
                    if (this.field.logic.actions && this.field.logic.actions.length > 0) {
                        let actions = collect(this.field.logic.actions);

                        actions.each((action) => {
                            if (action.type === this.actionTypes.disable) {
                                val = true;
                            }

                            if (action.type === this.actionTypes.enable) {
                                val = false;
                            }
                        });
                    }
                }
            }

            return val;
        },
        updateConfig: function () {
            if (this.field.prefill) {
                this.value[this.field.id] = this.field.prefill;
            } else {
                this.value[this.field.id] = null;
            }

            this.config.altFormat = this.field.date_format;
            this.configInlineWeek.altFormat = this.field.date_format;
            this.configInlineNotWeek.altFormat = this.field.date_format;
            this.configNotInlineWeek.altFormat = this.field.date_format;

            // Check disable past dates is checked
            if (this.field.disable_past_dates) {
                this.config.minDate = "today";
                this.configInlineWeek.minDate = "today";
                this.configInlineNotWeek.minDate = "today";
                this.configNotInlineWeek.minDate = "today";
            } else {
                this.config.minDate = null;
                this.configInlineWeek.minDate = null;
                this.configInlineNotWeek.minDate = null;
                this.configNotInlineWeek.minDate = null;
            }

            // Min and max options
            if (this.field.min_max_options === this.minMaxOptionList.specific_date) {
                if (!this.field.disable_past_dates) {
                    if (this.field.min_date) {
                        this.config.minDate = this.field.min_date;
                        this.configInlineWeek.minDate = this.field.min_date;
                        this.configInlineNotWeek.minDate = this.field.min_date;
                        this.configNotInlineWeek.minDate = this.field.min_date;
                    } else {
                        this.config.minDate = null;
                        this.configInlineWeek.minDate = null;
                        this.configInlineNotWeek.minDate = null;
                        this.configNotInlineWeek.minDate = null;
                    }
                }

                if (this.field.max_date) {
                    this.config.maxDate = this.field.max_date;
                    this.configInlineWeek.maxDate = this.field.max_date;
                    this.configInlineNotWeek.maxDate = this.field.max_date;
                    this.configNotInlineWeek.maxDate = this.field.max_date;
                } else {
                    this.config.maxDate = null;
                    this.configInlineWeek.maxDate = null;
                    this.configInlineNotWeek.maxDate = null;
                    this.configNotInlineWeek.maxDate = null;
                }
            }

            if (this.field.min_max_options === this.minMaxOptionList.number_days) {
                if (!this.field.disable_past_dates) {
                    if (this.field.min_number_days) {
                        this.config.minDate = new Date().fp_incr(this.field.min_number_days);
                        this.configInlineWeek.minDate = new Date().fp_incr(this.field.min_number_days);
                        this.configInlineNotWeek.minDate = new Date().fp_incr(this.field.min_number_days);
                        this.configNotInlineWeek.minDate = new Date().fp_incr(this.field.min_number_days);
                    } else {
                        this.config.minDate = null;
                        this.configInlineWeek.minDate = null;
                        this.configInlineNotWeek.minDate = null;
                        this.configNotInlineWeek.minDate = null;
                    }
                }

                if (this.field.max_number_days) {
                    this.config.maxDate = new Date().fp_incr(this.field.max_number_days);
                    this.configInlineWeek.maxDate = new Date().fp_incr(this.field.max_number_days);
                    this.configInlineNotWeek.maxDate = new Date().fp_incr(this.field.max_number_days);
                    this.configNotInlineWeek.maxDate = new Date().fp_incr(this.field.max_number_days);
                } else {
                    this.config.maxDate = null;
                    this.configInlineWeek.maxDate = null;
                    this.configInlineNotWeek.maxDate = null;
                    this.configNotInlineWeek.maxDate = null;
                }
            }

            // Check has disable dates
            if (this.field.disable_dates) {
                this.config.disable = this.field.disable_dates;
                this.configInlineWeek.disable = this.field.disable_dates;
                this.configInlineNotWeek.disable = this.field.disable_dates;
                this.configNotInlineWeek.disable = this.field.disable_dates;
            } else {
                this.config.disable = [];
                this.configInlineWeek.disable = [];
                this.configInlineNotWeek.disable = [];
                this.configNotInlineWeek.disable = [];
            }
        },
    }
}
</script>

<style scoped>
.flatpickr-disabled-input {
    background-color: #eff2f7 !important;
    cursor: default !important;
}
</style>
