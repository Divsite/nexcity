@extends('layouts.app')

@section('title', __('messages.edit_group'))

@section('breadcrumbs', Breadcrumbs::render('groups.edit', $model))

@section('content')
    <div v-cloak id="app" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('messages.edit_group') }}</h4>
                </div>
                <div class="card-body">
                    <form method="POST" autocomplete="off" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('messages.name') }}</label>
                                    <input type="text" id="name" v-model="form.name" class="form-control">
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
                                <div class="mt-2">
                                    <button class="btn btn-primary btn-load" type="button" :disabled="loading"
                                            @click.once="submitForm()" :key="submit_form_key">
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
    </script>
    <!-- vue -->
    @vite('resources/js/views/groups/edit.js')
@endpush
