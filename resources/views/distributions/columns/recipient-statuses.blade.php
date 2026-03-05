@php
    $total = (int) ($row->recipients_count ?? 0);
    $distributed = (int) ($row->distributed_count ?? 0);
    $failed = (int) ($row->failed_count ?? 0);
    $redirected = (int) ($row->redirected_count ?? 0);
    $rescheduled = (int) ($row->rescheduled_count ?? 0);
    $pending = max(0, $total - ($distributed + $redirected + $failed + $rescheduled));
@endphp

<div class="d-flex flex-wrap gap-2">
    <span class="badge bg-soft-secondary text-secondary">{{ __('messages.pending') }}: {{ $pending }}</span>
    <span class="badge bg-soft-success text-success">{{ __('messages.distributed') }}: {{ $distributed }}</span>
    <span class="badge bg-soft-info text-info">{{ __('messages.redirected') }}: {{ $redirected }}</span>
    <span class="badge bg-soft-danger text-danger">{{ __('messages.failed') }}: {{ $failed }}</span>
    <span class="badge bg-soft-warning text-warning">{{ __('messages.rescheduled') }}: {{ $rescheduled }}</span>
</div>
