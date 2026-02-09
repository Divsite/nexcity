@php
    $isActive = $row->is_active;
@endphp
<div class="d-flex flex-column">
    <span class="badge {{ $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
        {{ $isActive ? __('messages.active') : __('messages.inactive') }}
    </span>
    <span class="small text-muted">
        {{ __('messages.order') }}: {{ $row->order ?? 0 }}
    </span>
</div>
