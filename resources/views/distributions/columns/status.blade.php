@php
    $status = $row->status ?? null;
    $map = [
        'pending' => ['class' => 'badge bg-warning-subtle text-warning', 'label' => __('messages.pending')],
        'completed' => ['class' => 'badge bg-success-subtle text-success', 'label' => __('messages.completed')],
        'failed' => ['class' => 'badge bg-danger-subtle text-danger', 'label' => __('messages.failed')],
    ];
    $badge = $map[$status] ?? ['class' => 'badge bg-secondary-subtle text-secondary', 'label' => $status ? __('messages.' . $status) : '-'];
@endphp

<span class="{{ $badge['class'] }}">
    {{ $badge['label'] }}
</span>
