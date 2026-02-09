@extends('layouts.app')

@section('title', __('messages.menu_builder'))

@section('content')
    <div id="app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0 flex-grow-1">{{ __('messages.menu_builder') }}</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-2 flex-wrap">
                                @can('edit-user-menus')
                                    <form action="{{ route('menus.flush') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary">
                                            <i class="ri-refresh-line align-bottom me-1"></i> {{ __('messages.flush_cache') }}
                                        </button>
                                    </form>
                                    <a href="{{ route('menus.create') }}" class="btn btn-success">
                                        <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.create') }}
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <livewire:menus.menu-table theme="bootstrap-5"/>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
