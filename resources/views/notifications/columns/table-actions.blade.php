<div class="dropdown dropstart position-static">
    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
        <i class="ri-more-fill align-middle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @if(!empty($row->data['redirect_to']))
            <li>
                <a href="{{ $row->data['redirect_to'] }}" class="dropdown-item">
                    <i class="ri-eye-fill align-bottom me-2 text-muted"></i> {{ __('messages.view') }}
                </a>
            </li>
        @endif
        @if(!$row->read_at)
            @can('edit-notifications')
                <li>
                    <a class="dropdown-item" role="button" wire:click="$dispatch('triggerRead', '{{ $row->id }}')">
                        <i class="ri-check-double-line align-bottom me-2 text-muted"></i> {{ __('messages.mark_as_has_read') }}
                    </a>
                </li>
            @endcan
        @endif
        @can('read-notifications')
            <li>
                <a href="{{ route('notifications.show', $row->id) }}" class="dropdown-item edit-item-btn">
                    <i class="ri-eye-fill align-bottom me-2 text-muted"></i> {{ __('messages.view') }}
                </a>
            </li>
        @endcan
        @can('delete-notifications')
            <li>
                <a class="dropdown-item" role="button" wire:click="$dispatch('triggerDelete', '{{ $row->id }}')">
                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> {{ __('messages.delete') }}
                </a>
            </li>
        @endcan
    </ul>
</div>
