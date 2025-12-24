import { createApp } from "vue";
import { createPinia } from 'pinia'
import FormRender from "../../components/Forms/FormRender.vue";

const pinia = createPinia()
const app = createApp(FormRender);
app.use(pinia);
app.mount('#form-render')
