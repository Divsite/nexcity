<div class="dropdown dropstart position-static">
    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
        <i class="ri-more-fill align-middle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a href="{{ route('submission.show', ['id' => $row->id, 'type' => $this->type]) }}"
               class="dropdown-item">
                <i class="ri-eye-fill align-bottom me-2 text-muted"></i> {{ __('messages.view') }}
            </a>
        </li>
        @if(($row->currentStatus && $row->currentStatus->is_revert_submitter) && $row->created_by == auth()->user()->id)
            <li>
                <a href="{{ route('submission.edit', $row->id) }}" class="dropdown-item edit-item-btn">
                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> {{ __('messages.edit') }}
                </a>
            </li>
        @endif
    </ul>
</div>
