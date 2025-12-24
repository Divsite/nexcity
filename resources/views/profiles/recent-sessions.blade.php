@extends('layouts.app')

@section('title', __('messages.recent_sessions'))

@section('breadcrumbs', Breadcrumbs::render('profile.recent-sessions'))

@section('content')
    <div class="row">
        <div class="col-xxl-2 col-xl-2 col-md-12 mb-4">
            @include('profiles.side-menu')
        </div>
        <div class="col-xxl-10 col-xl-10 col-md-12">
            <div class="card">
                <div class="card-body">
                    <livewire:profiles.authentication-log-table theme="bootstrap-5"/>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
