@php
    $period = $this->getAppliedFilterWithValue('period') ?? '';
@endphp

<div class="d-flex flex-wrap gap-2 align-items-center mb-2">
    <span class="text-muted small">{{ __('messages.quick_filter') }}:</span>
    <button type="button"
            class="btn btn-sm {{ $period === '' ? 'btn-primary' : 'btn-soft-secondary' }}"
            wire:click="applyPreset('')">
        {{ __('messages.all') }}
    </button>
    <button type="button"
            class="btn btn-sm {{ $period === 'today' ? 'btn-primary' : 'btn-soft-secondary' }}"
            wire:click="applyPreset('today')">
        {{ __('messages.today') }}
    </button>
    <button type="button"
            class="btn btn-sm {{ $period === 'this_year' ? 'btn-primary' : 'btn-soft-secondary' }}"
            wire:click="applyPreset('this_year')">
        {{ __('messages.this_year') }}
    </button>
</div>
