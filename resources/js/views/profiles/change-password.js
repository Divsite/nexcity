import { createApp } from "vue";
import PasswordStrength from "../../components/Widgets/PasswordStrength.vue";

let data = JSON.parse(user);

createApp({
    name: "ChangePasswordForm",
    components: {PasswordStrength},
    data() {
        return {
            form: {
                current_password: '',
                new_password: '',
                new_password_confirmation: '',
            },
            is_password_set: is_password_set,
            show_current_password: false,
            show_new_password: false,
            show_new_password_confirmation: false,
            submit_form: 0,
            errors: [],
            loading: false,
        }
    },
    computed: {
        currentPasswordType() {
            return this.show_current_password ? 'text' : 'password';
        },
        newPasswordType() {
            return this.show_new_password ? 'text' : 'password';
        },
        newPasswordConfirmationType() {
            return this.show_new_password_confirmation ? 'text' : 'password';
        }
    },
    methods: {
        toggleCurrentPassword: function () {
            this.show_current_password = !this.show_current_password;
        },
        toggleNewPassword: function () {
            this.show_new_password = !this.show_new_password;
        },
        toggleNewPasswordConfirmation: function () {
            this.show_new_password_confirmation = !this.show_new_password_confirmation;
        },
        submitForm: async function () {
            this.loading = true;

            let data = new FormData();
            data.append('current_password', this.form.current_password);
            data.append('new_password', this.form.new_password);
            data.append('new_password_confirmation', this.form.new_password_confirmation);

            await axios.post(route('profile.change-password'), data).then(response => {
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
