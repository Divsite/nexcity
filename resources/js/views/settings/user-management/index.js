import {createApp} from "vue";

createApp({
    data() {
        return {
            levels: window.userLevels || [],
            permissionGroups: window.permissionGroups || {},
            permissionGroupLabels: window.permissionGroupLabels || {},
            levelPermissions: window.levelPermissions || {},
            selectedLevelId: '',
            selectedPermissions: [],
            loading: false,
        };
    },
    mounted() {
        if (this.levels.length > 0) {
            this.selectedLevelId = this.levels[0].id;
            this.syncPermissions();
        }
    },
    methods: {
        syncPermissions() {
            if (!this.selectedLevelId) {
                this.selectedPermissions = [];
                return;
            }
            const existing = this.levelPermissions[this.selectedLevelId] || [];
            this.selectedPermissions = [...existing];
        },
        toggleGroup(groupPermissions) {
            const groupNames = groupPermissions.map(item => item.name);
            const allSelected = groupNames.every(name => this.selectedPermissions.includes(name));
            if (allSelected) {
                this.selectedPermissions = this.selectedPermissions.filter(name => !groupNames.includes(name));
            } else {
                for (const name of groupNames) {
                    if (!this.selectedPermissions.includes(name)) {
                        this.selectedPermissions.push(name);
                    }
                }
            }
        },
        async submit() {
            if (!this.selectedLevelId) {
                return;
            }
            this.loading = true;
            const url = window.userManagementUpdateUrl.replace('__LEVEL__', this.selectedLevelId);
            try {
                const response = await axios.post(url, {
                    permissions: this.selectedPermissions,
                });
                if (response?.data?.success) {
                    this.levelPermissions[this.selectedLevelId] = [...this.selectedPermissions];
                    if (response?.data?.message) {
                        // eslint-disable-next-line no-alert
                        alert(response.data.message);
                    }
                }
            } catch (error) {
                // ignore for now; backend will return 403/422
            } finally {
                this.loading = false;
            }
        },
    },
}).mount('#app');
