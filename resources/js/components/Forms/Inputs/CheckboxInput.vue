<template>
    <div :class="field.width">
        <div :class="['form-check mb-3', field.toggle_switch ? 'form-switch' : '']">
            <input type="checkbox" :id="field.id" v-model="value[field.id]"
                   :class="['form-check-input', form.errors[field.id] ? 'is-invalid' : '']" :disabled="disableField()">
            <label :for="field.id" class="form-check-label">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </label>
            <div class="invalid-feedback" v-if="form.errors[field.id]">
                <strong>{{ form.errors[field.id][0] }}</strong>
            </div>
            <div class="form-text mt-2" v-if="field.help">{{ field.help }}</div>
        </div>
    </div>
</template>

<script>
import {useFormSubmissionStore} from "../../../stores/FormSubmissionStore";
import {conditionMet} from "../../../helpers/conditional-rules";
import {collect} from "collect.js";

export default {
    name: "CheckboxInput",
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
