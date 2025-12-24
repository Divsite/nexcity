import { createApp } from "vue";

createApp({
    data() {
        return {
            email: '',
            errors: [],
            loading: false,
        }
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            let data = new FormData();
            data.append('email', this.email);

            await axios.post(route('password.email'), data).then(response => {
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
                this.loading = false; // Stop loading
            });
        },
    }
}).mount('#app');
