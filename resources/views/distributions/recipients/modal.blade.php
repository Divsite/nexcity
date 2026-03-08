<div wire:ignore.self class="modal fade" id="distribution-status-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.update_status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
            </div>
            <div class="modal-body">
                @if($statusAction === 'distributed')
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.delivered_to') }}</label>
                        <select class="form-select" wire:model.defer="deliveryMethod">
                            <option value="direct">{{ __('messages.delivered_direct') }}</option>
                            <option value="neighbor">{{ __('messages.delivered_neighbor') }}</option>
                            <option value="door">{{ __('messages.delivered_left') }}</option>
                        </select>
                    </div>
                @endif

                @if(in_array($statusAction, ['failed', 'rescheduled']))
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.status_reason') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('statusReason') is-invalid @enderror" wire:model.defer="statusReason">
                            <option value="">{{ __('messages.please_select') }}</option>
                            <option value="not_home">{{ __('messages.recipient_not_home') }}</option>
                            <option value="moved">{{ __('messages.recipient_moved') }}</option>
                            <option value="other">{{ __('messages.other') }}</option>
                        </select>
                        @error('statusReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif

                @if($statusAction === 'redirected')
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.redirect_target') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('redirectTarget') is-invalid @enderror" wire:model.defer="redirectTarget">
                            <option value="">{{ __('messages.please_select') }}</option>
                            <option value="street">{{ __('messages.redirect_target_street') }}</option>
                            <option value="other">{{ __('messages.other') }}</option>
                        </select>
                        @error('redirectTarget') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($redirectTarget === 'other')
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.redirect_target_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('redirectName') is-invalid @enderror" wire:model.defer="redirectName">
                            @error('redirectName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="alert alert-info py-2">
                        <div class="small">
                            {{ __('messages.total_money') }}:
                            <span class="fw-semibold">{{ \Cknow\Money\Money::IDR($redirectMoney ?? 0)->format(app()->getLocale()) }}</span>
                            · {{ __('messages.total_rice') }}:
                            <span class="fw-semibold">{{ \Illuminate\Support\Number::format($redirectRice ?? 0, 2, 2, app()->getLocale()) }} {{ __('messages.liter') }}</span>
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">
                        {{ __('messages.status_note') }}
                        @if(in_array($statusAction, ['failed', 'rescheduled']) && $statusReason === 'other')
                            <span class="text-danger">*</span>
                        @endif
                    </label>
                    <textarea class="form-control @error('statusNote') is-invalid @enderror" rows="3" wire:model.defer="statusNote"></textarea>
                    @error('statusNote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if($statusAction === 'distributed')
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.documentation_photos') }}</label>
                        <input type="file"
                               class="form-control @error('attachments.*') is-invalid @enderror"
                               wire:model="attachments"
                               accept="image/*"
                               capture="environment">
                        @error('attachments') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @error('attachments.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">{{ __('messages.file_extension_png_jpeg_jpg') }}</div>
                    </div>
                @endif

                @if($statusAction === 'rescheduled')
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.reschedule_date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('rescheduleAt') is-invalid @enderror" wire:model.defer="rescheduleAt">
                        @error('rescheduleAt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                <button type="button" class="btn btn-primary" wire:click="saveStatus">{{ __('messages.save') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        if (!window.Livewire) return;

        window.Livewire.on('distribution-status-modal:open', () => {
            const modalEl = document.getElementById('distribution-status-modal');
            if (!modalEl || !window.bootstrap) return;
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        window.Livewire.on('distribution-status-modal:close', () => {
            const modalEl = document.getElementById('distribution-status-modal');
            if (!modalEl || !window.bootstrap) return;
            window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        });

        window.Livewire.on('toast', (detail = {}) => {
            if (!window.Swal) return;
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: detail.type || 'success',
                title: detail.message || 'Saved successfully',
                timer: 2200,
                timerProgressBar: true,
                showConfirmButton: false,
            });
        });
    });
</script>

<div wire:ignore.self class="modal fade" id="distribution-view-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.documentation') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6 class="mb-2">{{ __('messages.documentation') }}</h6>
                    @if(empty($viewAttachments))
                        <div class="text-muted">{{ __('messages.no_documentation_found') }}</div>
                    @else
                        <div class="row g-3">
                            @foreach($viewAttachments as $attachment)
                                <div class="col-6 col-md-4">
                                    <div class="border rounded p-2 h-100">
                                        <a href="{{ $attachment['url'] }}" target="_blank" class="d-block mb-2">
                                            <img src="{{ $attachment['url'] }}" alt="{{ $attachment['original_name'] }}" class="img-fluid rounded">
                                        </a>
                                        <div class="small text-muted text-truncate">{{ $attachment['original_name'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h6 class="mb-2">{{ __('messages.status_history') }}</h6>
                    @if(empty($viewStatusLogs))
                        <div class="text-muted">{{ __('messages.data_not_found') }}</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($viewStatusLogs as $log)
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div class="fw-semibold">
                                            {{ __('messages.status') }}: {{ __('messages.' . ($log['to_status'] ?? 'pending')) }}
                                        </div>
                                        <div class="small text-muted">{{ $log['created_at'] ?? '-' }}</div>
                                    </div>
                                    <div class="small text-muted">
                                        {{ __('messages.updated_by') }}: {{ $log['created_by'] ?? '-' }}
                                    </div>
                                    @if(!empty($log['status_note']))
                                        <div class="small mt-1">{{ $log['status_note'] }}</div>
                                    @endif
                                    @if(!empty($log['redirect_target']))
                                        <div class="small mt-1">
                                            {{ __('messages.redirect_target') }}:
                                            {{ $log['redirect_target'] === 'street' ? __('messages.redirect_target_street') : ($log['redirect_name'] ?? '-') }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ __('messages.total_money') }}: {{ $log['redirect_money'] ?? '0' }}
                                            · {{ __('messages.total_rice') }}: {{ $log['redirect_rice'] ?? '0' }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        if (!window.Livewire) return;

        window.Livewire.on('distribution-view-modal:open', () => {
            const modalEl = document.getElementById('distribution-view-modal');
            if (!modalEl || !window.bootstrap) return;
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });
</script>
