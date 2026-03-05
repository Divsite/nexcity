@php
    $label = method_exists($row, 'statusLabel') ? $row->statusLabel() : ucfirst((string) $row->status);
    $badgeClass = method_exists($row, 'statusBadgeClass') ? $row->statusBadgeClass() : 'badge bg-secondary-subtle text-secondary';
@endphp

<div class="d-flex align-items-center gap-2">
    <span class="{{ $badgeClass }}">{{ $label }}</span>
    @can('edit-mosque-charity-transactions')
        <div class="dropdown">
            <button class="btn btn-sm btn-soft-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                {{ __('messages.update') }}
            </button>
            <ul class="dropdown-menu">
                @if($row->status !== 'paid')
                    <li>
                        <a class="dropdown-item" role="button" wire:click="updateStatus({{ $row->id }}, 'paid')">
                            {{ __('messages.paid') }}
                        </a>
                    </li>
                @endif
                @if($row->status !== 'draft')
                    <li>
                        <a class="dropdown-item" role="button" wire:click="updateStatus({{ $row->id }}, 'draft')">
                            {{ __('messages.draft') }}
                        </a>
                    </li>
                @endif
                @if($row->status !== 'cancelled')
                    <li>
                        <a class="dropdown-item" role="button" wire:click="updateStatus({{ $row->id }}, 'cancelled')">
                            {{ __('messages.cancelled') }}
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    @endcan
</div>
