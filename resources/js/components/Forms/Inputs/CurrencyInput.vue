<template>
    <div :class="field.width">
        <div class="mb-3">
            <label :for="field.id" class="form-label">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </label>
            <VueCurrencyInput
                v-model="value[field.id]"
                :id="field.id"
                :class="['form-control', form.errors[field.id] ? 'is-invalid' : '']"
                :placeholder="field.placeholder"
                :disabled="disableField()"
                :options="currencyOptions"
            />
            <span class="invalid-feedback" v-if="form.errors[field.id]"><strong>{{ form.errors[field.id][0] }}</strong></span>
            <div class="form-text" v-if="field.help">
                {{ field.help }}
            </div>
        </div>
    </div>
</template>

<script>
import {useFormSubmissionStore} from "../../../stores/FormSubmissionStore";
import {conditionMet} from "../../../helpers/conditional-rules";
import {collect} from "collect.js";
import VueCurrencyInput from "../Helpers/VueCurrencyInput.vue";

export default {
    name: "CurrencyInput",
    components: {
        VueCurrencyInput
    },
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
            loading: false,
            trans: {}
        }
    },
    computed: {
        currencyOptions() {
            return {
                currency: this.field.currency,
                currencyDisplay: "narrowSymbol",
                precision: this.field.precision,
                valueRange: {
                    min: this.field.min_value ?? undefined,
                    max: this.field.max_value ?? undefined,
                },
                autoDecimalDigits: this.field.auto_decimal_digits,
                hideCurrencySymbolOnFocus: this.field.hide_currency_symbol_on_focus,
            };
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
            if (this.loading) {
                return true;
            }

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
    }
}
</script>
