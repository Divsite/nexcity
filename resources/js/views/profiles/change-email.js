import { createApp } from "vue";

createApp({
    data() {
        return {
            form: {
                email: user.email,
                password: '',
            },
            show_password: false,
            submit_form: 0,
            errors: [],
            loading: false,
        }
    },
    computed: {
        passwordType() {
            return this.show_password ? 'text' : 'password';
        },
    },
    methods: {
        togglePassword: function () {
            this.show_password = !this.show_password;
        },
        submitForm: async function () {
            this.loading = true;

            let data = new FormData();
            data.append('email', this.form.email);
            data.append('password', this.form.password);

            await axios.post(route('profile.change-email'), data).then(response => {
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
