@extends('layouts.app')

@section('title', __('messages.view_user'))

@section('breadcrumbs', Breadcrumbs::render('users.show', $model))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <span>{{ __('messages.user') }}</span>
                                <span class="fw-medium">{{ $model->name }}</span>
                            </h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                @can('edit-users')
                                    <a href="{{ route('users.edit', ['user' => $model->id]) }}"
                                       class="btn btn-sm btn-info btn-icon me-1" data-bs-toggle="tooltip"
                                       data-bs-trigger="hover"
                                       data-bs-placement="top" title="{{ __('messages.edit') }}"
                                       data-bs-original-title="{{ __('messages.edit') }}">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                @endcan
                                @can('delete-users')
                                    <div id="delete-confirmation">
                                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip"
                                              data-bs-trigger="hover" data-bs-placement="top"
                                              title="{{ __('messages.delete') }}">
                                            <button class="btn btn-sm btn-danger btn-icon"
                                                    data-bs-original-title="{{ __('messages.delete') }}" role="button"
                                                    :disabled="loading" :key="delete_button_key"
                                                    @click.once="triggerDelete('{{ route('users.destroy', ['user' => $model->id]) }}')">
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
                    <div class="text-center">
                        <div class="profile-user position-relative d-inline-block">
                            <img src="{{ asset(\App\Models\Users\User::AVATAR_PATH . $model->avatar) }}" alt=""
                                 class="avatar-xl rounded-circle user-profile-image img-thumbnail shadow">
                        </div>
                        <h5 class="mt-3 mb-1">{{ $model->name }}</h5>
                    </div>

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
                                <td class="fw-medium text-end">{{ __('messages.email') }}</td>
                                <td>{{ $model->email }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.phone_number') }}</td>
                                <td>{{ $model->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.status') }}</td>
                                <td>
                                    @if ($model->email_verified_at)
                                        <span class="badge bg-success-subtle text-success fs-12">{{ __('messages.verified') }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger fs-12">{{ __('messages.unverified') }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.role') }}</td>
                                <td>{{ $model->roles->implode('display_name', ', ') }}</td>
                            </tr>
                            @if ($model->provider_id && $model->provider)
                                <tr>
                                    <td class="fw-medium text-end align-middle">{{ __('messages.social_media') }}</td>
                                    <td class="align-middle">
                                        @if ($model->provider == 'facebook')
                                            <i class="ri-facebook-circle-fill fs-18 text-info"></i>
                                        @endif

                                        @if ($model->provider == 'google')
                                            <i class="ri-google-fill fs-18 text-danger"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.created_at') }}</td>
                                <td>{{ $model->created_at ? $model->created_at->format('d/m/Y h:i A ') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-end">{{ __('messages.updated_at') }}</td>
                                <td>{{ $model->updated_at ? $model->updated_at->diffForHumans() : '-' }}</td>
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
