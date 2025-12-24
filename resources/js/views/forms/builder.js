import { createApp } from "vue";
import { createPinia } from 'pinia'
import FormBuilder from "../../components/Forms/FormBuilder.vue";

const pinia = createPinia()
const app = createApp(FormBuilder)
app.use(pinia)
app.mount('#form-builder')
