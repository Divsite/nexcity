@extends('layouts.app')

@section('title', __('messages.charity_transactions'))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">{{ __('messages.charity_transactions') }}</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
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
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.today_summary') }}</div>
                                <div class="fs-5 fw-semibold mb-1">{{ $summaryCards['today']['total_money_label'] ?? '0' }}</div>
                                <div class="text-muted fs-13">
                                    {{ __('messages.total_rice') }}: {{ $summaryCards['today']['total_rice_label'] ?? '0,00' }}
                                    · {{ __('messages.transactions') }}: {{ (int) ($summaryCards['today']['total_transactions'] ?? 0) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.this_year_summary') }}</div>
                                <div class="fs-5 fw-semibold mb-1">{{ $summaryCards['year']['total_money_label'] ?? '0' }}</div>
                                <div class="text-muted fs-13">
                                    {{ __('messages.total_rice') }}: {{ $summaryCards['year']['total_rice_label'] ?? '0,00' }}
                                    · {{ __('messages.transactions') }}: {{ (int) ($summaryCards['year']['total_transactions'] ?? 0) }}
                                </div>
                            </div>
                        </div>
                    </div>

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
                            <livewire:charities.charity-transaction-table theme="bootstrap-5"/>
                        </div>
                        <div class="tab-pane fade" id="charity-tab-distributions" role="tabpanel">
                            <div class="text-muted py-4">
                                {{ __('messages.placeholder') }}
                            </div>
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
@endsection

@push('vendor-scripts')
<script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        window.charityTransactionForm = @json($formPayload);
    </script>
    @vite('resources/js/views/charities/form.js')
@endpush
