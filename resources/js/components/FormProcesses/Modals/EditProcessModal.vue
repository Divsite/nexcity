<template>
    <div class="modal fade" id="edit-process-modal" tabindex="-1" role="dialog"
         aria-labelledby="add-process-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add-process-modal-title">{{ trans.edit_process }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            @click="handleClose"></button>
                </div>
                <div class="modal-body text-body p-0 mt-2">
                    <form autocomplete="off">
                        <div class="accordion accordion-flush">
                            <general-field :trans="trans" :form="form" :errors="errors"></general-field>
                            <status-field :trans="trans" :form="form" :errors="errors"></status-field>
                            <user-field :trans="trans" :form="form" :errors="errors"></user-field>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" :disabled="loading"
                            @click="handleClose">{{ trans.close }}
                    </button>
                    <button class="btn btn-primary btn-load" type="button" :disabled="loading"
                            @click.prevent="submitForm">
                        <span class="d-flex justify-content-center">
                            <span class="spinner-border me-2" role="status" v-if="loading">
                                <span class="visually-hidden">{{ trans.loading }}</span>
                            </span>
                            <span>{{ trans.save }}</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import GeneralField from "../Shared/GeneralField.vue";
import StatusField from "../Shared/StatusField.vue";
import UserField from "../Shared/UserField.vue";

export default {
    name: "EditProcessModal",
    components: {UserField, StatusField, GeneralField},
    emits: ['close'],
    props: {
        trans: {
            type: Object,
            required: false,
        },
        show: {
            type: Boolean,
            required: false
        },
        values: {
            type: Object,
            required: false,
        }
    },
    data() {
        return {
            form: {
                id: this.values.id,
                name: this.values.name,
                status: this.values.status,
                actions: this.values.actions,
                processor_users: this.values.processor_users,
                processor_roles: this.values.processor_roles,
                decision_type: this.values.decision_type,
                majority_percentage: this.values.majority_percentage,
                manager_id: this.values.manager_id,
            },
            editProcessModal: null,
            errors: [],
            loading: false,
        }
    },
    mounted() {
        if (this.show) {
            this.editProcessModal = new bootstrap.Modal('#edit-process-modal', {
                keyboard: false,
                backdrop: 'static',
            });
            this.editProcessModal.show();
        }
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            await axios.put(route('forms.processes.update', {id: this.form.id}), this.form).then(response => {
                console.log(response);
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.status) {
                    this.handleClose();
                    this.editProcessModal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: response.data.title,
                        text: response.data.text,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                }
            }).catch((error) => {
                console.log(error);

                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }

                if (error.response.status === 404) {
                    Swal.fire({
                        icon: 'error',
                        title: error.response.data.title,
                        text: error.response.data.text,
                    });
                }

                this.loading = false; // Stop loading
            });
        },
        handleClose: function () {
            this.$emit('close');
        },
    },
}
</script>
