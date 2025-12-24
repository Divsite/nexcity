@extends('layouts.front')

@section('title', 'Terms of Service — samsulhadiss.com')

@section('content')
<section class="section pb-0 hero-section min-vh-100" id="terms">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Terms of Service</h2>
          <p class="text-muted">Tanggal Berlaku: {{ date('F d, Y') }}</p>
        </div>

        <div class="text-start">
          <h4 class="fw-semibold mb-3">1. Pengantar</h4>
          <p>Selamat datang di <strong>samsulhadiss.com</strong> (“Situs”). Situs ini dimiliki dan dikelola oleh
            <strong>Samsul Hadi</strong>. Dengan mengakses atau menggunakan Situs, Anda setuju untuk terikat pada Syarat dan Ketentuan
            (“Ketentuan”) ini.</p>

          <h4 class="fw-semibold mb-3">2. Perubahan Ketentuan</h4>
          <p>Kami dapat memperbarui Ketentuan ini sewaktu-waktu. Versi terbaru akan ditampilkan di halaman ini
            dengan tanggal berlaku yang diperbarui. Penggunaan Anda yang berkelanjutan atas Situs berarti Anda
            menyetujui perubahan tersebut.</p>

          <h4 class="fw-semibold mb-3">3. Hak Kekayaan Intelektual</h4>
          <p>Kecuali dinyatakan lain, seluruh materi di Situs (termasuk teks, desain, logo, foto, dan konten)
            adalah milik Samsul Hadi atau pemegang lisensi terkait, dan dilindungi oleh hukum hak cipta.
            Anda tidak diperkenankan menyalin, memodifikasi, mendistribusikan, atau mengeksploitasi materi tanpa izin tertulis.</p>

          <h4 class="fw-semibold mb-3">4. Penggunaan yang Dilarang</h4>
          <ul>
            <li>Mengunggah atau menyebarkan materi yang melanggar hukum atau melanggar hak pihak lain.</li>
            <li>Mengganggu, merusak, atau mencoba mendapatkan akses tidak sah ke layanan atau sistem kami.</li>
            <li>Menggunakan konten untuk tujuan komersial tanpa izin.</li>
          </ul>

          <h4 class="fw-semibold mb-3">5. Konten & Layanan Pihak Ketiga</h4>
          <p>Situs dapat menampilkan tautan atau konten dari pihak ketiga seperti <em>YouTube, TikTok, Spotify, Facebook</em>.
            Penggunaan Anda terhadap konten/layanan tersebut tunduk pada kebijakan dan syarat platform masing-masing.
            Kami tidak bertanggung jawab atas praktik, konten, atau keamanan dari situs pihak ketiga.</p>

          <h4 class="fw-semibold mb-3">6. Akurasi Informasi</h4>
          <p>Kami berupaya menjaga informasi tetap akurat dan terkini, namun tidak memberikan jaminan atas
            kelengkapan, keandalan, atau ketersediaannya. Konten dapat berubah tanpa pemberitahuan.</p>

          <h4 class="fw-semibold mb-3">7. Penyangkalan Jaminan</h4>
          <p>Situs disediakan “sebagaimana adanya” tanpa jaminan apa pun, tersurat maupun tersirat, termasuk namun
            tidak terbatas pada jaminan kelayakan jual, kesesuaian untuk tujuan tertentu, atau non-pelanggaran.</p>

          <h4 class="fw-semibold mb-3">8. Batasan Tanggung Jawab</h4>
          <p>Dalam batas yang diizinkan hukum yang berlaku, kami tidak bertanggung jawab atas kerugian tidak langsung,
            insidental, khusus, konsekuensial, atau kerugian apa pun yang timbul dari penggunaan atau ketidakmampuan
            menggunakan Situs.</p>

          <h4 class="fw-semibold mb-3">9. Kebijakan Privasi</h4>
          <p>Penggunaan Anda atas Situs juga diatur oleh <a href="{{ route('privacy') }}">Kebijakan Privasi</a> kami.
            Harap membacanya untuk memahami bagaimana kami menangani data Anda.</p>

          <h4 class="fw-semibold mb-3">10. Hukum yang Berlaku</h4>
          <p>Ketentuan ini diatur oleh hukum Republik Indonesia. Sengketa yang timbul akan diselesaikan secara musyawarah
            atau melalui mekanisme hukum yang berlaku di Indonesia.</p>

          <h4 class="fw-semibold mb-3">11. Kontak</h4>
          <p>Untuk pertanyaan terkait Ketentuan ini, hubungi: <strong>samsulhadi@samsulhadiss.com</strong>.</p>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
