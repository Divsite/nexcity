<template>
    <div class="row">
        <div class="col-md-12">
            <div class="mb-1">
                <label for="general" class="form-label">{{ trans.general }}</label>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" :class="['form-check-input', errors.hidden ? 'is-invalid' : '']" id="hidden" v-model="value.hidden" :value="value.hidden">
                    <label for="hidden" class="form-check-label">
                        <div class="d-flex align-items-center">
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
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="name" class="form-label">{{ trans.text }} <span class="text-danger">*</span></label>
                <input type="text" id="text" name="text" v-model="value.text" :class="['form-control', errors.text ? 'is-invalid' : '']">
                <span class="invalid-feedback" v-if="errors.text"><strong>{{ errors.text[0] }}</strong></span>
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <label for="tag" class="form-label">{{ trans.tags }} <span class="text-danger">*</span></label>
                <select :class="['form-select', errors.tag ? 'is-invalid' : '']" v-model="value.tag">
                    <option v-for="heading in headingList" :value="heading">{{ heading.toUpperCase() }}</option>
                </select>
                <span class="invalid-feedback" v-if="errors.tag"><strong>{{ errors.tag[0] }}</strong></span>
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
    name: "HeadingField",
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
                text: this.form.text,
                tag: this.form.tag,
                logic: this.form.logic,
            },
            headingList: headingList,
            actionTypes: actionTypes,
            formStore: useFormStore(),
            trans: {
                general: trans.general,
                hidden: trans.hidden,
                text: trans.text,
                tags: trans.tags,
            }
        }
    },
    computed: {
        actionOptions() {
            let items = collect();

            let actions = collect(fieldActions[this.formStore.data.formFields.heading]);
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
        }
    },
    methods: {
        logicUpdate: function (items) {
            this.value.logic = items;
        }
    }
}
</script>
