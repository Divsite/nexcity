@extends('layouts.app')

@section('title', __('messages.charity_transactions'))

@section('content')
    <div v-cloak id="charity-transaction-form" class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">{{ __('messages.create') }} {{ __('messages.charity_transactions') }}</h5>
                </div>
                <div class="card-body">
                    @include('charities._form')
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.charityTransactionForm = @json($formPayload);
    </script>
    @vite('resources/js/views/charities/form.js')
@endpush
