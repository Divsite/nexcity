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
                :key="updateComponentKey"
                class="form-control"
                :placeholder="field.placeholder"
                :disabled="disableField()"
                :options="currencyOptions"
            />
            <div class="form-text" v-if="field.help">
                {{ field.help }}
            </div>
        </div>
    </div>
</template>

<script>
import {collect} from "collect.js";
import {conditionMet} from "../../../../helpers/conditional-rules";
import VueCurrencyInput from "../../Helpers/VueCurrencyInput.vue";

export default {
    name: "CurrencyView",
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
            value: this.formData,
            updateComponentKey: 0,
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
    watch: {
        'field.prefill'(newVal) {
            this.value[this.field.id] = newVal;
        },
        field: {
            handler() {
                this.updateComponentKey++;
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
