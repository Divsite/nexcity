@extends('layouts.app')

@section('title', __('messages.create_user'))

@section('breadcrumbs', Breadcrumbs::render('users.create'))

@section('content')
    <div v-cloak id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                        @csrf

                        <div class="row mt-2">
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
                                    <div class="form-text">{{ __('messages.file_size_maximum_2_mb') }}</div>
                                    <div class="form-text">{{ __('messages.file_extension_png_jpeg_jpg') }}</div>
                                    <span class="invalid-feedback d-block mt-3" role="alert"
                                          v-if="errors.avatar"><strong>@{{ errors.avatar[0] }}</strong></span>
                                </div>
                            </div>

                            <div class="col-xxl-9">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">{{ __('messages.name') }} <span
                                                        class="text-danger">*</span></label>
                                            <input type="text" id="name" name="name" v-model="form.name"
                                                   :class="['form-control', errors.name ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" v-if="errors.name"><strong>@{{ errors.name[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">{{ __('messages.username') }} <span
                                                        class="text-danger">*</span></label>
                                            <input type="text" id="username" name="username" v-model="form.username"
                                                   :class="['form-control', errors.username ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" v-if="errors.username"><strong>@{{ errors.username[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">{{ __('messages.email') }} <span
                                                        class="text-danger">*</span></label>
                                            <input type="email" id="email" name="email" v-model="form.email"
                                                   :class="['form-control', errors.email ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" v-if="errors.email"><strong>@{{ errors.email[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="phone"
                                                   class="form-label">{{ __('messages.phone_number') }}</label>
                                            <input type="tel" id="phone" name="phone" v-model="form.phone"
                                                   :class="['form-control', errors.phone ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" v-if="errors.phone"><strong>@{{ errors.phone[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">{{ __('messages.status') }} <span
                                                        class="text-danger">*</span></label>
                                            <select :class="['form-select', errors.status ? 'is-invalid' : '']"
                                                    v-model="form.status">
                                                <option disabled value="">{{ __('messages.please_select') }}</option>
                                                <option value="{{ \App\Models\Users\User::VERIFIED }}">{{ __('messages.verified') }}</option>
                                                <option value="{{ \App\Models\Users\User::UNVERIFIED }}">{{ __('messages.unverified') }}</option>
                                            </select>
                                            <span class="invalid-feedback" v-if="errors.status"><strong>@{{ errors.status[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">{{ __('messages.role') }} <span
                                                        class="text-danger">*</span></label>
                                            <select :class="['form-select', errors.role ? 'is-invalid' : '']"
                                                    v-model="form.role">
                                                <option disabled value="">{{ __('messages.please_select') }}</option>
                                                @foreach($roles as $index => $role)
                                                    <option value="{{ $index }}">{{ $role }}</option>
                                                @endforeach
                                            </select>
                                            <span class="invalid-feedback" v-if="errors.role"><strong>@{{ errors.role[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">{{ __('messages.password') }} <span
                                                        class="text-danger">*</span></label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input :type="passwordType" id="password" name="password"
                                                       v-model="form.password"
                                                       :class="['form-control', errors.password ? 'is-invalid' : '']">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                                        :class="{'me-4': errors.password}" type="button"
                                                        id="show_password" @click="togglePassword"><i
                                                            class="align-middle"
                                                            :class="{'ri-eye-off-fill': show_password, 'ri-eye-fill': !show_password}"></i>
                                                </button>
                                                <span :class="['invalid-feedback']" v-if="errors.password"><strong>@{{ errors.password[0] }}</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="password_confirmation"
                                                   class="form-label">{{ __('messages.password_confirmation') }} <span
                                                        class="text-danger">*</span></label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input :type="passwordConfirmationType" id="password_confirmation"
                                                       name="password_confirmation" v-model="form.password_confirmation"
                                                       class="form-control">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted shadow-none"
                                                        type="button" id="show_password_confirmation"
                                                        @click="togglePasswordConfirmation"><i class="align-middle"
                                                                                               :class="{'ri-eye-off-fill': show_password_confirmation, 'ri-eye-fill': !show_password_confirmation}"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mt-2">
                                            <button class="btn btn-primary btn-load" type="submit" :disabled="loading"
                                                    @click.prevent="submitForm()" :key="submit_form_key">
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
    <!-- profile-setting init js -->
    <script src="{{ asset('assets/js/pages/profile-setting.init.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        let default_avatar = '{{ asset('assets/images/users/user-dummy-img.jpg') }}';
    </script>
    <!-- vue -->
    @vite('resources/js/views/users/create.js')
@endpush
