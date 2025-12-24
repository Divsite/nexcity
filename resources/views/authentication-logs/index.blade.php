@extends('layouts.app')

@section('title', __('messages.all_user_sessions'))

@section('breadcrumbs', Breadcrumbs::render('settings.all-user-sessions'))

@section('content')
    <div id="app" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">{{ __('messages.user_sessions_list') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <livewire:authentication-logs.all-user-sessions-table theme="bootstrap-5"/>
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
