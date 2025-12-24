<template>
    <add-status-modal :trans="trans" :show="showAddStatusModal" :form-id="formId"
                      @close="showAddStatusModal = false"></add-status-modal>

    <template v-if="selectedStatusIndex !== null">
        <edit-status-modal :trans="trans" :values="statuses[selectedStatusIndex]"
                           :show="showEditStatusModal" @close="closeEditStatusModal"></edit-status-modal>
    </template>

    <div class="border rounded text-body p-4 mb-3">
        <form autocomplete="off">
            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-3">
                        <label for="default_submission_status" class="form-label">
                            {{ trans.default_submission_status }}
                        </label>
                        <input type="text" id="default_submission_status" v-model="default_submission_status"
                               :class="['form-control', errors.default_submission_status ? 'is-invalid' : '']"
                               @keydown.enter.prevent>
                        <div class="invalid-feedback" v-if="errors.default_submission_status">
                            <strong>{{ errors.default_submission_status[0] }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary btn-load" type="button" :disabled="loading"
                    @click.prevent="submitForm">
                <span class="d-flex justify-content-center">
                    <span class="spinner-border me-2" role="status" v-if="loading">
                        <span class="visually-hidden">{{ trans.loading }}</span>
                    </span>
                    <span>{{ trans.save }}</span>
                </span>
            </button>
        </form>
    </div>

    <div class="border rounded p-4">
        <div class="row g-4 align-items-center gy-3">
            <div class="col-sm">
                <button type="button" class="btn btn-primary" @click="showAddStatusModal = true">
                    <i class="ri-add-line align-bottom me-1"></i> {{ trans.add }}
                </button>
            </div>
            <div class="col-sm-auto">
                <div class="d-flex gap-1 flex-wrap">
                    <select id="status_filter" v-model="activation" class="form-select">
                        <option v-for="(item, index) in statusItems" :key="index" :value="index">
                            {{ item }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive text-body mt-3">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th class="text-center">#</th>
                    <th>{{ trans.name }}</th>
                    <th>{{ trans.status }}</th>
                    <th class="text-center">{{ trans.actions }}</th>
                </tr>
                </thead>
                <tbody>
                <template v-if="listStatus.length !== 0">
                    <tr v-for="(item, index) in listStatus">
                        <td class="text-center" style="width: 5%">{{ index + 1 }}</td>
                        <td style="width: 50%">{{ item.name }}</td>
                        <td style="width: 15%">{{ item.status_text }}</td>
                        <td class="text-center" style="width: 15%">
                            <a role="button"
                               class="text-primary d-inline-block edit-item-btn me-2" v-tooltip
                               data-bs-placement="top" :title="trans.edit" @click="editStatus(index)">
                                <i class="ri-pencil-fill fs-16"></i>
                            </a>

                            <a role="button"
                               class="text-danger d-inline-block edit-item-btn" v-tooltip
                               data-bs-placement="top" :title="trans.delete" @click="deleteStatus(index)">
                                <i class="ri-delete-bin-fill fs-16"></i>
                            </a>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <tr>
                        <td colspan="4">{{ trans.no_items_found }}</td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import AddStatusModal from "./Modals/AddStatusModal.vue";
import EditStatusModal from "./Modals/EditStatusModal.vue";
import {collect} from "collect.js";
import {mapWritableState} from "pinia";
import {useFormProcessStore} from "../../stores/FormProcessStore";
import {tooltip} from "../../directives/tooltip";

export default {
    name: "Statuses",
    components: {EditStatusModal, AddStatusModal},
    directives: {tooltip},
    props: ['trans', 'formId'],
    data() {
        return {
            showAddStatusModal: false,
            showEditStatusModal: false,
            selectedStatusIndex: null,
            activation: 'all',
            statusItems: window.statusItems,
            statusList: window.statusList,
            errors: [],
            loading: false,
        }
    },
    computed: {
        ...mapWritableState(useFormProcessStore, ['statuses', 'default_submission_status', 'processes']),
        listStatus: function () {
            if (this.activation !== null) {

                if (this.activation === this.statusList.all) {
                    return this.statuses;
                }

                if (this.activation === this.statusList.active) {
                    let items = collect(this.statuses);
                    return items.where('status', true).all();
                }

                if (this.activation === this.statusList.inactive) {
                    let items = collect(this.statuses);
                    return items.where('status', false).all();
                }
            }

            return this.statuses;
        },
    },
    watch: {
        showAddStatusModal: async function (newVal) {
            if (!newVal) {
                await this.getStatuses();
                await this.getProcesses();
            }
        }
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            let data = {
                default_submission_status: this.default_submission_status,
            };

            await axios.post(route('forms.update-default-status', {id: this.formId}), data).then(response => {
                console.log(response);
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.status) {
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
        editStatus: function (index) {
            this.selectedStatusIndex = index;
            this.showEditStatusModal = true
        },
        getStatuses: async function () {
            await axios.get(route('forms.process.statuses', {id: this.formId})).then(response => {
                this.statuses = response.data.items;
            }).catch((error) => {
                Swal.fire({
                    icon: 'error',
                    title: error.response.data.title,
                    text: error.response.data.text,
                });
            });
        },
        getProcesses: async function () {
            await axios.get(route('forms.processes.list', {id: this.formId})).then(response => {
                this.processes = response.data.items;
            }).catch((error) => {
                Swal.fire({
                    icon: 'error',
                    title: error.response.data.title,
                    text: error.response.data.text,
                });
            });
        },
        closeEditStatusModal: async function () {
            this.selectedStatusIndex = null;
            this.showEditStatusModal = false;
            await this.getStatuses();
            await this.getProcesses();
        },
        deleteStatus: async function (index) {
            let title = window.messages.are_you_sure;
            let text = window.messages.you_wont_be_able_to_revert_this;

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
            }).then(async (result) => {
                if (result.isConfirmed) {
                    let statusId = this.statuses[index].id;
                    await axios.delete(route('forms.process.statuses.destroy', {id: statusId})).then(async response => {
                        console.log(response.data);

                        if (response.data.status) {
                            await this.getStatuses();
                            await this.getProcesses();
                            Swal.fire({
                                icon: 'success',
                                title: response.data.title,
                                text: response.data.text,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        }
                    }).catch(async (error) => {
                        console.log(error.response);
                        Swal.fire({
                            icon: 'error',
                            title: error.response.data.title,
                            text: error.response.data.text,
                        });
                        await this.getStatuses();
                        await this.getProcesses();
                    });
                }
            });
        },
    },
}
</script>
