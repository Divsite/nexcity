@extends('layouts.app')

@section('title', __('messages.create') . ' ' . __('messages.menu_builder'))

@section('content')
    <div v-cloak id="menu-form-app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">{{ __('messages.create') }} {{ __('messages.menu_builder') }}</h5>
                </div>
                <div class="card-body">
                    <form autocomplete="off" novalidate>
                        @include('settings.menus._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        window.menuForm = {!! Js::from($formPayload) !!};
    </script>
    @vite('resources/js/views/settings/menus/form.js')
@endpush
