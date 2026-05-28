<div class="toast-container position-fixed bottom-0 top-0 end-0 p-3">
    <div id="flash-toast" class="toast align-items-center {{ flash()->class }} border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-2">
                        @if(flash()->level == 'success')
                            <i class="ri-checkbox-circle-fill align-middle fs-18"></i>
                        @elseif(flash()->level == 'info')
                            <i class="ri-information-fill align-middle fs-18"></i>
                        @elseif(flash()->level == 'warning')
                            <i class="ri-notification-off-line align-middle fs-18"></i>
                        @elseif(flash()->level == 'error')
                            <i class="ri-alert-line align-middle fs-18"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 text-white">{{ flash()->message }}</h6>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        @if(flash()->message)
        (function () {
            var el = document.getElementById('flash-toast');
            if (!el) return;
            new bootstrap.Toast(el, { delay: 8000 }).show();
        })();
        @endif
    </script>
@endpush
