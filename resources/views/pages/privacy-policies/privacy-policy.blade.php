@extends('layouts.front')

@section('title', config('app.name'))

@section('content')
<!-- start privacy policy section -->
<section class="section pb-0 hero-section min-vh-100" id="privacy-policy">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Privacy Policy</h2>
                    <p class="text-muted">Effective Date: {{ date('F d, Y') }}</p>
                </div>

                <div class="text-start">
                    <h4 class="fw-semibold mb-3">1. Informasi yang Dikumpulkan</h4>
                    <p>Auto Konten AI Bot <strong>tidak menyimpan atau membagikan</strong> informasi pribadi pengguna. Namun, bot dapat mengakses informasi berikut secara sementara:
                        <ul>
                            <li>Nama pengguna Telegram</li>
                            <li>ID Telegram Anda</li>
                            <li>Konten yang Anda kirim untuk diproses</li>
                        </ul>
                        Semua data diproses secara <strong>sementara</strong> dan <strong>tidak disimpan secara permanen</strong>.
                    </p>

                    <h4 class="fw-semibold mb-3">2. Penggunaan Data</h4>
                    <p>Data hanya digunakan untuk:
                        <ul>
                            <li>Generate konten otomatis (caption, gambar, video)</li>
                            <li>Menjadwalkan posting sosial media</li>
                            <li>Meningkatkan pengalaman pengguna</li>
                        </ul>
                        Tidak digunakan untuk periklanan atau dibagikan ke pihak ketiga.
                    </p>

                    <h4 class="fw-semibold mb-3">3. Integrasi Pihak Ketiga</h4>
                    <p>Bot ini terhubung dengan platform pihak ketiga (seperti <em>n8n, AI API, dan sosial media</em>) yang memiliki kebijakan privasi masing-masing.</p>

                    <h4 class="fw-semibold mb-3">4. Keamanan</h4>
                    <p>Kami menjaga keamanan data yang diproses, namun mohon <strong>tidak mengirimkan informasi sensitif</strong> ke bot ini.</p>

                    <h4 class="fw-semibold mb-3">5. Perubahan Kebijakan</h4>
                    <p>Kami dapat memperbarui kebijakan ini sewaktu-waktu. Perubahan signifikan akan diinformasikan melalui bot atau halaman resmi.</p>

                    <h4 class="fw-semibold mb-3">6. Kontak</h4>
                    <p>Jika ada pertanyaan mengenai privasi, hubungi kami melalui Telegram: <strong>@info@botautomation.com</strong> atau email: <strong>hasan.cakrawala@gmail.com</strong></p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- end privacy policy section -->
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/libs/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/landing.init.js') }}"></script>
@endpush
