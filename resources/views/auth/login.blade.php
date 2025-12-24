@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.auth')

@section('title', __('messages.login'))

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
                            <h5 class="text-primary">{{ __('messages.welcome_back') }}</h5>
                            <p class="text-muted">{{ __('messages.login_to_continue_to', ['app_name' => config('app.name')]) }}</p>
                        </div>
                        <div class="p-2 mt-4">
                            <form method="POST" autocomplete="off" novalidate @submit.prevent="submitForm()">
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        {{ __('messages.email') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" v-model="email"
                                           :class="['form-control', errors.email ? 'is-invalid' : '']">
                                    <span :class="['invalid-feedback']" role="alert" v-if="errors.email">
                                        <strong>@{{ errors.email[0] }}</strong>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        {{ Str::title(__('messages.password')) }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                        <input :type="passwordType" id="password" name="password" v-model="password"
                                               :class="['form-control', errors.password ? 'is-invalid' : '']">
                                        <button
                                            class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                            :class="{'me-4': errors.password}" type="button" id="show_password"
                                            @click="togglePassword">
                                            <i class="align-middle"
                                               :class="{'ri-eye-off-fill': show_password, 'ri-eye-fill': !show_password}"></i>
                                        </button>
                                        <span :class="['invalid-feedback']" role="alert" v-if="errors.password">
                                            <strong>@{{ errors.password[0] }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <div class="form-check">
                                    <div class="float-end">
                                        <a href="{{ route('password.request') }}" class="text-muted">
                                            {{ __('messages.forgot_your_password') }}
                                        </a>
                                    </div>

                                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                           v-model="remember">
                                    <label class="form-check-label"
                                           for="remember">{{ __('messages.remember_me') }}</label>
                                </div>

                                <div class="d-grid mt-4">
                                    <button class="btn btn-primary btn-load" type="submit" :disabled="loading">
                                        <span class="d-flex justify-content-center">
                                            <span class="spinner-border" role="status" v-if="loading">
                                                <span class="visually-hidden">{{ __('messages.loading') }}</span>
                                            </span>
                                            <span class="ms-2">
                                                {{ __('messages.login') }}
                                            </span>
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <div class="mt-4 text-center">
                                <p class="mb-0">
                                    {{ __('messages.dont_have_an_account') }}
                                    <a href="{{ url(config('core.register_link')) }}"
                                       class="fw-semibold text-primary text-decoration-underline">
                                        {{ __('messages.register') }}
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/views/auth/login.js')
@endpush
