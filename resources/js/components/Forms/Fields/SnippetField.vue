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
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="content" class="form-label">{{ trans.html_content }}</label>
                <CodeEditor v-model="value.content" :line-nums="true" :languages="[['html', 'HTML']]" height="150px"
                            :header="false" font-size="14px" width="100%" :theme="theme"></CodeEditor>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="preview" class="form-label">{{ trans.preview }}</label>
                <div v-html="value.content" class="border rounded p-4"></div>
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
import hljs from 'highlight.js';
import CodeEditor from "simple-code-editor";

export default {
    name: "SnippetField",
    emits: ['value-updated', 'action-options'],
    components: {
        ConditionEditor,
        CodeEditor,
    },
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
                content: this.form.content,
                logic: this.form.logic,
            },
            actionTypes: actionTypes,
            formStore: useFormStore(),
            trans: {
                general: trans.general,
                hidden: trans.hidden,
                exclude_this_field: trans.exclude_this_field,
                snippet: trans.snippet,
                html_content: trans.html_content,
                preview: trans.preview,
            }
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    computed: {
        actionOptions() {
            let items = collect();

            let actions = collect(fieldActions[this.formStore.data.formFields.snippet]);
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
        },
        theme() {
            if (localStorage.getItem("data-bs-theme") === 'light') {
                return 'github';
            }

            return 'github-dark';
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
        },
    }
}
</script>
