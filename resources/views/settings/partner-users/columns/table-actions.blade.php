@php
    $user = auth()->user();
    $isOrgSuperadmin = $user
        ? $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', '%-superadmin')
            ->exists()
        : false;
@endphp

<div class="dropdown d-inline-block">
    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ri-more-fill align-middle"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @if($isOrgSuperadmin)
            <li>
                <a href="{{ route('settings.users.edit', $row->id) }}" class="dropdown-item edit-item-btn">
                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>{{ __('messages.edit') }}
                </a>
            </li>
        @endif
        @if($isOrgSuperadmin)
            <li>
                <button class="dropdown-item remove-item-btn" wire:click="destroy({{ $row->id }})">
                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>{{ __('messages.delete') }}
                </button>
            </li>
        @endif
    </ul>
</div>
