<template>
    <div :class="field.width">
        <div :class="errorClass">
            <div class="form-label fw-medium">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </div>
            <flat-pickr
                v-model="value[field.id]"
                :config="config"
                :id="field.id"
                :key="field.id"
                :class="dateFlatpickrClass"
                :placeholder="field.placeholder"
                :disabled="disableField()"
            />
            <div class="invalid-feedback d-block" v-if="form.errors[field.id]">
                <strong>{{ form.errors[field.id][0] }}</strong>
            </div>
            <div class="form-text mt-1" v-if="field.help">
                {{ field.help }}
            </div>
        </div>
    </div>
</template>

<script>
import {useFormSubmissionStore} from "../../../stores/FormSubmissionStore";
import {conditionMet} from "../../../helpers/conditional-rules";
import {collect} from "collect.js";
import flatPickr from "vue-flatpickr-component";

export default {
    name: "DateInput",
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
            form: useFormSubmissionStore(),
            id: this.field.id,
            value: this.formData,
            actionTypes: actionTypes,
            minMaxOptionList: minMaxOptionList,
        }
    },
    computed: {
        config() {
            let minDate = null;
            let maxDate = null;

            if (this.field.disable_past_dates) {
                minDate = "today";
            }

            if (this.field.min_max_options === this.minMaxOptionList.specific_date) {
                if (this.field.min_date) {
                    minDate = this.field.min_date;
                }

                if (this.field.max_date) {
                    maxDate = this.field.max_date;
                }
            }

            if (this.field.min_max_options === this.minMaxOptionList.number_days) {
                if (this.field.min_number_days) {
                    minDate = new Date().fp_incr(this.field.min_number_days);
                }

                if (this.field.max_number_days) {
                    maxDate = new Date().fp_incr(this.field.max_number_days);
                }
            }

            return {
                altInput: true,
                altFormat: this.field.date_format,
                dateFormat: "Y-m-d",
                minDate: minDate,
                maxDate: maxDate,
                disable: this.field.disable_dates ?? [],
                inline: this.field.inline,
                weekNumbers: this.field.week_numbers,
                locale: flatpickrLocale,
            };
        },
        dateFlatpickrClass() {
            return {
                'flatpickr-disabled-input': this.disableField(),
                'd-none': this.field.inline,
            }
        },
        errorClass() {
            return {
                'mb-3': true,
                'flatpickr-is-invalid': !!this.form.errors[this.field.id],
            }
        }
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
        }
    },
}
</script>

<style scoped>
.flatpickr-disabled-input {
    background-color: #eff2f7 !important;
    cursor: default !important;
}
</style>
