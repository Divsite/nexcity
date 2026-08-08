@extends('layouts.app')

@section('title', __('messages.edit') . ' ' . __('messages.resident'))

@section('content')
    <div v-cloak id="resident-form-app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('messages.edit') }} {{ __('messages.resident') }}</h5>
                    <div class="d-flex gap-2 align-items-center">
                        @if($resident->residentProfile?->qr_token)
                            <a href="{{ route('residents.qr-card', $resident) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ri-qr-code-line me-1"></i>{{ __('messages.print_qr_card') }}
                            </a>
                        @endif
                        <a href="{{ route('residents.index') }}" class="btn btn-link">{{ __('messages.back') }}</a>
                    </div>
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
