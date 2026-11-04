@extends('layouts.app')

@section('title', __('messages.citizen_dues'))

@section('content')
    <div class="row">
        <div class="col-lg-12">
            @include('rt.dues._flash')
        </div>

        @capability('add-rt-dues')
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header border-bottom-dashed">
                        <h5 class="card-title mb-0">{{ __('messages.dues_open_scheme') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('rt.dues.store') }}" id="dues-scheme-form">
                            @csrf

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="name">{{ __('messages.dues_scheme_name') }}</label>
                                    <input id="name" type="text" name="name" class="form-control"
                                           value="{{ old('name', 'Iuran Bulanan') }}"
                                           placeholder="Iuran Bulanan / Iuran HUT RI" required>
                                </div>

                                <div class="col-7">
                                    <label class="form-label" for="type">{{ __('messages.type') ?? 'Jenis' }}</label>
                                    <select id="type" name="type" class="form-select" required>
                                        <option value="monthly" @selected(old('type', 'monthly') === 'monthly')>
                                            {{ __('messages.dues_type_monthly') }}
                                        </option>
                                        <option value="seasonal" @selected(old('type') === 'seasonal')>
                                            {{ __('messages.dues_type_seasonal') }}
                                        </option>
                                    </select>
                                </div>

                                <div class="col-5">
                                    <label class="form-label" for="year">{{ __('messages.year') }}</label>
                                    {{-- A select, not a free number field. flatpickr has no
                                         year-only mode, and typing "2027" here would put twelve
                                         bills on every resident's phone for a year that has not
                                         started. Offering only what is allowed beats validating
                                         after the fact. --}}
                                    <select id="year" name="year" class="form-select" required>
                                        @foreach ($selectableYears as $year)
                                            <option value="{{ $year }}" @selected((int) old('year', now()->year) === $year)>
                                                {{ $year }}@if($year === now()->year) ({{ __('messages.this_year') ?? 'tahun ini' }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="due_date">{{ __('messages.dues_due_date') }}</label>
                                    <input id="due_date" type="text" name="due_date" class="form-control flatpickr-date"
                                           value="{{ old('due_date') }}" placeholder="{{ __('messages.optional') ?? 'Opsional' }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">{{ __('messages.dues_rates') }}</label>

                                    {{-- Rows, not columns: an RT with three golongan adds a row
                                         instead of waiting for a schema change. --}}
                                    <div id="dues-rates">
                                        @php
                                            $oldRates = old('rates', [
                                                ['label' => 'Ber KK', 'tier' => 'ber_kk', 'amount' => 20000],
                                                ['label' => 'Tidak Ber KK', 'tier' => 'tanpa_kk', 'amount' => 15000],
                                            ]);
                                            $defaultRate = (int) old('default_rate', 1);
                                        @endphp

                                        @foreach ($oldRates as $i => $rate)
                                            <div class="d-flex gap-2 align-items-center mb-2 dues-rate-row">
                                                <input type="text" name="rates[{{ $i }}][label]" class="form-control form-control-sm"
                                                       value="{{ $rate['label'] ?? '' }}" placeholder="{{ __('messages.dues_rate_label') }}" required>
                                                <input type="hidden" name="rates[{{ $i }}][tier]" value="{{ $rate['tier'] ?? '' }}">
                                                <div class="input-group input-group-sm" style="max-width: 150px;">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" name="rates[{{ $i }}][amount]" class="form-control"
                                                           value="{{ $rate['amount'] ?? 0 }}" min="0" step="500" required>
                                                </div>
                                                <div class="form-check mb-0" title="{{ __('messages.dues_rate_default_hint') }}">
                                                    <input class="form-check-input" type="radio" name="default_rate"
                                                           value="{{ $i }}" @checked($defaultRate === $i) required>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-soft-danger dues-rate-remove">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" class="btn btn-sm btn-soft-secondary" id="dues-add-rate">
                                        <i class="ri-add-line align-bottom me-1"></i>{{ __('messages.dues_add_rate') }}
                                    </button>
                                    <p class="text-muted small mt-2 mb-0">
                                        <i class="ri-radio-button-line align-bottom"></i>
                                        {{ __('messages.dues_rate_default_hint') }}
                                    </p>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="programs">{{ __('messages.dues_programs') }}</label>
                                    <textarea id="programs" name="programs" rows="4" class="form-control"
                                              placeholder="Santunan Sosial&#10;Kain Kafan Gratis&#10;Pengadaan Hansip">{{ old('programs') }}</textarea>
                                    <p class="text-muted small mt-1 mb-0">{{ __('messages.dues_programs_hint') }}</p>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="ri-add-line align-bottom me-1"></i>
                                        {{ __('messages.dues_open_scheme') }}
                                    </button>
                                    <p class="text-muted small mt-2 mb-0">{{ __('messages.dues_reopen_hint') }}</p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcapability

        <div class="@capability('add-rt-dues') col-lg-7 @else col-lg-12 @endcapability">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">
                                {{ __('messages.dues_schemes') }}
                                @if ($organization)
                                    <span class="text-muted fw-normal">· {{ $organization->name }}</span>
                                @endif
                            </h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-2 flex-wrap">
                                @if ($years->count() > 1)
                                    {{-- Dues recur every year, so the list grows without end.
                                         The filter is what keeps last year reachable instead of
                                         buried. --}}
                                    <form method="GET" action="{{ route('rt.dues') }}">
                                        <select name="year" class="form-select form-select-sm"
                                                onchange="this.form.submit()">
                                            <option value="">{{ __('messages.dues_all_years') }}</option>
                                            @foreach ($years as $year)
                                                <option value="{{ $year }}" @selected($selectedYear === $year)>
                                                    {{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif

                                <a href="{{ route('rt.dues.tiers') }}" class="btn btn-soft-primary btn-sm">
                                    <i class="ri-group-line align-bottom me-1"></i>{{ __('messages.dues_tiers') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($schemes->isEmpty())
                        <p class="text-muted mb-0">{{ __('messages.dues_no_schemes') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>{{ __('messages.dues_scheme') }}</th>
                                    <th>{{ __('messages.dues_rates') }}</th>
                                    <th class="text-end">{{ __('messages.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($schemes as $scheme)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $scheme->name }}</span>
                                            <div class="small text-muted">
                                                {{ $scheme->year }} ·
                                                {{ $scheme->isMonthly() ? __('messages.dues_type_monthly') : __('messages.dues_type_seasonal') }}
                                                · {{ $scheme->periods_count }} {{ __('messages.dues_periods_count') }}
                                            </div>
                                        </td>
                                        <td>
                                            @foreach ($scheme->rates as $rate)
                                                <div class="small">
                                                    {{ $rate->label }}:
                                                    <span class="fw-semibold">Rp {{ number_format((float) $rate->amount, 0, ',', '.') }}</span>
                                                    @if ($rate->is_default)
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ __('messages.dues_rate_default') }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('rt.dues.show', $scheme) }}" class="btn btn-sm btn-soft-primary">
                                                {{ __('messages.view') ?? 'Lihat' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.flatpickr) {
                flatpickr('.flatpickr-date', { dateFormat: 'Y-m-d', allowInput: true });
            }

            const list = document.getElementById('dues-rates');
            const addButton = document.getElementById('dues-add-rate');

            // Rows are added client-side so an RT with an unusual number of
            // golongan is not blocked waiting on a deploy.
            addButton?.addEventListener('click', function () {
                const index = list.querySelectorAll('.dues-rate-row').length;
                const row = document.createElement('div');
                row.className = 'd-flex gap-2 align-items-center mb-2 dues-rate-row';
                row.innerHTML = `
                    <input type="text" name="rates[${index}][label]" class="form-control form-control-sm" placeholder="{{ __('messages.dues_rate_label') }}" required>
                    <input type="hidden" name="rates[${index}][tier]" value="golongan_${index}">
                    <div class="input-group input-group-sm" style="max-width: 150px;">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="rates[${index}][amount]" class="form-control" value="0" min="0" step="500" required>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="default_rate" value="${index}">
                    </div>
                    <button type="button" class="btn btn-sm btn-soft-danger dues-rate-remove"><i class="ri-close-line"></i></button>`;
                list.appendChild(row);
            });

            list?.addEventListener('click', function (event) {
                const remove = event.target.closest('.dues-rate-remove');
                if (!remove) return;

                // Never leave the form with zero rates: a scheme without one
                // bills nobody, and the button would look broken.
                if (list.querySelectorAll('.dues-rate-row').length <= 1) return;

                remove.closest('.dues-rate-row').remove();
            });
        });
    </script>
@endpush
