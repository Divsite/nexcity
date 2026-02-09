import {createApp} from "vue";

const payload = window.menuForm || {
    mode: 'create',
    routes: {},
    options: {
        contexts: {},
        organizations: [],
        levels: [],
    },
    form: {
        is_active: true,
        route_parameters: [],
        visibility_rules: [],
    },
    messages: {
        invalid_json: 'Invalid JSON',
    },
};

const formatJson = (value) => {
    if (!value || Object.keys(value).length === 0) {
        return '';
    }

    try {
        return JSON.stringify(value, null, 2);
    } catch (e) {
        return '';
    }
};

createApp({
    data() {
        return {
            mode: payload.mode,
            routes: payload.routes,
            options: payload.options,
            messages: payload.messages,
            form: {
                ...payload.form,
                is_active: Boolean(payload.form.is_active ?? true),
            },
            jsonFields: {
                route_parameters: formatJson(payload.form.route_parameters),
                visibility_rules: formatJson(payload.form.visibility_rules),
            },
            errors: {},
            loading: false,
            submit_form_key: 0,
        }
    },
    methods: {
        parseJsonField(field) {
            const value = this.jsonFields[field];

            if (!value) {
                return null;
            }

            try {
                return JSON.parse(value);
            } catch (e) {
                const message = this.messages.invalid_json || 'Invalid JSON';
                this.errors[field] = [message];
                return false;
            }
        },
        async submitForm(loading = true) {
            this.loading = loading;
            this.errors = {};

            const url = this.mode === 'edit' ? this.routes.update : this.routes.store;
            const data = JSON.parse(JSON.stringify(this.form));

            const routeParameters = this.parseJsonField('route_parameters');
            if (routeParameters === false) {
                this.loading = false;
                this.submit_form_key++;
                return;
            }

            const visibilityRules = this.parseJsonField('visibility_rules');
            if (visibilityRules === false) {
                this.loading = false;
                this.submit_form_key++;
                return;
            }

            data.route_parameters = routeParameters;
            data.visibility_rules = visibilityRules;

            if (this.mode === 'edit') {
                data._method = 'PUT';
            }

            try {
                const response = await axios.post(url, data);
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submit_form_key++;
            } finally {
                this.loading = false;
            }
        },
    }
}).mount('#menu-form-app');
