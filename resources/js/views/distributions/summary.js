import { createApp, nextTick } from "vue";
import YearPicker from "../../components/Forms/Helpers/YearPicker.vue";
import VueCurrencyInput from "../../components/Forms/Helpers/VueCurrencyInput.vue";

const payload = window.distributionSummaryPayload || {};

const defaultSummary = {
    distributed_money_label: '0',
    distributed_rice_label: '0,00',
    distributed_recipients: 0,
    remaining_money_label: '0',
    remaining_rice_label: '0,00',
    pending_recipients: 0,
};

const currencyOptions = {
    currency: 'IDR',
    currencyDisplay: 'narrowSymbol',
    precision: 2,
    autoDecimalDigits: true,
    hideCurrencySymbolOnFocus: true,
    hideGroupingSeparatorOnFocus: false,
};

const toNumber = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const parsed = Number(value);
    return Number.isNaN(parsed) ? null : parsed;
};

const app = createApp({
    components: {
        YearPicker,
        VueCurrencyInput,
    },
    data() {
        const initialYear = payload.filters?.year ?? new Date().getFullYear();

        return {
            filters: {
                distribution_class_id: payload.filters?.distribution_class_id ?? '',
                year: String(initialYear),
                neighborhood_association_id: payload.filters?.neighborhood_association_id ?? '',
            },
            options: payload.options || { distribution_classes: [], years: [], neighborhoods: [] },
            routes: payload.routes || {},
            summary: { ...defaultSummary, ...(payload.summary || {}) },
            fundSources: [],
            fundOptions: { charity_types: [] },
            fundSummary: {},
            fundForm: {
                charity_type_ids: [],
                other_source_name: '',
                other_source_amount: '',
            },
            currencyOptions,
            fundErrors: {},
            fundLoading: false,
            loading: false,
            debounceTimer: null,
        };
    },
    computed: {
        selectedClassLabel() {
            if (!this.filters.distribution_class_id) {
                return window.messages?.all || 'All';
            }

            const selected = this.options.distribution_classes.find(
                (item) => String(item.id) === String(this.filters.distribution_class_id)
            );
            return selected?.label || selected?.source_name || (window.messages?.all || 'All');
        },
        selectedYearLabel() {
            if (!this.filters.year) {
                return window.messages?.all || 'All';
            }

            return String(this.filters.year);
        },
        selectedNeighborhoodLabel() {
            if (!this.filters.neighborhood_association_id) {
                return window.messages?.all || 'All';
            }

            const selected = this.options.neighborhoods.find(
                (item) => String(item.id) === String(this.filters.neighborhood_association_id)
            );
            return selected?.name || (window.messages?.all || 'All');
        },
        ruleItems() {
            let items = Array.isArray(this.options.distribution_classes)
                ? [...this.options.distribution_classes]
                : [];

            if (this.filters.year) {
                items = items.filter((item) => String(item.year) === String(this.filters.year));
            }

            if (this.filters.distribution_class_id) {
                items = items.filter((item) => String(item.id) === String(this.filters.distribution_class_id));
            }

            return items;
        },
        orderedSelectedCharityTypeIds() {
            const selected = new Set((this.fundForm.charity_type_ids || []).map((id) => String(id)));
            return (this.fundOptions.charity_types || [])
                .filter((item) => selected.has(String(item.id)))
                .map((item) => item.id);
        },
        requiredFundMoney() {
            return Number(this.fundSummary.required_money || 0);
        },
        maxAvailableFund() {
            const values = (this.fundOptions.charity_types || []).map((item) => Number(item.remaining_money || 0));
            return values.length > 0 ? Math.max(...values) : 0;
        },
        hasCoveringSource() {
            return this.requiredFundMoney > 0 && this.maxAvailableFund >= this.requiredFundMoney;
        },
        selectedCoveringId() {
            if (!this.hasCoveringSource) {
                return null;
            }
            const selected = this.orderedSelectedCharityTypeIds;
            for (const id of selected) {
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                if (Number(type?.remaining_money || 0) >= this.requiredFundMoney) {
                    return id;
                }
            }
            return null;
        },
        allocationMap() {
            const remaining = Number(this.fundSummary.required_money || 0);
            let needed = remaining;
            const allocations = {};
            for (const id of this.orderedSelectedCharityTypeIds) {
                if (needed <= 0) {
                    break;
                }
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                const available = Number(type?.remaining_money || 0);
                const use = Math.min(available, needed);
                allocations[id] = use;
                needed -= use;
            }
            return allocations;
        },
        selectedAvailableTotal() {
            return this.orderedSelectedCharityTypeIds.reduce((sum, id) => {
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                return sum + Number(type?.remaining_money || 0);
            }, 0);
        },
        remainingNeeded() {
            const required = Number(this.fundSummary.required_money || 0);
            const otherAmount = Number(this.fundForm.other_source_amount || 0);
            const covered = this.selectedAvailableTotal + otherAmount;
            return Math.max(required - covered, 0);
        },
        surplusAmount() {
            const required = Number(this.fundSummary.required_money || 0);
            const otherAmount = Number(this.fundForm.other_source_amount || 0);
            const covered = this.selectedAvailableTotal + otherAmount;
            return Math.max(covered - required, 0);
        },
    },
    watch: {
        'filters.distribution_class_id'() {
            this.queueFetch();
        },
        'filters.neighborhood_association_id'() {
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
        'fundForm.charity_type_ids': {
            handler() {
                this.normalizeFundSelection();
            },
            deep: true,
        },
    },
    mounted() {
        this.fetchSummary();
        if (window.Livewire && typeof window.Livewire.on === 'function') {
            window.Livewire.on('distributionSaved', () => {
                this.fetchSummary();
            });
        }
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
            return `${amount} ${label}`;
        },
        fundTypeName(id) {
            const selected = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
            return selected?.name || '-';
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
                    distribution_class_id: this.filters.distribution_class_id || undefined,
                    year: this.filters.year ? Number(this.filters.year) : undefined,
                    neighborhood_association_id: this.filters.neighborhood_association_id || undefined,
                };
                const { data } = await axios.get(this.routes.summary, { params });
                const summary = data?.summary || {};
                this.summary = {
                    ...defaultSummary,
                    ...summary,
                };
                await this.fetchFundSources();
            } catch (error) {
                // ignore
            } finally {
                this.loading = false;
                await nextTick();
            }
        },
        async fetchFundSources() {
            if (!this.routes.fund_sources) {
                this.fundSources = [];
                this.fundOptions = { charity_types: [] };
                this.fundSummary = {};
                this.fundForm.charity_type_ids = [];
                this.fundForm.other_source_name = '';
                this.fundForm.other_source_amount = '';
                return;
            }
            this.fundLoading = true;
            try {
                const { data } = await axios.get(this.routes.fund_sources, {
                    params: {
                        distribution_class_id: this.filters.distribution_class_id || undefined,
                        year: this.filters.year ? Number(this.filters.year) : undefined,
                        neighborhood_association_id: this.filters.neighborhood_association_id || undefined,
                    },
                });
                this.fundSources = data?.fund_sources || [];
                this.fundOptions = data?.options || { charity_types: [] };
                this.fundSummary = data?.summary || {};
                const selection = data?.selection || {};
                this.fundForm.charity_type_ids = Array.isArray(selection.charity_type_ids)
                    ? selection.charity_type_ids
                    : [];
                this.fundForm.other_source_name = selection.other_source_name || '';
                this.fundForm.other_source_amount = toNumber(selection.other_source_amount);
                this.normalizeFundSelection();
            } catch (error) {
                this.fundSources = [];
            } finally {
                this.fundLoading = false;
            }
        },
        async submitFundSource() {
            if (!this.routes.fund_sources_store) {
                return;
            }
            this.fundLoading = true;
            this.fundErrors = {};
            try {
                const payloadData = {
                    distribution_class_id: this.filters.distribution_class_id || null,
                    year: this.filters.year ? Number(this.filters.year) : null,
                    neighborhood_association_id: this.filters.neighborhood_association_id || null,
                    charity_type_ids: this.orderedSelectedCharityTypeIds,
                    other_source_name: this.fundForm.other_source_name || null,
                    other_source_amount: toNumber(this.fundForm.other_source_amount),
                };
                const { data } = await axios.post(this.routes.fund_sources_store, payloadData);
                if (data?.payload) {
                    this.fundSources = data.payload.fund_sources || [];
                    this.fundOptions = data.payload.options || this.fundOptions;
                    this.fundSummary = data.payload.summary || this.fundSummary;
                    const selection = data.payload.selection || {};
                    this.fundForm.charity_type_ids = Array.isArray(selection.charity_type_ids)
                        ? selection.charity_type_ids
                        : this.fundForm.charity_type_ids;
                    this.fundForm.other_source_name = selection.other_source_name || '';
                    this.fundForm.other_source_amount = toNumber(selection.other_source_amount);
                    this.normalizeFundSelection();
                }
            } catch (error) {
                this.fundErrors = error?.response?.data?.errors || {};
            } finally {
                this.fundLoading = false;
            }
        },
        async removeFundSource(id) {
            if (!this.routes.fund_sources_delete) {
                return;
            }
            const url = this.routes.fund_sources_delete
                .replace('__source__', id);
            this.fundLoading = true;
            try {
                await axios.delete(url);
                await this.fetchFundSources();
            } catch (error) {
                this.fundErrors = error?.response?.data?.errors || {};
            } finally {
                this.fundLoading = false;
            }
        },
        normalizeFundSelection() {
            if (!this.hasCoveringSource) {
                return;
            }

            const required = this.requiredFundMoney;
            const selected = this.orderedSelectedCharityTypeIds;
            const coveringSelected = selected.find((id) => {
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                return Number(type?.remaining_money || 0) >= required;
            });

            if (coveringSelected) {
                if (selected.length !== 1 || String(selected[0]) !== String(coveringSelected)) {
                    this.fundForm.charity_type_ids = [coveringSelected];
                }
                return;
            }

            const allowedIds = (this.fundOptions.charity_types || [])
                .filter((item) => Number(item.remaining_money || 0) >= required)
                .map((item) => item.id);

            const nextSelected = (this.fundForm.charity_type_ids || []).filter((id) =>
                allowedIds.map(String).includes(String(id))
            );

            if (nextSelected.length !== (this.fundForm.charity_type_ids || []).length) {
                this.fundForm.charity_type_ids = nextSelected;
            }
        },
        isFundSourceDisabled(item) {
            const remaining = Number(item?.remaining_money || 0);
            if (remaining <= 0) {
                return true;
            }
            if (!this.hasCoveringSource) {
                return false;
            }
            const required = this.requiredFundMoney;
            if (this.selectedCoveringId) {
                return String(item.id) !== String(this.selectedCoveringId);
            }

            return remaining < required;
        },
        fundSourceRemainingLabel(item) {
            if (item.source_type !== 'charity') {
                return '-';
            }
            const type = this.fundOptions.charity_types.find(
                (option) => String(option.id) === String(item.charity_type_id)
            );
            const available = Number(type?.remaining_money || 0);
            const used = Number(item.amount_used || 0);
            const remaining = Math.max(available - used, 0);
            return this.formatMoney(remaining);
        },
    },
});

app.mount("#distribution-summary");
