@use(App\Settings\SystemPreferences)

<nav class="navbar navbar-expand-lg navbar-landing fixed-top" id="navbar">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" class="card-logo card-logo-dark"
                 alt="logo dark" height="100">
            <img src="{{ asset(app(SystemPreferences::class)->logo_lg) }}" class="card-logo card-logo-light"
                 alt="logo light" height="100">
        </a>
        <button class="navbar-toggler py-0 fs-20 text-body" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
            <i class="mdi mdi-menu"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mx-auto mt-2 mt-lg-0" id="navbar-example">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about-us">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#plans">Plans</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>
            </ul>

            @guest
                <div>
                    <a href="{{ route('login') }}" class="btn btn-ghost-primary fw-medium">
                        {{ __('messages.login') }}
                    </a>
                    <a href="{{ url(config('core.register_link')) }}"
                       class="btn btn-primary">{{ __('messages.register') }}</a>
                </div>
            @endguest

            @auth
                <div>
                    <a href="{{ route('dashboard') }}" class="btn btn-primary me-1">{{ __('messages.dashboard') }}</a>
                    <a class="btn btn-primary" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ri-logout-box-r-line align-bottom me-1"></i>
                        {{ __('Logout') }}
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            @endauth
        </div>
    </div>
</nav>
