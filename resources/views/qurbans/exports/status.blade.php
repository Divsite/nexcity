@extends('layouts.app')

@section('title', __('messages.qurban_coupons'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0">
                    <i class="ri-file-pdf-line align-bottom me-2"></i>
                    {{ $export->type === 'all' ? 'Export Semua Kupon Qurban' : 'Export Kupon Qurban' }}
                </h5>
            </div>
            <div class="card-body text-center py-5">

                {{-- Pending / Processing --}}
                <div id="state-loading" class="{{ in_array($export->status, ['pending', 'processing']) ? '' : 'd-none' }}">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mb-1" id="state-loading-title">
                        {{ $export->status === 'pending' ? 'Antrian diproses...' : 'Sedang membuat PDF...' }}
                    </h5>
                    <p class="text-muted small mb-0">Halaman ini otomatis terupdate. Jangan tutup tab ini.</p>
                </div>

                {{-- Ready --}}
                <div id="state-ready" class="{{ $export->status === 'ready' ? '' : 'd-none' }}">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-success-subtle text-success rounded-circle fs-1">
                            <i class="ri-check-line"></i>
                        </div>
                    </div>
                    <h5 class="mb-1">PDF Siap Diunduh</h5>
                    <p class="text-muted small mb-4">File akan otomatis diunduh. Klik tombol jika tidak otomatis.</p>
                    <a id="download-link"
                       href="{{ $export->isReady() ? route('mosque.qurban.coupon-exports.download', $export) : '#' }}"
                       class="btn btn-primary">
                        <i class="ri-download-line align-bottom me-1"></i> Unduh PDF
                    </a>
                    <div class="mt-3">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('mosque.qurban') }}" class="text-muted small">
                            &larr; Kembali
                        </a>
                    </div>
                </div>

                {{-- Failed --}}
                <div id="state-failed" class="{{ $export->status === 'failed' ? '' : 'd-none' }}">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-1">
                            <i class="ri-close-line"></i>
                        </div>
                    </div>
                    <h5 class="mb-1">Gagal Membuat PDF</h5>
                    @if($export->error_message)
                        <p class="text-muted small mb-4">{{ $export->error_message }}</p>
                    @else
                        <p class="text-muted small mb-4">Terjadi kesalahan saat memproses export.</p>
                    @endif
                    <a href="{{ route('mosque.qurban') }}" class="btn btn-outline-secondary">
                        &larr; Kembali ke Qurban
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const statusUrl = '{{ route('mosque.qurban.coupon-exports.status', $export) }}';
    const downloadUrl = '{{ route('mosque.qurban.coupon-exports.download', $export) }}';
    let currentStatus = '{{ $export->status }}';
    let autoDownloaded = false;

    function showState(state) {
        ['loading', 'ready', 'failed'].forEach(s => {
            document.getElementById('state-' + s).classList.toggle('d-none', s !== state);
        });
    }

    function poll() {
        fetch(statusUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            currentStatus = data.status;

            if (data.status === 'ready') {
                document.getElementById('download-link').href = data.download_url || downloadUrl;
                showState('ready');
                if (!autoDownloaded) {
                    autoDownloaded = true;
                    window.location.href = data.download_url || downloadUrl;
                }
                return;
            }

            if (data.status === 'failed') {
                showState('failed');
                return;
            }

            const title = document.getElementById('state-loading-title');
            if (title) {
                title.textContent = data.status === 'processing' ? 'Sedang membuat PDF...' : 'Antrian diproses...';
            }
            showState('loading');

            setTimeout(poll, 2000);
        })
        .catch(() => setTimeout(poll, 4000));
    }

    if (currentStatus === 'pending' || currentStatus === 'processing') {
        setTimeout(poll, 2000);
    }
})();
</script>
@endpush
