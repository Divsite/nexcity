@php($rowId = $row?->getKey())
<div class="d-flex flex-wrap gap-1">
    @can('read-mosque-charity-distribution-recipients')
        <button type="button"
                class="btn btn-sm btn-soft-info"
                wire:click="openViewModal({{ $rowId }})">
            <i class="ri-image-line me-1"></i>{{ __('messages.view') }}
        </button>
    @endcan

    @can('edit-mosque-charity-distribution-recipients')
        <div class="dropdown dropstart position-static">
            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ri-more-fill align-middle"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if($row->status !== 'distributed')
                    <li>
                        <a class="dropdown-item" role="button" wire:click="openStatusModal({{ $rowId }}, 'distributed')">
                            <i class="ri-checkbox-circle-line align-bottom me-2 text-muted"></i>{{ __('messages.distributed') }}
                        </a>
                    </li>
                @endif
                <li>
                    <a class="dropdown-item" role="button" wire:click="openStatusModal({{ $rowId }}, 'failed')">
                        <i class="ri-close-circle-line align-bottom me-2 text-muted"></i>{{ __('messages.not_distributed') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" role="button" wire:click="openStatusModal({{ $rowId }}, 'rescheduled')">
                        <i class="ri-time-line align-bottom me-2 text-muted"></i>{{ __('messages.reschedule') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" role="button" wire:click="openStatusModal({{ $rowId }}, 'redirected')">
                        <i class="ri-share-forward-line align-bottom me-2 text-muted"></i>{{ __('messages.redirected') }}
                    </a>
                </li>
            </ul>
        </div>
    @endcan
</div>
