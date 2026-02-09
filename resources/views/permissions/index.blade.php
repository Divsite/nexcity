@extends('layouts.app')

@section('title', __('messages.permissions'))

@section('breadcrumbs', Breadcrumbs::render('permissions.index'))

@section('content')
    <div id="app" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">

                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">{{ __('messages.permission_list') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <livewire:permissions.permission-table theme="bootstrap-5"/>
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
