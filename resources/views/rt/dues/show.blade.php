@extends('layouts.app')

@section('title', $scheme->name . ' · ' . $scheme->year)

@section('content')
    <div class="row">
        <div class="col-lg-12">
            @include('rt.dues._flash')

            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-1">{{ $scheme->name }} · {{ $scheme->year }}</h5>
                            <div class="text-muted small">
                                @foreach ($scheme->rates as $rate)
                                    {{ $rate->label }}: Rp {{ number_format((float) $rate->amount, 0, ',', '.') }}@if(!$loop->last) · @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('rt.dues') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line align-bottom me-1"></i>{{ __('messages.dues_back_to_schemes') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if ($scheme->programList())
                        <div class="alert alert-light border mb-3">
                            <strong>{{ __('messages.dues_programs') }}:</strong>
                            {{ implode(' · ', $scheme->programList()) }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>{{ __('messages.dues_period') }}</th>
                                <th>{{ __('messages.paid') }}</th>
                                <th>{{ __('messages.dues_collected') }}</th>
                                <th class="text-end">{{ __('messages.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($periods as $period)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $period->label }}</span>
                                        @if ($period->due_date)
                                            <div class="small text-muted">
                                                {{ __('messages.dues_due_date') }}: {{ $period->due_date->translatedFormat('d M Y') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $period->paid_count }} / {{ $period->bills_count }}
                                        @if ($period->waived_count)
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $period->waived_count }} {{ __('messages.dues_waived') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">
                                        Rp {{ number_format((float) ($period->collected_amount ?? 0), 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('rt.dues.period', $period) }}" class="btn btn-sm btn-soft-primary">
                                            {{ __('messages.dues_bill_list') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
