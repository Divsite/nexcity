<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0">{{ __('messages.charity_payments') }}</h6>
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
                            <label class="form-label">{{ __('messages.type') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" wire:model.defer="type">
                                <option value="transfer">{{ __('messages.transfer') }}</option>
                                <option value="qris">{{ __('messages.qris') }}</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ __('messages.bank') }}
                                @if($type !== 'qris') <span class="text-danger">*</span> @endif
                            </label>
                            <div
                                wire:ignore
                                data-livewire-select
                                data-livewire-id="{{ $componentId }}"
                                data-model-field="bank_id"
                                data-selected="{{ $bank_id ?? '' }}"
                                data-options='@json($banks)'
                            ></div>
                            @error('bank_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.account_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror" wire:model.defer="account_name">
                            @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @if($type !== 'qris')
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.account_number') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_number') is-invalid @enderror" wire:model.defer="account_number">
                                @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.qris_image') }} <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('qris_image') is-invalid @enderror" wire:model="qris_image" accept="image/*">
                                @error('qris_image') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                                @if($qris_image)
                                    <div class="mt-2">
                                        <img src="{{ $qris_image->temporaryUrl() }}" class="img-fluid rounded" alt="QRIS">
                                    </div>
                                @elseif($qris_image_path)
                                    <div class="mt-2">
                                        <img src="{{ asset('uploads/' . ltrim($qris_image_path, '/')) }}" class="img-fluid rounded" alt="QRIS">
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.notes') }}</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" rows="3" wire:model.defer="notes"></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="charity-payment-active">
                            <label class="form-check-label" for="charity-payment-active">
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
                            <th>{{ __('messages.type') }}</th>
                            <th>{{ __('messages.bank') }}</th>
                            <th>{{ __('messages.account_name') }}</th>
                            <th>{{ __('messages.account_number') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th class="text-end">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ ucfirst($item->type) }}</td>
                                <td>{{ $item->bank?->name ?? '-' }}</td>
                                <td>{{ $item->account_name ?? '-' }}</td>
                                <td>{{ $item->account_number ?? '-' }}</td>
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
                                <td colspan="6" class="text-center text-muted">{{ __('messages.data_not_found') }}</td>
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
