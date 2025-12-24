<div class="dropdown dropstart position-static">
    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ri-more-fill align-middle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @can('read-forms')
            <li>
                <a href="{{ route('forms.show', $row->id) }}" class="dropdown-item">
                    <i class="ri-eye-fill align-bottom me-2 text-muted"></i> {{ __('messages.view') }}
                </a>
            </li>
        @endcan
        @can('edit-forms')
            <li>
                <a href="{{ route('forms.edit', $row->id) }}" class="dropdown-item edit-item-btn">
                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> {{ __('messages.edit') }}
                </a>
            </li>
        @endcan
        @can('add-submissions')
            <li>
                <a href="{{ route('forms.submissions.create', $row->id) }}" class="dropdown-item edit-item-btn">
                    <i class="ri-share-forward-fill align-bottom me-2 text-muted"></i> {{ __('messages.view_form') }}
                </a>
            </li>
        @endcan
        @can('browse-submissions')
            <li>
                <a href="{{ route('forms.submissions.index', $row->id) }}" class="dropdown-item edit-item-btn">
                    <i class="ri-send-plane-fill align-bottom me-2 text-muted"></i> {{ __('messages.submissions') }}
                </a>
            </li>
        @endcan
        @can('manage-workflow-processes')
            <li>
                <a href="{{ route('forms.processes.index', $row->id) }}"
                   class="dropdown-item edit-item-btn">
                    <i class="ri-flow-chart align-bottom me-2 text-muted"></i> {{ __('messages.workflow_processes') }}
                </a>
            </li>
        @endcan
        @can('delete-forms')
            <li>
                <a class="dropdown-item" role="button" wire:click="$dispatch('triggerDelete',{{ $row->id }})">
                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> {{ __('messages.delete') }}
                </a>
            </li>
        @endcan
    </ul>
</div>
