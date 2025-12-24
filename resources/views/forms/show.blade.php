@extends('layouts.app')

@section('title', __('messages.view_forms'))

@section('breadcrumbs', Breadcrumbs::render('forms.show', $model))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <span class="fw-medium">{{ $model->name }}</span>
                            </h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                @can('edit-forms')
                                    <a href="{{ route('forms.edit', ['form' => $model->id]) }}" class="btn btn-sm btn-info btn-icon me-1" data-bs-toggle="tooltip"
                                       data-bs-trigger="hover"
                                       data-bs-placement="top" title="{{ __('messages.edit') }}"
                                       data-bs-original-title="{{ __('messages.edit') }}">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                @endcan
                                @can('delete-forms')
                                    <div id="delete-confirmation">
                                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip"
                                              data-bs-trigger="hover" data-bs-placement="top"
                                              title="{{ __('messages.delete') }}">
                                            <button class="btn btn-sm btn-danger btn-icon"
                                                    data-bs-original-title="{{ __('messages.delete') }}" role="button"
                                                    :disabled="loading" :key="delete_button_key"
                                                    @click.once="triggerDelete('{{ route('forms.destroy', ['form' => $model->id]) }}')">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card mt-4">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <tbody class="text-nowrap">
                            <tr>
                                <td class="fw-medium text-end w-25">{{ __('messages.id') }}</td>
                                <td class="w-75">{{ $model->id }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.form_name') }}</td>
                                <td>{{ $model->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.form_type') }}</td>
                                <td>{{ $model->type ? $model->type->name : null }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.created_at') }}</td>
                                <td>{{ $model->created_at->format('d/m/Y h:i A ') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.updated_at') }}</td>
                                <td>{{ $model->updated_at->diffForHumans() }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@can('delete-forms')
    @push('scripts')
        <!-- vue -->
        @vite('resources/js/views/partials/delete-confirmation.js')
    @endpush
@endcan
