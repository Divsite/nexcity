<div class="d-flex flex-row gap-2 align-items-center">
    <i class="{{ $row->icon ?? 'ri-menu-line' }} fs-5 text-muted"></i>
    <div class="d-flex flex-column">
        <span class="fw-semibold">{{ __($row->label) }}</span>
        @if($row->section)
            <span class="small text-muted">{{ __($row->section) }}</span>
        @endif
    </div>
</div>
