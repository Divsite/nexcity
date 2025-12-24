import { createApp } from "vue";

createApp({
    data() {
        return {
            form: {
                username: user.username,
            },
            submit_form: 0,
            errors: [],
            loading: false,
        }
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            let data = new FormData();
            data.append('username', this.form.username);

            await axios.post(route('profile.change-username'), data).then(response => {
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
                this.submit_form++;
                this.loading = false; // Stop loading
            });
        },
    }
}).mount('#app')
