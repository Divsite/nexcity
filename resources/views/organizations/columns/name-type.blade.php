@php
    $typeLabels = \App\Models\Organizations\Organization::typeLabels();
    $typeLabel = $typeLabels[$row->type] ?? \Illuminate\Support\Str::headline($row->type);
@endphp
<div class="d-flex flex-column">
    <span class="fw-semibold">{{ $row->name }}</span>
    <div class="d-flex flex-wrap gap-2 align-items-center small text-muted mt-1">
        <span class="badge bg-light text-dark border">{{ $typeLabel }}</span>
        @if($row->slug)
            <span class="text-truncate">/{{ $row->slug }}</span>
        @endif
        @if($row->email)
            <span class="text-truncate">
                <i class="ri-mail-line me-1"></i>{{ $row->email }}
            </span>
        @endif
    </div>
</div>
