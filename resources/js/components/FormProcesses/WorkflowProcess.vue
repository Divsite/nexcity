<template>
    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#statuses" role="tab"
               aria-selected="true">
                <i class="ri-list-check me-2"></i> {{ trans.status }}
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#processes" role="tab"
               aria-selected="false" tabindex="-1">
                <i class="ri-flow-chart me-2"></i> {{ trans.process_builder }}
            </a>
        </li>
    </ul>

    <div class="tab-content text-muted">
        <div class="tab-pane active show" id="statuses" role="tabpanel">
            <statuses :trans="trans" :form-id="formId"></statuses>
        </div>
        <div class="tab-pane" id="processes" role="tabpanel">
            <processes :trans="trans" :form-id="formId"></processes>
        </div>
    </div>
</template>

<script>
import Statuses from "./Statuses.vue";
import Processes from "./Processes.vue";

export default {
    name: "WorkflowProcess",
    components: {Processes, Statuses},
    props: ['trans', 'formId'],
}
</script>
