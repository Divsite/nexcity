import { createApp } from "vue";

createApp({
    data() {
        return {
            form: {
                name: user.name,
                username: user.username,
                email: user.email,
                phone: user.phone ?? '',
                avatar: '',
                initial_name: user.initial_name,
            },
            avatar_url: avatar,
            submit_form: 0,
            errors: [],
            loading: false,
        }
    },
    methods: {
        avatarFile: function () {
            this.form.avatar = this.$refs.file.files[0];

            if (['image/png', 'image/jpeg', 'image/jpg'].includes(this.form.avatar.type)) {
                this.avatar_url = URL.createObjectURL(this.form.avatar);
            } else {
                this.avatar_url = default_avatar;
            }
        },
        removeAvatar: function () {
            if (this.form.initial_name === avatar_not_initial_name) {
                this.avatar_url = default_avatar;
                this.form.initial_name = avatar_initial_name;
            }
        },
        submitForm: async function () {
            this.loading = true;

            let data = new FormData();
            data.append('_method', document.getElementsByName('_method')[0].value);
            data.append('name', this.form.name);
            data.append('phone', this.form.phone);
            data.append('avatar', this.form.avatar);
            data.append('initial_name', this.form.initial_name);

            await axios.post(route('profile.update'), data).then(response => {
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
