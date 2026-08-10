@php
    // "Dibebaskan" is deliberately not "Lunas": the household did not pay, and
    // the books should not read as though they did.
    $badge = match ($row->status) {
        'paid'   => ['bg-success-subtle text-success', __('messages.paid')],
        'waived' => ['bg-info-subtle text-info', __('messages.dues_waived')],
        default  => ['bg-warning-subtle text-warning', __('messages.pending')],
    };
@endphp
<span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
