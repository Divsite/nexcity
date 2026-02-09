import {createApp} from "vue";

createApp({
    data() {
        return {
            form: {
                name: '',
                display_name: '',
                description: '',
                permissions: [],
            },
            permissions_groups: permissionByGroup,
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
                display_name: this.form.display_name,
                description: this.form.description,
                permissions: this.form.permissions,
            };

            await axios.post(route('roles.store'), data).then(response => {
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
        selectAllPermissions: function () {
            for (let permissions in this.permissions_groups) {
                for (let item in this.permissions_groups[permissions]) {
                    if (!this.form.permissions.includes(this.permissions_groups[permissions][item]['id'])) {
                        this.form.permissions.push(this.permissions_groups[permissions][item]['id']);
                    }
                }
            }
        },
        deselectAllPermissions: function () {
            this.form.permissions = [];
        },
    }
}).mount('#app')
