<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0">{{ __('messages.distribution_classes') }}</h6>
        <button type="button" class="btn btn-sm btn-soft-primary" wire:click="createNew">
            <i class="ri-add-line align-bottom me-1"></i> {{ __('messages.create') }}
        </button>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        {{ $editingId ? __('messages.edit') : __('messages.create') }}
                    </h6>
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.distribution_class_sources') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('distribution_class_source_id') is-invalid @enderror" wire:model.defer="distribution_class_source_id">
                                <option value="">{{ __('messages.please_select') }}</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                                @endforeach
                            </select>
                            @error('distribution_class_source_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.year') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('year') is-invalid @enderror" wire:model.defer="year" min="2000" max="2100" readonly>
                            @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row" data-livewire-currency data-livewire-id="{{ $componentId }}" data-currency-code="IDR">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('messages.get_money') }}</label>
                                    <input type="text" class="form-control @error('get_money') is-invalid @enderror" wire:ignore data-currency-field="get_money" data-initial="{{ $get_money ?? '' }}">
                                    @error('get_money') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('messages.get_rice') }}</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('get_rice') is-invalid @enderror" wire:model.defer="get_rice" step="1" min="0">
                                        <span class="input-group-text">{{ __('messages.liter') }}</span>
                                    </div>
                                    @error('get_rice') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" rows="3" wire:model.defer="description"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="distribution-class-active">
                            <label class="form-check-label" for="distribution-class-active">
                                {{ __('messages.active') }}
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                            @if($editingId)
                                <button type="button" class="btn btn-light" wire:click="createNew">{{ __('messages.cancel') }}</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="mb-3">
                <input
                    type="text"
                    class="form-control"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('messages.search') }}"
                >
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.distribution_class_sources') }}</th>
                            <th>{{ __('messages.year') }}</th>
                            <th>{{ __('messages.organization') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th class="text-end">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->source?->name ?? '-' }}</td>
                                <td>{{ $item->year }}</td>
                                <td>{{ $item->organization?->name ?? '-' }}</td>
                                <td>{{ $item->is_active ? __('messages.active') : __('messages.inactive') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        <button class="btn btn-sm btn-soft-secondary" wire:click="edit({{ $item->id }})">
                                            {{ __('messages.edit') }}
                                        </button>
                                        <button class="btn btn-sm btn-soft-danger" wire:click="delete({{ $item->id }})">
                                            {{ __('messages.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('messages.data_not_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
