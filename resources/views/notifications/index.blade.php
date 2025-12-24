@extends('layouts.app')

@section('title', __('messages.notifications'))

@section('breadcrumbs', Breadcrumbs::render('notifications.index'))

@section('content')
    <div id="app" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">{{ __('messages.notification_list') }}</h5>
                        </div>
                        @if(auth()->user()->notifications->count() != 0)
                            @can('delete-notifications')
                                <div class="col-sm-auto">
                                    <div class="d-flex gap-1 flex-wrap" id="delete-confirmation">
                                        <button class="btn btn-danger"
                                                data-bs-original-title="{{ __('messages.delete') }}" role="button"
                                                :disabled="loading" :key="delete_button_key"
                                                @click.once="triggerDelete('{{ route('notifications.destroy-all') }}')">
                                            <i class="ri-delete-bin-line align-bottom me-1"></i> {{ __('messages.delete_all_notifications') }}
                                        </button>
                                    </div>
                                </div>
                            @endcan
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <livewire:notifications.notification-table theme="bootstrap-5"/>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@if(auth()->user()->notifications->count() != 0)
    @can('delete-notifications')
        @push('scripts')
            @vite('resources/js/views/partials/delete-confirmation.js')
        @endpush
    @endcan
@endif
