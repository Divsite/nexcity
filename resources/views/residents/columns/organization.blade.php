@php
    $organization = $row->residentProfile?->organization;
    $typeLabels = \App\Models\Organizations\Organization::typeLabels();
@endphp
<div class="d-flex flex-column">
    <span class="fw-semibold">{{ $organization?->name ?? '—' }}</span>
    <span class="small text-muted">
        {{ $typeLabels[$organization?->type] ?? __('messages.organization_type') }}
    </span>
</div>
