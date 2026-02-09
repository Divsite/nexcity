@extends('layouts.app')

@section('title', __('messages.user_management'))

@section('content')
    <div id="app" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="card-title mb-1">{{ __('messages.user_management') }}</h5>
                            <p class="text-muted mb-0">{{ __('messages.manage_user_levels_permissions') ?? __('messages.user_management') }}</p>
                        </div>
                        <button class="btn btn-primary" :disabled="loading || !selectedLevelId" @click="submit">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            {{ __('messages.save') ?? __('messages.update') }}
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('messages.user_level') }}</label>
                            <select class="form-select" v-model="selectedLevelId" @change="syncPermissions">
                                <option value="" disabled>{{ __('messages.select') ?? 'Select' }}</option>
                                <option v-for="level in levels" :key="level.id" :value="level.id">
                                    @{{ level.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4" v-if="selectedLevelId">
                        <div class="row g-3">
                            <div class="col-lg-6" v-for="(groupPermissions, groupName) in permissionGroups" :key="groupName">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 text-uppercase">@{{ groupName.replaceAll('_', ' ') }}</h6>
                                        <button class="btn btn-sm btn-outline-secondary" @click="toggleGroup(groupPermissions)">
                                            {{ __('messages.select') ?? 'Select' }}
                                        </button>
                                    </div>
                                    <div class="form-check" v-for="perm in groupPermissions" :key="perm.name">
                                        <input class="form-check-input" type="checkbox" :id="perm.name" :value="perm.name" v-model="selectedPermissions">
                                        <label class="form-check-label" :for="perm.name">
                                            <span class="fw-semibold">@{{ perm.display_name }}</span>
                                            <span class="text-muted d-block small">@{{ perm.description }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-muted py-5" v-else>
                        <i class="ri-user-settings-line fs-1 d-block mb-2"></i>
                        <p class="mb-0">{{ __('messages.select_user_level_to_manage') ?? 'Select a user level to manage permissions.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.userLevels = @json($levels);
        window.permissionGroups = @json($permissionGroups);
        window.permissionGroupLabels = @json($permissionGroupLabels);
        window.levelPermissions = @json($levelPermissions);
        window.userManagementUpdateUrl = "{{ route('settings.user-management.update', ['level' => '__LEVEL__']) }}";
    </script>
    @vite('resources/js/views/settings/user-management/index.js')
@endpush
