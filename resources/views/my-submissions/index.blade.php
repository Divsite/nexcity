@extends('layouts.app')

@section('title', __('messages.my_submissions'))

@section('breadcrumbs', Breadcrumbs::render('my-submissions.index'))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">{{ __('messages.my_submissions') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <livewire:my-submissions.my-submission-table theme="bootstrap-5"/>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
