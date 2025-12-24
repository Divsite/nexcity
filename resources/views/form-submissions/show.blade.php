@extends('layouts.app')

@section('title', __('messages.view_submission'))

@section('breadcrumbs', Breadcrumbs::render('submissions.show', $model))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <span class="fw-medium">{{ $model->form->name }}</span>
                            </h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                @can('edit-submissions')
                                    <a href="{{ route('submissions.edit', ['submission' => $model->id]) }}"
                                       class="btn btn-sm btn-info btn-icon me-1" data-bs-toggle="tooltip"
                                       data-bs-trigger="hover" data-bs-placement="top" title="{{ __('messages.edit') }}"
                                       data-bs-original-title="{{ __('messages.edit') }}">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                @endcan
                                @can('delete-submissions')
                                    <div id="delete-confirmation">
                                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip"
                                              data-bs-trigger="hover" data-bs-placement="top"
                                              title="{{ __('messages.delete') }}">
                                            <button class="btn btn-sm btn-danger btn-icon"
                                                    data-bs-original-title="{{ __('messages.delete') }}" role="button"
                                                    :disabled="loading" :key="delete_button_key"
                                                    @click.once="triggerDelete('{{ route('submissions.destroy', ['submission' => $model->id]) }}')">
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
                        <table class="table table-bordered table-striped mb-4">
                            <tbody class="text-nowrap">
                            <tr class="table-light fw-semibold fs-14">
                                <td colspan="2">{{ __('messages.submission_information') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ __('messages.id') }}</td>
                                <td class="w-75">{{ $model->id }}</td>
                            </tr>
                            @foreach($model->formData() as $formData)
                                <tr id="{{ $formData['id'] }}">
                                    <td class="fw-medium align-middle text-end">{{ $formData['name'] }}</td>
                                    <td class="text-wrap">
                                        @if(is_array($formData['value']))
                                            @foreach($formData['value'] as $item)
                                                <span class="badge bg-primary me-2 mb-1 mt-1 fs-12 pe-none">
                                                    {{ $item }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span>{{ $formData['value'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if($model->files()->exists())
                                <tr class="table-light fw-semibold fs-14">
                                    <td colspan="2">{{ __('messages.files_information') }}</td>
                                </tr>
                                @foreach($model->files as $file)
                                    <tr id="{{ $file->id }}">
                                        <td class="fw-medium text-end">{{ $file->label }}</td>
                                        <td class="align-middle">
                                            <a href="{{ route('form-submission-files.show', $file->id) }}"
                                               target="_blank"
                                               class="link-primary link-offset-2 text-decoration-underline link-underline-opacity-25 link-underline-opacity-100-hover">
                                                {{ __('messages.view') }}
                                            </a>
                                            <span class="badge rounded-pill bg-info ms-2">{{ $file->mime_type }}</span>
                                            <span class="badge rounded-pill bg-info">
                                                {{ format_bytes($file->size) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive table-card mt-4">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody class="text-nowrap">
                            <tr class="table-light fw-semibold fs-14">
                                <td colspan="2">{{ __('messages.additional_info') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ __('messages.created_by') }}</td>
                                <td>{{ $model->author->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ __('messages.created_at') }}</td>
                                <td>{{ $model->created_at->format('d/m/Y h:i A ') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end w-25">{{ __('messages.updated_by') }}</td>
                                <td>{{ $model->lastEditor->name ?? '-' }}</td>
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

@push('scripts')
    <!-- vue -->
    @vite('resources/js/views/partials/delete-confirmation.js')
@endpush
