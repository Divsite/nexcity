@extends('layouts.app')

@section('title', __('messages.distributions'))

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div>
                            <h5 class="mb-1">{{ $distribution->title ?? __('messages.distributions') }}</h5>
                            <div class="text-muted small">
                                {{ __('messages.distribution_class') }}: {{ $distributionClass?->source?->name ?? '-' }}
                                · {{ __('messages.year') }}: {{ $distribution->year ?? '-' }}
                                · {{ __('messages.status') }}: {{ $distribution->status ? __('messages.' . $distribution->status) : '-' }}
                            </div>
                        </div>
                        <a href="{{ route('mosque.charity-transactions.index') }}#charity-tab-distributions"
                           class="btn btn-soft-secondary">
                            <i class="ri-arrow-left-line align-bottom me-1"></i> {{ __('messages.back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.location') }}</div>
                                <div class="fw-semibold">
                                    {{ $distribution->neighborhoodAssociation?->name ?? __('messages.none') }}
                                    @if($distribution->citizensAssociation)
                                        <span class="text-muted">/ {{ $distribution->citizensAssociation?->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.officers') }}</div>
                                <div class="fw-semibold">
                                    @if($distribution->officers->isEmpty())
                                        <span class="text-muted">{{ __('messages.none') }}</span>
                                    @else
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($distribution->officers as $officer)
                                                <span class="badge bg-soft-primary text-primary">
                                                    {{ $officer->officer?->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <livewire:distributions.distribution-recipient-table :distribution-id="$distribution->id" theme="bootstrap-5" />
                </div>
            </div>
        </div>
    </div>
@endsection
