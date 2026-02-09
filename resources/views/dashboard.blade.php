@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('breadcrumbs', Breadcrumbs::render('dashboard'))

@section('content')
    @if(auth()->user()->can('browse-users') && auth()->user()->can('browse-roles') && auth()->user()->can('browse-permissions'))
        <div class="row">
            <div class="col-xl-4 col-md-6">
                <!-- card -->
                <div class="card card-animate">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">{{ __('messages.total_user') }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    <span class="counter-value" data-target="{{ $total->get('user') }}"></span></h4>
                                <a href="{{ route('users.index') }}"
                                   class="text-decoration-underline">{{ __('messages.view_all_user') }}</a>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3 shadow">
                                <i class="ri ri-user-line text-primary"></i>
                            </span>
                            </div>
                        </div>
                    </div><!-- end card body -->
                </div><!-- end card -->
            </div><!-- end col -->

            <div class="col-xl-4 col-md-6">
                <!-- card -->
                <div class="card card-animate">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">{{ __('messages.total_role') }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    <span class="counter-value" data-target="{{ $total->get('role') }}"></span></h4>
                                <a href="{{ route('roles.index') }}"
                                   class="text-decoration-underline">{{ __('messages.view_all_role') }}</a>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3 shadow">
                                <i class="ri ri-user-settings-line text-primary"></i>
                            </span>
                            </div>
                        </div>
                    </div><!-- end card body -->
                </div><!-- end card -->
            </div><!-- end col -->

            <div class="col-xl-4 col-md-6">
                <!-- card -->
                <div class="card card-animate">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-0">{{ __('messages.total_permission') }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    <span class="counter-value" data-target="{{ $total->get('permission') }}"></span>
                                </h4>
                                <a href="{{ route('permissions.index') }}"
                                   class="text-decoration-underline">{{ __('messages.view_all_permission') }}</a>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3 shadow">
                                <i class="ri ri-lock-line text-primary"></i>
                            </span>
                            </div>
                        </div>
                    </div><!-- end card body -->
                </div><!-- end card -->
            </div><!-- end col -->
        </div> <!-- end row-->
    @else
        <div class="row d-flex justify-content-center">
            <div class="col-8">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="row align-items-end">
                            <div class="col-sm-8">
                                <div class="p-3">
                                    <p class="fs-16">
                                        {{ greeting(auth()->user()->name) }} <i class="mdi mdi-arrow-right"></i>
                                    </p>
                                    <div class="mt-3">
                                        <a href="{{ route(config('core.profile_route_name')) }}"
                                           class="btn btn-success waves-effect waves-light">
                                            {{ __('messages.my_account') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3">
                                    <img src="{{ asset('assets/images/user-illustarator-1.png') }}" class="img-fluid"
                                         alt="">
                                </div>
                            </div>
                        </div>
                    </div> <!-- end card-body-->
                </div>
            </div> <!-- end col-->
        </div> <!-- end row-->
    @endif
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
