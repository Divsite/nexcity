<template>
    <add-process-modal :trans="trans" :show="showAddProcessModal" :form-id="formId"
                       @close="showAddProcessModal = false"></add-process-modal>

    <template v-if="selectedProcessIndex !== null">
        <edit-process-modal :trans="trans" :values="processes[selectedProcessIndex]" :show="showEditProcessModal"
                            @close="closeEditProcessModal"></edit-process-modal>
    </template>

    <div class="row g-4 align-items-center gy-3">
        <div class="col-sm">
            <button type="button" class="btn btn-primary" @click="showAddProcessModal = true">
                <i class="ri-add-line align-bottom me-1"></i> {{ trans.add }}
            </button>
        </div>
        <div class="col-sm-auto align-middle" v-if="processes.length !== 0">
            <div class="d-flex gap-1 flex-wrap">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="enabled-sort" v-model="enableSort">
                    <label for="enabled-sort" class="form-check-label text-body">
                        <div class="d-flex align-items-center">
                            <span v-if="enableSort">{{ trans.on_sort }}</span>
                            <span v-else>{{ trans.off_sort }}</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <template v-if="processes.length !== 0">
        <draggable v-model="processes" tag="div" ghost-class="bg-dark-subtle" :animation="200" handle=".handle"
                   item-key="index" @change="updateSort">
            <template #item="{element, index}">
                <div class="border rounded-2 text-body bg-light-subtle mt-3 mb-2 p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 me-2">
                            <h6 class="fs-15 mb-1">
                                {{ trans.process.replace(':number', index + 1) }} : {{ element.name }}
                            </h6>
                            <span :class="statusBadgeClass(element.status)">{{ element.status_text }}</span>
                        </div>
                        <div class="flex-shrink-0 ms-2">
                            <template v-if="enableSort">
                                <button type="button"
                                        class="btn btn-icon shadow-none handle" style="cursor: move">
                                    <i class="ri-arrow-up-down-line text-dark fs-24"></i>
                                </button>
                            </template>
                            <template v-else>
                                <button type="button"
                                        class="btn btn-ghost-danger btn-icon btn-sm shadow-none me-1"
                                        @click="deleteProcess(index)">
                                    <i class="ri-delete-bin-line fs-18"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-ghost-primary btn-icon btn-sm shadow-none"
                                        @click="editProcess(index)">
                                    <i class="ri-settings-4-line fs-18"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>
    </template>
    <template v-else>
        <div class="text-center mt-5 mb-2">
            <div class="avatar-md mb-4 mx-auto">
                <div class="avatar-title bg-info-subtle text-info display-5 rounded-circle">
                    <i class="ri-flow-chart"></i>
                </div>
            </div>
            <h5>{{ trans.no_process_found }}</h5>
            <p class="text-muted">{{ trans.add_process_to_use_workflow_process }}</p>
        </div>
    </template>
</template>

<script>
import AddProcessModal from "./Modals/AddProcessModal.vue";
import {mapWritableState} from "pinia";
import {useFormProcessStore} from "../../stores/FormProcessStore";
import EditProcessModal from "./Modals/EditProcessModal.vue";
import draggable from "vuedraggable";

export default {
    name: "Processes",
    components: {draggable, EditProcessModal, AddProcessModal},
    props: ['trans', 'formId'],
    data() {
        return {
            enableSort: false,
            showAddProcessModal: false,
            selectedProcessIndex: null,
            showEditProcessModal: false,
        };
    },
    computed: {
        ...mapWritableState(useFormProcessStore, ['processes']),
    },
    watch: {
        showAddProcessModal: async function (newVal) {
            if (!newVal) {
                await this.getProcesses();
            }
        }
    },
    methods: {
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
        statusBadgeClass: function (status) {
            return {
                'badge': true,
                'pe-none': true,
                'bg-success': status,
                'bg-danger': !status,
            }
        },
        editProcess: function (index) {
            this.selectedProcessIndex = index;
            this.showEditProcessModal = true
        },
        closeEditProcessModal: async function () {
            this.selectedProcessIndex = null;
            this.showEditProcessModal = false;
            await this.getProcesses();
        },
        deleteProcess: async function (index) {
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
                    let processId = this.processes[index].id;
                    await axios.delete(route('forms.processes.destroy', {id: processId})).then(async response => {
                        console.log(response.data);

                        if (response.data.status) {
                            await this.getProcesses();
                            await this.updateSort();
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
                        await this.getProcesses();
                    });
                }
            });
        },
        updateSort: async function () {
            this.processes.map((process, index) => {
                process.order = index + 1;
            });

            await axios.post(route('forms.processes.sort', {id: this.formId}), {processes: this.processes}).then(response => {
                this.processes = response.data.items;
            }).catch((error) => {
                Swal.fire({
                    icon: 'error',
                    title: error.response.data.title,
                    text: error.response.data.text,
                });
            });
        },
    }
}
</script>
