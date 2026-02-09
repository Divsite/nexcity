import { createApp } from "vue";

createApp({
    data() {
        return {
            form: {
                name: model.name,
                username: model.username,
                email: model.email,
                phone: model.phone ?? '',
                level_slug: currentLevelSlug ?? '',
                avatar: '',
                initial_name: model.initial_name,
                password: '',
                password_confirmation: '',
            },
            avatar_url: user_avatar,
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
        togglePassword() {
            this.show_password = !this.show_password;
        },
        togglePasswordConfirmation() {
            this.show_password_confirmation = !this.show_password_confirmation;
        },
        avatarFile() {
            this.form.avatar = this.$refs.file.files[0];

            if (this.form.avatar && ['image/png', 'image/jpeg', 'image/jpg'].includes(this.form.avatar.type)) {
                this.avatar_url = URL.createObjectURL(this.form.avatar);
            } else {
                this.avatar_url = user_avatar;
            }
        },
        removeAvatar() {
            if (this.form.initial_name === avatar_not_initial_name) {
                this.avatar_url = default_avatar;
                this.form.initial_name = avatar_initial_name;
            }
        },
        async submitForm(loading = true) {
            this.loading = loading;

            let data = new FormData();
            data.append('_method', document.getElementsByName('_method')[0].value);
            data.append('name', this.form.name);
            data.append('email', this.form.email);
            data.append('phone', this.form.phone);
            data.append('level_slug', this.form.level_slug);
            data.append('password', this.form.password);
            data.append('password_confirmation', this.form.password_confirmation);
            data.append('avatar', this.form.avatar);
            data.append('initial_name', this.form.initial_name);

            await axios.post(route('settings.users.update', {user: model.id}), data).then(response => {
                this.errors = [];
                this.loading = false;

                if (response.data.redirect) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submit_form_key++;
                this.loading = false;
            });
        },
    }
}).mount('#app')
