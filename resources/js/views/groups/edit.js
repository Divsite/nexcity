import {createApp} from "vue";

createApp({
    data() {
        return {
            form: {
                name: model.name,
                description: model.description,
            },
            submit_form_key: 0,
            errors: [],
            loading: false,
        }
    },
    methods: {
        submitForm: async function (loading = true) {
            this.loading = loading;

            let data = {
                _method: document.getElementsByName('_method')[0].value,
                name: this.form.name,
                description: this.form.description,
            };

            await axios.post(route('groups.update', {group: model.id}), data).then(response => {
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.redirect) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submit_form_key++;
                this.loading = false; // Stop loading
            });
        },
    }
}).mount('#app')
