<template>
    <div class="row">
        <div :class="submissionColClass">
            <submission-card></submission-card>
        </div>

        <template v-if="canProcessSubmission">
            <div class="col-xl-4">
                <status-card></status-card>
            </div>
        </template>

        <div :class="submissionColClass" v-if="statuses.length !== 0">
            <status-list-card></status-list-card>
        </div>

        <div :class="submissionColClass" v-if="processes.length !== 0">
            <process-list-card></process-list-card>
        </div>
    </div>
</template>

<script>
import {mapWritableState} from "pinia";
import {useSubmissionStore} from "../../stores/SubmissionStore";
import SubmissionCard from "./Shared/SubmissionCard.vue";
import StatusCard from "./Shared/StatusCard.vue";
import StatusListCard from "./Shared/StatusListCard.vue";
import ProcessListCard from "./Shared/ProcessListCard.vue";

export default {
    name: "SubmissionRender",
    components: {ProcessListCard, StatusListCard, StatusCard, SubmissionCard},
    data() {
        return {
            trans: window.trans,
        };
    },
    computed: {
        ...mapWritableState(useSubmissionStore, ['canProcessSubmission', 'statuses', 'processes']),
        submissionColClass() {
            return {
                'col-xl-8': this.canProcessSubmission,
                'col-xl-12': !this.canProcessSubmission,
            }
        },
    },
}
</script>
