@extends('layouts.app')

@section('title', __('messages.data_master'))

@section('breadcrumbs', Breadcrumbs::render('master-data.index'))

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-1">{{ __('messages.data_master') }}</h5>
                    <p class="text-muted mb-0">{{ __('messages.data_master_help') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <h6 class="text-muted text-uppercase">{{ __('messages.master_data') }}</h6>
        </div>
        @foreach($masterItems as $item)
            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="{{ $item['icon'] }} fs-4 text-primary me-2"></i>
                            <h6 class="mb-0">{{ $item['label'] }}</h6>
                        </div>
                        <button class="btn btn-sm btn-soft-primary" type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#master-data-modal"
                                data-title="{{ $item['label'] }}"
                                data-key="{{ $item['key'] }}">
                            {{ __('messages.open') }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mt-2">
        <div class="col-12">
            <h6 class="text-muted text-uppercase">{{ __('messages.location_master') }}</h6>
        </div>
        @foreach($locationItems as $item)
            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="{{ $item['icon'] }} fs-4 text-success me-2"></i>
                            <h6 class="mb-0">{{ $item['label'] }}</h6>
                        </div>
                        <button class="btn btn-sm btn-soft-success" type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#master-data-modal"
                                data-title="{{ $item['label'] }}"
                                data-key="{{ $item['key'] }}">
                            {{ __('messages.open') }}
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="modal fade" id="master-data-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="master-data-modal-title">{{ __('messages.data_master') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center text-muted py-5">
                        <i class="ri-database-2-line fs-1 d-block mb-2"></i>
                        <p class="mb-0">{{ __('messages.master_data_modal_placeholder') }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const masterDataModal = document.getElementById('master-data-modal');
        if (masterDataModal) {
            masterDataModal.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }
                const title = trigger.getAttribute('data-title') || '{{ __('messages.data_master') }}';
                const modalTitle = masterDataModal.querySelector('#master-data-modal-title');
                if (modalTitle) {
                    modalTitle.textContent = title;
                }
            });
        }
    </script>
@endpush
