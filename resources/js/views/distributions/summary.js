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
                priority_charity_type_ids: [],
                enforce_priority: true,
                other_source_name: '',
                other_source_amount: '',
                other_source_rice: '',
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

            const classes = Array.isArray(this.options.distribution_classes) ? this.options.distribution_classes : [];
            const selected = classes.find(
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

            const neighborhoods = Array.isArray(this.options.neighborhoods) ? this.options.neighborhoods : [];
            const selected = neighborhoods.find(
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
            const selected = (this.fundForm.charity_type_ids || []).map((id) => String(id));
            const priority = this.priorityIds;
            const ordered = [];
            priority.forEach((id) => {
                if (selected.includes(id)) {
                    ordered.push(id);
                }
            });
            selected.forEach((id) => {
                if (!ordered.includes(id)) {
                    ordered.push(id);
                }
            });
            return ordered.map((id) => Number(id));
        },
        priorityIds() {
            return (this.fundForm.priority_charity_type_ids || []).map((id) => String(id));
        },
        priorityTotals() {
            const totals = this.priorityIds.reduce(
                (acc, id) => {
                    const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                    acc.money += Number(type?.remaining_money || 0);
                    acc.rice += Number(type?.remaining_rice || 0);
                    return acc;
                },
                { money: 0, rice: 0 }
            );
            return totals;
        },
        priorityCoverageSufficient() {
            if (!this.fundForm.enforce_priority || this.priorityIds.length === 0) {
                return false;
            }
            const requiredMoney = this.requiredFundMoney;
            const requiredRice = this.requiredFundRice;
            return (requiredMoney > 0 ? this.priorityTotals.money >= requiredMoney : true)
                && (requiredRice > 0 ? this.priorityTotals.rice >= requiredRice : true);
        },
        requiredFundMoney() {
            return Number(this.fundSummary.required_money || 0);
        },
        requiredFundRice() {
            return Number(this.fundSummary.required_rice || 0);
        },
        maxAvailableFund() {
            const values = (this.fundOptions.charity_types || []).map((item) => Number(item.remaining_money || 0));
            return values.length > 0 ? Math.max(...values) : 0;
        },
        maxAvailableRice() {
            const values = (this.fundOptions.charity_types || []).map((item) => Number(item.remaining_rice || 0));
            return values.length > 0 ? Math.max(...values) : 0;
        },
        hasCoveringSource() {
            if (this.requiredFundMoney <= 0 && this.requiredFundRice <= 0) {
                return false;
            }
            return (this.requiredFundMoney > 0 ? this.maxAvailableFund >= this.requiredFundMoney : true)
                && (this.requiredFundRice > 0 ? this.maxAvailableRice >= this.requiredFundRice : true);
        },
        selectedCoveringId() {
            if (this.priorityCoverageSufficient) {
                return null;
            }
            if (!this.hasCoveringSource) {
                return null;
            }
            const selected = this.orderedSelectedCharityTypeIds;
            for (const id of selected) {
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                const moneyOk = this.requiredFundMoney > 0 ? Number(type?.remaining_money || 0) >= this.requiredFundMoney : true;
                const riceOk = this.requiredFundRice > 0 ? Number(type?.remaining_rice || 0) >= this.requiredFundRice : true;
                if (moneyOk && riceOk) {
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
        allocationRiceMap() {
            const remaining = Number(this.fundSummary.required_rice || 0);
            let needed = remaining;
            const allocations = {};
            for (const id of this.orderedSelectedCharityTypeIds) {
                if (needed <= 0) {
                    break;
                }
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                const available = Number(type?.remaining_rice || 0);
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
        selectedAvailableRiceTotal() {
            return this.orderedSelectedCharityTypeIds.reduce((sum, id) => {
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                return sum + Number(type?.remaining_rice || 0);
            }, 0);
        },
        remainingNeeded() {
            const required = Number(this.fundSummary.required_money || 0);
            const otherAmount = Number(this.fundForm.other_source_amount || 0);
            const covered = this.selectedAvailableTotal + otherAmount;
            return Math.max(required - covered, 0);
        },
        remainingRiceNeeded() {
            const required = Number(this.fundSummary.required_rice || 0);
            const otherRice = Number(this.fundForm.other_source_rice || 0);
            const covered = this.selectedAvailableRiceTotal + otherRice;
            return Math.max(required - covered, 0);
        },
        surplusAmount() {
            const required = Number(this.fundSummary.required_money || 0);
            const otherAmount = Number(this.fundForm.other_source_amount || 0);
            const covered = this.selectedAvailableTotal + otherAmount;
            return Math.max(covered - required, 0);
        },
        surplusRice() {
            const required = Number(this.fundSummary.required_rice || 0);
            const otherRice = Number(this.fundForm.other_source_rice || 0);
            const covered = this.selectedAvailableRiceTotal + otherRice;
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
        'fundForm.priority_charity_type_ids': {
            handler() {
                this.normalizeFundSelection();
            },
            deep: true,
        },
        'fundForm.enforce_priority'() {
            this.normalizeFundSelection();
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
        arraysEqual(a = [], b = []) {
            if (a.length !== b.length) {
                return false;
            }
            return a.every((value, index) => String(value) === String(b[index]));
        },
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
            this.fundForm.priority_charity_type_ids = [];
            this.fundForm.enforce_priority = true;
            this.fundForm.other_source_name = '';
            this.fundForm.other_source_amount = '';
            this.fundForm.other_source_rice = '';
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
                this.fundForm.priority_charity_type_ids = Array.isArray(selection.priority_charity_type_ids)
                    ? selection.priority_charity_type_ids
                    : [];
                this.fundForm.enforce_priority = selection.enforce_priority !== undefined
                    ? Boolean(selection.enforce_priority)
                    : true;
                this.fundForm.other_source_name = selection.other_source_name || '';
                this.fundForm.other_source_amount = toNumber(selection.other_source_amount);
                this.fundForm.other_source_rice = toNumber(selection.other_source_rice);
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
                    priority_charity_type_ids: this.fundForm.priority_charity_type_ids,
                    enforce_priority: this.fundForm.enforce_priority ? 1 : 0,
                    other_source_name: this.fundForm.other_source_name || null,
                    other_source_amount: toNumber(this.fundForm.other_source_amount),
                    other_source_rice: toNumber(this.fundForm.other_source_rice),
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
                    this.fundForm.priority_charity_type_ids = Array.isArray(selection.priority_charity_type_ids)
                        ? selection.priority_charity_type_ids
                        : this.fundForm.priority_charity_type_ids;
                    this.fundForm.enforce_priority = selection.enforce_priority !== undefined
                        ? Boolean(selection.enforce_priority)
                        : this.fundForm.enforce_priority;
                    this.fundForm.other_source_name = selection.other_source_name || '';
                    this.fundForm.other_source_amount = toNumber(selection.other_source_amount);
                    this.fundForm.other_source_rice = toNumber(selection.other_source_rice);
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
            if (this.fundForm.enforce_priority && this.priorityIds.length > 0) {
                const selected = new Set((this.fundForm.charity_type_ids || []).map((id) => String(id)));
                this.priorityIds.forEach((id) => selected.add(String(id)));
                const merged = Array.from(selected);
                if (!this.arraysEqual(this.fundForm.charity_type_ids || [], merged)) {
                    this.fundForm.charity_type_ids = merged;
                }

                if (this.priorityCoverageSufficient) {
                    const priorityList = [...this.priorityIds];
                    if (!this.arraysEqual(this.fundForm.charity_type_ids || [], priorityList)) {
                        this.fundForm.charity_type_ids = priorityList;
                    }
                    return;
                }
            }

            if (this.fundForm.enforce_priority) {
                return;
            }

            if (!this.hasCoveringSource) {
                return;
            }

            const requiredMoney = this.requiredFundMoney;
            const requiredRice = this.requiredFundRice;
            const selected = this.orderedSelectedCharityTypeIds;
            const coveringSelected = selected.find((id) => {
                const type = this.fundOptions.charity_types.find((item) => String(item.id) === String(id));
                const moneyOk = requiredMoney > 0 ? Number(type?.remaining_money || 0) >= requiredMoney : true;
                const riceOk = requiredRice > 0 ? Number(type?.remaining_rice || 0) >= requiredRice : true;
                return moneyOk && riceOk;
            });

            if (coveringSelected) {
                const next = [coveringSelected];
                if (!this.arraysEqual(selected, next)) {
                    this.fundForm.charity_type_ids = next;
                }
                return;
            }

            const allowedIds = (this.fundOptions.charity_types || [])
                .filter((item) => {
                    const moneyOk = requiredMoney > 0 ? Number(item.remaining_money || 0) >= requiredMoney : true;
                    const riceOk = requiredRice > 0 ? Number(item.remaining_rice || 0) >= requiredRice : true;
                    return moneyOk && riceOk;
                })
                .map((item) => item.id);

            const nextSelected = (this.fundForm.charity_type_ids || []).filter((id) =>
                allowedIds.map(String).includes(String(id))
            );

            if (nextSelected.length !== (this.fundForm.charity_type_ids || []).length) {
                if (!this.arraysEqual(this.fundForm.charity_type_ids || [], nextSelected)) {
                    this.fundForm.charity_type_ids = nextSelected;
                }
            }
        },
        isFundSourceDisabled(item) {
            const remainingMoney = Number(item?.remaining_money || 0);
            const remainingRice = Number(item?.remaining_rice || 0);
            if (remainingMoney <= 0 && remainingRice <= 0) {
                return true;
            }
            if (this.fundForm.enforce_priority && this.priorityCoverageSufficient) {
                return !this.priorityIds.includes(String(item.id));
            }
            if (!this.hasCoveringSource) {
                return false;
            }
            const requiredMoney = this.requiredFundMoney;
            const requiredRice = this.requiredFundRice;
            if (this.selectedCoveringId) {
                return String(item.id) !== String(this.selectedCoveringId);
            }

            const moneyOk = requiredMoney > 0 ? remainingMoney >= requiredMoney : true;
            const riceOk = requiredRice > 0 ? remainingRice >= requiredRice : true;
            return !(moneyOk && riceOk);
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
