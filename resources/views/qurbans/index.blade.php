@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    $isEditingBatch = (bool) $selectedBatch;
    $formAction = $isEditingBatch
        ? route('mosque.qurban.distribution-batches.update', $selectedBatch)
        : route('mosque.qurban.distribution-batches.store');
    $formMode = $isEditingBatch && $selectedBatch->coupons->whereNotNull('qurban_beneficiary_id')->isEmpty() ? 'blank' : 'residents';
    $selectedResidentIds = $isEditingBatch
        ? $selectedBatch->coupons->pluck('beneficiary.resident_id')->filter()->map(fn ($id) => (string) $id)->all()
        : array_map('strval', old('resident_ids', []));
    $selectedOfficerIds = $isEditingBatch
        ? $selectedBatch->officers->pluck('id')->map(fn ($id) => (string) $id)->all()
        : array_map('strval', old('officer_ids', []));
    $couponCountValue = $isEditingBatch ? $selectedBatch->coupons->count() : 100;
@endphp

@section('title', __('messages.qurban_distribution'))

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div>
                            <h5 class="card-title mb-1">{{ __('messages.qurban_distribution') }}</h5>
                            <div class="text-muted small">{{ $organizationName ?? '-' }}</div>
                        </div>
                        @if($selectedBatch)
                            <span class="badge bg-primary-subtle text-primary">
                                {{ __('messages.active_batch') }}: {{ $selectedBatch->title }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#qurban-tab-distribution" type="button" role="tab">
                                {{ __('messages.distribution_batches') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="qurban-tab-distribution" role="tabpanel">
                            @if($selectedBatch)
                                <div class="border rounded p-3 mb-3" id="qurban-scan-card">
                                    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-3">
                                        <div>
                                            <h6 class="mb-1">{{ __('messages.scan_coupon') }}</h6>
                                            <div class="text-muted small">{{ __('messages.scan_coupon_fast_hint') }}</div>
                                        </div>
                                        <span class="badge bg-info-subtle text-info">{{ __('messages.manual_code_primary') }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('mosque.qurban.coupons.scan') }}" id="qurban-scan-form">
                                        @csrf
                                        <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
                                        <div class="row g-2">
                                            <div class="col-lg-8">
                                                <label class="form-label">{{ __('messages.coupon_code') }} / QR</label>
                                                <div class="input-group input-group-lg">
                                                    <input type="text" name="code" id="qurban-coupon-code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" autocomplete="off" placeholder="{{ __('messages.type_coupon_code') }}">
                                                    <button type="button" class="btn btn-outline-primary" id="qurban-start-camera-scan" title="{{ __('messages.scan_coupon') }}">
                                                        <i class="ri-qr-scan-2-line"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">{{ __('messages.manual_coupon_code_hint') }}</div>
                                                @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-lg-4 d-flex align-items-start pt-lg-4">
                                                @can('scan-qurban-coupon')
                                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                                        <i class="ri-check-line align-bottom me-1"></i> {{ __('messages.claim_coupon') }}
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-primary btn-lg w-100" disabled>
                                                        {{ __('messages.claim_coupon') }}
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                        <div class="border rounded p-2 mt-3 d-none" id="qurban-camera-panel">
                                            <video id="qurban-camera-video" class="w-100 rounded bg-dark" playsinline muted style="max-height: 320px;"></video>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span class="text-muted small" id="qurban-camera-status">{{ __('messages.camera_scanner_ready') }}</span>
                                                <button type="button" class="btn btn-sm btn-soft-danger" id="qurban-stop-camera-scan">
                                                    {{ __('messages.stop_scan') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                            @endif

                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                                <div class="row g-2 flex-grow-1">
                                    <div class="col-md-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.total_coupons') }}</div>
                                            <div class="fs-5 fw-semibold" id="stat-total">{{ $summary['total'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.claimed') }}</div>
                                            <div class="fs-5 fw-semibold text-success" id="stat-claimed">{{ $summary['claimed'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.not_claimed') }}</div>
                                            <div class="fs-5 fw-semibold text-warning" id="stat-remaining">{{ $summary['remaining'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.progress') }}</div>
                                            <div class="fs-5 fw-semibold" id="stat-progress">{{ \Illuminate\Support\Number::format($summary['progress'] ?? 0, 0, 2, app()->getLocale()) }}%</div>
                                        </div>
                                    </div>
                                </div>
                                <form method="GET" action="{{ route('mosque.qurban') }}" class="d-flex align-items-end gap-2">
                                    <div>
                                        <label class="form-label">{{ __('messages.year') }}</label>
                                        <select name="year" class="form-select" style="width: 120px" onchange="this.form.submit()">
                                            @for($itemYear = now()->year - 3; $itemYear <= now()->year + 3; $itemYear++)
                                                <option value="{{ $itemYear }}" @selected((int) $year === $itemYear)>{{ $itemYear }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </form>
                            </div>

                            <div class="row g-3">
                                <div class="col-xl-4">
                                    <div class="border rounded p-3 mb-3">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                            <h6 class="mb-0">{{ $isEditingBatch ? __('messages.update') : __('messages.generate_coupons') }}</h6>
                                            @if($isEditingBatch)
                                                <a href="{{ route('mosque.qurban', ['year' => $year, 'create' => 1]) }}" class="btn btn-sm btn-soft-secondary">
                                                    {{ __('messages.reset') }}
                                                </a>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ $formAction }}">
                                            @csrf
                                            @if($isEditingBatch)
                                                @method('PUT')
                                            @endif
                                            <input type="hidden" name="year" id="qurban-form-year" value="{{ old('year', $selectedBatch?->year ?? $year) }}">
                                            <input type="hidden" name="title" id="qurban-form-title" value="{{ old('title', $selectedBatch?->title ?? ('Kupon Daging Qurban ' . $year)) }}">
                                            <input type="hidden" id="qurban-current-batch-id" value="{{ $selectedBatch?->id }}">

                                            <div class="row g-2 mb-3">
                                                <div class="col-7">
                                                    <label class="form-label">{{ __('messages.claim_date') }} <span class="text-danger">*</span></label>
                                                    <input type="date" name="claim_date" class="form-control @error('claim_date') is-invalid @enderror" value="{{ old('claim_date', $selectedBatch?->claim_starts_at?->format('Y-m-d')) }}">
                                                    @error('claim_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-5">
                                                    <label class="form-label">{{ __('messages.claim_time') }} <span class="text-danger">*</span></label>
                                                    <input type="time" name="claim_time" class="form-control @error('claim_time') is-invalid @enderror" value="{{ old('claim_time', $selectedBatch?->claim_starts_at?->format('H:i')) }}">
                                                    @error('claim_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">{{ __('messages.location') }}</h6>
                                                <button type="button" class="btn btn-sm btn-soft-secondary" id="qurban-toggle-advanced-location">
                                                    {{ __('messages.advanced_location') }}
                                                </button>
                                            </div>
                                            <div class="border rounded p-3 mb-3 d-none" id="qurban-advanced-location">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.country') }}</label>
                                                    @if($isEditingBatch)
                                                        <input type="hidden" name="country_id" value="{{ $selectedBatch->country_id }}">
                                                    @endif
                                                    <select name="country_id" id="qurban-country" class="form-select @error('country_id') is-invalid @enderror" @disabled($isEditingBatch)>
                                                        <option value="">{{ __('messages.please_select') }}</option>
                                                        @foreach($countries as $country)
                                                            <option value="{{ $country->id }}" @selected((string) old('country_id', $selectedBatch?->country_id ?? $organization?->country_id) === (string) $country->id)>
                                                                {{ $country->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('country_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.province') }}</label>
                                                    @if($isEditingBatch)
                                                        <input type="hidden" name="province_id" value="{{ $selectedBatch->province_id }}">
                                                    @endif
                                                    <select name="province_id" id="qurban-province" class="form-select @error('province_id') is-invalid @enderror" data-selected="{{ old('province_id', $selectedBatch?->province_id ?? $organization?->province_id) }}" @disabled($isEditingBatch)>
                                                        <option value="">{{ __('messages.please_select') }}</option>
                                                    </select>
                                                    @error('province_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.city') }}</label>
                                                    @if($isEditingBatch)
                                                        <input type="hidden" name="city_id" value="{{ $selectedBatch->city_id }}">
                                                    @endif
                                                    <select name="city_id" id="qurban-city" class="form-select @error('city_id') is-invalid @enderror" data-selected="{{ old('city_id', $selectedBatch?->city_id ?? $organization?->city_id) }}" @disabled($isEditingBatch)>
                                                        <option value="">{{ __('messages.please_select') }}</option>
                                                    </select>
                                                    @error('city_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.district') }}</label>
                                                    @if($isEditingBatch)
                                                        <input type="hidden" name="district_id" value="{{ $selectedBatch->district_id }}">
                                                    @endif
                                                    <select name="district_id" id="qurban-district" class="form-select @error('district_id') is-invalid @enderror" data-selected="{{ old('district_id', $selectedBatch?->district_id ?? $organization?->district_id) }}" @disabled($isEditingBatch)>
                                                        <option value="">{{ __('messages.please_select') }}</option>
                                                    </select>
                                                    @error('district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.village') }}</label>
                                                    @if($isEditingBatch)
                                                        <input type="hidden" name="village_id" value="{{ $selectedBatch->village_id }}">
                                                    @endif
                                                    <select name="village_id" id="qurban-village" class="form-select @error('village_id') is-invalid @enderror" data-selected="{{ old('village_id', $selectedBatch?->village_id ?? $organization?->village_id) }}" @disabled($isEditingBatch)>
                                                        <option value="">{{ __('messages.please_select') }}</option>
                                                    </select>
                                                    @error('village_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.citizens_associations') }}</label>
                                                @if($isEditingBatch)
                                                    <input type="hidden" name="citizens_association_id" value="{{ $selectedBatch->citizens_association_id }}">
                                                @endif
                                                <select name="citizens_association_id" id="qurban-batch-citizens" class="form-select @error('citizens_association_id') is-invalid @enderror" @disabled($isEditingBatch)>
                                                    <option value="">{{ __('messages.please_select') }}</option>
                                                    @foreach($citizensAssociations as $citizens)
                                                        <option value="{{ $citizens->id }}" @selected((string) old('citizens_association_id', $selectedBatch?->citizens_association_id ?? $organization?->citizens_association_id) === (string) $citizens->id)>
                                                            {{ $citizens->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('citizens_association_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.neighborhood_associations') }}</label>
                                                @if($isEditingBatch)
                                                    <input type="hidden" name="neighborhood_association_id" value="{{ $selectedBatch->neighborhood_association_id }}">
                                                @endif
                                                <select name="neighborhood_association_id" id="qurban-batch-neighborhood" class="form-select @error('neighborhood_association_id') is-invalid @enderror" @disabled($isEditingBatch)>
                                                    <option value="">{{ __('messages.please_select') }}</option>
                                                    @foreach($neighborhoodAssociations as $neighborhood)
                                                        <option value="{{ $neighborhood->id }}" data-citizens-id="{{ $neighborhood->citizens_association_id }}" @selected((string) old('neighborhood_association_id', $selectedBatch?->neighborhood_association_id ?? $organization?->neighborhood_association_id) === (string) $neighborhood->id)>
                                                            {{ $neighborhood->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('neighborhood_association_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.generation_mode') }}</label>
                                                <select name="mode" id="qurban-create-mode" class="form-select @error('mode') is-invalid @enderror">
                                                    <option value="residents" @selected(old('mode', $formMode) === 'residents')>{{ __('messages.selected_residents') }}</option>
                                                    <option value="blank" @selected(old('mode', $formMode) === 'blank')>{{ __('messages.blank_coupons') }}</option>
                                                </select>
                                                @error('mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="mb-3 qurban-create-blank-field d-none">
                                                <label class="form-label">{{ __('messages.coupon_count') }} <span class="text-danger">*</span></label>
                                                <input type="number" name="count" min="1" max="1000" class="form-control @error('count') is-invalid @enderror" value="{{ old('count', $couponCountValue) }}">
                                                @error('count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="mb-3 qurban-create-residents-field">
                                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                                    <label class="form-label mb-0">{{ __('messages.residents') }}</label>
                                                    <button type="button" class="btn btn-sm btn-soft-secondary" id="qurban-create-select-all-residents">
                                                        {{ __('messages.select_all') }}
                                                    </button>
                                                </div>
                                                <input type="text" id="qurban-create-resident-search" class="form-control form-control-sm mb-2" placeholder="{{ __('messages.search') }}">
                                                <div class="border rounded p-2" style="max-height: 260px; overflow-y: auto;" id="qurban-create-resident-list">
                                                    <div class="text-muted">{{ __('messages.select_location_first') }}</div>
                                                </div>
                                                <div class="form-text">
                                                    <span id="qurban-create-resident-count">0</span> {{ __('messages.selected') }}
                                                </div>
                                                @error('resident_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                @error('resident_ids.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label class="form-label mb-0">{{ __('messages.officers') }} <span class="text-danger">*</span></label>
                                                    <span class="text-muted small" id="qurban-officer-count">0 {{ __('messages.selected') }}</span>
                                                </div>
                                                <input type="text" id="qurban-officer-search" class="form-control form-control-sm mb-2" placeholder="{{ __('messages.search') }}">
                                                <div class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
                                                    @forelse($officers as $officer)
                                                        <div class="form-check qurban-officer-item mb-2" data-name="{{ Str::lower(($officer['name'] ?? '') . ' ' . ($officer['position'] ?? '')) }}">
                                                            <input class="form-check-input qurban-officer-checkbox" type="checkbox" name="officer_ids[]" value="{{ $officer['id'] }}" id="qurban-officer-{{ $officer['id'] }}" @checked(in_array((string) $officer['id'], $selectedOfficerIds, true))>
                                                            <label class="form-check-label" for="qurban-officer-{{ $officer['id'] }}">
                                                                {{ $officer['name'] }}
                                                                @if(! empty($officer['position']))
                                                                    <span class="text-muted">- {{ $officer['position'] }}</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    @empty
                                                        <div class="text-muted">{{ __('messages.no_data_available') }}</div>
                                                    @endforelse
                                                </div>
                                                @error('officer_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('messages.package_label') }}</label>
                                                    <input type="text" name="package_label" class="form-control @error('package_label') is-invalid @enderror" value="{{ old('package_label', $selectedBatch?->coupons?->first()?->package_label) }}" placeholder="Paket daging">
                                                    @error('package_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('messages.meat_weight') }}</label>
                                                    <div class="input-group @error('meat_weight') is-invalid @enderror">
                                                        <input type="number" step="0.01" min="0" name="meat_weight" class="form-control @error('meat_weight') is-invalid @enderror" value="{{ old('meat_weight', $selectedBatch?->coupons?->first()?->meat_weight) }}">
                                                        <span class="input-group-text">kg</span>
                                                    </div>
                                                    @error('meat_weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.coupon_color') }}</label>
                                                <input type="color" name="coupon_color" class="form-control form-control-color @error('coupon_color') is-invalid @enderror" value="{{ old('coupon_color', $selectedBatch?->coupon_color ?? '#111111') }}">
                                                <div class="form-text">{{ __('messages.coupon_color_hint') }}</div>
                                                @error('coupon_color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.notes') }}</label>
                                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('messages.coupon_notes_placeholder') }}">{{ old('notes', $selectedBatch?->notes ?? __('messages.default_coupon_note')) }}</textarea>
                                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            @can('add-qurban')
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="ri-coupon-3-line align-bottom me-1"></i> {{ $isEditingBatch ? __('messages.update') : __('messages.generate_coupons') }}
                                                </button>
                                            @endcan
                                        </form>
                                    </div>

                                    <div class="border rounded p-3">
                                        <h6 class="mb-3">{{ __('messages.distribution_batches') }}</h6>
                                        <div class="list-group list-group-flush">
                                            @forelse($batches as $batch)
                                                @php
                                                    $isActiveBatch = $selectedBatch?->id === $batch->id;
                                                @endphp
                                                <div class="list-group-item px-0 {{ $isActiveBatch ? 'active px-2 rounded' : '' }}">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <a href="{{ route('mosque.qurban', ['year' => $year, 'batch_id' => $batch->id]) }}"
                                                           class="flex-grow-1 text-decoration-none {{ $isActiveBatch ? 'text-white' : 'text-body' }}">
                                                            <div class="d-flex justify-content-between gap-2">
                                                                <div class="fw-semibold">{{ $batch->title }}</div>
                                                                <span class="badge {{ $isActiveBatch ? 'bg-light text-dark' : 'bg-soft-primary text-primary' }}">
                                                                    {{ $batch->coupons_count }}
                                                                </span>
                                                            </div>
                                                            <div class="{{ $isActiveBatch ? 'text-white-50' : 'text-muted' }} small">
                                                                {{ $batch->distribution_date?->format('d/m/Y') ?? '-' }}
                                                                @if($batch->neighborhoodAssociation || $batch->citizensAssociation)
                                                                    · {{ $batch->neighborhoodAssociation?->name ?? '-' }} / {{ $batch->citizensAssociation?->name ?? '-' }}
                                                                @endif
                                                                · {{ __('messages.claimed') }} {{ $batch->claimed_coupons_count }}/{{ $batch->coupons_count }}
                                                                @if($batch->officers->isNotEmpty())
                                                                    · {{ $batch->officers->pluck('name')->join(', ') }}
                                                                @endif
                                                            </div>
                                                        </a>
                                                        @can('delete-qurban')
                                                            <form method="POST" action="{{ route('mosque.qurban.distribution-batches.destroy', $batch) }}" class="qurban-delete-batch-form">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm {{ $isActiveBatch ? 'btn-light text-danger' : 'btn-soft-danger' }}" title="{{ __('messages.delete') }}">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="text-muted">{{ __('messages.no_data_available') }}</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-8">
                                    @if($selectedBatch)
                                        @php
                                            $batchCoupons = $selectedBatch->coupons;
                                            $claimedCount = $batchCoupons->where('status', \App\Models\Qurbans\QurbanCoupon::STATUS_CLAIMED)->count();
                                            $issuedCount = $batchCoupons->where('status', \App\Models\Qurbans\QurbanCoupon::STATUS_ISSUED)->count();
                                            $cancelledCount = $batchCoupons->where('status', \App\Models\Qurbans\QurbanCoupon::STATUS_CANCELLED)->count();
                                            $expiredCount = $batchCoupons->where('status', \App\Models\Qurbans\QurbanCoupon::STATUS_EXPIRED)->count();
                                            $totalMeatWeight = $batchCoupons->sum(fn ($coupon) => (float) ($coupon->meat_weight ?? 0));
                                        @endphp

                                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                                            <div>
                                                <h6 class="mb-1">{{ __('messages.qurban_coupons') }}</h6>
                                                <div class="text-muted small">
                                                    {{ $selectedBatch->neighborhoodAssociation?->name ?? '-' }} / {{ $selectedBatch->citizensAssociation?->name ?? '-' }}
                                                    · {{ __('messages.created_by') }}: {{ $selectedBatch->createdBy?->name ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <form method="POST" action="{{ route('mosque.qurban.coupon-exports.dispatch-batch', $selectedBatch) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-soft-primary">
                                                        <i class="ri-file-pdf-line align-bottom me-1"></i> {{ __('messages.print_coupons') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('mosque.qurban.coupon-exports.dispatch-all') }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-soft-secondary">
                                                        <i class="ri-file-pdf-line align-bottom me-1"></i> Print Semua Kupon
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table align-middle table-nowrap">
                                                <thead class="table-light">
                                                <tr>
                                                    <th>{{ __('messages.coupon_code') }}</th>
                                                    <th>{{ __('messages.beneficiary') }}</th>
                                                    <th>{{ __('messages.package_label') }}</th>
                                                    <th class="text-end">{{ __('messages.meat_weight') }}</th>
                                                    <th>{{ __('messages.status') }}</th>
                                                    <th>{{ __('messages.scan_result') }}</th>
                                                    <th>{{ __('messages.scanned_by') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @forelse($selectedBatch->coupons as $coupon)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $coupon->coupon_code }}</td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $coupon->beneficiary?->name_snapshot ?? __('messages.blank_coupon') }}</div>
                                                            <div class="text-muted small">
                                                                {{ $coupon->beneficiary?->phone_snapshot ?: __('messages.no_target_recipient') }}
                                                            </div>
                                                        </td>
                                                        <td>{{ $coupon->package_label ?: '-' }}</td>
                                                        <td class="text-end">
                                                            {{ $coupon->meat_weight !== null ? \Illuminate\Support\Number::format((float) $coupon->meat_weight, 2, 2, app()->getLocale()) . ' kg' : '-' }}
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $coupon->status === \App\Models\Qurbans\QurbanCoupon::STATUS_CLAIMED ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                                                {{ __('messages.' . $coupon->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @php($latestClaim = $coupon->claims->sortByDesc('created_at')->first())
                                                            @if($latestClaim)
                                                                <div>{{ __('messages.' . $latestClaim->scan_result) }}</div>
                                                                <div class="text-muted small">{{ $latestClaim->created_at?->format('d/m/Y H:i') }}</div>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $latestClaim?->scanner?->name ?? '-' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">{{ __('messages.no_data_available') }}</td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                                <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="3">
                                                        {{ __('messages.total_coupons') }}: {{ $batchCoupons->count() }}
                                                    </th>
                                                    <th class="text-end">
                                                        {{ \Illuminate\Support\Number::format($totalMeatWeight, 2, 2, app()->getLocale()) }} kg
                                                    </th>
                                                    <th colspan="3">
                                                        {{ __('messages.issued') }}: {{ $issuedCount }}
                                                        · {{ __('messages.claimed') }}: {{ $claimedCount }}
                                                        · {{ __('messages.cancelled') }}: {{ $cancelledCount }}
                                                        · {{ __('messages.expired') }}: {{ $expiredCount }}
                                                    </th>
                                                </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                    @else
                                        <div class="alert alert-info mb-0">
                                            {{ __('messages.create_distribution_batch_first') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bulkMode = document.getElementById('qurban-bulk-mode');
            const bulkBlankFields = document.querySelectorAll('.qurban-bulk-blank-field');
            const bulkResidentFields = document.querySelectorAll('.qurban-bulk-residents-field');
            const advancedToggle = document.getElementById('qurban-toggle-advanced-location');
            const advancedLocation = document.getElementById('qurban-advanced-location');
            const country = document.getElementById('qurban-country');
            const province = document.getElementById('qurban-province');
            const city = document.getElementById('qurban-city');
            const district = document.getElementById('qurban-district');
            const village = document.getElementById('qurban-village');
            const batchCitizens = document.getElementById('qurban-batch-citizens');
            const batchNeighborhood = document.getElementById('qurban-batch-neighborhood');
            const createMode = document.getElementById('qurban-create-mode');
            const createBlankFields = document.querySelectorAll('.qurban-create-blank-field');
            const createResidentFields = document.querySelectorAll('.qurban-create-residents-field');
            const createResidentList = document.getElementById('qurban-create-resident-list');
            const createResidentSearch = document.getElementById('qurban-create-resident-search');
            const createResidentCount = document.getElementById('qurban-create-resident-count');
            const createSelectAllResidents = document.getElementById('qurban-create-select-all-residents');
            const currentBatchId = document.getElementById('qurban-current-batch-id');
            const officerSearch = document.getElementById('qurban-officer-search');
            const officerCount = document.getElementById('qurban-officer-count');
            const residentSearch = document.getElementById('qurban-resident-search');
            const selectAllResidents = document.getElementById('qurban-select-all-residents');
            const scanForm = document.getElementById('qurban-scan-form');
            const codeInput = document.getElementById('qurban-coupon-code');
            const startCamera = document.getElementById('qurban-start-camera-scan');
            const stopCamera = document.getElementById('qurban-stop-camera-scan');
            const cameraPanel = document.getElementById('qurban-camera-panel');
            const cameraVideo = document.getElementById('qurban-camera-video');
            const cameraStatus = document.getElementById('qurban-camera-status');
            let cameraStream = null;
            let cameraTimer = null;
            const initialSelectedResidentIds = @json($selectedResidentIds);

            const locationRoutes = {
                provinces: @json(route('ajax.locations.provinces')),
                cities: @json(route('ajax.locations.cities')),
                districts: @json(route('ajax.locations.districts')),
                villages: @json(route('ajax.locations.villages')),
                citizens: @json(route('ajax.locations.citizens')),
                neighborhoods: @json(route('ajax.locations.neighborhoods')),
                residents: @json(route('mosque.qurban.residents')),
            };

            function setOptions(select, items, selectedValue = '') {
                if (!select) {
                    return;
                }

                select.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = @json(__('messages.please_select'));
                select.appendChild(placeholder);

                (items || []).forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    if (item.citizens_association_id) {
                        option.dataset.citizensId = item.citizens_association_id;
                    }
                    if (String(item.id) === String(selectedValue || '')) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }

            async function fetchJson(url, params = {}) {
                const query = new URLSearchParams(params);
                const response = await fetch(`${url}?${query.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) {
                    return [];
                }
                return response.json();
            }

            function selectedCreateResidentCheckboxes() {
                return Array.from(document.querySelectorAll('.qurban-create-resident-checkbox'))
                    .filter((checkbox) => checkbox.checked && !checkbox.disabled);
            }

            function updateCreateResidentCount() {
                if (createResidentCount) {
                    createResidentCount.textContent = selectedCreateResidentCheckboxes().length;
                }
            }

            function renderCreateResidents(items) {
                if (!createResidentList) {
                    return;
                }

                createResidentList.innerHTML = '';

                if (!items.length) {
                    const empty = document.createElement('div');
                    empty.className = 'text-muted';
                    empty.textContent = @json(__('messages.no_residents_found'));
                    createResidentList.appendChild(empty);
                    updateCreateResidentCount();
                    return;
                }

                items.forEach((resident) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'form-check qurban-create-resident-item mb-2';
                    wrapper.dataset.name = (resident.name || '').toLowerCase();

                    const input = document.createElement('input');
                    input.className = 'form-check-input qurban-create-resident-checkbox';
                    input.type = 'checkbox';
                    input.name = 'resident_ids[]';
                    input.value = resident.id;
                    input.id = `qurban-create-resident-${resident.id}`;
                    input.disabled = Boolean(resident.disabled);
                    input.checked = initialSelectedResidentIds.includes(String(resident.id));
                    input.addEventListener('change', updateCreateResidentCount);

                    const label = document.createElement('label');
                    label.className = 'form-check-label';
                    label.htmlFor = input.id;
                    label.textContent = `${resident.name || '-'} (RT ${resident.rt || '-'} / RW ${resident.rw || '-'})`;

                    if (resident.disabled) {
                        const badge = document.createElement('span');
                        badge.className = 'text-success';
                        badge.textContent = ` - ${@json(__('messages.coupon_already_issued'))}`;
                        label.appendChild(badge);
                    }

                    wrapper.appendChild(input);
                    wrapper.appendChild(label);
                    createResidentList.appendChild(wrapper);
                });

                filterCreateResidents();
                updateCreateResidentCount();
            }

            async function loadCreateResidents() {
                if (!createResidentList || !batchNeighborhood || !batchNeighborhood.value) {
                    renderCreateResidents([]);
                    if (createResidentList) {
                        createResidentList.innerHTML = `<div class="text-muted">${@json(__('messages.select_location_first'))}</div>`;
                    }
                    return;
                }

                createResidentList.innerHTML = `<div class="text-muted">${@json(__('messages.loading'))}</div>`;

                const response = await fetchJson(locationRoutes.residents, {
                    year: document.getElementById('qurban-form-year')?.value || @json($year),
                    batch_id: currentBatchId?.value || '',
                    country_id: country?.value || '',
                    province_id: province?.value || '',
                    city_id: city?.value || '',
                    district_id: district?.value || '',
                    village_id: village?.value || '',
                    citizens_association_id: batchCitizens?.value || '',
                    neighborhood_association_id: batchNeighborhood.value,
                });

                renderCreateResidents(response.data || response || []);
            }

            function filterCreateResidents() {
                const query = (createResidentSearch?.value || '').trim().toLowerCase();
                document.querySelectorAll('.qurban-create-resident-item').forEach((item) => {
                    item.classList.toggle('d-none', query && !(item.dataset.name || '').includes(query));
                });
            }

            function updateOfficerCount() {
                if (!officerCount) {
                    return;
                }
                const count = document.querySelectorAll('.qurban-officer-checkbox:checked').length;
                officerCount.textContent = `${count} ${@json(__('messages.selected'))}`;
            }

            async function hydrateInitialAdvancedLocation() {
                if (!country || !country.value) {
                    return;
                }

                const selectedProvince = province?.dataset.selected || '';
                const selectedCity = city?.dataset.selected || '';
                const selectedDistrict = district?.dataset.selected || '';
                const selectedVillage = village?.dataset.selected || '';

                const provinces = await fetchJson(locationRoutes.provinces, { country_id: country.value });
                setOptions(province, provinces, selectedProvince);

                if (selectedProvince) {
                    const cities = await fetchJson(locationRoutes.cities, { province_id: selectedProvince });
                    setOptions(city, cities, selectedCity);
                }

                if (selectedCity) {
                    const districts = await fetchJson(locationRoutes.districts, { city_id: selectedCity });
                    setOptions(district, districts, selectedDistrict);
                }

                if (selectedDistrict) {
                    const villages = await fetchJson(locationRoutes.villages, { district_id: selectedDistrict });
                    setOptions(village, villages, selectedVillage);
                }
            }

            advancedToggle?.addEventListener('click', function () {
                advancedLocation?.classList.toggle('d-none');
                advancedToggle.textContent = advancedLocation?.classList.contains('d-none')
                    ? @json(__('messages.advanced_location'))
                    : @json(__('messages.hide'));
            });

            country?.addEventListener('change', async function () {
                setOptions(province, []);
                setOptions(city, []);
                setOptions(district, []);
                setOptions(village, []);
                setOptions(batchCitizens, []);
                setOptions(batchNeighborhood, []);
                loadCreateResidents();
                if (country.value) {
                    setOptions(province, await fetchJson(locationRoutes.provinces, { country_id: country.value }));
                }
            });

            province?.addEventListener('change', async function () {
                setOptions(city, []);
                setOptions(district, []);
                setOptions(village, []);
                setOptions(batchCitizens, []);
                setOptions(batchNeighborhood, []);
                loadCreateResidents();
                if (province.value) {
                    setOptions(city, await fetchJson(locationRoutes.cities, { province_id: province.value }));
                }
            });

            city?.addEventListener('change', async function () {
                setOptions(district, []);
                setOptions(village, []);
                setOptions(batchCitizens, []);
                setOptions(batchNeighborhood, []);
                loadCreateResidents();
                if (city.value) {
                    setOptions(district, await fetchJson(locationRoutes.districts, { city_id: city.value }));
                }
            });

            district?.addEventListener('change', async function () {
                setOptions(village, []);
                setOptions(batchCitizens, []);
                setOptions(batchNeighborhood, []);
                loadCreateResidents();
                if (district.value) {
                    setOptions(village, await fetchJson(locationRoutes.villages, { district_id: district.value }));
                }
            });

            village?.addEventListener('change', async function () {
                setOptions(batchCitizens, []);
                setOptions(batchNeighborhood, []);
                loadCreateResidents();
                if (village.value) {
                    setOptions(batchCitizens, await fetchJson(locationRoutes.citizens, { village_id: village.value }));
                }
            });

            batchCitizens?.addEventListener('change', async function () {
                setOptions(batchNeighborhood, []);
                loadCreateResidents();
                if (batchCitizens.value) {
                    setOptions(batchNeighborhood, await fetchJson(locationRoutes.neighborhoods, { citizens_association_id: batchCitizens.value }));
                }
            });

            batchNeighborhood?.addEventListener('change', loadCreateResidents);

            hydrateInitialAdvancedLocation();

            function syncBatchNeighborhoods() {
                if (!batchCitizens || !batchNeighborhood) {
                    return;
                }

                const selectedCitizensId = batchCitizens.value;
                Array.from(batchNeighborhood.options).forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = selectedCitizensId && option.dataset.citizensId !== selectedCitizensId;
                    if (option.hidden && option.selected) {
                        batchNeighborhood.value = '';
                    }
                });
            }

            if (!advancedLocation || advancedLocation.classList.contains('d-none')) {
                batchCitizens?.addEventListener('change', syncBatchNeighborhoods);
                syncBatchNeighborhoods();
            }

            function syncCreateMode() {
                const mode = createMode ? createMode.value : 'residents';
                createBlankFields.forEach((field) => field.classList.toggle('d-none', mode !== 'blank'));
                createResidentFields.forEach((field) => field.classList.toggle('d-none', mode !== 'residents'));
            }

            createMode?.addEventListener('change', syncCreateMode);
            syncCreateMode();

            createResidentSearch?.addEventListener('input', filterCreateResidents);

            createSelectAllResidents?.addEventListener('click', function () {
                const visible = Array.from(document.querySelectorAll('.qurban-create-resident-item'))
                    .filter((item) => !item.classList.contains('d-none'))
                    .map((item) => item.querySelector('.qurban-create-resident-checkbox'))
                    .filter((checkbox) => checkbox && !checkbox.disabled);
                const shouldCheck = visible.some((checkbox) => !checkbox.checked);
                visible.forEach((checkbox) => {
                    checkbox.checked = shouldCheck;
                });
                updateCreateResidentCount();
            });

            officerSearch?.addEventListener('input', function () {
                const query = officerSearch.value.trim().toLowerCase();
                document.querySelectorAll('.qurban-officer-item').forEach((item) => {
                    item.classList.toggle('d-none', query && !(item.dataset.name || '').includes(query));
                });
            });

            document.querySelectorAll('.qurban-officer-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', updateOfficerCount);
            });
            updateOfficerCount();
            loadCreateResidents();

            function visibleResidentCheckboxes() {
                return Array.from(document.querySelectorAll('.qurban-resident-item'))
                    .filter((item) => !item.classList.contains('d-none'))
                    .map((item) => item.querySelector('.qurban-resident-checkbox'))
                    .filter((checkbox) => checkbox && !checkbox.disabled);
            }

            residentSearch?.addEventListener('input', function () {
                const query = residentSearch.value.trim().toLowerCase();
                document.querySelectorAll('.qurban-resident-item').forEach((item) => {
                    item.classList.toggle('d-none', query && !(item.dataset.name || '').includes(query));
                });
            });

            selectAllResidents?.addEventListener('click', function () {
                const checkboxes = visibleResidentCheckboxes();
                const shouldCheck = checkboxes.some((checkbox) => !checkbox.checked);
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = shouldCheck;
                });
            });

            function syncBulkMode() {
                const mode = bulkMode ? bulkMode.value : 'blank';
                bulkBlankFields.forEach((field) => field.classList.toggle('d-none', mode !== 'blank'));
                bulkResidentFields.forEach((field) => field.classList.toggle('d-none', mode !== 'residents'));
            }

            if (bulkMode) {
                bulkMode.addEventListener('change', syncBulkMode);
                syncBulkMode();
            }

            function showCameraPanel(message) {
                cameraPanel?.classList.remove('d-none');
                if (cameraStatus && message) {
                    cameraStatus.textContent = message;
                }
            }

            function setScannedCode(value, autoSubmit = false) {
                if (!codeInput || !value) {
                    return;
                }

                codeInput.value = value;
                codeInput.dispatchEvent(new Event('input', { bubbles: true }));
                stopCameraScan();

                if (autoSubmit && scanForm) {
                    if (typeof scanForm.requestSubmit === 'function') {
                        scanForm.requestSubmit();
                    } else {
                        scanForm.submit();
                    }
                }
            }

            window.qurbanOnBarcodeScanned = setScannedCode;
            window.onQurbanQrScanned = setScannedCode;
            window.scanCallback = function (value) {
                setScannedCode(value, true);
            };

            function stopCameraScan() {
                if (cameraTimer) {
                    window.clearInterval(cameraTimer);
                    cameraTimer = null;
                }

                if (cameraStream) {
                    cameraStream.getTracks().forEach((track) => track.stop());
                    cameraStream = null;
                }

                if (cameraVideo) {
                    cameraVideo.srcObject = null;
                }

                if (cameraPanel) {
                    cameraPanel.classList.add('d-none');
                }
            }

            async function startCameraScan() {
                if (window.Android && typeof window.Android.scanBarcode === 'function') {
                    window.Android.scanBarcode();
                    return;
                }

                if (window.NexcityBridge && typeof window.NexcityBridge.postMessage === 'function') {
                    window.NexcityBridge.postMessage(JSON.stringify({
                        type: 'qurban_scan_barcode'
                    }));
                    return;
                }

                if (!('BarcodeDetector' in window)) {
                    showCameraPanel(@json(__('messages.camera_scanner_not_supported')));
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showCameraPanel(@json(__('messages.camera_not_available')));
                    return;
                }

                stopCameraScan();

                try {
                    const preferredFormats = [
                        'qr_code',
                        'code_128',
                        'code_39',
                        'code_93',
                        'codabar',
                        'ean_13',
                        'ean_8',
                        'itf',
                        'upc_a',
                        'upc_e',
                    ];
                    let formats = preferredFormats;
                    if (typeof BarcodeDetector.getSupportedFormats === 'function') {
                        const supportedFormats = await BarcodeDetector.getSupportedFormats();
                        formats = preferredFormats.filter((format) => supportedFormats.includes(format));
                    }
                    const detector = new BarcodeDetector({ formats });
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false,
                    });

                    cameraPanel?.classList.remove('d-none');
                    cameraVideo.srcObject = cameraStream;
                    await cameraVideo.play();

                    if (cameraStatus) {
                        cameraStatus.textContent = @json(__('messages.camera_scanner_running'));
                    }

                    cameraTimer = window.setInterval(async function () {
                        if (!cameraVideo || cameraVideo.readyState < 2) {
                            return;
                        }

                        try {
                            const barcodes = await detector.detect(cameraVideo);
                            if (barcodes.length > 0) {
                                setScannedCode(barcodes[0].rawValue, true);
                            }
                        } catch (error) {
                            if (cameraStatus) {
                                cameraStatus.textContent = @json(__('messages.camera_scan_failed'));
                            }
                        }
                    }, 500);
                } catch (error) {
                    stopCameraScan();
                    showCameraPanel(@json(__('messages.camera_scan_failed')));
                }
            }

            document.querySelectorAll('.qurban-delete-batch-form').forEach((form) => {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!window.Swal) {
                        form.submit();
                        return;
                    }

                    const result = await window.Swal.fire({
                        title: window.messages?.are_you_sure || @json(__('messages.are_you_sure')),
                        text: @json(__('messages.qurban_delete_batch_confirmation')),
                        icon: 'warning',
                        showCancelButton: true,
                        customClass: {
                            confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                            cancelButton: 'btn btn-danger w-xs mt-2',
                        },
                        confirmButtonText: window.messages?.yes_delete_it || @json(__('messages.yes_delete_it')),
                        cancelButtonText: window.messages?.cancel || @json(__('messages.cancel')),
                        buttonsStyling: false,
                        showCloseButton: true,
                    });

                    if (result.value || result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            startCamera?.addEventListener('click', startCameraScan);
            stopCamera?.addEventListener('click', stopCameraScan);

        });
    </script>

    <script>
    (function () {
        const statsUrl = '{{ route('mosque.qurban.stats') }}';
        const year     = {{ (int) $year }};

        function updateStats() {
            fetch(statsUrl + '?year=' + year, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var el;
                el = document.getElementById('stat-total');
                if (el) el.textContent = data.total;

                el = document.getElementById('stat-claimed');
                if (el) el.textContent = data.claimed;

                el = document.getElementById('stat-remaining');
                if (el) el.textContent = data.remaining;

                el = document.getElementById('stat-progress');
                if (el) {
                    var pct = parseFloat(data.progress) || 0;
                    el.textContent = (Number.isInteger(pct) ? pct : pct.toFixed(2).replace(/\.?0+$/, '')) + '%';
                }
            })
            .catch(function () { /* silent — network hiccup, retry next tick */ });
        }

        setInterval(updateStats, 5000);
    })();
    </script>
@endpush
