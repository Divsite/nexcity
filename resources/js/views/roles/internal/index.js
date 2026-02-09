import {createApp} from "vue";

createApp({
    data() {
        const contexts = window.internalRoleContexts || {};
        return {
            contexts: Object.entries(contexts).map(([key, label]) => ({key, label})),
            levelsByContext: window.internalRoleLevels || {},
            permissionGroupsByContext: window.internalRolePermissionGroups || {},
            permissionGroupLabelsByContext: window.internalRolePermissionGroupLabels || {},
            levelPermissions: window.internalRolePermissions || {},
            selectedContext: '',
            selectedLevelSlug: '',
            selectedPermissions: [],
            selectedLevelName: '',
            selectedLevelDescription: '',
            newLevelName: '',
            newLevelDescription: '',
            loading: false,
            loadingCreate: false,
            loadingLevel: false,
        };
    },
    computed: {
        levelsForContext() {
            return this.levelsByContext[this.selectedContext] || [];
        },
        permissionGroupsForContext() {
            return this.permissionGroupsByContext[this.selectedContext] || {};
        },
        permissionGroupLabelsForContext() {
            return this.permissionGroupLabelsByContext[this.selectedContext] || {};
        },
    },
    mounted() {
        if (this.contexts.length > 0) {
            this.selectedContext = this.contexts[0].key;
            this.setDefaultLevel();
        }
    },
    methods: {
        onContextChange() {
            this.setDefaultLevel();
        },
        setDefaultLevel() {
            const levels = this.levelsForContext;
            this.selectedLevelSlug = levels.length > 0 ? levels[0].slug : '';
            this.syncPermissions();
        },
        syncPermissions() {
            if (!this.selectedContext || !this.selectedLevelSlug) {
                this.selectedPermissions = [];
                this.selectedLevelName = '';
                this.selectedLevelDescription = '';
                return;
            }
            const key = `${this.selectedContext}|${this.selectedLevelSlug}`;
            this.selectedPermissions = [...(this.levelPermissions[key] || [])];
            const current = this.levelsForContext.find(level => level.slug === this.selectedLevelSlug);
            this.selectedLevelName = current?.name || '';
            this.selectedLevelDescription = current?.description || '';
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
            if (!this.selectedContext || !this.selectedLevelSlug) {
                return;
            }
            this.loading = true;
            const url = window.internalRoleUpdateUrl
                .replace('__CONTEXT__', this.selectedContext)
                .replace('__SLUG__', this.selectedLevelSlug);
            try {
                const response = await axios.post(url, {
                    permissions: this.selectedPermissions,
                });
                if (response?.data?.success) {
                    const key = `${this.selectedContext}|${this.selectedLevelSlug}`;
                    this.levelPermissions[key] = [...this.selectedPermissions];
                }
            } catch (error) {
                // ignore for now; backend will return 403/422
            } finally {
                this.loading = false;
            }
        },
        async createLevel() {
            if (!this.selectedContext || !this.newLevelName) {
                return;
            }
            this.loadingCreate = true;
            const url = window.internalRoleCreateUrl.replace('__CONTEXT__', this.selectedContext);
            try {
                const response = await axios.post(url, {
                    name: this.newLevelName,
                    description: this.newLevelDescription,
                });
                const payload = response?.data;
                if (payload?.success && payload?.level) {
                    const levels = this.levelsByContext[this.selectedContext] || [];
                    levels.push(payload.level);
                    this.levelsByContext[this.selectedContext] = levels
                        .slice()
                        .sort((a, b) => a.name.localeCompare(b.name));
                    this.selectedLevelSlug = payload.level.slug;
                    this.newLevelName = '';
                    this.newLevelDescription = '';
                    this.syncPermissions();
                }
            } catch (error) {
                // ignore for now
            } finally {
                this.loadingCreate = false;
            }
        },
        async updateLevel() {
            if (!this.selectedContext || !this.selectedLevelSlug) {
                return;
            }
            this.loadingLevel = true;
            const url = window.internalRoleLevelUpdateUrl
                .replace('__CONTEXT__', this.selectedContext)
                .replace('__SLUG__', this.selectedLevelSlug);
            try {
                const response = await axios.put(url, {
                    name: this.selectedLevelName,
                    description: this.selectedLevelDescription,
                });
                const payload = response?.data;
                if (payload?.success) {
                    const levels = this.levelsByContext[this.selectedContext] || [];
                    const index = levels.findIndex(level => level.slug === this.selectedLevelSlug);
                    if (index !== -1) {
                        levels[index].name = this.selectedLevelName;
                        levels[index].description = this.selectedLevelDescription;
                        this.levelsByContext[this.selectedContext] = levels
                            .slice()
                            .sort((a, b) => a.name.localeCompare(b.name));
                    }
                }
            } catch (error) {
                // ignore for now
            } finally {
                this.loadingLevel = false;
            }
        },
        async deleteLevel() {
            if (!this.selectedContext || !this.selectedLevelSlug) {
                return;
            }
            if (!confirm('Delete this internal role?')) {
                return;
            }
            this.loadingLevel = true;
            const url = window.internalRoleLevelDeleteUrl
                .replace('__CONTEXT__', this.selectedContext)
                .replace('__SLUG__', this.selectedLevelSlug);
            try {
                const response = await axios.delete(url);
                const payload = response?.data;
                if (payload?.success) {
                    const levels = (this.levelsByContext[this.selectedContext] || [])
                        .filter(level => level.slug !== this.selectedLevelSlug);
                    this.levelsByContext[this.selectedContext] = levels;
                    this.selectedLevelSlug = levels.length ? levels[0].slug : '';
                    this.syncPermissions();
                }
            } catch (error) {
                // ignore for now
            } finally {
                this.loadingLevel = false;
            }
        },
    },
}).mount('#app');
