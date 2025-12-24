<template>
    <div class="accordion-item">
        <h2 class="accordion-header" id="user-accordion">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseUser" aria-expanded="true"
                    aria-controls="collapseUser">
                {{ trans.user }}
            </button>
        </h2>
        <div id="collapseUser" class="accordion-collapse collapse show"
             aria-labelledby="general">
            <div class="accordion-body text-body">
                <div id="processor-users" class="mb-3">
                    <label for="processor_users" class="form-label">{{ trans.processor_users }}</label>

                    <div class="search-box mb-3">
                        <input type="text" v-model="search" class="form-control search"
                               :placeholder="trans.search_user+'...'">
                        <i class="ri-search-line search-icon"></i>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-center">#</th>
                                <th scope="col">{{ trans.name }}</th>
                                <th scope="col">{{ trans.username }}</th>
                                <th scope="col">{{ trans.email }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template v-if="loading">
                                <tr>
                                    <td colspan="4">
                                        <span>{{ trans.loading }}</span>
                                        <span class="spinner-border spinner-border-sm align-middle ms-2" role="status">
                                            <span class="visually-hidden">{{ trans.loading }}</span>
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <template v-if="users && users.data && users.data.length !== 0">
                                    <tr v-for="user in users.data">
                                        <th scope="row">
                                            <div class="form-check text-center">
                                                <input type="checkbox" v-model="form.processor_users" :value="user.id"
                                                       class="form-check-input">
                                            </div>
                                        </th>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img :src="user.avatar" :alt="'avatar_'+user.id"
                                                         class="avatar-xs rounded-circle"/>
                                                </div>
                                                <div class="flex-grow-1">{{ user.name }}</div>
                                            </div>
                                        </td>
                                        <td>{{ user.username }}</td>
                                        <td>{{ user.email }}</td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <tr>
                                        <td colspan="4">{{ trans.no_items_found }}</td>
                                    </tr>
                                </template>
                            </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="invalid-feedback fw-semibold d-block mt-2" v-if="errors.processor_users">
                        {{ errors.processor_users[0] }}
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-soft-info btn-sm me-2"
                                v-if="users && users.links && users.links.prev"
                                @click="paginationUser(users.links.prev)">
                            <i class="ri-arrow-left-line align-bottom me-1"></i>
                            {{ trans.previous_btn }}
                        </button>

                        <button type="button" class="btn btn-soft-info btn-sm"
                                v-if="users && users.links && users.links.next"
                                @click="paginationUser(users.links.next)">
                            <i class="ri-arrow-right-line align-bottom me-1"></i>
                            {{ trans.next }}
                        </button>
                    </div>
                </div>

                <div id="processor-roles" class="mb-3">
                    <div class="row">
                        <div class="col-xl-12">
                            <div :class="[errors.processor_roles ? 'select2-is-invalid' : '']">
                                <label for="roles" class="form-label">{{ trans.processor_roles }}</label>
                                <select id="roles" class="form-control w-100 select2"
                                        v-model="form.processor_roles" v-select2>
                                    <option v-for="role in roles" :key="role.id" :value="role.id" class="text-body">
                                        {{ role.display_name }}
                                    </option>
                                </select>
                                <div class="invalid-feedback d-block" v-if="errors.processor_roles">
                                    <strong>{{ errors.processor_roles[0] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="decision-maker">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="decision-maker" class="form-label">
                                    {{ trans.decision_maker }} <span class="text-danger">*</span>
                                </label>
                                <div class="mt-1">
                                    <div class="form-check form-radio-primary mb-3"
                                         v-for="(type, index) in decisionTypeList" :key="index">
                                        <input type="radio" :id="'decision_maker_'+index"
                                               v-model="form.decision_type" :value="index"
                                               :class="['form-check-input', errors.decision_type ? 'is-invalid' : '']">
                                        <label class="form-check-label" :for="'decision_maker_'+index">
                                            {{ type }}
                                        </label>
                                    </div>
                                    <div class="invalid-feedback d-block" v-if="errors.decision_type">
                                        <strong>{{ errors.decision_type[0] }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12" v-if="form.decision_type === decisionTypeItems.majority">
                            <div class="mb-3">
                                <label for="majority_percentage" class="form-label">
                                    {{ trans.percentage }} <span class="text-danger">*</span>
                                </label>
                                <input type="number" id="majority_percentage" v-model.number="form.majority_percentage"
                                       :class="['form-control', errors.majority_percentage ? 'is-invalid' : '']"
                                       @blur="isPercentage" @keydown.enter.prevent>
                                <div class="invalid-feedback" v-if="errors.majority_percentage">
                                    <strong>{{ errors.majority_percentage[0] }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12" v-if="showPICField">
                            <div class="mb-3">
                                <label for="manager_id" class="form-label text-capitalize">
                                    <div class="d-flex align-items-center">
                                        <span>{{ trans.person_in_charge }}</span>
                                        <i class="ri-information-line ms-1" v-tooltip data-bs-placement="right"
                                           :title="trans.the_decision_of_the_person_in_charge_will_be_taken_for_the_next_process_action"></i>
                                        <span class="text-danger ms-1">*</span>
                                    </div>
                                </label>

                                <div data-simplebar data-simplebar-track="primary" style="max-height: 400px;"
                                     v-if="managers && managers.data && managers.data.length !== 0">
                                    <div class="row g-2">
                                        <div class="col-lg-12" v-for="(manager, index) in managers.data"
                                             :key="manager.id">
                                            <div class="form-check card-radio">
                                                <input type="radio" :id="'manager_'+manager.id"
                                                       v-model="form.manager_id" class="form-check-input"
                                                       :value="manager.id">
                                                <label class="form-check-label" :for="'manager_'+manager.id">
                                                    <div class="d-flex mb-2 align-items-center text-wrap">
                                                        <div class="flex-shrink-0">
                                                            <img :src="manager.avatar" :alt="'manager_avatar_'+index"
                                                                 class="avatar-md rounded-circle"/>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h5 class="list-title fs-15 mb-1">{{ manager.name }}</h5>
                                                            <p class="list-text text-muted mb-0 fs-12">
                                                                {{ trans.email }} : {{ manager.email }}
                                                            </p>
                                                            <p class="list-text text-muted mb-0 fs-12">
                                                                {{ trans.username }} : {{ manager.username }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-dashed rounded p-2" v-else>
                                    {{ trans.no_items_found }}
                                </div>

                                <div class="invalid-feedback d-block mt-1" v-if="errors.manager_id">
                                    <strong>{{ errors.manager_id[0] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import _ from "lodash";
import {select2} from "../../../directives/select2";
import {tooltip} from "../../../directives/tooltip";

export default {
    name: "UserField",
    props: ['trans', 'form', 'errors'],
    directives: {select2, tooltip},
    data() {
        return {
            search: null,
            users: [],
            roles: window.roles,
            decisionTypeList: window.decisionTypeList,
            decisionTypeItems: window.decisionTypeItems,
            managers: [],
            loading: false,
        }
    },
    async mounted() {
        this.loading = true;

        let roles = $("#roles");
        roles.select2({
            dropdownParent: $('#edit-process-modal'),
            multiple: true,
            placeholder: this.trans.please_select,
            language: {
                noResults: function () {
                    return messages.no_results_found;
                }
            }
        });
        roles.val(this.form.processor_roles).trigger('change');

        await this.getProcessorUsers();

        await this.getManagers();
    },
    computed: {
        showPICField: function () {
            return this.form.decision_type === this.decisionTypeItems.majority ||
                this.form.decision_type === this.decisionTypeItems.all;
        },
    },
    watch: {
        search: async function () {
            this.loading = true;
            await this.getProcessorUsers();
        },
        'form.decision_type': async function () {
            this.form.manager_id = null;
        },
        'form.processor_users': async function () {
            this.form.manager_id = null;
            await this.getManagers();
        },
        'form.processor_roles': async function () {
            this.form.manager_id = null;
            await this.getManagers();
        },
    },
    methods: {
        isPercentage: function () {
            if (isNaN(this.form.majority_percentage) || this.form.majority_percentage === '') {
                this.form.majority_percentage = 0.00;
            }

            this.form.majority_percentage = parseFloat(this.form.majority_percentage).toFixed(2);

            if (this.form.majority_percentage > 100) {
                this.form.majority_percentage = parseFloat(100).toFixed(2);
            }
        },
        paginationUser: function (url) {
            this.loading = true;
            this.getProcessorUsers(url);
        },
        getProcessorUsers: _.debounce(async function (url = route('forms.processes.processor-users')) {
            await axios.get(url, {params: {search: this.search}}).then(response => {
                this.users = response.data;
                this.loading = false;
            });
        }, 500),
        getManagers: async function () {
            let data = {
                'processor_users': this.form.processor_users,
                'processor_roles': this.form.processor_roles,
            };

            await axios.post(route('forms.processes.managers'), data).then(response => {
                this.managers = response.data;
            });
        },
    }
}
</script>

<style>
.select2-selection--multiple .select2-search--inline .select2-search__field {
    width: auto !important;
}

.select2-results__option {
    color: rgba(var(--vz-body-color-rgb), var(--vz-text-opacity)) !important;
}

.select2-container--default .select2-results__option[aria-selected=true]:hover {
    background-color: #405189 !important;
    color: #fff !important;
}
</style>
