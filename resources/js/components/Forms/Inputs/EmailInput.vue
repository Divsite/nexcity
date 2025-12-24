<template>
    <div :class="field.width">
        <div class="mb-3">
            <label :for="field.id" class="form-label">{{ field.label }} <span class="text-danger" v-if="requiredField()">*</span></label>
            <input type="email" v-model="value[field.id]" :id="field.id"
                   :class="['form-control', form.errors[field.id] ? 'is-invalid' : '']"
                   :placeholder="field.placeholder" :disabled="disableField()">
            <span class="invalid-feedback" v-if="form.errors[field.id]"><strong>{{ form.errors[field.id][0] }}</strong></span>
            <div class="text-muted mt-1 fst-italic fs-11" v-if="loading">
                {{ trans.please_wait_a_moment }}...
            </div>
            <div class="text-danger fw-medium mt-1 fs-11" v-if="urlHasError">
                <div v-if="urlErrorMessage">
                    {{ urlErrorMessage }}
                </div>
                <div v-if="urlValueError">
                    {{ trans.value_key_not_found_in_the_data }}
                </div>
            </div>
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
import {useFormSubmissionStore} from "../../../stores/FormSubmissionStore";
import {conditionMet} from "../../../helpers/conditional-rules";
import {collect} from "collect.js";

export default {
    name: "EmailInput",
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
            form: useFormSubmissionStore(),
            id: this.field.id,
            value: this.formData,
            actionTypes: actionTypes,
            dataSourceInput: dataSourceInput,
            appUrl: appUrl,
            urlData: '',
            urlHasError: false,
            urlErrorMessage: null,
            urlValueError: null,
            loading: false,
            trans: {
                please_wait_a_moment: trans.please_wait_a_moment,
                status_code: trans.status_code,
                value_key_not_found_in_the_data: trans.value_key_not_found_in_the_data,
            }
        }
    },
    async mounted() {
        this.loading = true;

        // Create submission only, edit submission not need fetch data
        if (!this.form.submissionId) {
            await this.getDataFromUrl();
        }

        this.loading = false;
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
        getDataFromUrl: async function () {
            if (this.field.data_source === this.dataSourceInput.url) {
                this.loading = true;

                // Reset options
                this.urlData = '';
                this.urlHasError = false;
                this.urlErrorMessage = null;

                let url = this.field.url;
                if (this.field.use_current_url) {
                    url = this.appUrl + this.field.url;
                }

                await axios.get(url)
                    .then(response => {
                        console.log(response);
                        this.urlData = response.data;

                        if (this.field.url_value) {
                            if (this.urlData.hasOwnProperty(this.field.url_value)) {
                                this.urlData = this.urlData[this.field.url_value];
                            } else {
                                this.urlData = null;
                                this.urlHasError = true;
                                this.urlValueError = true;
                            }
                        }
                    })
                    .catch(error => {
                        console.log(error);
                        this.urlHasError = true;
                        this.urlErrorMessage = error.toString();
                    })
                    .finally(() => this.loading = false)


                this.value[this.field.id] = this.urlData;
            }
        },
    },
}
</script>
