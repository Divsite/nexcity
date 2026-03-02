@if(!empty($row?->id))
    <div class="dropdown dropstart position-static">
        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ri-more-fill align-middle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            @can('read-mosque-charity-distributions')
                <li>
                    <a href="{{ route('mosque.charity-distributions.show', $row) }}" class="dropdown-item">
                        <i class="ri-eye-line align-bottom me-2 text-muted"></i> {{ __('messages.view') }}
                    </a>
                </li>
            @endcan
            @can('delete-mosque-charity-distributions')
                <li>
                    <a class="dropdown-item" role="button" wire:click="$dispatch('triggerDelete', [{{ $row->id }}])">
                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> {{ __('messages.delete') }}
                    </a>
                </li>
            @endcan
        </ul>
    </div>
@else
    <span class="text-muted">-</span>
@endif
