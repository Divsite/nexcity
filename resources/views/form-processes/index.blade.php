@php
    use App\Models\FormProcesses\FormProcessStatus;
    use App\Models\FormProcesses\FormProcess;
    use App\Utilities\FormProcesses\TranslationItems;
    use Illuminate\Support\Js;
@endphp

@extends('layouts.app')

@section('title', __('messages.workflow_processes'))

@section('breadcrumbs', Breadcrumbs::render('forms.processes.index', $model))

@section('content')
    <div id="app" v-cloak class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">{{ __('messages.workflow_processes') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <workflow-process :trans="trans" :form-id="formId"></workflow-process>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush

@push('scripts')
    <script>
        window.formId = "{{ $model->id }}";
        window.statuses = {{ Js::from($statuses) }};
        window.processes = {{ Js::from($processes) }};
        window.defaultSubmissionStatus = "{{ $model->defaultStatus->name ?? '' }}";
        window.statusList = {{ Js::from(FormProcessStatus::statusList()) }};
        window.statusItems = {{ Js::from(FormProcessStatus::statusItems()) }};
        window.revertEndProcess = {{ Js::from(FormProcess::revertEndProcess()) }};
        window.roles = {{ Js::from($roles) }};
        window.decisionTypeList = {{ Js::from(FormProcess::decisionTypeList()) }};
        window.decisionTypeItems = {{ Js::from(FormProcess::decisionTypeItems()) }};
        window.trans = {{ Js::from(TranslationItems::get()) }}
    </script>
    <!-- vue -->
    @vite('resources/js/views/form-processes/index.js')
@endpush
