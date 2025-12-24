@extends('layouts.front')

@section('title', config('app.name'))

@section('content')
    @include('pages.partials.hero-section')

    {{-- @include('pages.partials.info-section') --}}

    {{-- @include('pages.partials.feature-section') --}}

    {{-- @include('pages.partials.text-section') --}}

    {{-- @include('pages.partials.footer') --}}
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/landing.init.js') }}"></script>
@endpush
