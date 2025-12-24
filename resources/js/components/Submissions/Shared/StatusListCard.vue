<template>
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-4 align-items-center gy-3">
                <div class="col-sm">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <span class="fw-medium">{{ trans.status_list }}</span>
                    </h5>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div data-simplebar data-simplebar-track="primary" data-simplebar-auto-hide="false"
                 style="max-height: 400px;">
                <div class="acitivity-timeline">
                    <div class="acitivity-item d-flex" :class="activityItemClass(index)"
                         v-for="(item, index) in statuses" :key="item.id">
                        <div class="flex-shrink-0">
                            <img :src="item.processor_avatar" :alt="'avatar_'+index"
                                 class="avatar-xs rounded-circle acitivity-avatar">
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="fw-semibold mb-1">{{ item.status_name }}</h6>
                            <small class="text-muted" v-if="item.process_name">{{ item.process_name }}</small>
                            <p class="text-body text-justify mt-3" v-if="item.comment">{{ item.comment }}</p>
                            <div class="mb-0 text-muted mt-2">
                                {{ item.created_by }}
                                <span class="badge bg-info-subtle text-info align-middle">{{ item.created_at }}</span>
                            </div>
                        </div>
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
    name: "StatusListCard",
    data() {
        return {
            trans: window.trans,
        }
    },
    computed: {
        ...mapWritableState(useSubmissionStore, ['statuses']),
    },
    methods: {
        isFirst: function (index) {
            return index === 0;
        },
        isLast: function (index) {
            return index === this.statuses.length - 1;
        },
        activityItemClass: function (index) {
            return {
                'py-3': !this.isFirst(index) && !this.isLast(index),
                'pb-3': this.isFirst(index) && this.statuses.length === 2,
            }
        },
    }
}
</script>
