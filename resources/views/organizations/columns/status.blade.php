@php
    $isActive = $row->status === 'active';
@endphp
<div>
    <span class="badge {{ $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
        {{ $isActive ? __('messages.active') : __('messages.inactive') }}
    </span>
    <div class="small text-muted mt-1">
        {{ __('messages.updated_at') }}: {{ optional($row->updated_at)->diffForHumans() ?? '—' }}
    </div>
</div>
