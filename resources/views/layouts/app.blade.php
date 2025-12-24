@use(App\Settings\SystemPreferences)
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="{{ $theme['layout'] }}"
      data-layout-width="{{ $theme['width'] }}" data-layout-style="{{ $theme['layout-style'] }}"
      data-topbar="{{ $theme['topbar'] }}" data-sidebar="dark" data-sidebar-size="lg"
      data-bs-theme="{{ $theme['color-mode'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset(app(SystemPreferences::class)->favicon) }}">

    <title>@yield('title', 'Title') | {{ config('app.name') }}</title>

    @include('partials.styles')
</head>
<body>
    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('partials.header')

        @include('partials.menu')

        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <!-- Page-content -->
            <div class="page-content">
                <!-- container-fluid -->
                <div class="container-fluid">

                    @include('partials.page-title')

                    @yield('content')

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include('partials.footer')

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!-- toast --->
    @include('partials.toasts')

    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    @include('partials.scripts')

</body>
</html>
