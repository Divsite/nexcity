<template>
    <div class="row">
        <div class="col-md-12">
            <div class="form-check form-switch mb-3">
                <input type="checkbox" :class="['form-check-input', errors.hidden ? 'is-invalid' : '']" id="hidden"
                       v-model="value.hidden" :value="value.hidden">
                <label for="hidden" class="form-check-label">
                    <div class="d-flex align-middle">
                        <span>{{ trans.hidden }}</span>
                        <i class="ri-information-line ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                           :title="trans.exclude_this_field"></i>
                    </div>
                </label>
            </div>
            <div class="invalid-feedback d-block mb-3" v-if="errors.hidden">
                <strong>{{ errors.hidden[0] }}</strong>
            </div>
        </div>
        <div class="col-md-12">
            <div class="mb-3">
                <label for="thickness" class="form-label">{{ trans.thickness }} <span
                    class="text-danger">*</span></label>
                <select :class="['form-select', errors.thickness ? 'is-invalid' : '']" v-model="value.thickness">
                    <option v-for="thickness in thicknessList" :value="thickness">{{ thickness }}</option>
                </select>
                <span class="invalid-feedback" v-if="errors.thickness"><strong>{{ errors.thickness[0] }}</strong></span>
            </div>
        </div>
        <div class="col-md-12">
            <div class="mb-3">
                <label for="color" class="form-label">{{ trans.color }} <span class="text-danger">*</span></label>
                <input type="color" id="color" v-model="value.color"
                       :class="['form-control form-control-color w-100', errors.color ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.color"><strong>{{ errors.color[0] }}</strong></span>
            </div>
        </div>
    </div>

    <condition-editor :logic="value.logic" :action-options="actionOptions" :errors="errors"
                      @logic-updated="logicUpdate"></condition-editor>

</template>

<script>
import ConditionEditor from "../Rules/ConditionEditor.vue";
import {useFormStore} from "../../../stores/FormStore";
import {collect} from "collect.js";

export default {
    name: "DividerField",
    emits: ['value-updated', 'action-options'],
    components: {ConditionEditor},
    props: {
        form: {
            type: Object,
            required: false,
        },
        errors: {
            type: Object,
            required: false,
        },
    },
    data() {
        return {
            value: {
                hidden: this.form.hidden,
                thickness: this.form.thickness,
                color: this.form.color,
                logic: this.form.logic,
            },
            actionTypes: actionTypes,
            formStore: useFormStore(),
            thicknessList: thicknessList,
            trans: {
                hidden: trans.hidden,
                thickness: trans.thickness,
                color: trans.color,
            }
        }
    },
    watch: {
        value: {
            handler() {
                this.$emit('value-updated', this.value);
            },
            deep: true
        },
        actionOptions: {
            handler() {
                this.$emit('action-options', this.actionOptions);
            },
            deep: true
        },
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    computed: {
        actionOptions() {
            let items = collect();

            let actions = collect(fieldActions[this.formStore.data.formFields.divider]);
            actions.each((trans, action) => {
                if (this.value.hidden) {
                    if (action === this.actionTypes.show) {
                        items.push({value: action, name: trans});
                    }
                } else {
                    if (action === this.actionTypes.hide) {
                        items.push({value: action, name: trans});
                    }
                }
            });

            this.$emit('action-options', items.all());
            return items.all();
        }
    },
    methods: {
        logicUpdate: function (items) {
            this.value.logic = items;
        },
    }
}
</script>
