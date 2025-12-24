<template>
    <div v-for="(item, index) in group" class="border rounded mb-3 p-3">
        <div class="row">
            <div class="col-lg-12">
                <div class="mb-3">
                    <label :for="'name_'+index" class="form-label">{{ trans.name }}</label>
                    <input type="text" :id="'name_'+index" v-model="item.name"
                           :class="['form-control', errors['logic.conditions.group.'+index+'.name'] ? 'is-invalid' : '']">
                    <span class="invalid-feedback" v-if="errors['logic.conditions.group.'+index+'.name']">
                        <strong>{{ errors['logic.conditions.group.' + index + '.name'][0] }}</strong>
                    </span>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="mb-3">
                    <label :for="'condition_type_'+index" class="form-label">
                        {{ trans.condition_type }} <span class="text-danger">*</span>
                    </label>
                    <select :id="'condition_type_'+index"
                            :class="['form-select', errors['logic.conditions.group.'+index+'.operator'] ? 'is-invalid' : '']"
                            v-model="item.operator">
                        <option v-for="(name, operator) in operatorName" :value="operator">
                            {{ name }}
                        </option>
                    </select>
                    <span class="invalid-feedback" v-if="errors['logic.conditions.group.'+index+'.operator']">
                        <strong>{{ errors['logic.conditions.group.' + index + '.operator'][0] }}</strong>
                    </span>
                </div>
            </div>

            <div class="col-lg-12" v-if="formStore.propertiesDropdown.length > 0">
                <label for="rules" class="form-label">{{ trans.rules }}</label>

                <div class="bg-light rounded mb-3 p-3" v-if="item.rules.length > 0"
                     v-for="(rule, counter) in item.rules">
                    <div class="row">
                        <div class="col-lg-12 text-end">
                            <button type="button" class="btn-close text-end" aria-label="Close"
                                    @click="removeRule(index, counter)"></button>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label :for="'field_'+index+'_'+counter" class="form-label">
                                    {{ trans.field }} <span class="text-danger">*</span>
                                </label>
                                <select :id="'field_'+index+'_'+counter"
                                        :class="['form-select', errors['logic.conditions.group.'+index+'.rules.'+counter+'.property_id'] ? 'is-invalid' : '']"
                                        v-model="rule.property_id" @change="updateCompare(index, counter)">
                                    <option disabled value="" selected>{{ trans.please_select }}</option>
                                    <option v-for="property in formStore.propertiesDropdown" :value="property.id">
                                        {{ property.name }}
                                    </option>
                                </select>
                                <span class="invalid-feedback"
                                      v-if="errors['logic.conditions.group.'+index+'.rules.'+counter+'.property_id']">
                                        <strong>
                                            {{ errors['logic.conditions.group.' + index + '.rules.' + counter + '.property_id'][0] }}
                                        </strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label :for="'compare_'+index+'_'+counter" class="form-label">
                                    {{ trans.compare }} <span class="text-danger">*</span>
                                </label>
                                <select :id="'compare_'+index+'_'+counter"
                                        :class="['form-select', errors['logic.conditions.group.'+index+'.rules.'+counter+'.compare'] ? 'is-invalid' : '']"
                                        v-model="rule.compare" @change="updateValue(index, counter)">
                                    <option disabled value="" selected>{{ trans.please_select }}</option>
                                    <option v-for="(compare, key) in comparisonOperatorField(rule.property_id)"
                                            :value="key">
                                        {{ compare.name }}
                                    </option>
                                </select>
                                <span class="invalid-feedback"
                                      v-if="errors['logic.conditions.group.'+index+'.rules.'+counter+'.compare']">
                                        <strong>
                                            {{ errors['logic.conditions.group.' + index + '.rules.' + counter + '.compare'][0] }}
                                        </strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-12" v-if="showValue(rule)">
                            <div class="mb-3">
                                <label :for="'value_'+index+'_'+counter" class="form-label">
                                    Value <span class="text-danger">*</span>
                                </label>
                                <input :type="comparisonOperatorField(rule.property_id)[rule.compare].input_type"
                                       :id="'value_'+index+'_'+counter" v-model="rule.value"
                                       :class="['form-control', errors['logic.conditions.group.'+index+'.rules.'+counter+'.value'] ? 'is-invalid' : '']">
                                <span class="invalid-feedback" v-if="errors['logic.conditions.group.'+index+'.rules.'+counter+'.value']">
                                    <strong>{{ errors['logic.conditions.group.'+index+'.rules.'+counter+'.value'][0] }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <p class="text-muted border border-dotted rounded p-2 text-center">
                        {{ trans.the_rule_list_is_empty }}
                    </p>
                </div>

                <button type="button" class="btn btn-soft-success btn-sm" @click="addRule(index)">
                    <i class="ri-add-line align-bottom me-1"></i> {{ trans.add_rule }}
                </button>
            </div>
            <div class="col-lg-12 mt-3" v-else>
                <p class="text-muted border border-dotted rounded p-2 text-center">{{ trans.no_input_fields }}</p>
            </div>

            <div class="col-lg-12">
                <hr class="border-1">
            </div>

            <div class="col-lg-12 text-end">
                <button type="button" class="btn btn-soft-info btn-sm mb-2 mt-1" @click="duplicateGroup(item)">
                    <i class="ri-file-copy-line align-bottom me-1"></i> {{ trans.duplicate }}
                </button>
                <button type="button" class="btn btn-soft-danger btn-sm ms-2 mb-2 mt-1" @click="destroyGroup(index)">
                    <i class="ri-delete-bin-5-line align-bottom me-1"></i> {{ trans.delete }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import {useFormStore} from "../../../stores/FormStore";
import {mapActions} from "pinia";

export default defineComponent({
    name: "ConditionGroup",
    props: {
        group: {
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
            formStore: useFormStore(),
            operatorName: operatorName,
            comparisonOperator: comparisonOperatorDetails,
            trans: {
                name: trans.name,
                delete: trans.delete,
                duplicate: trans.duplicate,
                condition_type: trans.condition_type,
                rules: trans.rules,
                the_rule_list_is_empty: trans.the_rule_list_is_empty,
                add_rule: trans.add_rule,
                field: trans.field,
                no_input_fields: trans.no_input_fields,
                please_select: trans.please_select,
                compare: trans.compare,
            }
        }
    },
    methods: {
        ...mapActions(useFormStore, ['findById']),
        addRule: function (index) {
            this.group[index].rules.push({property_id: '', compare: '', value: null});
        },
        removeRule: function (index, counter) {
            this.group[index].rules.splice(counter, 1);
        },
        destroyGroup: function (index) {
            this.group.splice(index, 1);
        },
        duplicateGroup: function (item) {
            this.group.push(JSON.parse(JSON.stringify(item)));
        },
        showValue: function (rule) {
            if (this.formStore.findById(rule.property_id) && this.comparisonOperator[this.formStore.findById(rule.property_id).type].hasOwnProperty(rule.compare)) {
                return this.comparisonOperator[this.formStore.findById(rule.property_id).type][rule.compare].required;
            }

            return false;
        },
        updateCompare: function (index, counter) {
            // Clear compare if property change
            this.group[index].rules[counter].compare = '';
        },
        updateValue: function (index, counter) {
            let compare = this.group[index].rules[counter].compare;
            let propertyId = this.group[index].rules[counter].property_id;

            if (this.formStore.findById(propertyId) && this.comparisonOperator[this.formStore.findById(propertyId).type].hasOwnProperty(compare)) {
                this.group[index].rules[counter].value = this.comparisonOperator[this.formStore.findById(propertyId).type][compare].value;
            }
        },
        comparisonOperatorField: function (id) {
            if (this.formStore.findById(id)) {
                return this.comparisonOperator[this.formStore.findById(id).type];
            }
        }
    },
})
</script>
