import { createApp } from "vue";
import PasswordStrength from "../../components/Widgets/PasswordStrength.vue";

createApp({
    name: "RegisterPasswordForm",
    components: {PasswordStrength},
    data() {
        return {
            name: '',
            phone: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: '',
            show_password: false,
            show_password_confirmation: false,
            errors: [],
            loading: false,
        }
    },
    computed: {
        passwordType() {
            return this.show_password ? 'text' : 'password';
        },
        passwordConfirmationType() {
            return this.show_password_confirmation ? 'text' : 'password';
        }
    },
    methods: {
        togglePassword: function () {
            this.show_password = !this.show_password;
        },
        togglePasswordConfirmation: function () {
            this.show_password_confirmation = !this.show_password_confirmation;
        },
        submitForm: async function () {
            this.loading = true;

            let data = new FormData();
            data.append('name', this.name);
            data.append('username', this.username);
            data.append('email', this.email);
            data.append('password', this.password);
            data.append('password_confirmation', this.password_confirmation);

            await axios.post(route('register'), data).then(response => {
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
