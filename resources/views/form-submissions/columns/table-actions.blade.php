<div class="list-inline hstack gap-2 mb-0">
    @can('read-submissions')
        <a href="{{ route('submissions.show', $row->id) }}" class="text-primary d-inline-block" data-bs-toggle="tooltip"
           data-bs-trigger="hover"
           data-bs-placement="top" title="{{ __('messages.view') }}" data-bs-original-title="{{ __('messages.view') }}">
            <i class="ri-eye-fill fs-16"></i>
        </a>
    @endcan
    @can('edit-submissions')
        <a href="{{ route('submissions.edit', $row->id) }}" class="text-primary d-inline-block" data-bs-toggle="tooltip"
           data-bs-trigger="hover"
           data-bs-placement="top" title="{{ __('messages.edit') }}" data-bs-original-title="{{ __('messages.edit') }}">
            <i class="ri-pencil-fill fs-16"></i>
        </a>
    @endcan
    @can('delete-submissions')
        <a role="button" wire:click="$dispatch('triggerDelete',{{ $row->id }})" class="text-primary d-inline-block"
           data-bs-toggle="tooltip" data-bs-trigger="hover"
           data-bs-placement="top" title="{{ __('messages.delete') }}" data-bs-original-title="{{ __('messages.delete') }}">
            <i class="ri-delete-bin-5-fill fs-16 text-danger"></i>
        </a>
    @endcan
</div>
