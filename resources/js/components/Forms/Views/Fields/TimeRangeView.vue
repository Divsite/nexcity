<template>
    <div :class="field.width">
        <div class="mb-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label :for="field.id + '_from'" class="form-label">
                            {{ field.time_from_label }}
                            <span class="text-danger" v-if="requiredField()">*</span>
                        </label>
                        <flat-pickr
                            v-model="value[field.id].from"
                            :config="config"
                            :id="field.id + '_from'"
                            :key="updateFromComponentKey"
                            :class="['form-control', disableField() ? 'flatpickr-disabled-input' : '']"
                            :disabled="disableField()"
                        />
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label :for="field.id + '_to'" class="form-label">
                            {{ field.time_to_label }}
                            <span class="text-danger" v-if="requiredField()">*</span>
                        </label>
                        <flat-pickr
                            v-model="value[field.id].to"
                            :config="configTimeTo"
                            :id="field.id + '_to'"
                            :key="updateToComponentKey"
                            :class="['form-control', disableField() ? 'flatpickr-disabled-input' : '']"
                            :disabled="disableField()"
                        />
                    </div>
                </div>

                <div class="col-md-12 mt-n3">
                    <div class="form-text" v-if="field.help">
                        {{ field.help }}
                    </div>
                </div>
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
    name: "TimeRangeView",
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
            updateFromComponentKey: 0,
            updateToComponentKey: 0,
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
        },
        configTimeTo() {
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
            if (this.value[this.field.id].from) {
                if (this.field.time_24hr) {
                    minTime = dayjs(this.value[this.field.id].from, "HH:mm").format("HH:mm")

                    if (this.field.enable_seconds) {
                        minTime = dayjs(this.value[this.field.id].from, "HH:mm:ss").format("HH:mm")
                    }
                } else {
                    minTime = dayjs(this.value[this.field.id].from, "h:mm A").format("HH:mm")

                    if (this.field.enable_seconds) {
                        minTime = dayjs(this.value[this.field.id].from, "h:mm:ss A").format("HH:mm")
                    }
                }
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
        'field.prefill_from'(newVal) {
            let val = null;

            if (newVal) {
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

            this.value[this.field.id].from = val;
        },
        'field.prefill_to'(newVal) {
            let val = null;

            if (newVal) {
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

            this.value[this.field.id].to = val;
        },
        field: {
            handler() {
                this.updateFromComponentKey++;
                this.updateToComponentKey++;
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
