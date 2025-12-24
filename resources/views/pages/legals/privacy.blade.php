@extends('layouts.front')

@section('title', 'Privacy Policy — samsulhadiss.com')

@section('content')
<section class="section pb-0 hero-section min-vh-100" id="privacy-policy">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Privacy Policy</h2>
          <p class="text-muted">Tanggal Berlaku: {{ date('F d, Y') }}</p>
        </div>

        <div class="text-start">
          <h4 class="fw-semibold mb-3">1. Ringkasan</h4>
          <p>Kebijakan ini menjelaskan cara <strong>samsulhadiss.com</strong> (“Situs”) mengumpulkan, menggunakan,
            dan melindungi data pribadi pengunjung. Dengan menggunakan Situs, Anda menyetujui praktik yang dijelaskan di sini.</p>

          <h4 class="fw-semibold mb-3">2. Informasi yang Kami Kumpulkan</h4>
          <ul>
            <li><strong>Data yang Anda berikan</strong>: misalnya nama, alamat email, atau pesan yang dikirim melalui formulir kontak.</li>
            <li><strong>Data penggunaan</strong>: informasi teknis yang dikirim browser Anda (mis. alamat IP yang dianonimkan bila dimungkinkan,
              jenis perangkat/peramban, halaman yang diakses, waktu kunjung).</li>
            <li><strong>Konten publik pihak ketiga</strong>: metadata publik dari platform seperti YouTube/TikTok/Facebook/Spotify
              yang ditampilkan di Situs. Kami tidak meminta kredensial akun Anda untuk menampilkan konten publik tersebut.</li>
          </ul>

          <h4 class="fw-semibold mb-3">3. Cara Kami Menggunakan Data</h4>
          <ul>
            <li>Menyediakan dan meningkatkan fungsionalitas Situs.</li>
            <li>Menanggapi pertanyaan Anda (misalnya via formulir kontak).</li>
            <li>Analitik penggunaan (mengukur trafik & performa secara agregat).</li>
          </ul>

          <h4 class="fw-semibold mb-3">4. Cookie & Analitik</h4>
          <p>Situs ini dapat menggunakan analitik seperti <em>Umami</em> dan/atau <em>PostHog</em> untuk memahami penggunaan situs.
            Layanan ini mungkin menggunakan metode pelacakan tertentu (cookie atau teknik serupa) sesuai pengaturan Anda.
            Anda dapat mengontrol cookie melalui pengaturan peramban Anda.</p>

          <h4 class="fw-semibold mb-3">5. Konten & Embed Pihak Ketiga</h4>
          <p>Situs dapat menampilkan tautan atau media dari <em>YouTube, TikTok, Spotify, Facebook</em>.
            Platform tersebut mungkin memasang cookie atau mengumpulkan data sesuai kebijakan mereka masing-masing.
            Interaksi Anda dengan layanan tersebut tunduk pada kebijakan mereka.</p>

          <h4 class="fw-semibold mb-3">6. Berbagi Data</h4>
          <p>Kami tidak menjual data pribadi Anda. Kami dapat membagikan data terbatas kepada penyedia layanan (mis. analitik, hosting)
            hanya untuk menjalankan dan meningkatkan Situs, sesuai kebutuhan dan perjanjian yang berlaku.</p>

          <h4 class="fw-semibold mb-3">7. Penyimpanan & Retensi</h4>
          <p>Kami menyimpan data hanya selama diperlukan untuk tujuan pengumpulan atau sebagaimana diwajibkan hukum yang berlaku.
            Data formulir kontak disimpan seperlunya untuk menindaklanjuti komunikasi Anda.</p>

          <h4 class="fw-semibold mb-3">8. Keamanan</h4>
          <p>Kami menerapkan langkah-langkah keamanan yang wajar untuk melindungi data. Namun, tidak ada metode transmisi
            atau penyimpanan yang 100% aman. Gunakan Situs dengan bijak dan hindari mengirim informasi sangat sensitif.</p>

          <h4 class="fw-semibold mb-3">9. Hak Anda</h4>
          <p>Anda dapat meminta akses, pembaruan, atau penghapusan data pribadi yang Anda berikan kepada kami (jika berlaku).
            Silakan hubungi kami melalui email di bawah.</p>

          <h4 class="fw-semibold mb-3">10. Anak-Anak</h4>
          <p>Situs tidak ditujukan untuk anak di bawah 13 tahun. Kami tidak dengan sengaja mengumpulkan data dari anak-anak.</p>

          <h4 class="fw-semibold mb-3">11. Perubahan Kebijakan</h4>
          <p>Kami dapat memperbarui Kebijakan Privasi ini sewaktu-waktu. Versi terbaru akan dipublikasikan di halaman ini
            dengan tanggal berlaku yang diperbarui.</p>

          <h4 class="fw-semibold mb-3">12. Kontak</h4>
          <p>Untuk permintaan atau pertanyaan terkait privasi, hubungi: <strong>samsulhadi@samsulhadiss.com</strong>.</p>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
