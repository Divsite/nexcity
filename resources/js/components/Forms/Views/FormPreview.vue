<template>
    <div class="card">
        <div class="card-body">
            <template v-if="properties.length > 0">
                <form method="POST" autocomplete="off" novalidate>
                    <div class="row">
                        <template v-for="field in fieldPages[currentPageIndex]">
                            <component :is="getFieldComponents(field)"
                                       v-if="getFieldComponents(field) && !hiddenField[field.id]" :field="field"
                                       :formData="inputs" :key="field.id"></component>
                        </template>
                    </div>

                    <div class="row">
                        <div class="col-12 text-center mt-2">
                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <button type="button" class="btn btn-light w-lg btn-label"
                                        @click="onPreviousClicked" v-if="currentPageIndex > 0 && previousPageBreak">
                                    <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                    {{ previousLabel }}
                                </button>

                                <button type="button" class="btn btn-primary w-lg btn-label right"
                                        @click="onNextClicked" v-if="!isLastPage">
                                    <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                    {{ nextLabel }}
                                </button>

                                <button type="button" class="btn btn-primary w-lg" v-if="!hasPageBreak || isLastPage">
                                    {{ trans.submit }}
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
                        <div class="d-flex aligns-items-center justify-content-center fs-14 fw-semibold">
                            {{ trans.start_building_your_form_its_fast_easy_and_fun }}
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import {useFormStore} from "../../../stores/FormStore";
import {collect} from "collect.js";
import {mapWritableState} from "pinia";
import {conditionMet} from "../../../helpers/conditional-rules";
import dayjs from "dayjs";
import HeadingView from "./Fields/HeadingView.vue";
import TextView from "./Fields/TextView.vue";
import EmailView from "./Fields/EmailView.vue";
import TextareaView from "./Fields/TextareaView.vue";
import HiddenView from "./Fields/HiddenView.vue";
import ParagraphView from "./Fields/ParagraphView.vue";
import UrlView from "./Fields/UrlView.vue";
import NumberView from "./Fields/NumberView.vue";
import DateView from './Fields/DateView.vue';
import DividerView from './Fields/DividerView.vue';
import SnippetView from './Fields/SnippetView.vue';
import PhoneView from './Fields/PhoneView.vue';
import RadioView from "./Fields/RadioView.vue";
import CheckboxView from './Fields/CheckboxView.vue';
import SelectView from './Fields/SelectView.vue';
import FileView from './Fields/FileView.vue';
import CurrencyView from "./Fields/CurrencyView.vue";
import TimeView from './Fields/TimeView.vue';
import TimeRangeView from './Fields/TimeRangeView.vue';
import CheckboxGroupView from "./Fields/CheckboxGroupView.vue";
import DateRangeView from "./Fields/DateRangeView.vue";

export default {
    name: "FormPreview",
    components: {
        HeadingView,
        TextView,
        EmailView,
        TextareaView,
        HiddenView,
        ParagraphView,
        UrlView,
        NumberView,
        DateView,
        DividerView,
        SnippetView,
        PhoneView,
        RadioView,
        CheckboxView,
        SelectView,
        FileView,
        CurrencyView,
        TimeView,
        TimeRangeView,
        CheckboxGroupView,
        DateRangeView,
    },
    data() {
        return {
            currentPageIndex: 0,
            formFields: formFields,
            actionTypes: actionTypes,
            trans: {
                start_building_your_form_its_fast_easy_and_fun: trans.start_building_your_form_its_fast_easy_and_fun,
                submit: trans.submit,
                next: trans.next,
                previous: trans.previous,
            }
        }
    },
    computed: {
        ...mapWritableState(useFormStore, ['properties', 'inputs']),
        hasPageBreak() {
            let properties = collect(this.properties);
            return properties.some((property) => property.type === this.formFields.page_break);
        },
        fieldPages: function () {
            let pages = [];
            let currentPage = [];

            let properties = collect(this.properties);

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
                let properties = collect(this.properties);
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

            if (this.properties.length > 0) {
                let properties = collect(this.properties);
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
        getFieldComponents: function (field) {
            switch (field.type) {
                case this.formFields.heading:
                    return "HeadingView";
                case this.formFields.text:
                    return "TextView";
                case this.formFields.email:
                    return "EmailView";
                case this.formFields.textarea:
                    return "TextareaView";
                case this.formFields.hidden:
                    return "HiddenView";
                case this.formFields.paragraph:
                    return "ParagraphView";
                case this.formFields.url:
                    return "UrlView";
                case this.formFields.number:
                    return "NumberView";
                case this.formFields.date:
                    return "DateView";
                case this.formFields.divider:
                    return "DividerView";
                case this.formFields.snippet:
                    return "SnippetView";
                case this.formFields.phone:
                    return "PhoneView";
                case this.formFields.radio:
                    return "RadioView";
                case this.formFields.checkbox:
                    return "CheckboxView";
                case this.formFields.select:
                    return "SelectView";
                case this.formFields.file:
                    return "FileView";
                case this.formFields.currency:
                    return "CurrencyView";
                case this.formFields.time:
                    return "TimeView";
                case this.formFields.time_range:
                    return "TimeRangeView";
                case this.formFields.checkbox_group:
                    return "CheckboxGroupView";
                case this.formFields.date_range:
                    return "DateRangeView";
                default:
                    return null;
            }
        },
        onNextClicked: function () {
            if (this.currentPageIndex < this.totalPages - 1) {
                this.currentPageIndex++;
            }
        },
        onPreviousClicked: function () {
            if (this.currentPageIndex > 0) {
                this.currentPageIndex--;
            }
        },
        hiddenCondition: function (property) {
            let val = property.hidden;

            if (property.logic.enabled) {
                if (conditionMet(property, this.inputs)) {
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
            if (this.inputs.hasOwnProperty(property.id)) {
                if (
                    property.type === this.formFields.text ||
                    property.type === this.formFields.email ||
                    property.type === this.formFields.textarea ||
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
                    this.inputs[property.id] = property.prefill ?? null;
                }

                if (
                    property.type === this.formFields.file ||
                    property.type === this.formFields.checkbox_group
                ) {
                    this.inputs[property.id] = [];
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

                    this.inputs[property.id] = val;
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

                    this.inputs[property.id] = {
                        from: valFrom,
                        to: valTo,
                    };
                }
            }
        },
    }
}
</script>
