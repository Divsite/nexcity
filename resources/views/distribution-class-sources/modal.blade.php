<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0">{{ __('messages.distribution_class_sources') }}</h6>
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
                            <label class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" rows="3" wire:model.defer="description"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="distribution-class-source-active">
                            <label class="form-check-label" for="distribution-class-source-active">
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
                            <th>{{ __('messages.name') }}</th>
                            <th>{{ __('messages.slug') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th class="text-end">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->slug }}</td>
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
                                <td colspan="4" class="text-center text-muted">{{ __('messages.data_not_found') }}</td>
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
