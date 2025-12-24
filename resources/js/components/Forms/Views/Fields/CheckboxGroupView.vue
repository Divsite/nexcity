<template>
    <div class="mb-3">
        <div :id="field.id" class="form-label fw-medium">
            {{ field.label }} <span class="text-danger" v-if="requiredField()">*</span>
        </div>
        <template v-for="(option, index) in optionsDropdown" :key="index" v-if="!loading">
            <div :class="formCheckClass">
                <input type="checkbox" :id="field.id+`_`+index" :name="field.id" :value="option.value"
                       v-model="value[field.id]" class="form-check-input" :disabled="option.disabled">
                <label :for="field.id+`_`+index" class="form-check-label">
                    <div class="d-flex align-items-center">
                        {{ option.label }}
                        <i v-if="option.tooltip" class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                           :data-bs-original-title="option.tooltip"></i>
                    </div>
                </label>
            </div>
        </template>
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
        <div class="form-text mt-2" v-if="field.help">{{ field.help }}</div>
    </div>
</template>

<script>
import {collect} from "collect.js";
import {conditionMet} from "../../../../helpers/conditional-rules";
import {tooltip} from "../../../../directives/tooltip";

export default {
    name: "CheckboxGroupView",
    directives: {tooltip},
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

        await this.getOptionsFromUrl();

        this.loading = false;
    },
    computed: {
        optionsDropdown() {
            if (this.field.data_source === this.dataSourceInput.list) {
                return collect(this.field.options).all();
            }

            if (this.field.data_source === this.dataSourceInput.url) {
                let items = [];
                let options = collect(this.urlOptions);
                options.each(option => {
                    if (
                        option.hasOwnProperty(this.field.url_value) &&
                        option.hasOwnProperty(this.field.url_label)
                    ) {
                        let tooltip = null;

                        if (option.hasOwnProperty(this.field.url_tooltip)) {
                            if (option[this.field.url_tooltip]) {
                                tooltip = option[this.field.url_tooltip];
                            }
                        }

                        items.push({
                            value: option[this.field.url_value].toString(), // all value need to be a string to use in conditional rules
                            label: option[this.field.url_label],
                            tooltip: tooltip,
                        });
                    } else {
                        this.urlHasError = true;
                        this.urlValueLabelError = true;
                    }
                });
                return items;
            }
        },
        formCheckClass() {
            return {
                'form-check': true,
                'mb-2': true,
                'form-check-primary': this.field.outline,
                'form-check-inline': this.field.horizontal,
                'form-check-outline': this.field.outline && !this.field.toggle_switch,
                'form-switch': this.field.toggle_switch,
                'form-switch-custom': this.field.toggle_switch && this.field.outline,
                'form-switch-primary': this.field.toggle_switch && this.field.outline,
            };
        },
    },
    watch: {
        field: {
            handler() {
                // Reset checked
                this.value[this.field.id] = [];

                this.getOptionsFromUrl();
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
    }
}
</script>
