import { createApp } from "vue";

createApp({
    data() {
        return {
            email: '',
            password: '',
            remember: false,
            show_password: false,
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
            data.append('email', this.email);
            data.append('password', this.password);
            data.append('remember', this.remember);

            await axios.post(route('login'), data).then(response => {
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.redirect) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                this.errors = error.response.data.errors;
                this.loading = false; // Stop loading
            });
        },
    }
}).mount('#app')
