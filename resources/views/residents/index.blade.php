@extends('layouts.app')

@section('title', __('messages.residents'))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">{{ __('messages.resident_list') }}</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                @can('browse-rt-residents')
                                    <a href="{{ route('residents.qr-cards') }}" target="_blank" class="btn btn-outline-primary">
                                        <i class="ri-qr-code-line align-bottom me-1"></i> {{ __('messages.print_all_qr_cards') }}
                                    </a>
                                @endcan
                                @canany(['add-residents', 'add-rt-residents'])
                                    <a href="{{ route('residents.create') }}" class="btn btn-success">
                                        <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.create') }}
                                    </a>
                                @endcanany
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <livewire:residents.resident-table theme="bootstrap-5"/>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
