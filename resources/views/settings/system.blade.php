@use(Illuminate\Support\Js)

@extends('settings.partials.layout')

@section('title', __('messages.system_settings'))

@section('breadcrumbs', Breadcrumbs::render('settings.system.index'))

@section('setting-content')
    <div id="app" v-cloak class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-4 align-items-center gy-3">
                <div class="col-sm">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <span class="fw-medium">{{ __('messages.system_preferences') }}</span>
                    </h5>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                @csrf
                @method('POST')

                <div class="row mt-2">
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="name" class="form-label required">{{ __('messages.name') }}</label>
                            <input type="text" id="name" v-model="settings.name"
                                   class="form-control" :class="{ 'is-invalid': errors.name }">
                            <div class="invalid-feedback" v-if="errors.name">
                                <strong>@{{ errors.name[0] }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="logo_sm" class="form-label required">{{ __('messages.small_logo') }}</label>
                            <file-pond
                                ref="pond"
                                name="logo_sm"
                                :required="true"
                                :disabled="false"
                                :allow-file-type-validation="true"
                                :accepted-file-types="logoFileTypes"
                                label-file-type-not-allowed="{{ __('messages.file_of_invalid_type') }}"
                                file-validate-type-label-expected-types="{{ __('messages.expects') }}"
                                :allow-multiple="false"
                                label-idle="{{ __('messages.drag_and_drop_your_files_or_browse') }}"
                                max-file-size="3MB"
                                label-max-file-size-exceeded="{{ __('messages.file_is_too_large') }}"
                                label-max-file-size="{{ __('messages.maximum_file_size_is') }}"
                                :files="settings.logo_sm_asset"
                                @addfile="handleFileUpdateForLogoSm"
                                v-on:init="handleFilePondInit">
                            </file-pond>
                            <div class="form-text">
                                {{ __('messages.displayed_in_compact_views_accepted_formats_jpg_png_maximum_file_size_3_mb') }}
                            </div>
                            <div class="invalid-feedback d-block" v-if="errors.logo_sm">
                                <strong>@{{ errors.logo_sm[0] }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="large_logo" class="form-label required">{{ __('messages.large_logo') }}</label>
                            <file-pond
                                ref="pond"
                                name="large_logo"
                                :required="true"
                                :disabled="false"
                                :allow-file-type-validation="true"
                                :accepted-file-types="logoFileTypes"
                                label-file-type-not-allowed="{{ __('messages.file_of_invalid_type') }}"
                                file-validate-type-label-expected-types="{{ __('messages.expects') }}"
                                :allow-multiple="false"
                                label-idle="{{ __('messages.drag_and_drop_your_files_or_browse') }}"
                                max-file-size="3MB"
                                label-max-file-size-exceeded="{{ __('messages.file_is_too_large') }}"
                                label-max-file-size="{{ __('messages.maximum_file_size_is') }}"
                                :files="settings.logo_lg_asset"
                                @addfile="handleFileUpdateForLogoLg"
                                v-on:init="handleFilePondInit">
                            </file-pond>
                            <div class="form-text">
                                {{ __('messages.displayed_in_compact_views_accepted_formats_jpg_png_maximum_file_size_3_mb') }}
                            </div>
                            <div class="invalid-feedback d-block" v-if="errors.large_logo">
                                <strong>@{{ errors.large_logo[0] }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="mb-3">
                            <label for="favicon" class="form-label required">{{ __('messages.favicon') }}</label>
                            <file-pond
                                ref="pond"
                                name="favicon"
                                :required="true"
                                :disabled="false"
                                :allow-file-type-validation="true"
                                :accepted-file-types="faviconFileTypes"
                                label-file-type-not-allowed="{{ __('messages.file_of_invalid_type') }}"
                                file-validate-type-label-expected-types="{{ __('messages.expects') }}"
                                :allow-multiple="false"
                                label-idle="{{ __('messages.drag_and_drop_your_files_or_browse') }}"
                                max-file-size="1MB"
                                label-max-file-size-exceeded="{{ __('messages.file_is_too_large') }}"
                                label-max-file-size="{{ __('messages.maximum_file_size_is') }}"
                                :files="settings.favicon_asset"
                                @addfile="handleFileUpdateForFavicon"
                                v-on:init="handleFilePondInit">
                            </file-pond>
                            <div class="form-text">
                                {{ __('messages.accepted_formats_jpg_png_ico_maximum_file_size_1_mb') }}
                            </div>
                            <div class="invalid-feedback d-block" v-if="errors.favicon">
                                <strong>@{{ errors.favicon[0] }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="mt-2">
                            <button class="btn btn-primary btn-load" type="submit" :disabled="loading"
                                    @click.prevent="submitForm()" :key="submitFormKey">
                                <span class="d-flex justify-content-center">
                                    <span class="spinner-border me-2" role="status" v-if="loading">
                                        <span class="visually-hidden">{{ __('messages.loading') }}</span>
                                    </span>
                                    <span>{{ __('messages.save') }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        window.settings = {{ Js::from($settings) }};
    </script>
    <!-- vue -->
    @vite('resources/js/views/settings/system.js')
@endpush
