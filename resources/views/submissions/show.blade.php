@php
    use App\Utilities\Submissions\TranslationItems;
@endphp

@extends('layouts.app')

@section('title', $model->form->name)

@section('breadcrumbs', Breadcrumbs::render('my-submissions.show', $model, $parentBreadcrumbs))

@section('content')
    <div id="app" v-cloak>
        <submission-render></submission-render>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        window.model = {{ Js::from($model) }};
        window.trans = {{ Js::from(TranslationItems::get()) }}
    </script>
    <!-- vue -->
    @vite('resources/js/views/submissions/show.js')
@endpush
