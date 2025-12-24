<template>
    <div :class="field.width">
        <div :class="['mb-3', form.errors[field.id] ? 'select2-is-invalid' : '']">
            <label :for="field.id" class="form-label">
                {{ field.label }}
                <span class="text-danger" v-if="requiredField()">*</span>
            </label>
            <select class="form-select select2" v-model="value[field.id]" :id="field.id"
                    :data-placeholder="field.placeholder" :disabled="disableField()" v-select2>
                <option v-for="(option, index) in optionsDropdown" :key="index" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <span class="invalid-feedback d-block" v-if="form.errors[field.id]">
                <strong>{{ form.errors[field.id][0] }}</strong>
            </span>
            <div class="text-muted mt-1 fst-italic fs-11" v-if="loading">
                {{ trans.please_wait_a_moment }}...
            </div>
            <div class="text-danger fw-medium mt-1 fs-11" v-if="urlHasError">
                <div v-if="urlErrorMessage">
                    {{ urlErrorMessage }}
                </div>
                <div v-if="urlValueLabelError">
                    {{ trans.value_and_label_key_not_found_in_the_list }}
                </div>
            </div>
            <div class="form-text" v-if="field.help">{{ field.help }}</div>
        </div>
    </div>
</template>

<script>
import {useFormSubmissionStore} from "../../../stores/FormSubmissionStore";
import {conditionMet} from "../../../helpers/conditional-rules";
import {collect} from "collect.js";
import {select2} from "../../../directives/select2";

export default {
    name: "SelectInput",
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
    directives: {select2},
    data() {
        return {
            form: useFormSubmissionStore(),
            id: this.field.id,
            value: this.formData,
            actionTypes: actionTypes,
            dataSourceInput: dataSourceInput,
            appUrl: appUrl,
            urlOptions: [],
            urlHasError: false,
            urlErrorMessage: null,
            urlValueLabelError: false,
            loading: false,
            trans: {
                please_wait_a_moment: trans.please_wait_a_moment,
                status_code: trans.status_code,
                value_and_label_key_not_found_in_the_list: trans.value_and_label_key_not_found_in_the_list,
            }
        }
    },
    async mounted() {
        this.loading = true;

        $(".select2").select2({
            language: {
                noResults: function () {
                    return messages.no_results_found;
                }
            }
        });

        await this.getOptionsFromUrl();

        this.loading = false;
    },
    computed: {
        optionsDropdown() {
            if (this.field.data_source === this.dataSourceInput.list) {
                return collect(this.field.options).where('active', true).all();
            }

            if (this.field.data_source === this.dataSourceInput.url) {
                let items = [];
                let options = collect(this.urlOptions);
                options.each(option => {
                    if (option.hasOwnProperty(this.field.url_value) && option.hasOwnProperty(this.field.url_label)) {
                        items.push({value: option[this.field.url_value], label: option[this.field.url_label]});
                    } else {
                        this.urlHasError = true;
                        this.urlValueLabelError = true;
                    }
                });
                return items;
            }
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
        },
        disableField: function () {
            if (this.loading) {
                return true;
            }

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
        },
        getOptionsFromUrl: async function () {
            if (this.field.data_source === this.dataSourceInput.url) {
                this.loading = true;

                // Reset options
                this.urlOptions = [];
                this.urlHasError = false;
                this.urlErrorMessage = null;
                this.urlValueLabelError = false;

                let url = this.field.url;
                if (this.field.use_current_url) {
                    url = this.appUrl + this.field.url;
                }

                await axios.get(url)
                    .then(response => {
                        console.log(response);
                        this.urlOptions = response.data;
                    })
                    .catch(error => {
                        console.log(error);
                        this.urlHasError = true;
                        this.urlErrorMessage = error.toString();
                    })
                    .finally(() => this.loading = false)
            }
        },
    },
}
</script>
