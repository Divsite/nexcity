import {createApp} from "vue";
import WorkflowProcess from "../../components/FormProcesses/WorkflowProcess.vue";
import {createPinia} from "pinia";

const pinia = createPinia()

createApp({
    name: "FormProcess",
    components: {
        WorkflowProcess,
    },
    data() {
        return {
            trans: window.trans,
            formId: window.formId,
        }
    }
}).use(pinia).mount('#app');
