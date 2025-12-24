@extends('layouts.auth')

@section('title', __('messages.reset_password'))

@section('content')
    <div id="app" v-cloak class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center mt-sm-5 mb-4 text-white-50">
                    <div>
                        <a href="{{ url('/') }}" class="d-inline-block auth-logo">
                            <img src="{{ asset(config('logo.auth')) }}" alt="" height="45">
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card mt-2">
                    <div class="card-body p-4">
                        <div class="text-center mt-2">
                            <h5 class="text-primary">{{ __('messages.forgot_password') }}</h5>
                            <p class="text-muted">{{ __('messages.reset_password_with', ['app_name' => config('app.name')]) }}</p>
                        </div>
                        <div class="p-2 mt-4">
                            <form method="POST" autocomplete="off" @submit.prevent="submitForm()" novalidate>
                                @csrf

                                <div class="mb-4">
                                    <label for="email" class="form-label">
                                        {{ __('messages.email') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" v-model="email"
                                           :class="['form-control', errors.email ? 'is-invalid' : '']">
                                    <span :class="['invalid-feedback']" role="alert" v-if="errors.email"><strong>@{{ errors.email[0] }}</strong></span>
                                </div>

                                <div class="d-grid mt-4">
                                    <button class="btn btn-primary btn-load" type="submit" :disabled="loading">
                                        <span class="d-flex justify-content-center">
                                            <span class="spinner-border" role="status" v-if="loading">
                                                <span class="visually-hidden">
                                                    {{ __('messages.loading') }}
                                                </span>
                                            </span>
                                            <span class="ms-2">
                                                {{ __('messages.send_reset_link') }}
                                            </span>
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <div class="mt-5 text-center">
                                <p class="mb-0">
                                    {{ __('messages.wait_i_remember_my_password') }}
                                    <a href="{{ route('login') }}"
                                       class="fw-semibold text-primary text-decoration-underline">
                                        {{ __('messages.click_here') }}
                                    </a>
                                </p>
                            </div>
                        </div>
                        <!-- end card body -->
                    </div>
                    <!-- end card -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- vue -->
    @vite('resources/js/views/auth/passwords/email.js')
@endpush
