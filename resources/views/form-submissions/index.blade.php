@extends('layouts.app')

@section('title', $model->name)

@section('breadcrumbs', Breadcrumbs::render('forms.submissions.index', $model))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">{{ $model->name }}</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                @can('add-submissions')
                                    <a href="{{ route('forms.submissions.create', $model->id) }}" class="btn btn-success">
                                        <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.create') }}
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <livewire:form-submissions.form-submission-table formId="{{ $model->id }}" theme="bootstrap-5"/>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    @include('partials.tooltip-livewire')
@endpush
