import {createApp} from "vue";

createApp({
    data() {
        return {
            form: {
                name: model.name,
                description: model.description,
            },
            id: model.id,
            errors: [],
            submit_key: 0,
            loading: false,
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            let data = {
                _method: document.getElementsByName('_method')[0].value,
                name: this.form.name,
                description: this.form.description,
            };

            await axios.post(route('form-types.update', {form_type: this.id}), data).then(response => {
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.redirect) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                console.log(error.response);
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submit_key++;
                this.loading = false; // Stop loading
            });
        },
    }
}).mount('#app');
