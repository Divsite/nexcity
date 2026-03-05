import { createApp } from "vue";

const payload = window.distributionFundSourcesPayload || {};
const routes = window.distributionFundSourcesRoutes || {};

const app = createApp({
    data() {
        return {
            fundSources: payload.fund_sources || [],
            options: payload.options || { charity_types: [] },
            summary: payload.summary || {},
            form: {
                source_type: 'charity',
                charity_type_id: '',
                source_name: '',
                amount_used: '',
                notes: '',
            },
            loading: false,
            errors: {},
        };
    },
    computed: {
        selectedType() {
            if (!this.form.charity_type_id) {
                return null;
            }
            return this.options.charity_types.find((item) => String(item.id) === String(this.form.charity_type_id)) || null;
        },
    },
    methods: {
        formatMoney(value) {
            const amount = Number(value || 0);
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
            }).format(amount);
        },
        formatRice(value) {
            const amount = Number(value || 0);
            const label = window.messages?.liter || 'liter';
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(Number.isNaN(amount) ? 0 : amount);
            return `${formatted} ${label}`;
        },
        selectedName(id) {
            const selected = this.options.charity_types.find((item) => String(item.id) === String(id));
            return selected?.name || '-';
        },
        async refreshPayload(payloadData) {
            this.fundSources = payloadData.fund_sources || [];
            this.options = payloadData.options || this.options;
            this.summary = payloadData.summary || this.summary;
        },
        async submit() {
            if (!routes.store) {
                return;
            }
            this.loading = true;
            this.errors = {};
            try {
                const payloadData = {
                    source_type: this.form.source_type,
                    charity_type_id: this.form.source_type === 'charity' ? this.form.charity_type_id : null,
                    source_name: this.form.source_type === 'other' ? this.form.source_name : null,
                    amount_used: this.form.amount_used,
                    notes: this.form.notes,
                };
                const { data } = await axios.post(routes.store, payloadData);
                if (data?.payload) {
                    await this.refreshPayload(data.payload);
                }
                this.form.amount_used = '';
                this.form.notes = '';
                if (this.form.source_type === 'other') {
                    this.form.source_name = '';
                }
            } catch (error) {
                this.errors = error?.response?.data?.errors || {};
            } finally {
                this.loading = false;
            }
        },
        async remove(id) {
            if (!routes.delete) {
                return;
            }
            const url = routes.delete.replace(/\/0$/, `/${id}`);
            this.loading = true;
            try {
                const { data } = await axios.delete(url);
                if (data?.payload) {
                    await this.refreshPayload(data.payload);
                }
            } finally {
                this.loading = false;
            }
        },
    },
});

app.mount("#distribution-fund-sources");
