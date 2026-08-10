@extends('layouts.app')

@section('title', __('messages.dues_tiers'))

@section('content')
    <div class="row">
        <div class="col-lg-12">
            @include('rt.dues._flash')

            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-1">
                                {{ __('messages.dues_tiers') }}
                                @if ($organization)
                                    <span class="text-muted fw-normal">· {{ $organization->name }}</span>
                                @endif
                            </h5>
                            <div class="text-muted small">{{ __('messages.dues_tier_hint') }}</div>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('rt.dues') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line align-bottom me-1"></i>{{ __('messages.dues_back_to_schemes') }}
                            </a>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('rt.dues.tiers.update') }}">
                    @csrf
                    @method('PATCH')

                    @capability('edit-rt-dues')
                        <div class="card-body border-bottom bg-light-subtle">
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <label class="mb-0 fw-semibold">{{ __('messages.dues_apply_tier') }}:</label>

                                <select name="tier" class="form-select form-select-sm" style="max-width: 220px;">
                                    <option value="">{{ __('messages.dues_tier_unset') }}</option>
                                    @foreach ($commonTiers as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="ri-check-line align-bottom me-1"></i>{{ __('messages.save') ?? 'Simpan' }}
                                </button>
                            </div>
                        </div>
                    @endcapability

                    <div class="card-body">
                        @if ($residents->isEmpty())
                            <p class="text-muted mb-0">{{ __('messages.no_data') ?? 'Belum ada warga.' }}</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" class="form-check-input" id="dues-select-all"
                                                   title="{{ __('messages.dues_select_all') }}">
                                        </th>
                                        <th>{{ __('messages.resident') }}</th>
                                        <th>{{ __('messages.family_card_number') ?? 'No. KK' }}</th>
                                        <th>{{ __('messages.dues_tier') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($residents as $profile)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input dues-resident"
                                                       name="resident_ids[]" value="{{ $profile->user_id }}">
                                            </td>
                                            <td>
                                                <span class="fw-semibold">{{ $profile->user?->name ?? '—' }}</span>
                                                @if ($profile->user?->phone)
                                                    <div class="small text-muted">{{ $profile->user->phone }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                {{-- Shown as evidence, not as the rule. Most profiles
                                                     have none, which is exactly why the decision is
                                                     stored rather than derived. --}}
                                                @if ($profile->family_card_number)
                                                    <span class="font-monospace small">{{ $profile->family_card_number }}</span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($profile->dues_tier)
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        {{ $commonTiers[$profile->dues_tier] ?? $profile->dues_tier }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        {{ __('messages.dues_tier_unset') }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">{{ $residents->links() }}</div>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const all = document.getElementById('dues-select-all');

            // Only the current page: the checkbox cannot honestly claim to
            // select rows that are not on screen.
            all?.addEventListener('change', function () {
                document.querySelectorAll('.dues-resident').forEach(function (box) {
                    box.checked = all.checked;
                });
            });
        });
    </script>
@endpush
