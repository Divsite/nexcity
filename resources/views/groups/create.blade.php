@extends('layouts.app')

@section('title', __('messages.create_group'))

@section('breadcrumbs', Breadcrumbs::render('groups.create'))

@section('content')
    <div v-cloak id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('messages.create_group') }}</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                        @csrf
                        <div class="row mt-2">
                            <div class="col-xxl-12">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">{{ __('messages.name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="name" name="name" v-model="form.name"
                                                   :class="['form-control', errors.name ? 'is-invalid' : '']">
                                            <span class="invalid-feedback" v-if="errors.name"><strong>@{{ errors.name[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label for="description"
                                                   class="form-label">{{ __('messages.description') }} </label>
                                            <textarea name="description" id="description" v-model="form.description"
                                                      :class="['form-control', errors.description ? 'is-invalid' : '']"></textarea>
                                            <span class="invalid-feedback" v-if="errors.description"><strong>@{{ errors.username[0] }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mt-2">
                                            <button class="btn btn-primary btn-load" type="submit" :disabled="loading"
                                                    @click.prevent="submitForm()" :key="submit_form_key">
                                                <span class="d-flex justify-content-center">
                                                    <span class="spinner-border" role="status" v-if="loading">
                                                        <span
                                                            class="visually-hidden">{{ __('messages.loading') }}</span>
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
    <!-- vue -->
    @vite('resources/js/views/groups/create.js')
@endpush
