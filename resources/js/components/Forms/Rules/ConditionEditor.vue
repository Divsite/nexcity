<template>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <h5 class="mb-1">{{ trans.conditional_rules }}</h5>
            <small class="text-muted fs-12">
                {{ trans.start_by_adding_some_conditions_and_then_add_some_actions }}
            </small>
        </div>

        <div class="col-md-12 mt-4" v-if="formStore.propertiesDropdown.length > 0">
            <div class="text-center" v-if="!value.enabled">
                <div class="avatar-md mb-4 mx-auto">
                    <div class="avatar-title bg-info-subtle text-info display-5 rounded-circle">
                        <i class="ri-brackets-line"></i>
                    </div>
                </div>
                <h5>{{ trans.no_conditions }}</h5>
                <p class="text-muted">{{ trans.the_condition_list_is_empty }}</p>
                <button type="button" class="btn btn-soft-info" @click="initCondition">
                    {{ trans.add_condition }}
                </button>
            </div>

            <div v-if="value.enabled">
                <p class="fw-semibold fs-14">1. {{ trans.conditions }}</p>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="condition_type" class="form-label">
                                {{ trans.condition_type }} <span class="text-danger">*</span>
                            </label>
                            <select
                                :class="['form-select', errors['logic.conditions.operator'] ? 'is-invalid' : '']"
                                v-model="value.conditions.operator">
                                <option v-for="(name, operator) in operatorName" :value="operator">
                                    {{ name }}
                                </option>
                            </select>
                            <span class="invalid-feedback" v-if="errors['logic.conditions.operator']">
                                <strong>{{ errors['logic.conditions.operator'][0] }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <ConditionGroup v-if="value.conditions.group" :group="value.conditions.group"
                                :errors="errors"></ConditionGroup>

                <button type="button" class="btn btn-info" @click="addGroup">
                    <i class="ri-add-line align-bottom me-1"></i> {{ trans.add_group }}
                </button>

                <ActionEditor :actions="value.actions" :action-options="actionOptions"
                              :operator="value.conditions.operator"
                              :errors="errors" @action-updated="actionUpdate"></ActionEditor>
            </div>
        </div>
        <div class="col-md-12 mt-4" v-else>
            <div class="text-center">
                <div class="avatar-md mb-4 mx-auto">
                    <div class="avatar-title bg-warning-subtle text-warning display-5 rounded-circle">
                        <i class="ri-information-fill"></i>
                    </div>
                </div>
                <h5>{{ trans.no_input_fields }}</h5>
                <p class="text-muted">{{ trans.add_input_field_to_use_conditional_rules }}</p>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import ConditionGroup from "./ConditionGroup.vue";
import {useFormStore} from "../../../stores/FormStore";
import ActionEditor from "./ActionEditor.vue";

export default defineComponent({
    name: "ConditionEditor",
    emits: ['logic-updated'],
    props: {
        logic: {
            type: Object,
            required: false,
        },
        actionOptions: {
            type: Object,
            required: false,
        },
        errors: {
            type: Object,
            required: false,
        },
    },
    components: {ActionEditor, ConditionGroup},
    data() {
        return {
            value: {
                enabled: this.logic.enabled,
                conditions: this.logic.conditions,
                actions: this.logic.actions,
            },
            defaultOperator: defaultOperator,
            operatorName: operatorName,
            formStore: useFormStore(),
            trans: {
                conditional_rules: trans.conditional_rules,
                start_by_adding_some_conditions_and_then_add_some_actions: trans.start_by_adding_some_conditions_and_then_add_some_actions,
                conditions: trans.conditions,
                add_condition: trans.add_condition,
                add_group: trans.add_group,
                no_conditions: trans.no_conditions,
                the_condition_list_is_empty: trans.the_condition_list_is_empty,
                condition_type: trans.condition_type,
                no_input_fields: trans.no_input_fields,
                add_input_field_to_use_conditional_rules: trans.add_input_field_to_use_conditional_rules,
            }
        }
    },
    watch: {
        value: {
            handler() {
                if (this.value.conditions !== null) {
                    if (this.value.conditions.group.length === 0) {
                        this.value.enabled = false;
                        this.value.conditions = null;
                        this.value.actions = null;
                    }
                }

                this.$emit('logic-updated', this.value);
            },
            deep: true
        },
    },
    methods: {
        initCondition: async function () {
            this.value.enabled = true;

            this.value.conditions = {
                operator: this.defaultOperator,
                group: [],
            };

            this.value.conditions.group.push({
                name: null,
                operator: this.defaultOperator,
                rules: [{property_id: '', compare: '', value: null}],
            });

            this.value.actions = [];
            this.value.actions.push({type: ''});
        },
        addGroup: function () {
            this.value.conditions.group.push({
                name: null,
                operator: this.defaultOperator,
                rules: [{property_id: '', compare: '', value: null}],
            })
        },
        actionUpdate: function (item) {
            this.value.actions = item;
        }
    },
})
</script>
