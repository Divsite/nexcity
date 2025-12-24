@php
    use App\Utilities\FormSubmissions\TranslationItems;
    use Illuminate\Support\Js;
@endphp

@extends('layouts.app')

@if($submission)
    @section('title', $model->form->name)
    @section('breadcrumbs', Breadcrumbs::render('submissions.edit', $model))
@else
    @section('title', $model->name)
    @section('breadcrumbs', Breadcrumbs::render('forms.submissions.create', $model))
@endif

@section('content')
    <div v-cloak id="form-render">
        <form-render></form-render>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        let model = {{ Js::from($submission ? $model->form : $model) }};
        let data = {{ Js::from($formData) }};
        let formFields = {{ Js::from(form_fields()) }};
        let actionTypes = {{ Js::from(action_types()) }};
        let comparisonOperator = {{ Js::from(comparison_operator()) }};
        let conditionalRuleOperator = {{ Js::from(conditional_rule_operator()) }};
        let dataSourceInput = {{ Js::from(data_source_input())}};
        let appUrl = "{{ config('app.url') }}";
        let flatpickrLocale = {{ Js::from(flatpickr_locale()) }};
        let minMaxOptionList = {{ Js::from(min_max_option_list()) }};

        let formSubmissionId;
        let updateRouteName;

        @if($submission)
            formSubmissionId = {{ $model->id }};
            updateRouteName = "{{ $updateRouteName }}";
        @endif
        <!-- Translation -->
        let trans = {{ Js::from(TranslationItems::get()) }};
    </script>
    <!-- vue -->
    @vite('resources/js/views/forms/render.js')
@endpush
