<template>
    <div class="modal fade" id="add-process-modal" tabindex="-1" role="dialog"
         aria-labelledby="add-process-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add-process-modal-title">{{ trans.add_process }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            @click="handleClose"></button>
                </div>
                <div class="modal-body text-body">
                    <form autocomplete="off">
                        <general-form-field :trans="trans" :form="form" :errors="errors"
                                            toggle_enabled="add_toggle_process"></general-form-field>
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
import GeneralFormField from "../Shared/GeneralFormField.vue";

export default {
    name: "AddProcessModal",
    components: {GeneralFormField},
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
        formId: {
            type: String,
            required: false
        },
    },
    data() {
        return {
            form: {
                name: null,
                status: true,
            },
            addProcessModal: null,
            errors: [],
            loading: false,
        }
    },
    mounted() {
        this.addProcessModal = new bootstrap.Modal('#add-process-modal', {
            keyboard: false,
            backdrop: 'static',
        });
    },
    watch: {
        show() {
            if (this.show) {
                this.addProcessModal.show();
            }
        }
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            await axios.post(route('forms.processes.store', {id: this.formId}), this.form).then(response => {
                console.log(response);
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.status) {
                    this.handleClose();
                    this.addProcessModal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: response.data.title,
                        text: response.data.text,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                }
            }).catch((error) => {
                console.log(error.response);

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
        reset: function () {
            this.form.name = null;
            this.form.status = true;

            this.errors = [];
            this.loading = false;
        },
        handleClose: function () {
            this.$emit('close');
            this.reset();
        }
    },
}
</script>
