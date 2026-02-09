@php
    $profile = $row->residentProfile;
@endphp
<div class="d-flex flex-column">
    <span>{{ $row->phone ?? '—' }}</span>
    <span class="small text-muted">
        {{ __('messages.national_id') ?? 'NIK' }}: {{ $profile?->national_id_number ?? '—' }}
    </span>
    <span class="small text-muted">
        {{ __('messages.family_card_number') ?? 'KK' }}: {{ $profile?->family_card_number ?? '—' }}
    </span>
</div>
