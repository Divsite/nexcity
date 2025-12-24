<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="{{ $theme['layout'] }}" data-layout-width="{{ $theme['width'] }}"  data-topbar="{{ $theme['topbar'] }}" data-sidebar="dark" data-sidebar-size="lg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <title>@yield('title', 'Title') | {{ config('app.name') }}</title>

    @include('partials.styles')
</head>
<body>
<!-- auth-page wrapper -->
<div class="auth-page-wrapper py-5 d-flex justify-content-center align-items-center min-vh-100">

    <!-- auth-page content -->
    <div class="auth-page-content overflow-hidden p-0">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-xl-4 text-center">
                    <div class="error-500 position-relative">
                        <img src="{{ asset('assets/images/error500.png') }}" alt="" class="img-fluid error-500-img error-img" />
                        <h1 class="title text-muted">@yield('code')</h1>
                    </div>
                    <div>
                        <h4 class="mb-4">@yield('message')</h4>
                        <a href="{{ route('dashboard') }}" class="btn btn-success"><i class="mdi mdi-home me-1"></i>{{ __('messages.back_to_home') }}</a>
                    </div>
                </div><!-- end col-->
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end auth-page content -->
    </div>
    <!-- end auth-page-wrapper -->

</body>
</html>
