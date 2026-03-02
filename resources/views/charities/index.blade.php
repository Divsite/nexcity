@extends('layouts.app')

@section('title', __('messages.charity_transactions'))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">{{ __('messages.charity_transactions') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#charity-tab-transactions" type="button" role="tab">
                                {{ __('messages.charity_transactions') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#charity-tab-distributions" type="button" role="tab">
                                {{ __('messages.distributions') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#charity-tab-finance" type="button" role="tab">
                                {{ __('messages.finance') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="charity-tab-transactions" role="tabpanel">
                            <div id="charity-summary" v-cloak>
                                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                                    <div class="fw-semibold">{{ __('messages.today_summary') }} &amp; {{ __('messages.this_year_summary') }}</div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <select class="form-select form-select-sm" v-model="filters.type_id">
                                            <option value="">{{ __('messages.all') }}</option>
                                            <option v-for="item in options.charity_types" :key="item.id" :value="item.id">
                                                @{{ item.name }}
                                            </option>
                                        </select>
                                        <select class="form-select form-select-sm" v-model="filters.payment_method">
                                            <option value="">{{ __('messages.all') }}</option>
                                            <option v-for="method in options.payment_methods" :key="method.value" :value="method.value">
                                                @{{ method.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="text-muted text-uppercase fs-12">{{ __('messages.today_summary') }}</div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        @{{ selectedTypeLabel }}
                                                    </span>
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        @{{ selectedPaymentLabel }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="fs-5 fw-semibold mb-1">@{{ summary.today.total_money_label || '0' }}</div>
                                            <div class="text-muted fs-13">
                                                {{ __('messages.total_rice') }}: @{{ summary.today.total_rice_label || '0,00' }}
                                                · {{ __('messages.transactions') }}: @{{ summary.today.total_transactions || 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="text-muted text-uppercase fs-12">{{ __('messages.this_year_summary') }}</div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        @{{ selectedTypeLabel }}
                                                    </span>
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        @{{ selectedPaymentLabel }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="fs-5 fw-semibold mb-1">@{{ summary.year.total_money_label || '0' }}</div>
                                            <div class="text-muted fs-13">
                                                {{ __('messages.total_rice') }}: @{{ summary.year.total_rice_label || '0,00' }}
                                                · {{ __('messages.transactions') }}: @{{ summary.year.total_transactions || 0 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                                    <div class="fw-semibold">{{ __('messages.yearly_summary') }}</div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <select class="form-select form-select-sm" v-model="filters.year_type_id">
                                            <option value="">{{ __('messages.all') }}</option>
                                            <option v-for="item in options.charity_types" :key="`year-${item.id}`" :value="item.id">
                                                @{{ item.name }}
                                            </option>
                                        </select>
                                        <select class="form-select form-select-sm" v-model="filters.year_payment_method">
                                            <option value="">{{ __('messages.all') }}</option>
                                            <option v-for="method in options.payment_methods" :key="`year-${method.value}`" :value="method.value">
                                                @{{ method.label }}
                                            </option>
                                        </select>
                                        <year-picker
                                            v-model="filters.year"
                                            label="{{ __('messages.year') }}"
                                            :start="5"
                                            :end="5"
                                            input-class="form-control form-control-sm"
                                            style="max-width: 110px;"
                                        ></year-picker>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="text-muted text-uppercase fs-12">
                                                    {{ __('messages.year') }} @{{ summary.yearly.year || '' }}
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-info-subtle text-info">
                                                        @{{ selectedYearTypeLabel }}
                                                    </span>
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        @{{ selectedYearPaymentLabel }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="fs-5 fw-semibold mb-1">@{{ summary.yearly.total_money_label || '0' }}</div>
                                            <div class="text-muted fs-13">
                                                {{ __('messages.total_rice') }}: @{{ summary.yearly.total_rice_label || '0,00' }}
                                                · {{ __('messages.transactions') }}: @{{ summary.yearly.total_transactions || 0 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mb-3">
                                <i class="ri-information-line align-bottom me-1"></i>
                                {{ __('messages.charity_transactions_paid_only_notice') }}
                            </div>

                            <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
                                <a href="{{ $dailyRecapPrintUrl }}?date={{ now()->toDateString() }}"
                                   target="_blank"
                                   class="btn btn-soft-primary">
                                    <i class="ri-printer-line align-bottom me-1"></i> {{ __('messages.daily_recap_print') }}
                                </a>
                                <button type="button"
                                        class="btn btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#charity-transaction-modal">
                                    <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.create') }}
                                </button>
                            </div>

                            <livewire:charities.charity-transaction-table theme="bootstrap-5"/>
                        </div>
                        <div class="tab-pane fade" id="charity-tab-distributions" role="tabpanel">
                            <div id="distribution-summary" v-cloak>
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div class="fw-semibold">{{ __('messages.yearly_summary') }}</div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <select class="form-select form-select-sm" v-model="filters.distribution_class_id">
                                            <option value="">{{ __('messages.all') }}</option>
                                            <option v-for="item in options.distribution_classes" :key="item.id" :value="item.id">
                                                @{{ item.label || item.source_name }}
                                            </option>
                                        </select>
                                        <year-picker v-model="filters.year" />
                                        <select class="form-select form-select-sm" v-model="filters.neighborhood_association_id">
                                            <option value="">{{ __('messages.all') }}</option>
                                            <option v-for="item in options.neighborhoods" :key="item.id" :value="item.id">
                                                @{{ item.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                                <div class="text-muted text-uppercase fs-12">{{ __('messages.distribution_delivered') }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge bg-info-subtle text-info">@{{ selectedClassLabel }}</span>
                                                    <span class="badge bg-warning-subtle text-warning">@{{ selectedYearLabel }}</span>
                                                    <span class="badge bg-soft-primary text-primary" v-if="filters.neighborhood_association_id">
                                                        @{{ selectedNeighborhoodLabel }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="fs-5 fw-semibold mb-1">@{{ summary.distributed_money_label || '0' }}</div>
                                            <div class="text-muted fs-13">
                                                {{ __('messages.total_rice') }}: @{{ summary.distributed_rice_label || '0,00' }}
                                                · {{ __('messages.recipients') }}: @{{ summary.distributed_recipients || 0 }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                                <div class="text-muted text-uppercase fs-12">{{ __('messages.distribution_remaining') }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge bg-info-subtle text-info">@{{ selectedClassLabel }}</span>
                                                    <span class="badge bg-warning-subtle text-warning">@{{ selectedYearLabel }}</span>
                                                    <span class="badge bg-soft-primary text-primary" v-if="filters.neighborhood_association_id">
                                                        @{{ selectedNeighborhoodLabel }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="fs-5 fw-semibold mb-1">
                                                @{{ summary.remaining_money_label || '0' }}
                                            </div>
                                            <div class="text-muted fs-13">
                                                {{ __('messages.total_rice') }}: @{{ summary.remaining_rice_label || '0,00' }}
                                                · {{ __('messages.recipients') }}: @{{ summary.pending_recipients || 0 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info" v-if="ruleItems.length">
                                    <div class="fw-semibold mb-1">{{ __('messages.distribution_rules') }}</div>
                                    <div class="text-muted small mb-2">{{ __('messages.distribution_rules_notice') }}</div>
                                    <div class="d-flex flex-column gap-1">
                                        <div v-for="item in ruleItems" :key="item.id" class="text-muted small">
                                            <span class="fw-semibold text-dark">@{{ item.source_name }}</span>
                                            · {{ __('messages.money_per_person') }}: @{{ formatMoney(item.money_per_person) }}
                                            · {{ __('messages.rice_per_person') }}: @{{ formatRice(item.rice_per_person) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="border rounded p-3 mb-3" v-if="fundSummary">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                        <h6 class="mb-0">{{ __('messages.fund_sources') }}</h6>
                                        <div class="text-muted small">{{ __('messages.based_on_current_filters') }}</div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <div class="border rounded p-2 h-100">
                                                <div class="text-muted small">{{ __('messages.required_funds') }}</div>
                                                <div class="fw-semibold">@{{ fundSummary.required_money_label || '0' }}</div>
                                                <div class="text-muted small">{{ __('messages.total_rice') }}: @{{ fundSummary.required_rice_label || '0,00' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-2 h-100">
                                                <div class="text-muted small">{{ __('messages.used_funds') }}</div>
                                                <div class="fw-semibold">@{{ fundSummary.used_money_label || '0' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-2 h-100">
                                                <div class="text-muted small">{{ __('messages.remaining_funds') }}</div>
                                                <div class="fw-semibold">@{{ fundSummary.remaining_money_label || '0' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-lg-8">
                                            <div class="alert alert-warning mb-2">
                                                {{ __('messages.fund_sources_sync_note') }}
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="fw-semibold">{{ __('messages.select_sources') }}</div>
                                                <div class="text-muted small">{{ __('messages.total_money') }} / {{ __('messages.used_funds') }} / {{ __('messages.available_funds') }}</div>
                                            </div>
                                            <div class="alert alert-info mb-2" v-if="hasCoveringSource">
                                                {{ __('messages.fund_sources_single_source_note') }}
                                            </div>
                                            <div class="list-group">
                                                <label class="list-group-item d-flex align-items-start gap-2"
                                                       v-for="item in fundOptions.charity_types"
                                                       :key="item.id"
                                                       :class="isFundSourceDisabled(item) ? 'opacity-50' : ''">
                                                    <input class="form-check-input mt-1"
                                                           type="checkbox"
                                                           :value="item.id"
                                                           v-model="fundForm.charity_type_ids"
                                                           :disabled="isFundSourceDisabled(item)">
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">@{{ item.name }}</div>
                                                        <div class="text-muted small">
                                                            @{{ item.total_money_label }} · @{{ item.used_money_label }} · @{{ item.remaining_money_label }}
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="text-muted small">{{ __('messages.allocated') }}: @{{ formatMoney(allocationMap[item.id] || 0) }}</div>
                                                        <div class="text-muted small">{{ __('messages.available_funds') }}: @{{ item.remaining_money_label }}</div>
                                                    </div>
                                                </label>
                                            </div>
                                            <span class="invalid-feedback d-block" v-if="fundErrors.charity_type_ids">
                                                <strong>@{{ fundErrors.charity_type_ids[0] }}</strong>
                                            </span>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="border rounded p-3 mb-3">
                                                <div class="text-muted small">{{ __('messages.selected_total') }}</div>
                                                <div class="fw-semibold">@{{ formatMoney(selectedAvailableTotal) }}</div>
                                                <div class="text-muted small mt-2">{{ __('messages.remaining_needed') }}: @{{ formatMoney(remainingNeeded) }}</div>
                                                <div class="text-muted small">{{ __('messages.surplus') }}: @{{ formatMoney(surplusAmount) }}</div>
                                            </div>
                                            <div class="border rounded p-3">
                                                <div class="fw-semibold mb-2">{{ __('messages.other_source') }}</div>
                                                <label class="form-label">{{ __('messages.source_name') }}</label>
                                                <input type="text" class="form-control" v-model="fundForm.other_source_name">
                                                <span class="invalid-feedback d-block" v-if="fundErrors.other_source_name">
                                                    <strong>@{{ fundErrors.other_source_name[0] }}</strong>
                                                </span>
                                                <label class="form-label mt-2">{{ __('messages.other_source_amount') }}</label>
                                                <vue-currency-input
                                                    v-model="fundForm.other_source_amount"
                                                    :options="currencyOptions"
                                                    :class="`form-control ${fundErrors.other_source_amount ? 'is-invalid' : ''}`"
                                                ></vue-currency-input>
                                                <span class="invalid-feedback d-block" v-if="fundErrors.other_source_amount">
                                                    <strong>@{{ fundErrors.other_source_amount[0] }}</strong>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mb-3">
                                        <button type="button" class="btn btn-primary" @click="submitFundSource" :disabled="fundLoading">
                                            {{ __('messages.save_sources') }}
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>{{ __('messages.source') }}</th>
                                                    <th class="text-end">{{ __('messages.allocated_amount') }}</th>
                                                    <th class="text-end">{{ __('messages.remaining_amount') }}</th>
                                                    <th class="text-end">{{ __('messages.actions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-if="fundSources.length === 0">
                                                    <td colspan="4" class="text-muted">{{ __('messages.data_not_found') }}</td>
                                                </tr>
                                                <tr v-for="item in fundSources" :key="item.id">
                                                    <td>
                                                        <span v-if="item.source_type === 'charity'">@{{ item.source_name || fundTypeName(item.charity_type_id) }}</span>
                                                        <span v-else>@{{ item.source_name || '-' }}</span>
                                                    </td>
                                                    <td class="text-end">@{{ item.amount_used_label }}</td>
                                                    <td class="text-end">@{{ item.remaining_amount_label || fundSourceRemainingLabel(item) }}</td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-soft-danger" @click="removeFundSource(item.id)">
                                                            {{ __('messages.delete') }}
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mb-3">
                                <button type="button"
                                        class="btn btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#distribution-modal">
                                    <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.create') }}
                                </button>
                            </div>

                            <livewire:distributions.distribution-table theme="bootstrap-5"/>
                        </div>
                        <div class="tab-pane fade" id="charity-tab-finance" role="tabpanel">
                            <div class="text-muted py-4">
                                {{ __('messages.placeholder') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="charity-transaction-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.create') }} {{ __('messages.charity_transactions') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div v-cloak id="charity-transaction-form">
                        @include('charities._form')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="distribution-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.create') }} {{ __('messages.distributions') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div v-cloak id="distribution-form">
                        @include('distributions._form')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        window.charityTransactionForm = @json($formPayload);
        window.distributionFormPayload = @json($distributionFormPayload);
        window.charitySummaryPayload = @json($summaryPayload);
        window.distributionSummaryPayload = @json($distributionSummaryPayload);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const storageKey = 'charity-active-tab';

            const tabParamToName = (value) => {
                if (value === 'distributions') {
                    return 'distributions';
                }
                if (value === 'charities' || value === 'transactions') {
                    return 'transactions';
                }

                return null;
            };

            const activateTab = (tab) => {
                if (!tab) {
                    return;
                }
                const targetId = tab === 'distributions' ? '#charity-tab-distributions' : '#charity-tab-transactions';
                const trigger = document.querySelector(`[data-bs-target="${targetId}"]`);
                if (trigger && window.bootstrap) {
                    window.bootstrap.Tab.getOrCreateInstance(trigger).show();
                }
            };

            const resolveTab = () => {
                const params = new URLSearchParams(window.location.search);
                const storedTab = window.sessionStorage ? window.sessionStorage.getItem(storageKey) : null;
                return tabParamToName(params.get('tab'))
                    || (params.has('distributions') ? 'distributions' : null)
                    || storedTab;
            };

            activateTab(resolveTab());

            document.querySelectorAll('[data-bs-toggle="tab"]').forEach((trigger) => {
                trigger.addEventListener('shown.bs.tab', (event) => {
                    const target = event.target.getAttribute('data-bs-target');
                    const url = new URL(window.location.href);
                    if (!window.sessionStorage) {
                        return;
                    }
                    if (target === '#charity-tab-distributions') {
                        window.sessionStorage.setItem(storageKey, 'distributions');
                        url.searchParams.set('tab', 'distributions');
                        url.hash = '#charity-tab-distributions';
                    } else if (target === '#charity-tab-transactions') {
                        window.sessionStorage.setItem(storageKey, 'transactions');
                        url.searchParams.set('tab', 'charities');
                        url.hash = '#charity-tab-transactions';
                    }

                    window.history.pushState({}, '', url.toString());
                });
            });

            window.addEventListener('popstate', () => {
                activateTab(resolveTab());
            });

            window.addEventListener('pageshow', () => {
                activateTab(resolveTab());
            });
        });
    </script>
    @vite('resources/js/views/charities/form.js')
    @vite('resources/js/views/charities/summary.js')
    @vite('resources/js/views/distributions/form.js')
    @vite('resources/js/views/distributions/summary.js')
@endpush
