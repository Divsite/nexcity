@use(App\Settings\SystemPreferences)
@use(Illuminate\Support\Str)

<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset(app(SystemPreferences::class)->logo_sm) }}" alt="" height="30">
            </span>
            <span class="logo-lg">
                <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" alt="" height="100">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset(app(SystemPreferences::class)->logo_sm) }}" alt="" height="30">
            </span>
            <span class="logo-lg">
                <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" alt="" height="100">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span data-key="t-menu">{{ __('messages.menu') }}</span></li>

                @php
                    $menuBuilder = app(\App\Services\Menus\MenuBuilder::class);
                    $contextResolver = app(\App\Services\Menus\MenuContextResolver::class);
                    if(auth()->check()) {
                        [$menuContext, $menuOrganization] = $contextResolver->resolve(auth()->user());
                        $dynamicMenuSections = $menuBuilder->forUser(auth()->user(), $menuContext, $menuOrganization);
                    } else {
                        $menuContext = 'admin';
                        $menuOrganization = null;
                        $dynamicMenuSections = collect();
                    }
                @endphp

                <li class="nav-item">
                    <a class="nav-link menu-link {{ set_active('dashboard') }}" href="{{ route('dashboard') }}">
                        <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">{{ __('messages.dashboard') }}</span>
                    </a>
                </li>

                @if($menuContext === 'admin')
                    @canany(['browse-forms', 'browse-submissions'])
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ menu_forms() }}" href="#sidebar-form"
                            data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar-form">
                                <i class="ri-file-list-3-line"></i> <span data-key="t-forms">{{ __('messages.forms') }}</span>
                            </a>
                            <div class="collapse menu-dropdown {{ menu_forms(true) }}" id="sidebar-form">
                                <ul class="nav nav-sm flex-column">
                                    @can('browse-forms')
                                        <li class="nav-item">
                                            <a href="{{ route('forms.index') }}" class="nav-link {{ forms_link() }}">
                                                {{ __('messages.manage_forms') }}
                                            </a>
                                        </li>
                                    @endcan
                                    @can('add-submissions')
                                        <li class="nav-item">
                                            <a href="{{ route('fill.forms') }}" class="nav-link {{ set_active('fill.forms') }}">
                                                {{ __('messages.fill_up_form') }}
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link menu-link {{ menu_submissions() }}" href="#sidebar-submission"
                            data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar-submission">
                                <i class="ri-survey-line"></i> <span data-key="t-submissions">{{ __('messages.submissions') }}</span>
                            </a>
                            <div class="collapse menu-dropdown {{ menu_submissions(true) }}" id="sidebar-submission">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ route('my-submissions.index') }}"
                                        class="nav-link {{ set_active('my-submissions') }}">
                                            {{ __('messages.my_submissions') }}
                                        </a>
                                    </li>
                                    @can('process-submissions')
                                        <li class="nav-item">
                                            <a href="{{ route('submission.list') }}"
                                            class="nav-link {{ set_active('submission.list') }}">
                                                {{ __('messages.submission_list') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('tasks.current') }}"
                                            class="nav-link {{ set_active('tasks.current') }}">
                                                {{ __('messages.my_current_tasks') }}
                                                @if(total_current_task() > 0)
                                                    <span class="badge badge-pill bg-info">{{ total_current_task() }}</span>
                                                @endif
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('tasks.completed') }}"
                                            class="nav-link {{ set_active('tasks.completed') }}">
                                                {{ __('messages.my_completed_tasks') }}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endcanany
                @endif

                @foreach($dynamicMenuSections as $section => $items)
                    @php
                        $sectionId = 'dynamic-'.Str::slug($section).'-'.$loop->index;
                        $hasParent = $section !== \App\Services\Menus\MenuBuilder::DEFAULT_SECTION;
                        $sectionActive = $items->contains(fn($item) => $item->isActive());
                    @endphp
                    @if(! $hasParent)
                        @foreach($items as $item)
                            <li class="nav-item">
                                <a class="nav-link menu-link {{ $item->isActive() ? 'active' : '' }}"
                                   href="{{ $item->resolved_url }}">
                                    <i class="{{ $item->icon ?? 'ri-apps-line' }}"></i>
                                    <span>{{ __($item->label) }}</span>
                                </a>
                            </li>
                        @endforeach
                    @else
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $sectionActive ? 'active' : '' }}" href="#{{ $sectionId }}"
                               data-bs-toggle="collapse" role="button" aria-expanded="{{ $sectionActive ? 'true' : 'false' }}"
                               aria-controls="{{ $sectionId }}">
                                <i class="{{ $items->first()->icon ?? 'ri-menu-line' }}"></i>
                                <span>{{ __($section) }}</span>
                            </a>
                            <div class="collapse menu-dropdown {{ $sectionActive ? 'show' : '' }}" id="{{ $sectionId }}">
                                <ul class="nav nav-sm flex-column">
                                    @foreach($items as $item)
                                        <li class="nav-item">
                                            <a href="{{ $item->resolved_url }}" class="nav-link {{ $item->isActive() ? 'active' : '' }}">
                                                {{ __($item->label) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                @endforeach

                @if (config('core.custom_module_menu_enabled'))
                    @if (config('core.custom_module_menu_view'))
                        @include(config('core.custom_module_menu_view'))
                    @endif
                @endif

                {{-- Settings Partners -> Hanya Superadmin Mitra --}}
                @if($menuContext !== 'admin')
                    @php
                        $user = auth()->user();
                        $isOrgSuperadmin = $user
                            ? $user->organizationMemberships()
                                ->where('is_primary', true)
                                ->where('level_slug', 'like', '%-superadmin')
                                ->exists()
                            : false;
                        $canSettings = $user && $isOrgSuperadmin;
                    @endphp
                    @if($canSettings)
                        @php
                            $settingsActive = route_is_active(['profile.*', 'settings.summary', 'settings.organization-profile', 'settings.user-management.index', 'settings.security', 'settings.billing']);
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-link {{ $settingsActive ? 'active' : '' }}" href="#sidebar-partner-setting"
                               data-bs-toggle="collapse" role="button" aria-expanded="{{ $settingsActive ? 'true' : 'false' }}" aria-controls="sidebar-partner-setting">
                                <i class="ri-settings-3-line"></i> <span data-key="t-settings">{{ __('messages.settings') }}</span>
                            </a>
                            <div class="collapse menu-dropdown {{ $settingsActive ? 'show' : '' }}" id="sidebar-partner-setting">
                                <ul class="nav nav-sm flex-column">
                                    @if($isOrgSuperadmin || $user->can('read-settings-summary'))
                                        <li class="nav-item">
                                            <a href="{{ route('settings.summary') }}"
                                               class="nav-link {{ set_active('settings.summary') }}">
                                                {{ __('messages.account_summary') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if($isOrgSuperadmin)
                                        <li class="nav-item">
                                            <a href="{{ route('profile.index') }}" class="nav-link {{ set_active('profile') }}">
                                                {{ __('messages.my_account') }}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('settings.organization-profile') }}"
                                               class="nav-link {{ set_active('settings.organization-profile') }}">
                                                {{ __('messages.organization_profile') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if($isOrgSuperadmin || $user->can('read-settings-user-managements'))
                                        <li class="nav-item">
                                            <a href="{{ route('settings.user-management.index') }}"
                                               class="nav-link {{ set_active('settings.user-management.index') }}">
                                                {{ __('messages.user_management') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if($isOrgSuperadmin || $user->can('read-users'))
                                        <li class="nav-item">
                                            <a href="{{ route('settings.users.index') }}"
                                               class="nav-link {{ set_active('settings.users.index') }}">
                                                {{ __('messages.users') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if($isOrgSuperadmin || $user->can('read-settings-security'))
                                        <li class="nav-item">
                                            <a href="{{ route('settings.security') }}"
                                               class="nav-link {{ set_active('settings.security') }}">
                                                {{ __('messages.security') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if($isOrgSuperadmin || $user->can('read-settings-billing'))
                                        <li class="nav-item">
                                            <a href="{{ route('settings.billing') }}"
                                               class="nav-link {{ set_active('settings.billing') }}">
                                                {{ __('messages.billing') }}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                    @endif
                @endif

                {{-- Manage Partners -> Hanya Superadmin Global --}}
                @canany(['browse-users', 'browse-roles', 'browse-permissions', 'browse-user-menus', 'browse-groups', 'browse-form-types'])
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ menu_manage() }}" href="#sidebar-manage"
                           data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar-manage">
                            <i class="ri-apps-line"></i> <span data-key="t-manage">{{ __('messages.manage') }}</span>
                        </a>
                        <div class="collapse menu-dropdown {{ menu_manage(true) }}" id="sidebar-manage">
                            <ul class="nav nav-sm flex-column">
                                @if (config('core.custom_management_menu_enabled'))
                                    @if (config('core.custom_management_menu_view'))
                                        @include(config('core.custom_management_menu_view'))
                                    @endif
                                @endif

                                @if(auth()->user()->can('browse-users'))
                                    <li class="nav-item">
                                        <a href="{{ route(config('core.user_module_route_name')) }}"
                                           class="nav-link {{ set_active('users') }}">{{ __('messages.users') }}</a>
                                    </li>
                                @endif

                                @if(auth()->user()->can('browse-roles') && auth()->user()->can('edit-roles'))
                                    <li class="nav-item">
                                        <a href="{{ route('roles.index') }}" class="nav-link {{ set_active('roles') }}">{{ __('messages.user_roles') }}</a>
                                    </li>
                                @endif
                                @if(auth()->user()->can('manage-internal-roles'))
                                    <li class="nav-item">
                                        <a href="{{ route('internal-roles.index') }}" class="nav-link {{ set_active('internal-roles.index') }}">{{ __('messages.internal_roles') }}</a>
                                    </li>
                                @endif

                                @if(auth()->user()->can('browse-permissions'))
                                    <li class="nav-item">
                                        <a href="{{ route('permissions.index') }}" class="nav-link {{ set_active('permissions') }}">{{ __('messages.user_permissions') }}</a>
                                    </li>
                                @endif

                                @if(auth()->user()->can('browse-user-menus'))
                                    <li class="nav-item">
                                        <a href="{{ route('menus.index') }}" class="nav-link {{ set_active('menus') }}">{{ __('messages.user_menus') }}</a>
                                    </li>
                                @endif

                                @if(auth()->user()->can('browse-groups'))
                                    <li class="nav-item">
                                        <a href="{{ route('groups.index') }}"
                                           class="nav-link {{ set_active('groups') }}">{{ __('messages.groups') }}</a>
                                    </li>
                                @endif

                                @if(auth()->user()->can('browse-form-types'))
                                    <li class="nav-item">
                                        <a href="{{ route('form-types.index') }}"
                                           class="nav-link {{ set_active('form-types') }}">{{ __('messages.form_types') }}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                @endcanany

                {{-- Settings Global -> Hanya Superadmin Global --}}
                @canany(['all-user-activities', 'all-user-sessions'])
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ set_active('settings') }}" href="#sidebar-setting" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar-setting">
                            <i class="ri-settings-3-line"></i> <span data-key="t-settings">{{ __('messages.settings') }}</span>
                        </a>

                        <div class="collapse menu-dropdown {{ show_menu_dropdown('settings') }}" id="sidebar-setting">
                            <ul class="nav nav-sm flex-column">
                                @if (config('core.custom_setting_menu_enabled'))
                                    @if (config('core.custom_setting_menu_view'))
                                        @include(config('core.custom_setting_menu_view'))
                                    @endif
                                @endif

                                @can('browse-settings')
                                    <li class="nav-item">
                                        <a href="{{ route('settings.system.index') }}"
                                           class="nav-link {{ set_active('settings.system.index') }}">
                                            {{ __('messages.general_settings') }}
                                        </a>
                                    </li>
                                @endcan
                                @if(auth()->user()->can('all-user-activities'))
                                    <li class="nav-item">
                                        <a href="{{ route('settings.all-user-activities') }}" class="nav-link {{ set_active('settings.all-user-activities') }}">{{ __('messages.all_user_activities') }}</a>
                                    </li>
                                @endif
                                @if(auth()->user()->can('all-user-sessions'))
                                    <li class="nav-item">
                                        <a href="{{ route('settings.all-user-sessions') }}" class="nav-link {{ set_active('settings.all-user-sessions') }}">{{ __('messages.all_user_sessions') }}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                @endcanany

                {{-- Data Master -> Superadmin Mitra dan Superadmin Global --}}
                @php
                    $user = auth()->user();
                    $isOrgSuperadmin = $user
                        ? $user->organizationMemberships()
                            ->where('is_primary', true)
                            ->where('level_slug', 'like', '%-superadmin')
                            ->exists()
                        : false;
                @endphp
                @if($user && ($user->can('read-data-master') || $isOrgSuperadmin))
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ set_active('master-data') }}" href="{{ route('master-data.index') }}">
                            <i class="ri-database-2-line"></i> <span data-key="t-master-data">{{ __('messages.data_master') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- App Menu End -->
