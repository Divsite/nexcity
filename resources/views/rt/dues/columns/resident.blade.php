<div class="d-flex flex-column">
    <span class="fw-semibold">{{ $row->resident?->name ?? '—' }}</span>
    @if($row->resident?->phone)
        <span class="small text-muted">{{ $row->resident->phone }}</span>
    @endif
    @if($row->paid_at)
        <span class="small text-muted">
            {{ __('messages.paid') }}: {{ $row->paid_at->translatedFormat('d M Y') }}
            @if($row->payment_method) · {{ $row->payment_method }} @endif
        </span>
    @endif
</div>
