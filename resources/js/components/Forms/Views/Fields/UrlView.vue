<template>
    <div :class="field.width">
        <div class="mb-3">
            <label :for="field.id" class="form-label">{{ field.label }} <span class="text-danger" v-if="requiredField()">*</span></label>
            <input type="url" v-model="value[field.id]" :id="field.id" class="form-control"
                   :placeholder="field.placeholder" :disabled="disableField()">
            <div class="form-text">
                <div class="row">
                    <div v-if="field.help" :class="[field.show_char_limit ? 'col-8' : 'col-12']">
                        {{ field.help }}
                    </div>
                    <div v-if="field.show_char_limit" class="text-end" :class="[field.help ? 'col-4' : 'col-12']">
                        {{ value[field.id] ? value[field.id].length : 0 }}/{{ field.max_char_limit }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {collect} from "collect.js";
import {conditionMet} from "../../../../helpers/conditional-rules";

export default {
    name: "UrlView",
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
