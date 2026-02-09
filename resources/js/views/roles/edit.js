import {createApp} from "vue";

createApp({
    data() {
        return {
            form: {
                display_name: model.display_name,
                description: model.description,
                permissions: rolePermission,
            },
            id: model.id,
            name: model.name,
            permissions_groups: permissionByGroup,
            errors: [],
            submit_key: 0,
            loading: false,
        }
    },
    mounted() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    },
    methods: {
        submitForm: async function () {
            this.loading = true;

            let data = {
                _method: document.getElementsByName('_method')[0].value,
                display_name: this.form.display_name,
                description: this.form.description,
                permissions: this.form.permissions,
            };

            await axios.post(route('roles.update', {role: this.id}), data).then(response => {
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.redirect) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                console.log(error.response);
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submit_key++;
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
}).mount('#app');
