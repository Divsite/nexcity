@php
    $total = (int) ($row->recipients_count ?? 0);
    $distributed = (int) ($row->distributed_count ?? 0);
    $redirected = (int) ($row->redirected_count ?? 0);
    $percent = $total > 0 ? (int) round((($distributed + $redirected) / $total) * 100) : 0;
@endphp

<div class="d-flex align-items-center gap-2">
    <div class="progress flex-grow-1" style="height:6px;">
        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%"></div>
    </div>
    <span class="text-muted fs-12">{{ $percent }}%</span>
</div>
