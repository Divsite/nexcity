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
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#charity-tab-scan" type="button" role="tab">
                                {{ __('messages.scan_barcode') }}
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
                                                    <span class="badge bg-soft-secondary text-secondary">
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
                                                    <span class="badge bg-soft-secondary text-secondary">
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
                                                    <span class="badge bg-soft-secondary text-secondary">
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
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.distribution_delivered') }}</div>
                                        <div class="fs-5 fw-semibold mb-1">{{ $distributionSummary['distributed_money_label'] ?? '0' }}</div>
                                        <div class="text-muted fs-13">
                                            {{ __('messages.total_rice') }}: {{ $distributionSummary['distributed_rice_label'] ?? '0,00' }}
                                            · {{ __('messages.recipients') }}: {{ (int) ($distributionSummary['distributed_recipients'] ?? 0) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.distribution_remaining') }}</div>
                                        <div class="fs-5 fw-semibold mb-1">
                                            {{ $distributionSummary['remaining_money_label'] ?? '0' }}
                                        </div>
                                        <div class="text-muted fs-13">
                                            {{ __('messages.total_rice') }}: {{ $distributionSummary['remaining_rice_label'] ?? '0,00' }}
                                            · {{ __('messages.recipients') }}: {{ (int) ($distributionSummary['pending_recipients'] ?? 0) }}
                                        </div>
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
                        <div class="tab-pane fade" id="charity-tab-scan" role="tabpanel">
                            <div class="text-muted py-4">
                                {{ __('messages.placeholder') }}
                            </div>
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
    </script>
    @vite('resources/js/views/charities/form.js')
    @vite('resources/js/views/charities/summary.js')
    @vite('resources/js/views/distributions/form.js')
@endpush
