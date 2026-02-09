@extends('layouts.app')

@section('title', __('messages.create') . ' ' . __('messages.resident'))

@section('content')
    <div v-cloak id="resident-form-app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">{{ __('messages.create') }} {{ __('messages.resident') }}</h5>
                </div>
                <div class="card-body">
                    <form autocomplete="off" novalidate>
                        @include('residents._form')
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
        window.residentForm = {!! Js::from($formPayload) !!};
    </script>
    @vite('resources/js/views/residents/form.js')
@endpush
