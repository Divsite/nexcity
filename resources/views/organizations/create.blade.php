@extends('layouts.app')

@section('title', __('messages.create') . ' ' . __('messages.organization'))

@section('content')
    <div v-cloak id="organization-form-app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">{{ __('messages.create') }} {{ __('messages.organization') }}</h5>
                </div>
                <div class="card-body">
                    <form autocomplete="off" novalidate>
                        @include('organizations._form')
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
        window.organizationForm = {!! Js::from($formPayload) !!};
    </script>
    @vite('resources/js/views/organizations/form.js')
@endpush
