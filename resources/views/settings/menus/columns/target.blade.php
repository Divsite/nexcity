<div class="d-flex flex-column">
    <span class="fw-semibold">{{ $row->organization->name ?? __('messages.none') }}</span>
    <span class="small text-muted">
        {{ $row->userLevel?->name ?? __('messages.all') }}
    </span>
</div>
