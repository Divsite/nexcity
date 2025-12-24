<template>
    <div id="edit-field-modal" class="modal zoomIn" tabindex="-1" aria-labelledby="edit-field-modal-title"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit-field-modal-title">{{ form.name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            @click="handleClose"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        {{ trans.field_name }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="name" name="name" v-model="form.name"
                                           :class="['form-control', errors.name ? 'is-invalid' : '']">
                                    <span class="invalid-feedback" v-if="errors.name">
                                        <strong>{{ errors.name[0] }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <template v-if="form.type === formFields.heading">
                            <heading-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.text">
                            <text-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.email">
                            <email-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.textarea">
                            <textarea-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.hidden">
                            <hidden-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.paragraph">
                            <paragraph-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.url">
                            <url-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.number">
                            <number-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.date">
                            <date-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.page_break">
                            <page-break-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.divider">
                            <divider-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"/>
                        </template>

                        <template v-if="form.type === formFields.snippet">
                            <snippet-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.phone">
                            <phone-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.radio">
                            <radio-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"></radio-field>
                        </template>

                        <template v-if="form.type === formFields.checkbox">
                            <checkbox-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.select">
                            <select-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.file">
                            <file-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.currency">
                            <currency-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.time">
                            <time-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.time_range">
                            <time-range-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.checkbox_group">
                            <checkbox-group-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>

                        <template v-if="form.type === formFields.date_range">
                            <date-range-field
                                :form="forms()"
                                :errors="errors"
                                @value-updated="valueUpdate"
                                @action-options="updateActionOptions"
                                :key="form.id"
                            />
                        </template>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-icon ms-3" :disabled="loading"
                            @click="deleteField" data-bs-toggle="tooltip" data-bs-trigger="hover"
                            data-bs-placement="top" :title="trans.delete">
                        <i class="ri-delete-bin-line fs-18"></i>
                    </button>
                    <button type="button" class="btn btn-info btn-icon btn-load" :disabled="loading"
                            @click="duplicateField"
                            data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top"
                            :title="trans.duplicate">
                        <span class="d-flex justify-content-center">
                            <span class="spinner-border" role="status" v-if="duplicate_loading">
                                <span class="visually-hidden">{{ trans.loading }}</span>
                            </span>
                            <span v-if="!duplicate_loading">
                               <i class="ri-file-copy-line fs-18"></i>
                            </span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" :disabled="loading"
                            @click="handleClose">{{ trans.close }}
                    </button>
                    <button class="btn btn-primary btn-load" type="button" :disabled="loading"
                            @click.prevent="updateField()" :key="submitFormKey">
                        <span class="d-flex justify-content-center">
                            <span class="spinner-border" role="status" v-if="submit_loading">
                                <span class="visually-hidden">{{ trans.loading }}</span>
                            </span>
                            <span :class="[submit_loading ? 'ms-2' : '']">
                                {{ trans.save }}
                            </span>
                        </span>
                    </button>
                </div>

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</template>

<script>
import {useFormStore} from "../../../stores/FormStore";
import HeadingField from "../Fields/HeadingField.vue";
import TextField from "../Fields/TextField.vue";
import EmailField from "../Fields/EmailField.vue";
import TextareaField from "../Fields/TextareaField.vue";
import HiddenField from '../Fields/HiddenField.vue';
import ParagraphField from '../Fields/ParagraphField.vue';
import UrlField from '../Fields/UrlField.vue';
import NumberField from '../Fields/NumberField.vue';
import DateField from '../Fields/DateField.vue';
import PageBreakField from "../Fields/PageBreakField.vue";
import DividerField from "../Fields/DividerField.vue";
import SnippetField from "../Fields/SnippetField.vue";
import PhoneField from "../Fields/PhoneField.vue";
import RadioField from '../Fields/RadioField.vue';
import CheckboxField from '../Fields/CheckboxField.vue';
import SelectField from "../Fields/SelectField.vue";
import FileField from "../Fields/FileField.vue";
import CurrencyField from "../Fields/CurrencyField.vue";
import TimeField from "../Fields/TimeField.vue";
import TimeRangeField from "../Fields/TimeRangeField.vue";
import CheckboxGroupField from "../Fields/CheckboxGroupField.vue";
import DateRangeField from "../Fields/DateRangeField.vue";

export default {
    name: "FormEditFieldModal",
    components: {
        HeadingField,
        TextField,
        EmailField,
        TextareaField,
        HiddenField,
        ParagraphField,
        UrlField,
        NumberField,
        DateField,
        PageBreakField,
        DividerField,
        SnippetField,
        PhoneField,
        RadioField,
        CheckboxField,
        SelectField,
        FileField,
        CurrencyField,
        TimeField,
        TimeRangeField,
        CheckboxGroupField,
        DateRangeField,
    },
    props: {
        field: {
            type: Object,
            required: false
        },
        show: {
            type: Boolean,
            required: false
        }
    },
    data() {
        return {
            form: Object.assign({}, this.field), // copy from field, update when click button save
            editBlockModal: null,
            formStore: useFormStore(),
            actionOptions: null,
            submitFormKey: 0,
            loading: false,
            submit_loading: false,
            duplicate_loading: false,
            errors: [],
            formFields: formFields,
            trans: {
                field_name: trans.field_name,
                close: trans.close,
                save: trans.save,
                loading: trans.loading,
                are_you_sure_you_want_to_delete_this_field: trans.are_you_sure_you_want_to_delete_this_field,
                if_you_do_any_data_associated_with_this_field_will_be_deleted_too_if_this_form_has_at_least_one_form_submission_you_should_export_your_data_first: trans.if_you_do_any_data_associated_with_this_field_will_be_deleted_too_if_this_form_has_at_least_one_form_submission_you_should_export_your_data_first,
                delete: trans.delete,
                duplicate: trans.duplicate,
            }
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        if (this.show) {
            this.editBlockModal = new bootstrap.Modal('#edit-field-modal', {
                keyboard: false,
                backdrop: 'static',
            });
            this.editBlockModal.show();
        }
    },
    methods: {
        updateField: async function () {
            this.loading = true;
            this.submit_loading = true;

            let data = JSON.parse(JSON.stringify(this.form));

            // Add input field only to validate
            data.properties_dropdown = this.formStore.propertiesDropdown;
            // Action for current field
            data.action_options = this.actionOptions;
            // Get field type for conditional rules
            data.properties = this.formStore.properties;

            await axios.post(route('form-builder.edit-field'), data).then(response => {
                console.log(response);
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading
                this.submit_loading = false;

                // Get clean data
                let properties = response.data.properties;
                for (let key in properties) {
                    if (properties.hasOwnProperty(key)) {
                        this.form[key] = properties[key];
                    }
                }

                this.$emit('field-updated', this.form);
                this.handleClose();
                this.editBlockModal.hide();
            }).catch((error) => {
                console.log(error.response);
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submitFormKey++;
                this.loading = false; // Stop loading
                this.submit_loading = false;
            });
        },
        deleteField: async function () {
            let title = window.messages.are_you_sure;
            let text = window.messages.you_wont_be_able_to_revert_this;

            if (this.formStore.id) {
                title = this.trans.are_you_sure_you_want_to_delete_this_field;
                text = this.trans.if_you_do_any_data_associated_with_this_field_will_be_deleted_too_if_this_form_has_at_least_one_form_submission_you_should_export_your_data_first;
            }

            await Swal.fire({
                title: title,
                text: text,
                icon: "warning",
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                    cancelButton: 'btn btn-danger w-xs mt-2',
                },
                confirmButtonText: window.messages.yes_delete_it,
                cancelButtonText: window.messages.cancel,
                buttonsStyling: false,
                showCloseButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.formStore.removePropertyRule(this.form.id);
                    this.formStore.removeInput(this.form.id);
                    this.$emit('field-deleted');
                    this.handleClose();
                    this.editBlockModal.hide();
                }
            });
        },
        duplicateField: async function () {
            this.loading = true;
            this.duplicate_loading = true;

            let data = new FormData();
            data.append('name', this.form.name);
            data.append('type', this.form.type);

            await axios.post(route('form-builder.add-field'), data).then(response => {
                console.log(response);
                this.loading = false;
                this.duplicate_loading = false;

                if (response.data.status) {
                    let item = JSON.parse(JSON.stringify(this.field));
                    item.id = response.data.generate_id;

                    this.$emit('field-duplicate', item);
                    this.handleClose();
                    this.editBlockModal.hide();
                }
            }).catch((error) => {
                console.log(error.response);

                // If not page expired (419)
                if (error.response.status !== 419) {
                    Swal.fire({
                        icon: 'error',
                        title: window.messages.oops,
                        text: error.response.data.message,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    });
                }

                this.loading = false;
                this.duplicate_loading = false;
            });
        },
        /**
         * Prepared value to pass to selected field components
         *
         * @returns {null}
         */
        forms: function () {
            let field = null;

            if (this.form.type === this.formFields.heading) {
                field = {
                    hidden: this.form.hidden,
                    text: this.form.text,
                    tag: this.form.tag,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.text) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    data_source: this.form.data_source,
                    prefill: this.form.prefill,
                    column_name: this.form.column_name,
                    input_group: this.form.input_group,
                    left_text_input_group: this.form.left_text_input_group,
                    right_text_input_group: this.form.right_text_input_group,
                    display_input_group_text: this.form.display_input_group_text,
                    help: this.form.help,
                    width: this.form.width,
                    max_char_limit: this.form.max_char_limit,
                    show_char_limit: this.form.show_char_limit,
                    url: this.form.url,
                    use_current_url: this.form.use_current_url,
                    url_value: this.form.url_value,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if ( this.form.type === this.formFields.email) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    data_source: this.form.data_source,
                    prefill: this.form.prefill,
                    column_name: this.form.column_name,
                    help: this.form.help,
                    width: this.form.width,
                    max_char_limit: this.form.max_char_limit,
                    show_char_limit: this.form.show_char_limit,
                    url: this.form.url,
                    use_current_url: this.form.use_current_url,
                    url_value: this.form.url_value,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.textarea) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    data_source: this.form.data_source,
                    prefill: this.form.prefill,
                    column_name: this.form.column_name,
                    help: this.form.help,
                    size: this.form.size,
                    width: this.form.width,
                    max_char_limit: this.form.max_char_limit,
                    show_char_limit: this.form.show_char_limit,
                    url: this.form.url,
                    use_current_url: this.form.use_current_url,
                    url_value: this.form.url_value,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.hidden) {
                field = {
                    data_source: this.form.data_source,
                    prefill: this.form.prefill,
                    column_name: this.form.column_name,
                }
            }

            if (this.form.type === this.formFields.paragraph) {
                field = {
                    hidden: this.form.hidden,
                    text: this.form.text,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.url) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    prefill: this.form.prefill,
                    help: this.form.help,
                    width: this.form.width,
                    max_char_limit: this.form.max_char_limit,
                    show_char_limit: this.form.show_char_limit,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.number) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    prefill: this.form.prefill,
                    help: this.form.help,
                    width: this.form.width,
                    min_number: this.form.min_number,
                    max_number: this.form.max_number,
                    step_number: this.form.step_number,
                    number_pattern: this.form.number_pattern,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.date) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    date_format: this.form.date_format,
                    prefill: this.form.prefill,
                    disable_past_dates: this.form.disable_past_dates,
                    inline: this.form.inline,
                    week_numbers: this.form.week_numbers,
                    min_max_options: this.form.min_max_options,
                    min_date: this.form.min_date,
                    max_date: this.form.max_date,
                    min_number_days: this.form.min_number_days,
                    max_number_days: this.form.max_number_days,
                    disable_dates: this.form.disable_dates,
                    help: this.form.help,
                    width: this.form.width,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.page_break) {
                field = {
                    next_btn_text: this.form.next_btn_text,
                    previous_btn_text: this.form.previous_btn_text,
                }
            }

            if (this.form.type === this.formFields.divider) {
                field = {
                    hidden: this.form.hidden,
                    thickness: this.form.thickness,
                    color: this.form.color,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.snippet) {
                field = {
                    hidden: this.form.hidden,
                    content: this.form.content,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.phone) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    data_source: this.form.data_source,
                    prefill: this.form.prefill,
                    column_name: this.form.column_name,
                    minlength: this.form.minlength,
                    maxlength: this.form.maxlength,
                    pattern: this.form.pattern,
                    help: this.form.help,
                    width: this.form.width,
                    url: this.form.url,
                    use_current_url: this.form.use_current_url,
                    url_value: this.form.url_value,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.radio) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    label: this.form.label,
                    help: this.form.help,
                    horizontal: this.form.horizontal,
                    outline: this.form.outline,
                    options: JSON.parse(JSON.stringify(this.form.options)),
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.checkbox) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    prefill: this.form.prefill,
                    help: this.form.help,
                    toggle_switch: this.form.toggle_switch,
                    width: this.form.width,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.select) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    help: this.form.help,
                    width: this.form.width,
                    data_source: this.form.data_source,
                    options: JSON.parse(JSON.stringify(this.form.options)),
                    url: this.form.url,
                    use_current_url: this.form.use_current_url,
                    url_value: this.form.url_value,
                    url_label: this.form.url_label,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.file) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    accept_files: this.form.accept_files,
                    multiple_files: this.form.multiple_files,
                    max_files: this.form.max_files,
                    max_file_size: this.form.max_file_size,
                    help: this.form.help,
                    width: this.form.width,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.currency) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    prefill: this.form.prefill,
                    currency: this.form.currency,
                    precision: this.form.precision,
                    min_value: this.form.min_value,
                    max_value: this.form.max_value,
                    auto_decimal_digits: this.form.auto_decimal_digits,
                    hide_currency_symbol_on_focus: this.form.hide_currency_symbol_on_focus,
                    help: this.form.help,
                    width: this.form.width,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.time) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    prefill: this.form.prefill,
                    min_time: this.form.min_time,
                    max_time: this.form.max_time,
                    time_24hr: this.form.time_24hr,
                    enable_seconds: this.form.enable_seconds,
                    help: this.form.help,
                    width: this.form.width,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.time_range) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    time_from_label: this.form.time_from_label,
                    time_to_label: this.form.time_to_label,
                    prefill_from: this.form.prefill_from,
                    prefill_to: this.form.prefill_to,
                    min_time: this.form.min_time,
                    max_time: this.form.max_time,
                    time_24hr: this.form.time_24hr,
                    enable_seconds: this.form.enable_seconds,
                    help: this.form.help,
                    width: this.form.width,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.checkbox_group) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    label: this.form.label,
                    help: this.form.help,
                    horizontal: this.form.horizontal,
                    outline: this.form.outline,
                    toggle_switch: this.form.toggle_switch,
                    data_source: this.form.data_source,
                    options: JSON.parse(JSON.stringify(this.form.options)),
                    url: this.form.url,
                    use_current_url: this.form.use_current_url,
                    url_value: this.form.url_value,
                    url_label: this.form.url_label,
                    url_tooltip: this.form.url_tooltip,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            if (this.form.type === this.formFields.date_range) {
                field = {
                    hidden: this.form.hidden,
                    required: this.form.required,
                    disabled: this.form.disabled,
                    label: this.form.label,
                    placeholder: this.form.placeholder,
                    date_format: this.form.date_format,
                    prefill: this.form.prefill,
                    disable_past_dates: this.form.disable_past_dates,
                    inline: this.form.inline,
                    week_numbers: this.form.week_numbers,
                    min_max_options: this.form.min_max_options,
                    min_date: this.form.min_date,
                    max_date: this.form.max_date,
                    min_number_days: this.form.min_number_days,
                    max_number_days: this.form.max_number_days,
                    disable_dates: this.form.disable_dates,
                    help: this.form.help,
                    width: this.form.width,
                    logic: JSON.parse(JSON.stringify(this.form.logic)),
                }
            }

            return field;
        },
        /**
         * Update value from each field's components
         *
         * @param value
         */
        valueUpdate: function (value) {
            if (this.form.type === this.formFields.heading) {
                this.form.hidden = value.hidden;
                this.form.text = value.text;
                this.form.tag = value.tag;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.text) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.data_source = value.data_source;
                this.form.prefill = value.prefill;
                this.form.column_name = value.column_name;
                this.form.input_group = value.input_group;
                this.form.left_text_input_group = value.left_text_input_group;
                this.form.right_text_input_group = value.right_text_input_group;
                this.form.display_input_group_text = value.display_input_group_text;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.max_char_limit = value.max_char_limit;
                this.form.show_char_limit = value.show_char_limit;
                this.form.url = value.url;
                this.form.use_current_url = value.use_current_url;
                this.form.url_value = value.url_value;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.email) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.data_source = value.data_source;
                this.form.prefill = value.prefill;
                this.form.column_name = value.column_name;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.max_char_limit = value.max_char_limit;
                this.form.show_char_limit = value.show_char_limit;
                this.form.url = value.url;
                this.form.use_current_url = value.use_current_url;
                this.form.url_value = value.url_value;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.textarea) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.data_source = value.data_source;
                this.form.prefill = value.prefill;
                this.form.column_name = value.column_name;
                this.form.help = value.help;
                this.form.size = value.size;
                this.form.width = value.width;
                this.form.max_char_limit = value.max_char_limit;
                this.form.show_char_limit = value.show_char_limit;
                this.form.url = value.url;
                this.form.use_current_url = value.use_current_url;
                this.form.url_value = value.url_value;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.hidden) {
                this.form.data_source = value.data_source;
                this.form.prefill = value.prefill;
                this.form.column_name = value.column_name;
            }

            if (this.form.type === this.formFields.paragraph) {
                this.form.hidden = value.hidden;
                this.form.text = value.text;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.url) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.prefill = value.prefill;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.max_char_limit = value.max_char_limit;
                this.form.show_char_limit = value.show_char_limit;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.number) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.prefill = value.prefill;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.min_number = value.min_number;
                this.form.max_number = value.max_number;
                this.form.step_number = value.step_number;
                this.form.number_pattern = value.number_pattern;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.date) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.date_format = value.date_format;
                this.form.prefill = value.prefill;
                this.form.disable_past_dates = value.disable_past_dates;
                this.form.inline = value.inline;
                this.form.week_numbers = value.week_numbers;
                this.form.min_max_options = value.min_max_options;
                this.form.min_date = value.min_date;
                this.form.max_date = value.max_date;
                this.form.min_number_days = value.min_number_days;
                this.form.max_number_days = value.max_number_days;
                this.form.disable_dates = value.disable_dates;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.page_break) {
                this.form.next_btn_text = value.next_btn_text;
                this.form.previous_btn_text = value.previous_btn_text;
            }

            if (this.form.type === this.formFields.divider) {
                this.form.hidden = value.hidden;
                this.form.thickness = value.thickness;
                this.form.color = value.color;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.snippet) {
                this.form.hidden = value.hidden;
                this.form.content = value.content;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.phone) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.data_source = value.data_source;
                this.form.prefill = value.prefill;
                this.form.column_name = value.column_name;
                this.form.minlength = value.minlength;
                this.form.maxlength = value.maxlength;
                this.form.pattern = value.pattern;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.url = value.url;
                this.form.use_current_url = value.use_current_url;
                this.form.url_value = value.url_value;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.radio) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.label = value.label;
                this.form.help = value.help;
                this.form.horizontal = value.horizontal;
                this.form.outline = value.outline;
                this.form.options = value.options;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.checkbox) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.prefill = value.prefill;
                this.form.help = value.help;
                this.form.toggle_switch = value.toggle_switch;
                this.form.width = value.width;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.select) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.data_source = value.data_source;
                this.form.options = value.options;
                this.form.url = value.url;
                this.form.use_current_url = value.use_current_url;
                this.form.url_value = value.url_value;
                this.form.url_label = value.url_label;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.file) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.accept_files = value.accept_files;
                this.form.multiple_files = value.multiple_files;
                this.form.max_files = value.max_files;
                this.form.max_file_size = value.max_file_size;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.currency) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.prefill = value.prefill;
                this.form.currency = value.currency;
                this.form.precision = value.precision;
                this.form.min_value = value.min_value;
                this.form.max_value = value.max_value;
                this.form.auto_decimal_digits = value.auto_decimal_digits;
                this.form.hide_currency_symbol_on_focus = value.hide_currency_symbol_on_focus;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.time) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.prefill = value.prefill;
                this.form.min_time = value.min_time;
                this.form.max_time = value.max_time;
                this.form.time_24hr = value.time_24hr;
                this.form.enable_seconds = value.enable_seconds;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.time_range) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.time_from_label = value.time_from_label;
                this.form.time_to_label = value.time_to_label;
                this.form.prefill_from = value.prefill_from;
                this.form.prefill_to = value.prefill_to;
                this.form.min_time = value.min_time;
                this.form.max_time = value.max_time;
                this.form.time_24hr = value.time_24hr;
                this.form.enable_seconds = value.enable_seconds;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.checkbox_group) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.label = value.label;
                this.form.help = value.help;
                this.form.horizontal = value.horizontal;
                this.form.outline = value.outline;
                this.form.toggle_switch = value.toggle_switch;
                this.form.data_source = value.data_source;
                this.form.options = value.options;
                this.form.url = value.url;
                this.form.use_current_url = value.use_current_url;
                this.form.url_value = value.url_value;
                this.form.url_label = value.url_label;
                this.form.url_tooltip = value.url_tooltip;
                this.form.logic = value.logic;
            }

            if (this.form.type === this.formFields.date_range) {
                this.form.hidden = value.hidden;
                this.form.required = value.required;
                this.form.disabled = value.disabled;
                this.form.label = value.label;
                this.form.placeholder = value.placeholder;
                this.form.date_format = value.date_format;
                this.form.prefill = value.prefill;
                this.form.disable_past_dates = value.disable_past_dates;
                this.form.inline = value.inline;
                this.form.week_numbers = value.week_numbers;
                this.form.min_max_options = value.min_max_options;
                this.form.min_date = value.min_date;
                this.form.max_date = value.max_date;
                this.form.min_number_days = value.min_number_days;
                this.form.max_number_days = value.max_number_days;
                this.form.disable_dates = value.disable_dates;
                this.form.help = value.help;
                this.form.width = value.width;
                this.form.logic = value.logic;
            }
        },
        updateActionOptions: function (value) {
            this.actionOptions = value;
        },
        handleClose() {
            this.form = {};
            this.$emit('close');
        }
    }
}
</script>
