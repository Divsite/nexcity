<div class="d-flex flex-column">
    <span class="fw-semibold">{{ $row->village->name ?? '—' }}</span>
    <span class="small text-muted">
        {{ collect([$row->district->name ?? null, $row->city->name ?? null, $row->province->name ?? null])->filter()->implode(', ') }}
    </span>
    <span class="small text-muted">
        {{ __('messages.rw') }} {{ $row->citizensAssociation->number ?? '—' }}
        /
        {{ __('messages.rt') }} {{ $row->neighborhoodAssociation->number ?? '—' }}
    </span>
</div>
