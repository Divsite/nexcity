<template>
    <div class="mb-3">
        <div>
            <label :for="field.id" class="form-label">
                {{ field.label }} <span class="text-danger" v-if="requiredField()">*</span>
            </label>
        </div>
        <template v-for="(option, index) in field.options" :key="index">
            <div class="form-check form-radio-primary"
                 :class="[field.horizontal ? 'form-check-inline' : 'mb-2', field.outline ? 'form-radio-outline' : '']">
                <input type="radio" :id="field.id+`_`+index" :name="field.id" :value="option.value"
                       v-model="value[field.id]" class="form-check-input" :disabled="option.disabled">
                <label :for="field.id+`_`+index" class="form-check-label">{{ option.label }}</label>
            </div>
        </template>
        <div class="form-text mt-2" v-if="field.help">{{ field.help }}</div>
    </div>
</template>

<script>
import {collect} from "collect.js";
import {conditionMet} from "../../../../helpers/conditional-rules";

export default {
    name: "RadioView",
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
        }
    }
}
</script>
