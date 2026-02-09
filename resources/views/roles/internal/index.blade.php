@extends('layouts.app')

@section('title', __('messages.internal_roles'))

@section('content')
    <div id="app" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="card-title mb-1">{{ __('messages.internal_roles') }}</h5>
                            <p class="text-muted mb-0">{{ __('messages.manage_internal_roles') ?? __('messages.internal_roles') }}</p>
                        </div>
                        <button class="btn btn-primary" :disabled="loading || !selectedContext || !selectedLevelSlug" @click="submit">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            {{ __('messages.save') ?? __('messages.update') }}
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('messages.organization_type') }}</label>
                            <select class="form-select" v-model="selectedContext" @change="onContextChange">
                                <option value="" disabled>{{ __('messages.select') ?? 'Select' }}</option>
                                <option v-for="context in contexts" :key="context.key" :value="context.key">
                                    @{{ context.label }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('messages.user_level') }}</label>
                            <select class="form-select" v-model="selectedLevelSlug" @change="syncPermissions" :disabled="!selectedContext">
                                <option value="" disabled>{{ __('messages.select') ?? 'Select' }}</option>
                                <option v-for="level in levelsForContext" :key="level.slug" :value="level.slug">
                                    @{{ level.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('messages.slug') }}</label>
                            <input type="text" class="form-control" v-model="selectedLevelSlug" disabled>
                        </div>
                    </div>

                    <div class="mt-4" v-if="selectedContext">
                        <div class="card border mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-1">{{ __('messages.internal_role_details') ?? __('messages.internal_roles') }}</h6>
                                        <p class="text-muted mb-0">{{ __('messages.update_internal_role_details') ?? '' }}</p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary" :disabled="!selectedLevelSlug || loadingLevel" @click="updateLevel">
                                            <span v-if="loadingLevel" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                            {{ __('messages.update') ?? 'Update' }}
                                        </button>
                                        <button class="btn btn-outline-danger" :disabled="!selectedLevelSlug || loadingLevel" @click="deleteLevel">
                                            {{ __('messages.delete') ?? 'Delete' }}
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('messages.name') }}</label>
                                        <input type="text" class="form-control" v-model="selectedLevelName" :disabled="!selectedLevelSlug">
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('messages.description') }}</label>
                                        <input type="text" class="form-control" v-model="selectedLevelDescription" :disabled="!selectedLevelSlug">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-1">{{ __('messages.add_internal_role') ?? __('messages.internal_roles') }}</h6>
                                        <p class="text-muted mb-0">{{ __('messages.create_internal_role_help') ?? '' }}</p>
                                    </div>
                                    <button class="btn btn-primary" :disabled="loadingCreate || !newLevelName || !selectedContext" @click="createLevel">
                                        <span v-if="loadingCreate" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                        {{ __('messages.add') ?? __('messages.create') ?? 'Create' }}
                                    </button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('messages.name') }}</label>
                                        <input type="text" class="form-control" v-model="newLevelName" placeholder="{{ __('messages.name') }}">
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('messages.description') }}</label>
                                        <input type="text" class="form-control" v-model="newLevelDescription" placeholder="{{ __('messages.description') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" v-if="selectedContext && selectedLevelSlug">
                        <div class="row g-3">
                            <div class="col-lg-6" v-for="(groupPermissions, groupName) in permissionGroupsForContext" :key="groupName">
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
        window.internalRoleContexts = @json($contexts);
        window.internalRoleLevels = @json($levelsByContext);
        window.internalRolePermissionGroups = @json($permissionGroupsByContext);
        window.internalRolePermissionGroupLabels = @json($permissionGroupLabelsByContext);
        window.internalRolePermissions = @json($levelPermissions);
        window.internalRoleUpdateUrl = "{{ route('internal-roles.update', ['context' => '__CONTEXT__', 'slug' => '__SLUG__']) }}";
        window.internalRoleCreateUrl = "{{ route('internal-roles.levels.store', ['context' => '__CONTEXT__']) }}";
        window.internalRoleLevelUpdateUrl = "{{ route('internal-roles.levels.update', ['context' => '__CONTEXT__', 'slug' => '__SLUG__']) }}";
        window.internalRoleLevelDeleteUrl = "{{ route('internal-roles.levels.destroy', ['context' => '__CONTEXT__', 'slug' => '__SLUG__']) }}";
    </script>
    @vite('resources/js/views/roles/internal/index.js')
@endpush
