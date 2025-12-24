<div class="dropdown dropstart position-static">
    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
        <i class="ri-more-fill align-middle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @can('read-form-types')
            <li>
                <a href="{{ route('form-types.show', $row->id) }}" class="dropdown-item">
                    <i class="ri-eye-fill align-bottom me-2 text-muted"></i> {{ __('messages.view') }}
                </a>
            </li>
        @endcan
        @can('edit-form-types')
            <li>
                <a href="{{ route('form-types.edit', ['form_type' => $row->id]) }}"
                   class="dropdown-item edit-item-btn">
                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> {{ __('messages.edit') }}
                </a>
            </li>
        @endcan
        @can('delete-form-types')
            <li>
                <a class="dropdown-item" role="button" wire:click="$dispatch('triggerDelete',{{ $row->id }})">
                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> {{ __('messages.delete') }}
                </a>
            </li>
        @endcan
    </ul>
</div>


