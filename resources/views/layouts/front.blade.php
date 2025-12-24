@use(App\Settings\SystemPreferences)
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="{{ $theme['layout'] }}"
      data-layout-width="boxed" data-topbar="{{ $theme['topbar'] }}" data-sidebar="dark" data-sidebar-size="lg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset(app(SystemPreferences::class)->favicon) }}">

    <title>@yield('title', 'Title')</title>

    @include('partials.front.styles')
</head>

<body data-bs-spy="scroll" data-bs-target="#navbar-example">
<!-- Begin page -->
<div class="layout-wrapper landing">
    @include('partials.front.header')

    <div class="vertical-overlay" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent.show"></div>

    @yield('content')

    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon landing-back-top" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->
</div>

@include('partials.scripts')

</body>
</html>
