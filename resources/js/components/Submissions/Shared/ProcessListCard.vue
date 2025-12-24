<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0">{{ trans.process_list }}</h5>
                </div>
                <div class="flex-shrink-0">
                    <ul class="list-inline card-toolbar-menu d-flex align-items-center mb-0">
                        <li class="list-inline-item">
                            <a class="align-middle" role="button" @click="showCardBody = !showCardBody">
                                <i :class="openCloseClass"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body" v-if="showCardBody">
            <div class="table-responsive table-card">
                <table class="table table-sm align-middle mb-0" id="customerTable">
                    <thead class="table-light">
                    <tr>
                        <th scope="col">{{ trans.status }}</th>
                        <th scope="col">{{ trans.process.replace(':number', '') }}</th>
                        <th scope="col">{{ trans.comment }}</th>
                        <th scope="col">{{ trans.created_by }}</th>
                        <th scope="col">{{ trans.created_at }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="process in processes">
                        <td>{{ process.status_name }}</td>
                        <td>{{ process.process_name }}</td>
                        <td>{{ process.comment }}</td>
                        <td>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="flex-shrink-0">
                                    <img :src="process.processor_avatar" :alt="'processor_avatar_'+process.id"
                                         class="avatar-xxs rounded-circle"/></div>
                                <div class="flex-grow-1">{{ process.created_by }}</div>
                            </div>
                        </td>
                        <td>{{ process.created_at }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import {mapWritableState} from "pinia";
import {useSubmissionStore} from "../../../stores/SubmissionStore";

export default {
    name: "ProcessListCard",
    data() {
        return {
            trans: window.trans,
            showCardBody: false,
        }
    },
    computed: {
        ...mapWritableState(useSubmissionStore, ['processes']),
        openCloseClass: function () {
            return {
                'ri-add-fill': !this.showCardBody,
                'ri-subtract-fill': this.showCardBody,
                'align-middle': true,
                'fs-20': true,
            }
        }
    },
}
</script>
