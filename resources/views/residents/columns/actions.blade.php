<div class="dropdown dropstart position-static">
    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ri-more-fill align-middle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @canany(['edit-residents', 'edit-rt-residents'])
            <li>
                <a href="{{ route('residents.edit', $row->id) }}" class="dropdown-item">
                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> {{ __('messages.edit') }}
                </a>
            </li>
        @endcanany
        @canany(['delete-residents', 'delete-rt-residents'])
            <li>
                <a class="dropdown-item" role="button" wire:click="$dispatch('triggerDelete',{{ $row->id }})">
                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> {{ __('messages.delete') }}
                </a>
            </li>
        @endcanany
    </ul>
</div>
