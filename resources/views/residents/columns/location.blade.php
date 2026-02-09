@php
    $profile = $row->residentProfile;
@endphp
<div class="d-flex flex-column">
    <span class="fw-semibold">{{ $profile?->village?->name ?? '—' }}</span>
    <span class="small text-muted">
        {{ __('messages.rw') }} {{ $profile?->citizensAssociation?->number ?? '—' }}
        /
        {{ __('messages.rt') }} {{ $profile?->neighborhoodAssociation?->number ?? '—' }}
    </span>
    @if($profile?->address_line)
        <span class="small text-muted">{{ $profile->address_line }}</span>
    @endif
</div>
