import { createApp } from "vue";
import VueCurrencyInput from "../../components/Forms/Helpers/VueCurrencyInput.vue";

const payload = window.charityTransactionForm || {
    mode: 'create',
    form: {},
    routes: {},
    options: {},
    context: null,
    ui: { modal: false },
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

const createEmptyPayer = (options = {}) => ({
    payer_name: '',
    payer_phone: '',
    payer_email: '',
    is_money: options.is_money !== undefined ? Boolean(options.is_money) : true,
    is_rice: options.is_rice !== undefined ? Boolean(options.is_rice) : false,
    multiplier_count: toNumber(options.multiplier_count),
    total_money: toNumber(options.total_money),
    total_rice: toNumber(options.total_rice),
    notes: '',
});

createApp({
    components: {
        VueCurrencyInput,
    },
    data() {
        const initialForm = {
            organization_id: payload.form.organization_id || '',
            charity_type_id: payload.form.charity_type_id || '',
            year: payload.form.year || new Date().getFullYear(),
            payer_name: payload.form.payer_name || '',
            payer_phone: payload.form.payer_phone || '',
            payer_email: payload.form.payer_email || '',
            payment_method: payload.form.payment_method || 'cash',
            charity_payment_id: payload.form.charity_payment_id || '',
            is_package: Boolean(payload.form.is_package),
            use_same_package_amount: Boolean(payload.form.use_same_package_amount),
            is_input_family_members: Boolean(payload.form.is_input_family_members),
            representative_total_money: toNumber(payload.form.representative_total_money),
            representative_total_rice: toNumber(payload.form.representative_total_rice),
            package_amount_each: toNumber(payload.form.package_amount_each),
            package_members_count: toNumber(payload.form.package_members_count),
            multiplier_count: toNumber(payload.form.multiplier_count),
            amount_money: toNumber(payload.form.amount_money),
            amount_rice: toNumber(payload.form.amount_rice),
            package_payers: Array.isArray(payload.form.package_payers)
                ? payload.form.package_payers.map((payer) => ({
                    payer_name: payer.payer_name || '',
                    payer_phone: payer.payer_phone || '',
                    payer_email: payer.payer_email || '',
                    is_money: payer.is_money !== undefined ? Boolean(payer.is_money) : true,
                    is_rice: payer.is_rice !== undefined ? Boolean(payer.is_rice) : false,
                    multiplier_count: toNumber(payer.multiplier_count),
                    total_money: toNumber(payer.total_money),
                    total_rice: toNumber(payer.total_rice),
                    notes: payer.notes || '',
                }))
                : [],
            status: payload.form.status || 'paid',
            total_money: toNumber(payload.form.total_money),
            total_rice: toNumber(payload.form.total_rice),
            notes: payload.form.notes || '',
            detail: {
                ...(payload.form.detail || {}),
                is_rice: Boolean(payload.form?.detail?.is_rice),
                is_money: payload.form?.detail?.is_money !== undefined
                    ? Boolean(payload.form?.detail?.is_money)
                    : true,
            },
        };

        if (initialForm.is_package && !initialForm.use_same_package_amount) {
            initialForm.is_input_family_members = true;
        }

        if (initialForm.is_package && (!initialForm.use_same_package_amount || initialForm.is_input_family_members) && initialForm.package_payers.length === 0) {
            initialForm.package_payers = [
                createEmptyPayer({
                    total_money: initialForm.use_same_package_amount ? initialForm.package_amount_each : null,
                    is_money: true,
                    is_rice: false,
                }),
            ];
        }

        if (initialForm.is_package && !initialForm.package_members_count) {
            initialForm.package_members_count = initialForm.use_same_package_amount
                ? (initialForm.package_payers.length > 0 ? initialForm.package_payers.length + 1 : 1)
                : (initialForm.package_payers.length + 1);
        }

        const hasRice = Boolean(initialForm.detail?.is_rice)
            || Boolean(toNumber(initialForm.amount_rice))
            || Boolean(toNumber(initialForm.total_rice))
            || Boolean(toNumber(initialForm.representative_total_rice))
            || initialForm.package_payers.some((payer) => Boolean(payer.is_rice) || Boolean(toNumber(payer.total_rice)));

        if (hasRice) {
            initialForm.detail.is_rice = true;
        }

        return {
            mode: payload.mode,
            ui: payload.ui || { modal: false },
            context: payload.context,
            form: { ...initialForm },
            options: payload.options || { charity_types: [], payment_methods: [], payments: [], statuses: [] },
            routes: payload.routes || {},
            errors: {},
            loading: false,
            submit_form_key: 0,
            initialForm,
            currencyOptions,
        };
    },
    computed: {
        isModalMode() {
            return Boolean(this.ui?.modal);
        },
        selectedCharityType() {
            const selectedId = Number(this.form.charity_type_id);

            if (!selectedId) {
                return null;
            }

            return this.options.charity_types.find((item) => Number(item.id) === selectedId) || null;
        },
        filteredPayments() {
            const method = this.form.payment_method;
            if (!method || method === 'cash') {
                return [];
            }
            return (this.options.payments || []).filter((item) => String(item.type).toLowerCase() === String(method).toLowerCase());
        },
        selectedPayment() {
            const selectedId = Number(this.form.charity_payment_id);

            if (!selectedId) {
                return null;
            }

            return this.filteredPayments.find((item) => Number(item.id) === selectedId)
                || this.options.payments.find((item) => Number(item.id) === selectedId)
                || null;
        },
        familyMembersRowsCount() {
            return Array.isArray(this.form.package_payers) ? this.form.package_payers.length : 0;
        },
        totalMoneyPreview() {
            if (!this.form.detail.is_money) {
                return 0;
            }

            if (this.form.is_package) {
                return Number(this.form.total_money || 0);
            }

            const baseAmount = toNumber(this.form.amount_money) || 0;
            const multiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                ? this.resolveMultiplier(this.form.multiplier_count)
                : 1;

            return baseAmount * multiplier;
        },
        totalRicePreview() {
            return this.calculateTotalRice();
        },
        totalRicePreviewLabel() {
            return this.formatDecimal(this.totalRicePreview);
        },
    },
    watch: {
        'form.representative_total_money'() {
            if (this.form.is_package && !this.form.use_same_package_amount) {
                this.recalculatePackageTotal();
            }
        },
        'form.is_package'(value) {
            if (value) {
                if (!this.form.package_members_count) {
                    this.form.package_members_count = this.form.use_same_package_amount
                        ? (this.form.package_payers.length > 0 ? this.form.package_payers.length + 1 : 1)
                        : (this.form.package_payers.length + 1);
                }

                if (!this.form.use_same_package_amount) {
                    this.form.is_input_family_members = true;
                }

                this.form.amount_money = null;
                this.form.amount_rice = null;

                if ((!this.form.use_same_package_amount || this.form.is_input_family_members) && this.form.package_payers.length === 0) {
                    this.form.package_payers.push(
                        createEmptyPayer({
                            total_money: this.form.use_same_package_amount ? this.form.package_amount_each : null,
                            is_money: true,
                            is_rice: false,
                        })
                    );
                }
            }

            if (!value) {
                this.form.package_payers = [];
                this.form.use_same_package_amount = false;
                this.form.is_input_family_members = false;
                this.form.representative_total_money = null;
                this.form.representative_total_rice = null;
                this.form.package_amount_each = null;
                this.form.package_members_count = null;
            }

            this.recalculatePackageTotal();
            this.updateMoneyPreview();
            this.updateRicePreview();
        },
        'form.use_same_package_amount'(value) {
            if (!this.form.is_package) {
                return;
            }

            if (value) {
                this.form.representative_total_money = null;
            }

            if (!value) {
                this.form.package_amount_each = null;
                this.form.is_input_family_members = true;

                if (this.form.package_payers.length === 0) {
                    this.form.package_payers.push(createEmptyPayer({ is_money: true, is_rice: false }));
                }
            }

            if (value && !this.form.package_members_count) {
                this.form.package_members_count = this.form.package_payers.length || 1;
            }

            this.applySamePackageAmount();
            this.recalculatePackageTotal();
        },
        'form.is_input_family_members'(value) {
            if (!this.form.is_package || !this.form.use_same_package_amount) {
                return;
            }

            if (value && this.form.package_payers.length === 0) {
                this.form.package_payers.push(
                    createEmptyPayer({ total_money: this.form.package_amount_each, is_money: true, is_rice: false })
                );
            }

            if (!value) {
                this.form.package_payers = [];
            }

            this.applySamePackageAmount();
            this.recalculatePackageTotal();
        },
        'form.package_members_count'() {
            this.recalculatePackageTotal();
            this.updateRicePreview();
        },
        'form.multiplier_count'() {
            this.recalculatePackageTotal();
            this.updateRicePreview();
            this.updateMoneyPreview();
        },
        'form.amount_money'() {
            this.updateMoneyPreview();
        },
        'form.amount_rice'() {
            this.updateRicePreview();
        },
        'form.representative_total_rice'() {
            this.updateRicePreview();
        },
        'form.package_amount_each'() {
            if (!this.form.is_package || !this.form.use_same_package_amount) {
                return;
            }

            this.applySamePackageAmount();
            this.recalculatePackageTotal();
            this.updateRicePreview();
        },
        'form.package_payers': {
            deep: true,
            handler() {
                this.recalculatePackageTotal();
                this.updateRicePreview();
            },
        },
        'form.payment_method'(value) {
            if (value === 'cash') {
                this.form.charity_payment_id = '';
                return;
            }
            if (this.form.charity_payment_id) {
                const exists = this.filteredPayments.some(
                    (item) => String(item.id) === String(this.form.charity_payment_id)
                );
                if (!exists) {
                    this.form.charity_payment_id = '';
                }
            }
        },
        selectedCharityType(value) {
            if (!value) {
                return;
            }

            if (value.is_rice && !this.form.total_rice && value.total_rice) {
                this.form.total_rice = toNumber(value.total_rice);
            }
            if (value.is_rice && !this.form.amount_rice && value.total_rice && !this.form.is_package) {
                this.form.amount_rice = toNumber(value.total_rice);
            }
            if (value.is_rice && this.form.is_package && !this.form.representative_total_rice && value.total_rice) {
                this.form.representative_total_rice = toNumber(value.total_rice);
            }

            if (!value.is_rice) {
                this.form.detail.is_rice = false;
                this.form.detail.is_money = true;
                this.form.total_rice = null;
                this.form.package_payers = this.form.package_payers.map((payer) => ({
                    ...payer,
                    is_rice: false,
                    total_rice: null,
                }));
            }

            if (value.is_rice && !this.form.detail.is_money && !this.form.detail.is_rice) {
                this.form.detail.is_money = true;
            }

        if (value.use_multipliers) {
            if (!this.form.multiplier_count || Number(this.form.multiplier_count) < 1) {
                this.form.multiplier_count = 1;
            }
            this.form.package_payers = this.form.package_payers.map((payer) => ({
                ...payer,
                multiplier_count: (payer.is_money || payer.is_rice)
                    ? (payer.multiplier_count ? toNumber(payer.multiplier_count) : null)
                    : null,
            }));
        } else {
                this.form.multiplier_count = null;
                this.form.package_payers = this.form.package_payers.map((payer) => ({
                    ...payer,
                    multiplier_count: null,
                }));
            }
        },
        'form.detail.is_money'(value) {
            if (!value) {
                this.form.representative_total_money = null;
                this.form.amount_money = null;
            }
            this.recalculatePackageTotal();
            this.updateMoneyPreview();
        },
        'form.detail.is_rice'(value) {
            if (!value) {
                this.form.total_rice = null;
                this.form.amount_rice = null;
                this.form.representative_total_rice = null;
            return;
            }

            if (this.selectedCharityType && this.selectedCharityType.total_rice) {
                if (!this.form.is_package && !this.form.amount_rice) {
                    this.form.amount_rice = toNumber(this.selectedCharityType.total_rice);
                }
                if (this.form.is_package && !this.form.representative_total_rice) {
                    this.form.representative_total_rice = toNumber(this.selectedCharityType.total_rice);
                }
            }
            this.updateRicePreview();
        },
    },
    methods: {
        formatCurrency(value) {
            const numeric = Number(value || 0);

            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number.isNaN(numeric) ? 0 : numeric);
        },
        formatDecimal(value) {
            const numeric = Number(value || 0);

            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number.isNaN(numeric) ? 0 : numeric);
        },
        firstError(path) {
            const field = this.errors[path];

            if (!field || !field.length) {
                return null;
            }

            return field[0];
        },
        payerMoneyPreview(payer) {
            if (!payer || !payer.is_money) {
                return 0;
            }

            const baseAmount = toNumber(payer.total_money) || 0;
            if (!baseAmount) {
                return 0;
            }

            const multiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                ? this.resolveMultiplier(payer.multiplier_count)
                : 1;

            return baseAmount * multiplier;
        },
        payerRicePreview(payer) {
            if (!payer || !payer.is_rice) {
                return 0;
            }

            const baseAmount = toNumber(payer.total_rice) || 0;
            if (!baseAmount) {
                return 0;
            }

            const multiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                ? this.resolveMultiplier(payer.multiplier_count)
                : 1;

            return baseAmount * multiplier;
        },
        addPackagePayer() {
            const payer = createEmptyPayer({ is_money: true, is_rice: false });

            if (this.form.use_same_package_amount) {
                payer.total_money = toNumber(this.form.package_amount_each);
            }

            this.form.package_payers.push(payer);
        },
        removePackagePayer(index) {
            this.form.package_payers.splice(index, 1);
            this.recalculatePackageTotal();
        },
        recalculatePackageTotal() {
            if (!this.form.is_package) {
                return;
            }

            if (this.form.use_same_package_amount) {
                if (this.form.is_input_family_members && this.form.package_payers.length > 0) {
                    this.form.package_members_count = this.form.package_payers.length + 1;
                }

                const amountEach = toNumber(this.form.package_amount_each) || 0;
                const membersCount = Number(this.form.package_members_count || 0);
                const totalMembers = Number.isNaN(membersCount) ? 0 : membersCount;
                const allMoney = this.form.detail.is_money
                    && this.form.package_payers.every((payer) => payer.is_money);
                if (allMoney) {
                    const representativeMultiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                        ? this.resolveMultiplier(this.form.multiplier_count)
                        : 1;
                    let totalMultiplier = 0;
                    if (this.form.is_input_family_members && this.form.package_payers.length > 0) {
                        const payersMultiplierSum = this.form.package_payers.reduce((carry, payer) => {
                            if (!payer.is_money) {
                                return carry;
                            }
                            return carry + (this.selectedCharityType && this.selectedCharityType.use_multipliers
                                ? this.resolveMultiplier(payer.multiplier_count)
                                : 1);
                        }, 0);
                        totalMultiplier = representativeMultiplier + payersMultiplierSum;
                    } else {
                        totalMultiplier = representativeMultiplier * totalMembers;
                    }
                    this.form.total_money = amountEach * totalMultiplier;
                } else {
                    const representativeAmount = this.form.detail.is_money ? amountEach : 0;
                    const representativeMultiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                        ? this.resolveMultiplier(this.form.multiplier_count)
                        : 1;
                    const representativeTotal = representativeAmount * representativeMultiplier;
                    const payersAmount = this.form.package_payers.reduce((carry, payer) => {
                        const baseAmount = (payer.is_money ? toNumber(payer.total_money) : 0) || 0;
                        const payerMultiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                            ? this.resolveMultiplier(payer.multiplier_count)
                            : 1;
                        return carry + (baseAmount * payerMultiplier);
                    }, 0);
                    this.form.total_money = representativeTotal + payersAmount;
                }

                return;
            }

            const representativeAmount = this.form.detail.is_money ? (toNumber(this.form.representative_total_money) || 0) : 0;
            const representativeMultiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                ? this.resolveMultiplier(this.form.multiplier_count)
                : 1;
            const representativeTotal = representativeAmount * representativeMultiplier;
            const familyMembersAmount = this.form.package_payers.reduce((carry, payer) => {
                const baseAmount = (payer.is_money ? toNumber(payer.total_money) : 0) || 0;
                const payerMultiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                    ? this.resolveMultiplier(payer.multiplier_count)
                    : 1;
                return carry + (baseAmount * payerMultiplier);
            }, 0);
            this.form.total_money = representativeTotal + familyMembersAmount;
            this.form.package_members_count = this.form.package_payers.length + 1;
        },
        updateMoneyPreview() {
            if (!this.form.detail.is_money) {
                this.form.total_money = null;
                return;
            }

            if (this.form.is_package) {
                return;
            }

            this.form.total_money = this.totalMoneyPreview > 0 ? this.totalMoneyPreview : null;
        },
        updateRicePreview() {
            if (!this.form.detail.is_rice) {
                this.form.total_rice = null;
                return;
            }

            const computed = this.calculateTotalRice();
            this.form.total_rice = computed > 0 ? computed : null;
        },
        applySamePackageAmount() {
            if (!this.form.is_package || !this.form.use_same_package_amount || !this.form.is_input_family_members) {
                return;
            }

            const sharedAmount = toNumber(this.form.package_amount_each);

            this.form.package_payers = this.form.package_payers.map((payer) => ({
                ...payer,
                total_money: payer.is_money ? sharedAmount : null,
            }));
        },
        resetForm() {
            this.form = {
                ...this.initialForm,
                package_payers: this.initialForm.package_payers.length
                    ? this.initialForm.package_payers.map((payer) => ({ ...payer }))
                    : [],
            };

            if (this.form.is_package && this.form.package_payers.length === 0) {
                this.form.package_payers = [createEmptyPayer({ is_money: true, is_rice: false })];
            }

            this.errors = {};
            this.submit_form_key++;
        },
        buildPayload() {
            let computedTotalRice = this.calculateTotalRice();
            if (computedTotalRice === 0 || !this.form.detail.is_rice) {
                computedTotalRice = null;
            }
            let computedTotalMoney = this.form.is_package ? toNumber(this.form.total_money) : this.totalMoneyPreview;
            if (!this.form.detail.is_money || !computedTotalMoney || computedTotalMoney <= 0) {
                computedTotalMoney = null;
            }

            const data = {
                ...this.form,
                total_money: computedTotalMoney ? Number(computedTotalMoney) : null,
                total_rice: toNumber(computedTotalRice),
                amount_money: (!this.form.is_package && this.form.detail.is_money) ? toNumber(this.form.amount_money) : null,
                amount_rice: (!this.form.is_package && this.form.detail.is_rice) ? toNumber(this.form.amount_rice) : null,
                is_package: Boolean(this.form.is_package),
                use_same_package_amount: this.form.is_package ? Boolean(this.form.use_same_package_amount) : null,
                is_input_family_members: this.form.is_package
                    ? (this.form.use_same_package_amount ? Boolean(this.form.is_input_family_members) : true)
                    : null,
                representative_total_money: (this.form.is_package && !this.form.use_same_package_amount && this.form.detail.is_money)
                    ? toNumber(this.form.representative_total_money)
                    : null,
                representative_total_rice: (this.form.is_package && this.form.detail.is_rice)
                    ? toNumber(this.form.representative_total_rice)
                    : null,
                multiplier_count: toNumber(this.form.multiplier_count),
                package_amount_each: this.form.is_package ? toNumber(this.form.package_amount_each) : null,
                package_members_count: this.form.is_package ? Number(this.form.package_members_count || 0) : null,
                package_payers: this.form.is_package
                    ? (
                        (!this.form.use_same_package_amount || this.form.is_input_family_members)
                            ? this.form.package_payers.map((payer) => ({
                                payer_name: payer.payer_name || '',
                                payer_phone: payer.payer_phone || '',
                                payer_email: payer.payer_email || '',
                                is_money: Boolean(payer.is_money),
                                is_rice: Boolean(payer.is_rice),
                                multiplier_count: (this.selectedCharityType && this.selectedCharityType.use_multipliers && (payer.is_money || payer.is_rice))
                                    ? (payer.multiplier_count ? Number(payer.multiplier_count) : null)
                                    : null,
                                total_money: payer.is_money ? toNumber(payer.total_money) : null,
                                total_rice: payer.is_rice ? toNumber(payer.total_rice) : null,
                                notes: payer.notes || '',
                            }))
                            : []
                    )
                    : null,
                detail: {
                    ...(this.form.detail || {}),
                    is_rice: Boolean(this.form.detail?.is_rice),
                    is_money: Boolean(this.form.detail?.is_money),
                },
            };

            // keep empty rows so validation can catch missing payer_name when input family members is enabled

            if (this.mode === 'edit') {
                data._method = 'PUT';
            }

            if (this.isModalMode) {
                data._mode = 'modal';
            }

            if (data.payment_method === 'cash') {
                data.charity_payment_id = null;
            }

            return data;
        },
        resolveMultiplier(value) {
            const numeric = Number(value || 1);

            return Number.isNaN(numeric) || numeric < 1 ? 1 : numeric;
        },
        calculateTotalRice() {
            if (!this.form.detail.is_rice) {
                return 0;
            }

            const multiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                ? this.resolveMultiplier(this.form.multiplier_count)
                : 1;

            if (!this.form.is_package) {
                return (toNumber(this.form.amount_rice) || 0) * multiplier;
            }

            if (!this.form.is_input_family_members) {
                const membersCount = Number(this.form.package_members_count || 0);
                const perPersonRice = toNumber(this.form.representative_total_rice) || 0;
                return perPersonRice * (Number.isNaN(membersCount) ? 0 : membersCount) * multiplier;
            }

            const packageRiceTotal = this.form.package_payers.reduce((carry, payer) => {
                const baseRice = (payer.is_rice ? toNumber(payer.total_rice) : 0) || 0;
                const payerMultiplier = this.selectedCharityType && this.selectedCharityType.use_multipliers
                    ? this.resolveMultiplier(payer.multiplier_count)
                    : 1;
                return carry + (baseRice * payerMultiplier);
            }, 0);
            const representativeRice = this.form.detail.is_rice ? (toNumber(this.form.representative_total_rice) || 0) : 0;

            return (representativeRice * multiplier) + packageRiceTotal;
        },
        closeModal() {
            const element = document.getElementById('charity-transaction-modal');

            if (!element || !window.bootstrap) {
                return;
            }

            const instance = window.bootstrap.Modal.getOrCreateInstance(element);
            instance.hide();
        },
        notifySuccess(message) {
            if (window.Swal) {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: message || 'Saved successfully',
                    timer: 2200,
                    timerProgressBar: true,
                    showConfirmButton: false,
                });
            }
        },
        async submitForm(loading = true) {
            this.loading = loading;
            this.errors = {};

            const url = this.mode === 'edit' ? this.routes.update : this.routes.store;
            const data = this.buildPayload();

            try {
                const response = await axios.post(url, data);

                if (this.isModalMode && response.data.success) {
                    this.notifySuccess(response.data.message || 'Saved');
                    this.closeModal();
                    this.resetForm();

                    if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                        window.Livewire.dispatch('charityTransactionSaved');
                    }

                    this.loading = false;

                    return;
                }

                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {};
                }

                this.submit_form_key++;
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        this.updateMoneyPreview();
        this.updateRicePreview();
    },
}).mount('#charity-transaction-form');
