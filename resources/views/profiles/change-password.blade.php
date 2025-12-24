@php
    use Illuminate\Support\Js;

    $passwordRules = config('core.password_rules');
@endphp

@extends('layouts.app')

@section('title', __('messages.change_password'))

@section('breadcrumbs', Breadcrumbs::render('profile.change-password-form'))

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
                                    <div class="col-lg-12" v-if="is_password_set">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">
                                                {{ __('messages.current_password') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input :type="currentPasswordType" id="current_password"
                                                       name="current_password" v-model="form.current_password"
                                                       :class="['form-control', errors.current_password ? 'is-invalid' : '']">
                                                <button
                                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                                    :class="{'me-4': errors.current_password}" type="button"
                                                    id="show_current_password" @click="toggleCurrentPassword">
                                                    <i class="align-middle"
                                                       :class="{'ri-eye-off-fill': show_current_password, 'ri-eye-fill': !show_current_password}"></i>
                                                </button>
                                                <span :class="['invalid-feedback']" role="alert"
                                                      v-if="errors.current_password">
                                                    <strong>@{{ errors.current_password[0] }}</strong>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">
                                                {{ __('messages.new_password') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input :type="newPasswordType" id="new_password" name="new_password"
                                                       v-model="form.new_password"
                                                       :class="['form-control', errors.new_password ? 'is-invalid' : '']">
                                                <button
                                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                                    :class="{'me-4': errors.new_password}" type="button"
                                                    id="show_new_password" @click="toggleNewPassword">
                                                    <i class="align-middle"
                                                        :class="{'ri-eye-off-fill': show_new_password, 'ri-eye-fill': !show_new_password}"></i>
                                                </button>
                                                <span :class="['invalid-feedback']" role="alert"
                                                      v-if="errors.new_password">
                                                    <strong>@{{ errors.new_password[0] }}</strong>
                                                </span>
                                            </div>

                                            @if($passwordRules['enabled'])
                                                <password-strength
                                                    :value="form.new_password"
                                                    :rules="{{ Js::from($passwordRules) }}"
                                                    :trans="{{ password_lang() }}">
                                                </password-strength>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="new_password_confirmation" class="form-label">
                                                {{ __('messages.password_confirmation') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input :type="newPasswordConfirmationType"
                                                       id="new_password_confirmation" name="new_password_confirmation"
                                                       v-model="form.new_password_confirmation" class="form-control">
                                                <button
                                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                                    type="button" id="show_new_password_confirmation"
                                                    @click="toggleNewPasswordConfirmation">
                                                    <i class="align-middle"
                                                       :class="{'ri-eye-off-fill': show_new_password_confirmation, 'ri-eye-fill': !show_new_password_confirmation}"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mt-2">
                                            <button class="btn btn-primary btn-load" type="submit" :disabled="loading"
                                                    @click.prevent="submitForm()" :key="submit_form">
                                                <span class="d-flex justify-content-center">
                                                    <span class="spinner-border" role="status" v-if="loading">
                                                        <span class="visually-hidden">
                                                            {{ __('messages.loading') }}
                                                        </span>
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
        let user = '@json($user)';
        let is_password_set = {{ $is_password_set ? 'true' : 'false' }};
    </script>
    <!-- vue -->
    @vite('resources/js/views/profiles/change-password.js')
@endpush
