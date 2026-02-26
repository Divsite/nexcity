<div wire:ignore.self class="modal fade" id="distribution-status-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.update_status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.status_note') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('statusNote') is-invalid @enderror" rows="3" wire:model.defer="statusNote"></textarea>
                    @error('statusNote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

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
    document.addEventListener('distribution-status-modal:open', () => {
        const modalEl = document.getElementById('distribution-status-modal');
        if (!modalEl || !window.bootstrap) return;
        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    document.addEventListener('distribution-status-modal:close', () => {
        const modalEl = document.getElementById('distribution-status-modal');
        if (!modalEl || !window.bootstrap) return;
        window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });

    document.addEventListener('toast', (event) => {
        if (!window.Swal) return;
        const detail = event.detail || {};
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
</script>
