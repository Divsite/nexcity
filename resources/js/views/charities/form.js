import { createApp, watch } from "vue";
import { useCurrencyInput } from "vue-currency-input";

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

const createEmptyPayer = (defaultTotalMoney = null) => ({
    payer_name: '',
    payer_phone: '',
    payer_email: '',
    total_money: toNumber(defaultTotalMoney),
    notes: '',
});

const CurrencyInputField = {
    name: 'CurrencyInputField',
    props: {
        modelValue: {
            required: false,
            default: null,
        },
        inputClass: {
            type: String,
            default: 'form-control',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const { inputRef, numberValue, setValue } = useCurrencyInput(currencyOptions, false);

        watch(
            () => props.modelValue,
            (value) => {
                setValue(toNumber(value));
            },
            { immediate: true }
        );

        watch(numberValue, (value) => {
            emit('update:modelValue', toNumber(value));
        });

        return {
            inputRef,
        };
    },
    template: '<input ref="inputRef" type="text" :class="inputClass" :disabled="disabled">',
};

createApp({
    components: {
        CurrencyInputField,
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
            package_amount_each: toNumber(payload.form.package_amount_each),
            package_members_count: toNumber(payload.form.package_members_count),
            package_payers: Array.isArray(payload.form.package_payers)
                ? payload.form.package_payers.map((payer) => ({
                    payer_name: payer.payer_name || '',
                    payer_phone: payer.payer_phone || '',
                    payer_email: payer.payer_email || '',
                    total_money: toNumber(payer.total_money),
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
            },
        };

        if (initialForm.is_package && !initialForm.use_same_package_amount) {
            initialForm.is_input_family_members = true;
        }

        if (initialForm.is_package && (!initialForm.use_same_package_amount || initialForm.is_input_family_members) && initialForm.package_payers.length === 0) {
            initialForm.package_payers = [
                createEmptyPayer(initialForm.use_same_package_amount ? initialForm.package_amount_each : null),
            ];
        }

        if (initialForm.is_package && !initialForm.package_members_count) {
            initialForm.package_members_count = initialForm.use_same_package_amount
                ? (initialForm.package_payers.length > 0 ? initialForm.package_payers.length + 1 : 1)
                : (initialForm.package_payers.length + 1);
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
        selectedPayment() {
            const selectedId = Number(this.form.charity_payment_id);

            if (!selectedId) {
                return null;
            }

            return this.options.payments.find((item) => Number(item.id) === selectedId) || null;
        },
        familyMembersRowsCount() {
            return Array.isArray(this.form.package_payers) ? this.form.package_payers.length : 0;
        },
    },
    watch: {
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

                if ((!this.form.use_same_package_amount || this.form.is_input_family_members) && this.form.package_payers.length === 0) {
                    this.form.package_payers.push(
                        createEmptyPayer(this.form.use_same_package_amount ? this.form.package_amount_each : null)
                    );
                }
            }

            if (!value) {
                this.form.package_payers = [];
                this.form.use_same_package_amount = false;
                this.form.is_input_family_members = false;
                this.form.representative_total_money = null;
                this.form.package_amount_each = null;
                this.form.package_members_count = null;
            }

            this.recalculatePackageTotal();
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
                    this.form.package_payers.push(createEmptyPayer());
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
                    createEmptyPayer(this.form.package_amount_each)
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
        },
        'form.representative_total_money'() {
            this.recalculatePackageTotal();
        },
        'form.package_amount_each'() {
            if (!this.form.is_package || !this.form.use_same_package_amount) {
                return;
            }

            this.applySamePackageAmount();
            this.recalculatePackageTotal();
        },
        'form.package_payers': {
            deep: true,
            handler() {
                this.recalculatePackageTotal();
            },
        },
        selectedCharityType(value) {
            if (!value) {
                return;
            }

            if (value.is_rice && !this.form.total_rice && value.total_rice) {
                this.form.total_rice = toNumber(value.total_rice);
            }

            if (!value.is_rice) {
                this.form.detail.is_rice = false;
                this.form.total_rice = null;
            }
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
        firstError(path) {
            const field = this.errors[path];

            if (!field || !field.length) {
                return null;
            }

            return field[0];
        },
        addPackagePayer() {
            const payer = createEmptyPayer();

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
                this.form.total_money = amountEach * (Number.isNaN(membersCount) ? 0 : membersCount);

                return;
            }

            const representativeAmount = toNumber(this.form.representative_total_money) || 0;
            const familyMembersAmount = this.form.package_payers.reduce((carry, payer) => {
                return carry + (toNumber(payer.total_money) || 0);
            }, 0);
            this.form.total_money = representativeAmount + familyMembersAmount;

            this.form.package_members_count = this.form.package_payers.length + 1;
        },
        applySamePackageAmount() {
            if (!this.form.is_package || !this.form.use_same_package_amount || !this.form.is_input_family_members) {
                return;
            }

            const sharedAmount = toNumber(this.form.package_amount_each);

            this.form.package_payers = this.form.package_payers.map((payer) => ({
                ...payer,
                total_money: sharedAmount,
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
                this.form.package_payers = [createEmptyPayer()];
            }

            this.errors = {};
            this.submit_form_key++;
        },
        buildPayload() {
            const data = {
                ...this.form,
                total_money: toNumber(this.form.total_money),
                total_rice: toNumber(this.form.total_rice),
                is_package: Boolean(this.form.is_package),
                use_same_package_amount: this.form.is_package ? Boolean(this.form.use_same_package_amount) : null,
                is_input_family_members: this.form.is_package
                    ? (this.form.use_same_package_amount ? Boolean(this.form.is_input_family_members) : true)
                    : null,
                representative_total_money: (this.form.is_package && !this.form.use_same_package_amount)
                    ? toNumber(this.form.representative_total_money)
                    : null,
                package_amount_each: this.form.is_package ? toNumber(this.form.package_amount_each) : null,
                package_members_count: this.form.is_package ? Number(this.form.package_members_count || 0) : null,
                package_payers: this.form.is_package
                    ? (
                        (!this.form.use_same_package_amount || this.form.is_input_family_members)
                            ? this.form.package_payers.map((payer) => ({
                                payer_name: payer.payer_name || '',
                                payer_phone: payer.payer_phone || '',
                                payer_email: payer.payer_email || '',
                                total_money: toNumber(payer.total_money),
                                notes: payer.notes || '',
                            }))
                            : []
                    )
                    : null,
                detail: {
                    ...(this.form.detail || {}),
                    is_rice: Boolean(this.form.detail?.is_rice),
                },
            };

            if (data.is_package && data.use_same_package_amount && data.is_input_family_members) {
                data.package_payers = data.package_payers.filter((payer) => {
                    return payer.payer_name || payer.payer_phone || payer.payer_email || payer.notes;
                });
            }

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
}).mount('#charity-transaction-form');
