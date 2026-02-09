@extends('layouts.app')

@section('title', __('messages.edit_role'))

@section('breadcrumbs', Breadcrumbs::render('roles.edit', $model))

@section('content')
    <div v-cloak id="app" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('messages.edit_role') }}</h4>
                </div>
                <div class="card-body">
                    <form method="POST" autocomplete="off" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('messages.name') }}</label>
                                    <input type="text" id="name" v-model="name" class="form-control" disabled>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">{{ __('messages.display_name') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="display_name" v-model="form.display_name"
                                           :class="['form-control', errors.display_name ? 'is-invalid' : '']">
                                    <span class="invalid-feedback" role="alert" v-if="errors.display_name"><strong>@{{ errors.display_name[0] }}</strong></span>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">{{ __('messages.description') }}</label>
                                    <textarea name="description" id="description" v-model="form.description"
                                              :class="['form-control', errors.description ? 'is-invalid' : '']"></textarea>
                                    <span class="invalid-feedback" role="alert" v-if="errors.description"><strong>@{{ errors.description[0] }}</strong></span>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="permission" class="form-label">@lang('messages.permissions')</label>
                                    <div class="mb-3 mt-1">
                                        <a role="button"
                                           class="fw-semibold text-decoration-underline text-primary pe-auto"
                                           @click="selectAllPermissions()">{{ __('messages.select_all') }}</a>
                                        <span> / </span>
                                        <a role="button"
                                           class="fw-semibold text-decoration-underline text-primary pe-auto"
                                           @click="deselectAllPermissions()">{{ __('messages.deselect_all') }}</a>
                                    </div>
                                    <div class="row">
                                        <div class="col-12" v-for="(items, group) in permissions_groups">
                                            <p class="fw-semibold text-decoration-underline pe-none fs-6">@{{ group
                                                }}</p>
                                            <div class="row">
                                                <div class="col-md-12"
                                                     v-for="permission in items">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox"
                                                               :id="'permission-'+permission.id"
                                                               v-model="form.permissions"
                                                               :value="permission.id">
                                                        <label class="form-check-label"
                                                               :for="'permission-'+permission.id">
                                                            <div class="d-flex align-middle">
                                                                <span>@{{ permission.display_name }}</span>
                                                                <i class="ri-information-line ms-1"
                                                                   data-bs-toggle="tooltip" data-bs-placement="right"
                                                                   :title="permission.description"></i>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mt-2">
                                    <button class="btn btn-primary btn-load" type="button" :disabled="loading"
                                            @click.once="submitForm()" :key="submit_key">
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
        let model = {{ \Illuminate\Support\Js::from($model) }};
        let permissionByGroup = {{ \Illuminate\Support\Js::from($permissionByGroup) }};
        let rolePermission = {{ \Illuminate\Support\Js::from($rolePermission) }};
    </script>
    <!-- vue -->
    @vite('resources/js/views/roles/edit.js')
@endpush
