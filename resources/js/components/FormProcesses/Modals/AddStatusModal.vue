<template>
    <div class="modal fade" id="add-status-modal" tabindex="-1" role="dialog"
         aria-labelledby="add-status-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add-status-modal-title">{{ trans.add_status }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            @click="handleClose"></button>
                </div>
                <div class="modal-body text-body">
                    <form autocomplete="off">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        {{ trans.name }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="name" v-model="form.name"
                                           :class="['form-control', errors.name ? 'is-invalid' : '']"
                                           @keydown.enter.prevent>
                                    <div class="invalid-feedback" v-if="errors.name">
                                        <strong>{{ errors.name[0] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="add_status_enabled" class="form-label">
                                        {{ trans.status }}
                                    </label>
                                    <div class="form-check form-switch mb-3">
                                        <input type="checkbox" id="add_status_toggle" v-model="form.status"
                                               :class="['form-check-input', errors.status ? 'is-invalid' : '']">
                                        <label for="add_status_toggle" class="form-check-label">
                                            <div class="d-flex align-items-center">
                                                <span>{{ trans.enabled }}</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="invalid-feedback d-block mb-3" v-if="errors.status">
                                        <strong>{{ errors.status[0] }}</strong>
                                    </div>
                                </div>
                            </div>
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
export default {
    name: "AddStatusModal",
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
            addStatusModal: null,
            errors: [],
            loading: false,
        }
    },
    mounted() {
        this.addStatusModal = new bootstrap.Modal('#add-status-modal', {
            keyboard: false,
            backdrop: 'static',
        });
    },
    watch: {
        show() {
            if (this.show) {
                this.addStatusModal.show();
            }
        }
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            await axios.post(route('forms.process.statuses.store', {id: this.formId}), this.form).then(response => {
                console.log(response);
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.status) {
                    this.handleClose();
                    this.addStatusModal.hide();

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
        },
        handleClose: function () {
            this.$emit('close');
            this.reset();
        }
    },
}
</script>
