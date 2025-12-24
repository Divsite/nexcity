@extends('layouts.app')

@section('title', __('messages.my_account'))

@section('breadcrumbs', Breadcrumbs::render('profile.index'))

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
                        @method('PUT')

                        <div class="row">
                            <div class="col-xxl-3 mb-3">
                                <div class="text-center">
                                    <h5 class="fs-16 mb-3">{{ __('messages.profile_picture') }}</h5>
                                    <div class="profile-user position-relative d-inline-block mx-auto mb-2">
                                        <img :src="avatar_url"
                                             class="rounded-circle avatar-xl img-thumbnail user-profile-image shadow"
                                             alt="user-profile-image">
                                        <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                            <input id="profile-img-file-input" type="file"
                                                   class="profile-img-file-input"
                                                   accept="image/png, image/jpeg, image/jpg" ref="file"
                                                   @change="avatarFile()">
                                            <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                                <span class="avatar-title rounded-circle bg-light text-body shadow">
                                                    <i class="ri-camera-fill"></i>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mt-2 mb-3"
                                         v-if="form.initial_name === {{ \App\Models\Users\User::AVATAR_NOT_INITIAL_NAME }}">
                                        <button type="button" class="btn btn-danger btn-sm" @click="removeAvatar"><i
                                                    class="ri-delete-bin-fill align-bottom me-1"></i> Remove
                                        </button>
                                    </div>
                                    <div class="form-text">{{ __('messages.file_size_maximum_2_mb') }}</div>
                                    <div class="form-text">{{ __('messages.file_extension_png_jpeg_jpg') }}</div>
                                    <span class="invalid-feedback d-block mt-3" role="alert"
                                          v-if="errors.avatar"><strong>@{{ errors.avatar[0] }}</strong></span>
                                </div>
                            </div>
                            <div class="col-xxl-9">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">{{ __('messages.name') }} <span
                                                        class="text-danger">*</span></label>
                                            <input type="text" id="name" name="name" v-model="form.name"
                                                   :class="['form-control', errors.name ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" role="alert" v-if="errors.name"><strong>@{{ errors.name[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="username"
                                                   class="form-label">{{ __('messages.username') }}</label>
                                            <input type="text" id="username" name="username" v-model="form.username"
                                                   class="form-control" disabled>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">{{ __('messages.email') }}</label>
                                            <input type="email" id="email" name="email" v-model="form.email"
                                                   class="form-control" disabled>
                                            @if(config('core.verify_enabled'))
                                                @if(!$user->hasVerifiedEmail())
                                                    <div class="form-text">
                                                        {{ __('messages.verify_your_email_address') }} {{ __('messages.before_proceeding_please_check_your_email_for_a_verification_link') }}
                                                    </div>
                                                    <div class="form-text">
                                                        {{ __('messages.if_you_did_not_receive_the_email') }}

                                                        <a href="{{ route('verification.resend') }}"
                                                           class="fw-semibold text-primary text-decoration-underline"
                                                           onclick="event.preventDefault(); document.getElementById('verification-resend').submit();">
                                                            {{ __('messages.click_here_to_request_another') }}
                                                        </a>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="phone"
                                                   class="form-label">{{ __('messages.phone_number') }}</label>
                                            <input type="tel" id="phone" name="phone" v-model="form.phone"
                                                   :class="['form-control', errors.phone ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" role="alert" v-if="errors.phone"><strong>@{{ errors.phone[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mt-2">
                                            <button class="btn btn-primary btn-load" type="submit" :disabled="loading"
                                                    @click.prevent="submitForm()" :key="submit_form">
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

                    @if(config('core.verify_enabled'))
                        <form id="verification-resend" action="{{ route('verification.resend') }}" method="POST"
                              class="d-none">
                            @csrf
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <!-- profile-setting init js -->
    <script src="{{ asset('assets/js/pages/profile-setting.init.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        let user = {{ \Illuminate\Support\Js::from($user) }};
        let avatar = '{{ asset(\App\Models\Users\User::AVATAR_PATH . $user->avatar) }}';
        let default_avatar = '{{ Avatar::create($user->name)->toBase64() }}';
        let avatar_initial_name = {{ \App\Models\Users\User::AVATAR_INITIAL_NAME }};
        let avatar_not_initial_name = {{ \App\Models\Users\User::AVATAR_NOT_INITIAL_NAME }};
    </script>
    <!-- vue -->
    @vite('resources/js/views/profiles/index.js')
@endpush
