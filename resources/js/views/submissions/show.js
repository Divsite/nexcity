import {createApp} from "vue";
import {createPinia} from "pinia";
import SubmissionRender from "../../components/Submissions/SubmissionRender.vue";

const pinia = createPinia()
const app = createApp(SubmissionRender);
app.use(pinia);
app.mount('#app')
