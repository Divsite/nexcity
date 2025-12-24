@use(App\Settings\SystemPreferences)

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
                <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" alt="" height="40">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset(app(SystemPreferences::class)->logo_sm) }}" alt="" height="30">
            </span>
            <span class="logo-lg">
                <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" alt="" height="40">
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

                <li class="nav-item">
                    <a class="nav-link menu-link {{ set_active('dashboard') }}" href="{{ route('dashboard') }}">
                        <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">{{ __('messages.dashboard') }}</span>
                    </a>
                </li>

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

                @include('partials.menu-block', ['key' => 'ssblog'])

                @include('partials.menu-block', ['key' => 'farms'])

                @include('partials.menu-block', ['key' => 'legals'])

                @include('partials.menu-block', ['key' => 'products'])

                @include('partials.menu-block', ['key' => 'automations'])

                @if (config('core.custom_module_menu_enabled'))
                    @if (config('core.custom_module_menu_view'))
                        @include(config('core.custom_module_menu_view'))
                    @endif
                @endif

                @canany(['browse-users', 'browse-groups'])
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

                @canany(['browse-roles', 'read-roles', 'edit-roles', 'add-roles', 'delete-role', 'browse-permissions', 'read-permissions', 'all-user-activities', 'all-user-sessions'])
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
                                @if(auth()->user()->can('browse-roles') && auth()->user()->can('edit-roles'))
                                    <li class="nav-item">
                                        <a href="{{ route('settings.roles.index') }}" class="nav-link {{ set_active('settings.roles') }}">{{ __('messages.roles') }}</a>
                                    </li>
                                @endif
                                @if(auth()->user()->can('browse-permissions'))
                                    <li class="nav-item">
                                        <a href="{{ route('settings.permissions.index') }}" class="nav-link {{ set_active('settings.permissions') }}">{{ __('messages.permissions') }}</a>
                                    </li>
                                @endif
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
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- App Menu End -->
