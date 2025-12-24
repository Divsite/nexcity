@extends('layouts.app')

@section('title', __('messages.change_email'))

@section('breadcrumbs', Breadcrumbs::render('profile.change-email-form'))

@section('content')
    <div v-cloak id="app" class="row">
        <div class="col-xxl-2 col-xl-2 col-md-12 mb-4">
            @include('profiles.side-menu')
        </div>
        <div class="col-xxl-10 col-xl-10 col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                        @csrf

                        <div class="row">
                            <div class="col-xxl-12">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">{{ __('messages.email') }} <span class="text-danger">*</span></label>
                                            <input type="text" id="email" name="email" v-model="form.email" :class="['form-control', errors.email ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" role="alert" v-if="errors.email"><strong>@{{ errors.email[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">
                                                {{ __('messages.password') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input :type="passwordType" id="password"
                                                       name="password" v-model="form.password"
                                                       autocomplete="off"
                                                       :class="['form-control', errors.password ? 'is-invalid' : '']">
                                                <button
                                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                                    :class="{'me-4': errors.password}" type="button"
                                                    id="show_password" @click="togglePassword">
                                                    <i class="align-middle"
                                                       :class="{'ri-eye-off-fill': show_password, 'ri-eye-fill': !show_password}"></i>
                                                </button>
                                                <span :class="['invalid-feedback']" role="alert"
                                                      v-if="errors.password">
                                                    <strong>@{{ errors.password[0] }}</strong>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mt-2">
                                            <button class="btn btn-primary btn-load" type="submit" :disabled="loading" @click.prevent="submitForm()" :key="submit_form">
                                                <span class="d-flex justify-content-center">
                                                    <span class="spinner-border" role="status" v-if="loading">
                                                        <span class="visually-hidden">{{ __('messages.loading') }}</span>
                                                    </span>
                                                    <span :class="[loading ? 'ms-2' : '']">
                                                        {{ __('messages.save') }}
                                                    </span>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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
    <script>
        let user = {{ \Illuminate\Support\Js::from($user) }};
    </script>
    <!-- vue -->
    @vite('resources/js/views/profiles/change-email.js')
@endpush
