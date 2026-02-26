<div class="d-flex gap-1 flex-wrap justify-content-end">
    @if($row->status !== 'distributed')
        <button type="button"
                class="btn btn-sm btn-soft-success"
                wire:click="markDistributed({{ $row->id }})">
            {{ __('messages.distributed') }}
        </button>
    @endif
    <button type="button"
            class="btn btn-sm btn-soft-danger"
            wire:click="openStatusModal({{ $row->id }}, 'failed')">
        {{ __('messages.not_distributed') }}
    </button>
    <button type="button"
            class="btn btn-sm btn-soft-warning"
            wire:click="openStatusModal({{ $row->id }}, 'rescheduled')">
        {{ __('messages.reschedule') }}
    </button>
</div>
