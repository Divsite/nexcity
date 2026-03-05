@if(!empty($row?->id))
    <div class="dropdown dropstart position-static">
        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ri-more-fill align-middle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            @can('edit-mosque-charity-transactions')
                <li>
                    <a href="{{ route('mosque.charity-transactions.edit', $row->id) }}" class="dropdown-item">
                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> {{ __('messages.edit') }}
                    </a>
                </li>
            @endcan
            @can('print-mosque-charity-transactions')
                @if($row->status === 'paid')
                    <li>
                        <a href="{{ route('mosque.charity-transactions.invoice', $row->id) }}" target="_blank" class="dropdown-item">
                            <i class="ri-printer-line align-bottom me-2 text-muted"></i> {{ __('messages.print_invoice') }}
                        </a>
                    </li>
                @endif
            @endcan
            @can('delete-mosque-charity-transactions')
                <li>
                    <a class="dropdown-item" role="button" wire:click="$dispatch('triggerDelete',{{ $row->id }})">
                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> {{ __('messages.delete') }}
                    </a>
                </li>
            @endcan
        </ul>
    </div>
@else
    <span class="text-muted">-</span>
@endif
