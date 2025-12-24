import { createApp } from "vue";

createApp({
    data() {
        return {
            form: {
                name: '',
                email: '',
                username: '',
                phone: '',
                status: '',
                role: '',
                avatar: '',
                password: '',
                password_confirmation: '',
            },
            avatar_url: default_avatar,
            show_password: false,
            show_password_confirmation: false,
            submit_form_key: 0,
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
        avatarFile: function () {
            this.form.avatar = this.$refs.file.files[0];

            if (['image/png', 'image/jpeg', 'image/jpg'].includes(this.form.avatar.type)) {
                this.avatar_url = URL.createObjectURL(this.form.avatar);
            } else {
                this.avatar_url = default_avatar;
            }
        },
        submitForm: async function (loading = true) {
            this.loading = loading;

            let data = new FormData();
            data.append('name', this.form.name);
            data.append('username', this.form.username);
            data.append('email', this.form.email);
            data.append('phone', this.form.phone);
            data.append('status', this.form.status);
            data.append('role', this.form.role);
            data.append('password', this.form.password);
            data.append('password_confirmation', this.form.password_confirmation);
            data.append('avatar', this.form.avatar);

            await axios.post(route('users.store'), data).then(response => {
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
