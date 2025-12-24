@extends('layouts.app')

@section('title', __('messages.view_notification'))

@section('breadcrumbs', Breadcrumbs::render('notifications.show', $model))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <span class="fw-medium">{{ __('messages.notification_information') }}</span>
                            </h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                @if(!$model->read_at)
                                    @can('edit-notifications')
                                        <div id="read-confirmation">
                                            <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip"
                                                  data-bs-trigger="hover" data-bs-placement="top"
                                                  title="{{ __('messages.mark_as_has_read') }}">
                                                <button class="btn btn-sm btn-primary btn-icon"
                                                        data-bs-original-title="{{ __('messages.mark_as_has_read') }}"
                                                        :disabled="loading" :key="read_button_key" role="button"
                                                        @click.once="triggerRead('{{ route('notifications.set-as-has-read', ['id' => $model->id]) }}')">
                                                    <i class="ri-check-double-line"></i>
                                                </button>
                                            </span>
                                        </div>
                                    @endcan
                                @endif
                                @can('delete-notifications')
                                    <div id="delete-confirmation">
                                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip"
                                              data-bs-trigger="hover" data-bs-placement="top"
                                              title="{{ __('messages.delete') }}">
                                            <button class="btn btn-sm btn-danger btn-icon"
                                                    data-bs-original-title="{{ __('messages.delete') }}"
                                                    :disabled="loading" :key="delete_button_key" role="button"
                                                    @click.once="triggerDelete('{{ route('notifications.destroy', ['id' => $model->id]) }}')">
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
                                <td class="fw-medium text-end">{{ __('messages.form_id') }}</td>
                                <td>{{ $model->data['form_id'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.description') }}</td>
                                <td>{{ $model->data['data'] ?? null }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.has_read') }}</td>
                                <td>
                                    @if($model->read_at != null)
                                        {{ __('messages.yes') }}
                                    @else
                                        {{ __('messages.no') }}
                                    @endif
                                </td>
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

@can('delete-notifications')
    @push('scripts')
        <!-- vue -->
        @vite('resources/js/views/partials/delete-confirmation.js')
    @endpush
@endcan

@if(!$model->read_at)
    @can('edit-notifications')
        @push('scripts')
            <!-- vue -->
            @vite('resources/js/views/partials/read-confirmation.js')
        @endpush
    @endcan
@endif
