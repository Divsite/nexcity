import { createApp, nextTick } from "vue";
import YearPicker from "../../components/Forms/Helpers/YearPicker.vue";

const payload = window.charitySummaryPayload || {};

const defaultSummary = {
    today: {
        total_money_label: '0',
        total_rice_label: '0,00',
        total_transactions: 0,
    },
    year: {
        total_money_label: '0',
        total_rice_label: '0,00',
        total_transactions: 0,
    },
    yearly: {
        year: new Date().getFullYear(),
        total_money_label: '0',
        total_rice_label: '0,00',
        total_transactions: 0,
    },
};

const app = createApp({
    components: {
        YearPicker,
    },
    data() {
        const initialYear = payload.filters?.year ?? new Date().getFullYear();

        return {
            filters: {
                type_id: payload.filters?.type_id ?? null,
                year_type_id: payload.filters?.year_type_id ?? null,
                year: String(initialYear),
                payment_method: payload.filters?.payment_method ?? '',
                year_payment_method: payload.filters?.year_payment_method ?? '',
            },
            options: payload.options || { charity_types: [], payment_methods: [] },
            routes: payload.routes || {},
            summary: { ...defaultSummary, ...(payload.summary || {}) },
            loading: false,
            debounceTimer: null,
        };
    },
    mounted() {
        this.bindLivewireRefresh();
        this.fetchSummary();
    },
    computed: {
        selectedTypeLabel() {
            if (!this.filters.type_id) {
                return window.messages?.all || 'All';
            }

            const selected = this.options.charity_types.find((item) => String(item.id) === String(this.filters.type_id));
            return selected?.name || (window.messages?.all || 'All');
        },
        selectedYearTypeLabel() {
            if (!this.filters.year_type_id) {
                return window.messages?.all || 'All';
            }

            const selected = this.options.charity_types.find((item) => String(item.id) === String(this.filters.year_type_id));
            return selected?.name || (window.messages?.all || 'All');
        },
        selectedPaymentLabel() {
            if (!this.filters.payment_method) {
                return window.messages?.all || 'All';
            }

            const selected = this.options.payment_methods.find((item) => String(item.value) === String(this.filters.payment_method));
            return selected?.label || (window.messages?.all || 'All');
        },
        selectedYearPaymentLabel() {
            if (!this.filters.year_payment_method) {
                return window.messages?.all || 'All';
            }

            const selected = this.options.payment_methods.find((item) => String(item.value) === String(this.filters.year_payment_method));
            return selected?.label || (window.messages?.all || 'All');
        },
    },
    watch: {
        'filters.type_id'() {
            this.queueFetch();
        },
        'filters.year_type_id'() {
            this.queueFetch();
        },
        'filters.payment_method'() {
            this.queueFetch();
        },
        'filters.year_payment_method'() {
            this.queueFetch();
        },
        'filters.year'(value) {
            const normalized = this.normalizeYear(value);
            if (normalized !== value) {
                this.filters.year = normalized;
                return;
            }
            this.queueFetch();
        },
    },
    methods: {
        bindLivewireRefresh() {
            if (window.Livewire && typeof window.Livewire.on === 'function') {
                window.Livewire.on('charityTransactionSaved', () => {
                    this.fetchSummary();
                });
            }
        },
        queueFetch() {
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            this.debounceTimer = setTimeout(() => {
                this.fetchSummary();
            }, 300);
        },
        normalizeYear(value) {
            if (value instanceof Date) {
                return String(value.getFullYear());
            }

            if (typeof value === 'number') {
                return String(value);
            }

            return value;
        },
        async fetchSummary() {
            if (!this.routes.summary) {
                return;
            }
            this.loading = true;
            try {
                const params = {
                    type_id: this.filters.type_id || undefined,
                    year_type_id: this.filters.year_type_id || undefined,
                    year: this.filters.year ? Number(this.filters.year) : undefined,
                    payment_method: this.filters.payment_method || undefined,
                    year_payment_method: this.filters.year_payment_method || undefined,
                };
                const { data } = await axios.get(this.routes.summary, { params });
                const summary = data?.summary || {};
                this.summary = {
                    ...defaultSummary,
                    ...summary,
                };
            } catch (error) {
                // ignore fetch errors, keep previous summary
            } finally {
                this.loading = false;
                await nextTick();
            }
        },
    },
});

app.mount("#charity-summary");
