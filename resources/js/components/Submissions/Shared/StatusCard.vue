<template>
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-4 align-items-center gy-3">
                <div class="col-sm">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <span class="fw-medium">{{ trans.status }}</span>
                    </h5>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3 text-center fs-14">
                <h5 class="fs-14 mb-0">
                    <span class="text-muted mb-2 fw-medium">
                        {{ current_process.label }} :
                    </span>
                    <span class="badge rounded-pill bg-info fs-12 text-wrap ms-1">
                        {{ current_process.name }}
                    </span>
                </h5>
            </div>

            <div class="mt-4">
                <form autocomplete="off" @submit.prevent="submitForm">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="process_status" class="form-label">
                                    {{ trans.status }} <span class="text-danger">*</span>
                                </label>
                                <select id="process_status" v-model="formData.status_id"
                                        :class="['form-select', errors.status_id ? 'is-invalid' : '']">
                                    <option disabled :value="null" selected>{{ trans.please_select }}</option>
                                    <option v-for="action in current_process.actions" :value="action.id"
                                            :key="action.id">
                                        {{ action.name }}
                                    </option>
                                </select>
                                <div class="invalid-feedback" v-if="errors.status_id">
                                    <strong>{{ errors.status_id[0] }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="comment" class="form-label">
                                    {{ trans.comment }} <span class="text-danger" v-if="showCommentRequired">*</span>
                                </label>
                                <textarea id="comment" v-model="formData.comment" rows="3"
                                          :class="['form-control', errors.comment ? 'is-invalid' : '']"></textarea>
                                <div class="invalid-feedback" v-if="errors.comment">
                                    <strong>{{ errors.comment[0] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-load mt-2" type="submit" :disabled="loading">
                        <span class="d-flex justify-content-center">
                            <span class="spinner-border me-2" role="status" v-if="loading">
                                <span class="visually-hidden">
                                    {{ trans.loading }}
                                </span>
                            </span>
                            <span>{{ trans.save }}</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import {mapWritableState} from "pinia";
import {useSubmissionStore} from "../../../stores/SubmissionStore";
import {collect} from "collect.js";

export default {
    name: "StatusCard",
    data() {
        return {
            trans: window.trans,
            formData: {
                status_id: null,
                comment: null,
            },
            loading: false,
            errors: [],
        }
    },
    mounted() {
        $(".select2").select2({
            language: {
                noResults: function () {
                    return messages.no_results_found;
                }
            }
        });
    },
    computed: {
        ...mapWritableState(useSubmissionStore, ['submission', 'form', 'can_process_submissions', 'is_workflow_exists', 'current_process', 'statuses', 'processes']),
        showCommentRequired: function () {
            let actions = collect(this.current_process.actions).where('id', this.formData.status_id).first();

            if (actions) {
                return actions.comment_required;
            }

            return false;
        },
    },
    methods: {
        getSubmission: async function () {
            await axios.get(route('submission.data', {id: this.submission.id})).then(response => {
                let data = response.data.data;
                this.submission = data.submission;
                this.form = data.form;
                this.can_process_submissions = data.can_process_submissions;
                this.is_workflow_exists = data.is_workflow_exists;
                this.current_process = data.current_process;
                this.statuses = data.statuses;
                this.processes = data.processes;
            }).catch((error) => {
                Swal.fire({
                    icon: 'error',
                    title: error.response.data.title,
                    text: error.response.data.text,
                });
            });
        },
        submitForm: async function () {
            this.loading = true;

            await axios.post(route('submission.process.store', {id: this.submission.id}), this.formData).then(async response => {
                console.log(response);
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.status) {
                    this.loading = true;

                    Swal.fire({
                        icon: 'success',
                        title: response.data.title,
                        text: response.data.text,
                        timer: 3000,
                        timerProgressBar: true,
                    });

                    await this.getSubmission();

                    this.resetFormData();

                    this.loading = false;
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
        resetFormData: function () {
            this.formData.status_id = null;
            this.formData.comment = null;
        },
    },
}
</script>
