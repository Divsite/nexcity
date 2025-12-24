<template>
    <form-add-field-modal :show="showAddFieldModal" @field-added="fieldAdded"
                          @close="showAddFieldModal = false"></form-add-field-modal>
    <template v-if="selectedFieldIndex !== null">
        <form-edit-field-modal :field="formFields[selectedFieldIndex]" :show="showEditFieldModal"
                               @field-updated="fieldUpdated" @field-deleted="fieldDeleted" @field-duplicate="fieldAdded"
                               @close="closeFormEditFieldModal"></form-edit-field-modal>
    </template>

    <div class="accordion-item shadow">
        <h2 class="accordion-header" id="form-structure">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#form-structure-collapse" aria-expanded="false"
                    aria-controls="form-structure-collapse">
                <i class="ri-function-line me-2"></i> {{ trans.form_structure }}
            </button>
        </h2>
        <div id="form-structure-collapse" class="accordion-collapse collapse show"
             aria-labelledby="form-structure">
            <div class="accordion-body">
                <template v-if="formFields.length > 0">
                    <draggable v-model="formFields" tag="ul" class="list-group mb-3" ghost-class="bg-dark-subtle"
                               :animation="200" handle=".handle" item-key="index">
                        <template #item="{element, index}">
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 handle" style="cursor: move">
                                                <i class="ri-arrow-up-down-fill fs-20"></i>
                                            </div>
                                            <div class="flex-shrink-1 ms-3">
                                                <h6 class="fs-14 mb-0 text-wrap">{{ element.name }}</h6>
                                                <small class="text-muted">{{ typeNames[element.type] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button type="button"
                                                class="btn btn-ghost-danger btn-icon btn-sm shadow-none me-1"
                                                @click="deleteField(index)"><i class="ri-delete-bin-line fs-18"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-ghost-primary btn-icon btn-sm shadow-none"
                                                @click="editField(index)"><i class="ri-settings-4-line fs-18"></i>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </draggable>
                </template>
                <template v-else>
                    <div class="row mb-3">
                        <div class="col-xxl-12">
                            <div class="d-flex aligns-items-center justify-content-center">
                                <i class="ri-information-line fs-36 text-info"></i>
                            </div>
                            <div class="d-flex aligns-items-center justify-content-center fs-16 fw-semibold">
                                {{ trans.no_fields_created }}
                            </div>
                        </div>
                    </div>
                </template>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" type="button" @click="showAddFieldModal = true">
                        <i class="ri-add-line align-bottom me-1"></i> {{ trans.add_field }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import FormAddFieldModal from "../Modals/FormAddFieldModal.vue";
import FormEditFieldModal from "../Modals/FormEditFieldModal.vue";
import draggable from 'vuedraggable';
import {useFormStore} from "../../../stores/FormStore";
import {collect} from "collect.js";
import dayjs from "dayjs";

export default {
    name: "FormStructure",
    components: {
        FormAddFieldModal,
        FormEditFieldModal,
        draggable,
    },
    data() {
        return {
            formFields: [],
            form: useFormStore(),
            selectedFieldIndex: null,
            showAddFieldModal: false,
            showEditFieldModal: false,
            trans: {
                form_structure: trans.form_structure,
                add_field: trans.add_field,
                heading: trans.heading,
                no_fields_created: trans.no_fields_created,
                are_you_sure_you_want_to_delete_this_field: trans.are_you_sure_you_want_to_delete_this_field,
                if_you_do_any_data_associated_with_this_field_will_be_deleted_too_if_this_form_has_at_least_one_form_submission_you_should_export_your_data_first: trans.if_you_do_any_data_associated_with_this_field_will_be_deleted_too_if_this_form_has_at_least_one_form_submission_you_should_export_your_data_first,
            },
            typeNames: formTypeNames,
        }
    },
    mounted() {
        if (this.form.properties.length > 0) {
            this.formFields = this.form.properties;
        }
    },
    watch: {
        formFields: {
            handler() {
                this.form.properties = this.formFields;

                // Prepare input
                let formFields = collect(this.formFields);
                formFields.each((item) => {
                    if (
                        item.type === this.form.data.formFields.text ||
                        item.type === this.form.data.formFields.email ||
                        item.type === this.form.data.formFields.textarea ||
                        item.type === this.form.data.formFields.date ||
                        item.type === this.form.data.formFields.phone ||
                        item.type === this.form.data.formFields.number ||
                        item.type === this.form.data.formFields.url ||
                        item.type === this.form.data.formFields.hidden ||
                        item.type === this.form.data.formFields.checkbox ||
                        item.type === this.form.data.formFields.currency ||
                        item.type === this.form.data.formFields.date_range
                    ) {
                        if (!this.form.inputs.hasOwnProperty(item.id)) {
                            this.form.inputs[item.id] = item.prefill;
                        }
                    }

                    if (
                        item.type === this.form.data.formFields.radio ||
                        item.type === this.form.data.formFields.select
                    ) {
                        if (!this.form.inputs.hasOwnProperty(item.id)) {
                            this.form.inputs[item.id] = null;
                        }
                    }

                    if (
                        item.type === this.form.data.formFields.file ||
                        item.type === this.form.data.formFields.checkbox_group
                    ) {
                        if (!this.form.inputs.hasOwnProperty(item.id)) {
                            this.form.inputs[item.id] = [];
                        }
                    }

                    if (item.type === this.form.data.formFields.time) {
                        let val = null;

                        if (item.prefill !== null) {
                            if (item.time_24hr) {
                                val = dayjs(item.prefill, "h:mm A").format("HH:mm")

                                if (item.enable_seconds) {
                                    val = dayjs(item.prefill, "h:mm A").format("HH:mm:ss")
                                }
                            } else {
                                val = dayjs(item.prefill, "h:mm A").format("h:mm A")

                                if (item.enable_seconds) {
                                    val = dayjs(item.prefill, "h:mm A").format("h:mm:ss A")
                                }
                            }
                        }

                        this.form.inputs[item.id] = val;
                    }

                    if (item.type === this.form.data.formFields.time_range) {
                        let valFrom = null;
                        let valTo = null;

                        if (item.prefill_from) {
                            if (item.time_24hr) {
                                valFrom = dayjs(item.prefill_from, "h:mm A").format("HH:mm")

                                if (item.enable_seconds) {
                                    valFrom = dayjs(item.prefill_from, "h:mm A").format("HH:mm:ss")
                                }
                            } else {
                                valFrom = dayjs(item.prefill_from, "h:mm A").format("h:mm A")

                                if (item.enable_seconds) {
                                    valFrom = dayjs(item.prefill_from, "h:mm A").format("h:mm:ss A")
                                }
                            }
                        }

                        if (item.prefill_to) {
                            if (item.time_24hr) {
                                valTo = dayjs(item.prefill_to, "h:mm A").format("HH:mm")

                                if (item.enable_seconds) {
                                    valTo = dayjs(item.prefill_to, "h:mm A").format("HH:mm:ss")
                                }
                            } else {
                                valTo = dayjs(item.prefill_to, "h:mm A").format("h:mm A")

                                if (item.enable_seconds) {
                                    valTo = dayjs(item.prefill_to, "h:mm A").format("h:mm:ss A")
                                }
                            }
                        }

                        this.form.inputs[item.id] = {
                            from: valFrom,
                            to: valTo,
                        };
                    }
                });
            },
            deep: true
        }
    },
    methods: {
        fieldAdded(field) {
            this.formFields.push(field);
        },
        fieldUpdated(field) {
            this.formFields.forEach((item, index) => {
                if (item.id === field.id) {
                    this.formFields[index] = field;
                }
            });
        },
        fieldDeleted() {
            this.formFields.splice(this.selectedFieldIndex, 1);
        },
        editField(index) {
            this.selectedFieldIndex = index;
            this.showEditFieldModal = true
        },
        async deleteField(index) {
            let title = window.messages.are_you_sure;
            let text = window.messages.you_wont_be_able_to_revert_this;

            if (this.form.id) {
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
                    this.form.removePropertyRule(this.formFields[index].id);
                    this.form.removeInput(this.formFields[index].id);
                    this.formFields.splice(index, 1);
                }
            });
        },
        closeFormEditFieldModal: function () {
            this.selectedFieldIndex = null;
            this.showEditFieldModal = false;
        }
    }
}
</script>
