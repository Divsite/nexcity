<div class="d-flex flex-column">
    <span class="fw-semibold">{{ $row->category->name ?? __('messages.none') }}</span>
    <div class="small text-muted">
        @if($row->phone)
            <span class="me-2"><i class="ri-phone-line me-1"></i>{{ $row->phone }}</span>
        @endif
        @if($row->website)
            <span class="text-truncate"><i class="ri-global-line me-1"></i>{{ $row->website }}</span>
        @endif
    </div>
</div>
