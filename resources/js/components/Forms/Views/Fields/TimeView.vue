<template>
    <div :class="field.width">
        <div class="mb-3">
            <label :for="field.id" class="form-label">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </label>
            <flat-pickr
                v-model="value[field.id]"
                :config="config"
                :id="field.id"
                :key="updateComponentKey"
                :class="['form-control', disableField() ? 'flatpickr-disabled-input' : '']"
                :disabled="disableField()"
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
import flatPickr from "vue-flatpickr-component";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";

dayjs.extend(customParseFormat);

export default {
    name: "TimeView",
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
            updateComponentKey: 0,
        }
    },
    computed: {
        config() {
            let dateFormat = null;
            if (this.field.time_24hr) {
                dateFormat = "H:i";

                if (this.field.enable_seconds) {
                    dateFormat = "H:i:S";
                }
            } else {
                dateFormat = "h:i K";

                if (this.field.enable_seconds) {
                    dateFormat = "h:i:S K";
                }
            }

            let minTime = null;
            if (this.field.min_time) {
                minTime = dayjs(this.field.min_time, "h:mm A").format("HH:mm");
            }

            let maxTime = null;
            if (this.field.max_time) {
                maxTime = dayjs(this.field.max_time, "h:mm A").format("HH:mm");
            }

            return {
                dateFormat: dateFormat,
                noCalendar: true,
                enableTime: true,
                time_24hr: this.field.time_24hr,
                enableSeconds: this.field.enable_seconds,
                minTime: minTime,
                maxTime: maxTime,
            };
        }
    },
    watch: {
        'field.prefill'(newVal) {
            let val = null;

            if (newVal !== null) {
                if (this.field.time_24hr) {
                    val = dayjs(newVal, "h:mm A").format("HH:mm")

                    if (this.field.enable_seconds) {
                        val = dayjs(newVal, "h:mm A").format("HH:mm:ss")
                    }
                } else {
                    val = dayjs(newVal, "h:mm A").format("h:mm A")

                    if (this.field.enable_seconds) {
                        val = dayjs(newVal, "h:mm A").format("h:mm:ss A")
                    }
                }
            }

            this.value[this.field.id] = val;
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

<style scoped>
.flatpickr-disabled-input {
    background-color: #eff2f7 !important;
    cursor: default !important;
}
</style>
