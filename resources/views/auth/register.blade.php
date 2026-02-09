@php
    use Illuminate\Support\Js;

    $passwordRules = config('core.password_rules');
@endphp

@extends('layouts.auth')

@section('title', __('messages.register'))

@section('content')
    <div id="app" v-cloak class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center mt-sm-5 mb-4 text-white-50">
                    <div>
                        <a href="{{ url('/') }}" class="d-inline-block auth-logo">
                            <img src="{{ asset(config('logo.auth')) }}" alt="" height="150">
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
                            <h5 class="text-primary">{{ __('messages.register_account') }}</h5>
                            <p class="text-muted">{{ __('messages.get_your_free_account_now', ['app_name' => config('app.name')]) }}</p>
                        </div>
                        <div class="p-2 mt-4">
                            <form method="POST" autocomplete="off" @submit.prevent="submitForm()" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        {{ __('messages.name') }}
                                        <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" v-model="name"
                                           :class="['form-control', errors.name ? 'is-invalid' : '']">
                                    <span :class="['invalid-feedback']" role="alert" v-if="errors.name">
                                        <strong>@{{ errors.name[0] }}</strong>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        {{ __('messages.username') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="username" name="username" v-model="username"
                                           :class="['form-control', errors.username ? 'is-invalid' : '']">
                                    <span :class="['invalid-feedback']" role="alert" v-if="errors.username">
                                        <strong>@{{ errors.username[0] }}</strong>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        {{ __('messages.email') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" v-model="email"
                                           :class="['form-control', errors.email ? 'is-invalid' : '']">
                                    <span :class="['invalid-feedback']" role="alert" v-if="errors.email"><strong>@{{ errors.email[0] }}</strong></span>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        {{ __('messages.password') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                        <input :type="passwordType" id="password" name="password" v-model="password"
                                               :class="['form-control', errors.password ? 'is-invalid' : '']">
                                        <button
                                            class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                            :class="{'me-4': errors.password}" type="button" id="show_password"
                                            @click="togglePassword"><i class="align-middle"
                                                                       :class="{'ri-eye-off-fill': show_password, 'ri-eye-fill': !show_password}"></i>
                                        </button>
                                        <span :class="['invalid-feedback']" role="alert" v-if="errors.password"><strong>@{{ errors.password[0] }}</strong></span>
                                    </div>

                                    @if($passwordRules['enabled'])
                                        <password-strength
                                            :value="password"
                                            :rules="{{ Js::from($passwordRules) }}"
                                            :trans="{{ password_lang() }}">
                                        </password-strength>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">
                                        {{ __('messages.password_confirmation') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                        <input :type="passwordConfirmationType" id="password_confirmation"
                                               name="password_confirmation" v-model="password_confirmation"
                                               class="form-control">
                                        <button
                                            class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                            type="button" id="show_password_confirmation"
                                            @click="togglePasswordConfirmation"><i class="align-middle"
                                                                                   :class="{'ri-eye-off-fill': show_password_confirmation, 'ri-eye-fill': !show_password_confirmation}"></i>
                                        </button>
                                    </div>
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
                                                {{ __('messages.register') }}
                                            </span>
                                        </span>
                                    </button>
                                </div>
                            </form>

                            <div class="mt-4 text-center">
                                <p class="mb-0">
                                    {{ __('messages.already_have_an_account') }}
                                    <a href="{{ route('login') }}"
                                       class="fw-semibold text-decoration-underline text-primary">
                                        {{ __('messages.login') }}
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
    @vite('resources/js/views/auth/register.js')
@endpush
