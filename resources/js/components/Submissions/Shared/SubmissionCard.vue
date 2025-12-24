<template>
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-4 align-items-center gy-3">
                <div class="col-sm">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <span class="fw-medium">{{ form.name }}</span>
                    </h5>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex gap-1 flex-wrap">
                        <a :href="submission.edit_url" class="btn btn-info btn-sm" v-if="showEditBtn">
                            <i class="ri-pencil-fill align-bottom me-1"></i> {{ trans.edit }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-pills nav-justified nav-custom nav-custom-light mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" data-bs-toggle="tab" href="#submission-information" role="tab"
                       aria-selected="false" tabindex="-1">
                        {{ trans.submission_information }}
                    </a>
                </li>
                <li class="nav-item" role="presentation" v-if="submission.latest_status">
                    <a class="nav-link" data-bs-toggle="tab" href="#latest-status" role="tab" aria-selected="true">
                        {{ trans.latest_status }}
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active show" id="submission-information" role="tabpanel">
                    <div class="table-responsive table-card p-3">
                        <table class="table table-bordered table-striped">
                            <tbody class="text-nowrap">
                            <tr class="table-light fw-semibold fs-14">
                                <td colspan="2">{{ trans.submission_information }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.id }}</td>
                                <td class="w-75">{{ submission.id }}</td>
                            </tr>
                            <tr v-for="item in submission.data" :key="item.id">
                                <td class="fw-medium align-middle text-end">{{ item.name }}</td>
                                <td class="text-wrap">
                                    <template v-if="Array.isArray(item.value)">
                                        <span v-for="(value, index) in item.value" :key="index"
                                              class="badge bg-primary me-2 mb-1 mt-1 fs-12 pe-none">
                                            {{ value }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span>{{ item.value }}</span>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="submission.file_exists">
                                <template v-for="file in submission.file_data" :key="file.id">
                                    <td class="fw-medium text-end">{{ file.label }}</td>
                                    <td class="align-middle">
                                        <a :href="file.url" target="_blank"
                                           class="link-info link-offset-2 text-decoration-underline link-underline-opacity-25 link-underline-opacity-100-hover">
                                            {{ trans.view }}
                                        </a>
                                        <span class="badge rounded-pill bg-info ms-2 me-1">{{ file.mime_type }}</span>
                                        <span class="badge rounded-pill bg-info">
                                            {{ file.size }}
                                        </span>
                                    </td>
                                </template>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive table-card p-3">
                        <table class="table table-bordered table-striped">
                            <tbody class="text-nowrap">
                            <tr class="table-light fw-semibold fs-14">
                                <td colspan="2">{{ trans.additional_info }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.created_by }}</td>
                                <td class="w-75">{{ submission.additional_info.created_by }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.created_at }}</td>
                                <td class="w-75">{{ submission.additional_info.created_at }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.updated_by }}</td>
                                <td class="w-75">{{ submission.additional_info.updated_by }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.updated_at }}</td>
                                <td class="w-75">{{ submission.additional_info.updated_at }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="latest-status" role="tabpanel" v-if="submission.latest_status">
                    <div class="table-responsive table-card p-3">
                        <table class="table table-bordered table-striped">
                            <tbody class="text-nowrap">
                            <tr class="table-light fw-semibold fs-14">
                                <td colspan="2">{{ trans.latest_status }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.status }}</td>
                                <td class="w-75">{{ submission.latest_status.status_name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.process.replace(':number', '') }}</td>
                                <td class="w-75">{{ submission.latest_status.process_name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ trans.updated_at }}</td>
                                <td class="w-75">{{ submission.latest_status.updated_at }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapWritableState} from "pinia";
import {useSubmissionStore} from "../../../stores/SubmissionStore";

export default {
    name: "SubmissionCard",
    data() {
        return {
            trans: window.trans,
        }
    },
    computed: {
        ...mapWritableState(useSubmissionStore, ['submission', 'form', 'current_process']),
        showEditBtn: function () {
            return this.current_process.is_revert_submitter && this.submission.is_owner;
        },
    },
}
</script>
