@php
    $status = $row->status ?? null;
    $map = [
        'pending' => ['class' => 'bg-soft-warning text-warning', 'label' => __('messages.pending')],
        'distributed' => ['class' => 'bg-soft-success text-success', 'label' => __('messages.distributed')],
        'failed' => ['class' => 'bg-soft-danger text-danger', 'label' => __('messages.failed')],
        'rescheduled' => ['class' => 'bg-soft-info text-info', 'label' => __('messages.rescheduled')],
    ];
    $badge = $map[$status] ?? ['class' => 'bg-soft-secondary text-secondary', 'label' => $status ? __('messages.' . $status) : '-'];
@endphp

<span class="badge {{ $badge['class'] }}">
    {{ $badge['label'] }}
</span>
