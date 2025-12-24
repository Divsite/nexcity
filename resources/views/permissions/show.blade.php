@extends('layouts.app')

@section('title', __('messages.view_permission'))

@section('breadcrumbs', Breadcrumbs::render('settings.permissions.show', $model))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <span class="fw-medium">{{ $model->display_name }}</span>
                            </h5>
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
                                <td class="fw-medium text-end">{{ __('messages.name') }}</td>
                                <td>{{ $model->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.display_name') }}</td>
                                <td>{{  $model->display_name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.description') }}</td>
                                <td>{{ $model->description }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.group') }}</td>
                                <td>{{ $model->group }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium align-middle text-end">{{ __('messages.roles') }}</td>
                                <td class="text-wrap">
                                    @forelse ($roles as $role)
                                        <span style="cursor: default"
                                              class="badge bg-primary-subtle text-primary me-2 mb-1 mt-1 fs-12"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              title="{{ $role->description }}">{{ $role->display_name }}
                                        </span>
                                    @empty
                                        {{ __('messages.no_role') }}
                                    @endforelse
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
