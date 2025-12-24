@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-xxl-3 col-xl-3 col-md-12">
            @include('settings.partials.side-menu')
        </div>

        <div class="col-xxl-9 col-xl-9 col-md-12">
            @yield('setting-content')
        </div>
    </div>
@endsection
