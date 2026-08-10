@extends('layouts.app')

@section('title', __('messages.dues_bill_list') . ' · ' . $period->label)

@section('content')
    <div class="row">
        <div class="col-lg-12">
            @include('rt.dues._flash')

            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-1">
                                {{ $period->scheme->name }} · {{ $period->label }}
                            </h5>
                            <div class="text-muted small">
                                {{ $period->paid_count }} / {{ $period->bills_count }} {{ __('messages.paid') }}
                                · {{ $period->waived_count }} {{ __('messages.dues_waived') }}
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('rt.dues.show', $period->rt_dues_scheme_id) }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line align-bottom me-1"></i>{{ __('messages.back') ?? 'Kembali' }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <livewire:dues.rt-dues-bill-table :period-id="$period->id" theme="bootstrap-5"/>
                </div>
            </div>
        </div>
    </div>
@endsection
