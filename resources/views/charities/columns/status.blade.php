@php
    $label = method_exists($row, 'statusLabel') ? $row->statusLabel() : ucfirst((string) $row->status);
    $badgeClass = method_exists($row, 'statusBadgeClass') ? $row->statusBadgeClass() : 'badge bg-secondary-subtle text-secondary';
@endphp

<span class="{{ $badgeClass }}">{{ $label }}</span>
