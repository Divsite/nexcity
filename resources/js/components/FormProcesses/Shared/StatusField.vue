<template>
    <div class="accordion-item">
        <h2 class="accordion-header" id="status_actions">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseStatus" aria-expanded="true"
                    aria-controls="collapseStatus">
                {{ trans.status }}
            </button>
        </h2>
        <div id="collapseStatus" class="accordion-collapse collapse show"
             aria-labelledby="status_actions">
            <div class="accordion-body text-body">
                <template v-if="form.actions.length !== 0">
                    <div v-for="(item, index) in form.actions" class="border rounded mb-3 p-3">
                        <div class="row">
                            <div class="col-lg-12 text-end">
                                <button type="button" class="btn-close text-end" aria-label="Close"
                                        @click="deleteStatus(index)"></button>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label :for="'status_'+index" class="form-label">
                                        {{ trans.status }} <span class="text-danger">*</span>
                                    </label>
                                    <select :id="'status_'+index" v-model="item.status_id"
                                            :class="['form-select', errors['actions.' + index + '.status_id'] ? 'is-invalid' : '']">
                                        <option disabled value="" selected>{{ trans.please_select }}</option>
                                        <option v-for="status in statusOptions" :value="status.id">
                                            {{ status.name }}
                                        </option>
                                    </select>
                                    <div class="invalid-feedback" v-if="errors['actions.'+index+'.status_id']">
                                        <strong>{{ errors['actions.' + index + '.status_id'][0] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label :for="'linked_process_'+index" class="form-label">
                                        {{ trans.process.replace(':number', '') }} <span class="text-danger">*</span>
                                    </label>
                                    <select :id="'linked_process_'+index" v-model="item.linked_process_id"
                                            :class="['form-select', errors['actions.' + index + '.linked_process_id'] ? 'is-invalid' : '']">
                                        <option disabled value="" selected>{{ trans.please_select }}</option>
                                        <option v-for="process in processOptions" :value="process.id">
                                            {{ process.name }}
                                        </option>
                                    </select>
                                    <div class="invalid-feedback" v-if="errors['actions.'+index+'.linked_process_id']">
                                        <strong>{{ errors['actions.' + index + '.linked_process_id'][0] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label :for="'comment_'+index" class="form-label">
                                        {{ trans.comment }}
                                    </label>
                                    <div class="form-check form-switch mb-3">
                                        <input type="checkbox" :id="'comment_required_'+index" v-model="item.comment_required"
                                               :class="['form-check-input', errors.comment_required ? 'is-invalid' : '']">
                                        <label :for="'comment_required_'+index" class="form-check-label">
                                            <div class="d-flex align-items-center">
                                                <span>{{ trans.required }}</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="invalid-feedback d-block mb-3" v-if="errors.comment_required">
                                        <strong>{{ errors.comment_required[0] }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <p class="text-muted border border-dotted rounded p-2 text-center">
                        {{ trans.no_items_found }}
                    </p>
                </template>

                <div class="invalid-feedback d-block fw-semibold mt-2 mb-3" v-if="errors.actions">
                    {{ errors.actions[0] }}
                </div>

                <button type="button" class="btn btn-info" @click="addStatus">
                    <i class="ri-add-line align-bottom me-1"></i> {{ trans.add }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import {mapWritableState} from "pinia";
import {collect} from "collect.js";
import {useFormProcessStore} from "../../../stores/FormProcessStore";

export default {
    name: "StatusField",
    props: ['trans', 'form', 'errors'],
    data() {
        return {
            revertEndProcess: window.revertEndProcess,
        }
    },
    computed: {
        ...mapWritableState(useFormProcessStore, ["statuses", "processes"]),
        statusOptions: function () {
            return collect(this.statuses).where('status', true).all();
        },
        processOptions: function () {
            let items = [];
            items.push(this.revertEndProcess.submitter);

            let processes = collect(this.processes);
            processes.each((process) => {
                if (process.id !== this.form.id && process.status) {
                    items.push({id: process.id, name: process.name});
                }
            });

            items.push(this.revertEndProcess.ended);
            return items;
        },
    },
    methods: {
        addStatus: function () {
            this.form.actions.push({
                id: null,
                status_id: '',
                linked_process_id: '',
                comment_required: false,
            })
        },
        deleteStatus: function (index) {
            this.form.actions.splice(index, 1);
        },
    }
}
</script>
