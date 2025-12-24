import {createApp} from "vue";

createApp({
    data() {
        return {
            form: {
                name: '',
                description: '',
            },
            submit_form_key: 0,
            errors: [],
            loading: false,
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    methods: {
        submitForm: async function (loading = true) {
            this.loading = loading;

            let data = {
                name: this.form.name,
                description: this.form.description,
            };

            await axios.post(route('form-types.store'), data).then(response => {
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
