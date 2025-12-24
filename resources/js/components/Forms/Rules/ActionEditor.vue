<template>
    <p class="fw-semibold fs-14 mt-4">2. {{ trans.actions }}</p>

    <template v-if="actionExist">
        <div v-for="(item, index) in value" class="border rounded mb-3 p-3">
            <div class="row">
                <div class="col-lg-12 text-end">
                    <button type="button" class="btn-close text-end" aria-label="Close"
                            @click="removeAction(index)"></button>
                </div>
                <div class="col-lg-12">
                    <div class="mb-3">
                        <label :for="'action_'+index" class="form-label">
                            {{ trans.action }} <span class="text-danger">*</span>
                        </label>
                        <select :id="'action_'+index"
                                :class="['form-select', errors['logic.actions.'+index+'.type'] ? 'is-invalid' : '']"
                                v-model="item.type">
                            <option disabled value="" selected>{{ trans.please_select }}</option>
                            <option v-for="(action, index) in actionOptions" :value="action.value" :key="index">
                                {{ action.name }}
                            </option>
                        </select>
                        <div class="form-text">
                            <template v-if="operator === conditionalRuleOperator.all">
                                {{ trans.action_triggerred_when_all_conditions_are_met }}
                            </template>
                            <template v-if="operator === conditionalRuleOperator.any">
                                {{ trans.action_triggerred_when_any_conditions_are_met }}
                            </template>
                        </div>
                        <span class="invalid-feedback" v-if="errors['logic.actions.'+index+'.type']">
                            <strong>{{ errors['logic.actions.' + index + '.type'][0] }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <template v-else>
        <p class="text-muted border rounded p-2 text-center">
            {{ trans.the_action_list_is_empty }}
        </p>
    </template>

    <button type="button" class="btn btn-info" @click="addAction">
        <i class="ri-add-line align-bottom me-1"></i> {{ trans.add_action }}
    </button>
</template>

<script>
import {defineComponent} from 'vue'
import {collect} from "collect.js";

export default defineComponent({
    name: "ActionEditor",
    emits: ['action-updated'],
    props: {
        actions: {
            type: Array,
            required: false,
        },
        operator: {
            type: String,
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
    data() {
        return {
            value: this.actions,
            conditionalRuleOperator: conditionalRuleOperator,
            trans: {
                actions: trans.actions,
                action: trans.action,
                duplicate: trans.duplicate,
                delete: trans.delete,
                please_select: trans.please_select,
                the_action_list_is_empty: trans.the_action_list_is_empty,
                add_action: trans.add_action,
                action_triggerred_when_all_conditions_are_met: trans.action_triggerred_when_all_conditions_are_met,
                action_triggerred_when_any_conditions_are_met: trans.action_triggerred_when_any_conditions_are_met,
            }
        }
    },
    computed: {
        actionExist() {
            return this.value && this.value.length > 0;
        }
    },
    watch: {
        actionOptions(options) {
            let items = collect(options).pluck('value').push('').all();
            this.value = collect(this.value).whereIn('type', items).all();
        },
        value: {
            handler() {
                this.$emit('action-updated', this.value);
            },
            deep: true
        },
    },
    methods: {
        addAction: function () {
            if (!Array.isArray(this.value)) {
                this.value = [];
            }

            this.value.push({type: ''});
        },
        removeAction: function (index) {
            this.value.splice(index, 1);
        }
    }
})
</script>
