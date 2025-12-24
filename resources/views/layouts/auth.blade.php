@use(App\Settings\SystemPreferences)
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset(app(SystemPreferences::class)->favicon) }}">

    <title>@yield('title', 'Title') | {{ config('app.name') }}</title>

    @include('partials.styles')
</head>
<body>
    <!-- auth-page wrapper -->
    <div class="auth-page-wrapper py-5 d-flex justify-content-center align-items-center min-vh-100">
        {{-- <div class="bg-overlay"></div> --}}
        <!-- auth-page content -->
        <div class="auth-page-content">
            @yield('content')
        </div>
        <!-- end auth page content -->

        <!-- footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0">© {{ \Carbon\Carbon::now()->year }} {{ config('app.name') }}. {{ __('messages.all_rights_reserved') }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->

    <!-- toast --->
    @include('partials.toasts')

    @include('partials.scripts')

</body>
</html>
