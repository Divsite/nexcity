<template>
    <div class="row justify-content-center">
        <div class="col-xxl-10">
            <!-- danger Alert -->
            <div class="alert alert-danger" role="alert" v-if="hasErrorOtherPage">
                <strong>{{ form.error_message }}</strong>
            </div>

            <div class="card">
                <div class="card-header border-bottom-dashed align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">{{ form.name }}</h4>
                </div>
                <div class="card-body">
                    <Transition name="slide-fade" mode="out-in">
                        <div v-if="!submitted" key="form">
                            <template v-if="form.properties.length > 0">
                                <form method="POST" enctype="multipart/form-data" @submit.prevent="submitForm"
                                      autocomplete="off" novalidate>
                                    <div class="row">
                                        <template v-for="field in fieldPages[currentPageIndex]">
                                            <component :is="getFieldComponents(field)"
                                                       v-if="getFieldComponents(field) && !hiddenField[field.id]" :field="field"
                                                       :formData="formData" :key="field.id"></component>
                                        </template>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 text-center mt-2">
                                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                                <button type="button" class="btn btn-light w-lg btn-label"
                                                        @click="onPreviousClicked"
                                                        v-if="currentPageIndex > 0 && previousPageBreak"
                                                        :disabled="loading" :key="'previous'">
                                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    {{ previousLabel }}
                                                </button>

                                                <button type="button"
                                                        class="btn btn-primary w-lg btn-label right btn-load"
                                                        :disabled="loading" @click.prevent="onNextClicked"
                                                        v-if="!isLastPage" :key="'next'">
                                                    <span class="d-flex justify-content-center">
                                                        <span class="spinner-border" role="status" v-if="loading">
                                                            <span class="visually-hidden">{{ trans.loading }}</span>
                                                        </span>
                                                        <span :class="[loading ? 'ms-2' : '']">
                                                            <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                            {{ nextLabel }}
                                                        </span>
                                                    </span>
                                                </button>

                                                <button class="btn btn-primary btn-load w-lg" type="submit"
                                                        :disabled="loading" :key="submitFormKey"
                                                        v-if="!hasPageBreak || isLastPage">
                                                    <span class="d-flex justify-content-center">
                                                        <span class="spinner-border" role="status" v-if="loading">
                                                            <span class="visually-hidden">{{ trans.loading }}</span>
                                                        </span>
                                                        <span :class="[loading ? 'ms-2' : '']">
                                                            {{ trans.submit }}
                                                        </span>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </template>
                            <template v-else>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex aligns-items-center justify-content-center">
                                            <i class="mdi mdi-form-select text-primary" style="font-size: 7rem"></i>
                                        </div>
                                        <div
                                            class="d-flex aligns-items-center justify-content-center fs-14 fw-semibold">
                                            {{ trans.start_building_your_form_its_fast_easy_and_fun }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div v-else key="submitted">
                            <div class="text-center">
                                <div class="avatar-md mb-4 mx-auto">
                                    <div class="avatar-title bg-light text-success display-4 rounded-circle">
                                        <i class="ri-checkbox-circle-fill"></i>
                                    </div>
                                </div>
                                <h5>{{ trans.thank_you }}</h5>
                                <p class="text-muted">{{ trans.your_form_has_been_successfully_submitted }}</p>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import {useFormSubmissionStore} from "../../stores/FormSubmissionStore";
import {collect} from "collect.js";
import {conditionMet} from "../../helpers/conditional-rules";
import dayjs from "dayjs";
import HeadingInput from "./Inputs/HeadingInput.vue";
import TextInput from "./Inputs/TextInput.vue";
import EmailInput from "./Inputs/EmailInput.vue";
import TextareaInput from "./Inputs/TextareaInput.vue";
import DateInput from "./Inputs/DateInput.vue";
import DividerInput from "./Inputs/DividerInput.vue";
import SnippetInput from "./Inputs/SnippetInput.vue";
import PhoneInput from "./Inputs/PhoneInput.vue";
import NumberInput from './Inputs/NumberInput.vue';
import UrlInput from "./Inputs/UrlInput.vue";
import ParagraphInput from './Inputs/ParagraphInput.vue';
import HiddenInput from './Inputs/HiddenInput.vue';
import RadioInput from "./Inputs/RadioInput.vue";
import CheckboxInput from "./Inputs/CheckboxInput.vue";
import SelectInput from "./Inputs/SelectInput.vue";
import FileInput from "./Inputs/FileInput.vue";
import CurrencyInput from "./Inputs/CurrencyInput.vue";
import TimeInput from "./Inputs/TimeInput.vue";
import TimeRangeInput from "./Inputs/TimeRangeInput.vue";
import CheckboxGroupInput from "./Inputs/CheckboxGroupInput.vue";
import DateRangeInput from "./Inputs/DateRangeInput.vue";

export default defineComponent({
    name: "FormRender",
    components: {
        HeadingInput,
        TextInput,
        EmailInput,
        TextareaInput,
        DividerInput,
        SnippetInput,
        DateInput,
        PhoneInput,
        NumberInput,
        UrlInput,
        ParagraphInput,
        HiddenInput,
        RadioInput,
        CheckboxInput,
        SelectInput,
        FileInput,
        CurrencyInput,
        TimeInput,
        TimeRangeInput,
        CheckboxGroupInput,
        DateRangeInput,
    },
    data() {
        return {
            form: useFormSubmissionStore(),
            formData: data,
            currentPageIndex: 0,
            hasErrorOtherPage: false,
            formFields: formFields,
            actionTypes: actionTypes,
            loading: false,
            submitFormKey: 0,
            submitted: false,
            updateRouteName: updateRouteName,
            trans: {
                start_building_your_form_its_fast_easy_and_fun: trans.start_building_your_form_its_fast_easy_and_fun,
                submit: trans.submit,
                loading: trans.loading,
                thank_you: trans.thank_you,
                your_form_has_been_successfully_submitted: trans.your_form_has_been_successfully_submitted,
                next: trans.next,
                previous: trans.previous,
            },
        }
    },
    computed: {
        hasPageBreak() {
            let properties = collect(this.form.properties);
            return properties.some((property) => property.type === this.formFields.page_break);
        },
        fieldPages: function () {
            let pages = [];
            let currentPage = [];

            let properties = collect(this.form.properties);

            properties.each((property) => {
                currentPage.push(property);

                if (property.type === this.formFields.page_break) {
                    pages.push(currentPage);
                    currentPage = [];
                }
            })

            // Last page
            pages.push(currentPage);

            return pages;
        },
        totalPages() {
            // Start with page 1
            let total = 1;

            if (this.hasPageBreak) {
                let properties = collect(this.form.properties);
                properties.each((property) => {
                    if (property.type === this.formFields.page_break) {
                        total++;
                    }
                });
            }

            return total;
        },
        currentPageBreak() {
            let pageBreak = collect(this.fieldPages[this.currentPageIndex]);
            return pageBreak.firstWhere('type', this.formFields.page_break);
        },
        previousPageBreak() {
            if (this.currentPageIndex !== 0) {
                let pageBreak = collect(this.fieldPages[this.currentPageIndex - 1]);
                return pageBreak.firstWhere('type', this.formFields.page_break);
            }

            return null;
        },
        nextLabel() {
            if (this.currentPageBreak !== null && this.currentPageBreak.next_btn_text) {
                return this.currentPageBreak.next_btn_text;
            }
            return this.trans.next;
        },
        previousLabel() {
            if (
                this.currentPageIndex !== 0 &&
                this.previousPageBreak !== null &&
                this.previousPageBreak.previous_btn_text
            ) {
                return this.previousPageBreak.previous_btn_text;
            }

            return this.trans.previous;
        },
        isLastPage() {
            return this.currentPageIndex === (this.fieldPages.length - 1)
        },
        hiddenField() {
            let values = {};

            if (this.form.properties.length > 0) {
                let properties = collect(this.form.properties);
                properties.each((property) => {

                    if (
                        property.type === this.formFields.heading ||
                        property.type === this.formFields.text ||
                        property.type === this.formFields.email ||
                        property.type === this.formFields.textarea ||
                        property.type === this.formFields.divider ||
                        property.type === this.formFields.snippet ||
                        property.type === this.formFields.date ||
                        property.type === this.formFields.phone ||
                        property.type === this.formFields.number ||
                        property.type === this.formFields.url ||
                        property.type === this.formFields.paragraph ||
                        property.type === this.formFields.radio ||
                        property.type === this.formFields.checkbox ||
                        property.type === this.formFields.select ||
                        property.type === this.formFields.file ||
                        property.type === this.formFields.currency ||
                        property.type === this.formFields.time ||
                        property.type === this.formFields.time_range ||
                        property.type === this.formFields.checkbox_group ||
                        property.type === this.formFields.date_range
                    ) {
                        values[property.id] = this.hiddenCondition(property);
                    }

                });
            }

            return values;
        },
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            let data = JSON.parse(JSON.stringify(this.formData));

            let method = 'POST';
            let url = route('forms.submissions.store', {form: this.form.id});
            if (this.form.submissionId) {
                method = 'PUT';
                url = route(this.updateRouteName, {submission: this.form.submissionId});
            }
            data._method = method;

            data.current_page_index = this.currentPageIndex;

            await axios.post(url, data).then(response => {
                console.log(response);
                this.form.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.success) {
                    this.loading = true;
                    this.submitted = true;
                }
            }).catch((error) => {
                console.log(error.response);
                if (error.response.status === 422) {
                    this.form.errors = error.response.data.errors;
                    this.form.error_message = error.response.data.message;
                    this.errorsOtherPages();
                }
                this.submitFormKey++;
                this.loading = false; // Stop loading
                this.submitted = false;
            });
        },
        getFieldComponents: function (field) {
            switch (field.type) {
                case this.formFields.heading:
                    return "HeadingInput";
                case this.formFields.text:
                    return "TextInput";
                case this.formFields.email:
                    return "EmailInput";
                case this.formFields.textarea:
                    return "TextareaInput";
                case this.formFields.divider:
                    return "DividerInput";
                case this.formFields.snippet:
                    return "SnippetInput";
                case this.formFields.date:
                    return "DateInput";
                case this.formFields.phone:
                    return "PhoneInput";
                case this.formFields.number:
                    return "NumberInput";
                case this.formFields.url:
                    return "UrlInput";
                case this.formFields.paragraph:
                    return "ParagraphInput";
                case this.formFields.hidden:
                    return "HiddenInput";
                case this.formFields.radio:
                    return "RadioInput";
                case this.formFields.checkbox:
                    return "CheckboxInput";
                case this.formFields.select:
                    return "SelectInput";
                case this.formFields.file:
                    return "FileInput";
                case this.formFields.currency:
                    return "CurrencyInput";
                case this.formFields.time:
                    return "TimeInput";
                case this.formFields.time_range:
                    return "TimeRangeInput";
                case this.formFields.checkbox_group:
                    return "CheckboxGroupInput";
                case this.formFields.date_range:
                    return "DateRangeInput";
                default:
                    return null;
            }
        },
        onNextClicked: async function () {
            if (this.currentPageIndex < this.totalPages - 1) {
                this.loading = true;
                this.hasErrorOtherPage = false;

                let data = JSON.parse(JSON.stringify(this.formData));

                let method = 'POST';
                let url = route('forms.submissions.store', {form: this.form.id});
                if (this.form.submissionId) {
                    method = 'PUT';
                    url = route('submissions.update', {submission: this.form.submissionId});
                }
                data._method = method;

                data.current_page_index = this.currentPageIndex;

                await axios.post(url, data).then(response => {
                    console.log(response);
                    this.form.errors = []; // Clear errors
                    this.loading = false; // Stop loading

                    this.currentPageIndex++;
                }).catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        this.form.errors = error.response.data.errors;
                    }
                    this.loading = false; // Stop loading
                });
            }
        },
        onPreviousClicked: function () {
            if (this.currentPageIndex > 0) {
                this.currentPageIndex--;
            }
        },
        errorsOtherPages: function () {
            this.hasErrorOtherPage = false;

            let errors = collect(this.form.errors);

            let fieldPages = collect(this.fieldPages);
            fieldPages.each((page, index) => {
                let properties = collect(page);
                properties.each((property) => {
                    if (this.currentPageIndex !== index && errors.has(property.id)) {
                        this.hasErrorOtherPage = true;
                    }
                })
            })
        },
        hiddenCondition: function (property) {
            let val = property.hidden;

            if (property.logic.enabled) {
                if (conditionMet(property, this.formData)) {
                    if (property.logic.actions && property.logic.actions.length > 0) {
                        let actions = collect(property.logic.actions);

                        actions.each((action) => {
                            if (action.type === this.actionTypes.show) {
                                val = false;
                            }

                            if (action.type === this.actionTypes.hide) {
                                val = true;
                                this.resetInput(property);
                            }
                        });
                    }
                }
            }

            return val;
        },
        resetInput: function (property) {
            if (this.formData.hasOwnProperty(property.id)) {
                if (
                    property.type === this.formFields.text ||
                    property.type === this.formFields.email ||
                    property.type === this.formFields.textarea||
                    property.type === this.formFields.date ||
                    property.type === this.formFields.phone ||
                    property.type === this.formFields.number ||
                    property.type === this.formFields.url ||
                    property.type === this.formFields.radio ||
                    property.type === this.formFields.checkbox ||
                    property.type === this.formFields.select ||
                    property.type === this.formFields.currency ||
                    property.type === this.formFields.date_range
                ) {
                    this.formData[property.id] = property.prefill ?? null;
                }

                if (
                    property.type === this.formFields.file ||
                    property.type === this.formFields.checkbox_group
                ) {
                    this.formData[property.id] = [];
                }

                if (property.type === this.formFields.time) {
                    let val = null;

                    if (property.prefill !== null) {
                        if (property.time_24hr) {
                            val = dayjs(property.prefill, "h:mm A").format("HH:mm")

                            if (property.enable_seconds) {
                                val = dayjs(property.prefill, "h:mm A").format("HH:mm:ss")
                            }
                        } else {
                            val = dayjs(property.prefill, "h:mm A").format("h:mm A")

                            if (property.enable_seconds) {
                                val = dayjs(property.prefill, "h:mm A").format("h:mm:ss A")
                            }
                        }
                    }

                    this.formData[property.id] = val;
                }

                if (property.type === this.formFields.time_range) {
                    let valFrom = null;
                    let valTo = null;

                    if (property.prefill_from) {
                        if (property.time_24hr) {
                            valFrom = dayjs(property.prefill_from, "h:mm A").format("HH:mm")

                            if (property.enable_seconds) {
                                valFrom = dayjs(property.prefill_from, "h:mm A").format("HH:mm:ss")
                            }
                        } else {
                            valFrom = dayjs(property.prefill_from, "h:mm A").format("h:mm A")

                            if (property.enable_seconds) {
                                valFrom = dayjs(property.prefill_from, "h:mm A").format("h:mm:ss A")
                            }
                        }
                    }

                    if (property.prefill_to) {
                        if (property.time_24hr) {
                            valTo = dayjs(property.prefill_to, "h:mm A").format("HH:mm")

                            if (property.enable_seconds) {
                                valTo = dayjs(property.prefill_to, "h:mm A").format("HH:mm:ss")
                            }
                        } else {
                            valTo = dayjs(property.prefill_to, "h:mm A").format("h:mm A")

                            if (property.enable_seconds) {
                                valTo = dayjs(property.prefill_to, "h:mm A").format("h:mm:ss A")
                            }
                        }
                    }

                    this.formData[property.id] = {
                        from: valFrom,
                        to: valTo,
                    };
                }
            }
        }
    }
})
</script>

<style scoped>
/*
  Enter and leave animations can use different
  durations and timing functions.
*/
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
