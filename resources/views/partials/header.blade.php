@use(App\Utilities\Themes\Theme)
@use(Illuminate\Support\Js)
@use(App\Settings\SystemPreferences)

<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ route('dashboard') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ asset(app(SystemPreferences::class)->logo_sm) }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" alt="" height="17">
                        </span>
                    </a>

                    <a href="{{ route('dashboard') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ asset(app(SystemPreferences::class)->logo_sm) }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" alt="" height="17">
                        </span>
                    </a>
                </div>

                <button type="button"
                        class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger shadow-none"
                        id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>

            <div id="header-end" v-cloak class="d-flex align-items-center">
                <!-- Languages -->
                @if(config('core.language_switcher_enabled'))
                    <div class="dropdown ms-1 topbar-head-dropdown header-item">
                        <button type="button"
                                class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle shadow-none"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            @if(App::getLocale() == 'ms')
                                <img src="{{ asset('assets/images/flags/my.svg') }}" alt="Header Language" height="20"
                                     class="rounded">
                            @else
                                <img src="{{ asset('assets/images/flags/gb.svg') }}" alt="Header Language" height="20"
                                     class="rounded">
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- item-->
                            <a href="{{ route('change-locale', 'en') }}" class="dropdown-item notify-item language py-2"
                               data-lang="en" title="English">
                                <img src="{{ asset('assets/images/flags/gb.svg') }}" alt="user-image"
                                     class="me-2 rounded"
                                     height="18">
                                <span class="align-middle">English</span>
                            </a>
                            <a href="{{ route('change-locale', 'ms') }}" class="dropdown-item notify-item language py-2"
                               data-lang="ms" title="English">
                                <img src="{{ asset('assets/images/flags/my.svg') }}" alt="user-image"
                                     class="me-2 rounded"
                                     height="18">
                                <span class="align-middle">Malay</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Light/dark mode -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button"
                            class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode shadow-none"
                            @click="toggleThemeMode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                <!-- Notification -->
                @include('partials.notification')

                <!-- User -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn shadow-none" id="page-header-user-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user"
                                 src="{{ asset(\App\Models\Users\User::AVATAR_PATH . auth()->user()->avatar) }}"
                                 alt="{{ __('messages.avatar') }}">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route(config('core.profile_route_name')) }}">
                            <i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">{{ __('messages.my_account') }}</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle" data-key="t-logout">{{ __('Logout') }}</span>
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- removeNotificationModal -->
<div id="removeNotificationModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="NotificationModalbtn-close"></button>
            </div>
            <div class="modal-body"></div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

@push('scripts')
    <script>
        window.themeType = {{ Js::from(Theme::themeType()) }};
    </script>
    <!-- vue -->
    @vite('resources/js/views/partials/header-end.js')
@endpush
