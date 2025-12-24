<template>
    <div :class="field.width">
        <div :class="['form-check mb-3', field.toggle_switch ? 'form-switch' : '']">
            <input type="checkbox" class="form-check-input" :id="field.id" v-model="value[field.id]"
                   :disabled="disableField()">
            <label :for="field.id" class="form-check-label">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </label>
            <div class="form-text mt-2" v-if="field.help">{{ field.help }}</div>
        </div>
    </div>
</template>

<script>
import {conditionMet} from "../../../../helpers/conditional-rules";
import {collect} from "collect.js";

export default {
    name: "CheckboxView",
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
        }
    },
    watch: {
        'field.prefill'(newVal) {
            this.value[this.field.id] = newVal;
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
        }
    }
}
</script>
