@extends('layouts.front')

@section('title', 'Hapus akun Nexcity')

@section('content')
{{--
  The page Google Play asks for: how to delete a Nexcity account, reachable
  without the app. What is removed and what is kept mirrors
  App\Actions\Accounts\CloseOwnAccount — change both together.
--}}
<section class="section pb-0 hero-section min-vh-100" id="account-deletion">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Hapus akun Nexcity</h2>
          <p class="text-muted">Cara menghapus akun Anda, dan data apa yang dihapus atau disimpan.</p>
        </div>

        <div class="text-start">
          <h4 class="fw-semibold mb-3">Dari aplikasi</h4>
          <ol>
            <li>Buka aplikasi Nexcity, lalu tab <strong>Saya</strong>.</li>
            <li>Pilih <strong>Hapus akun</strong>.</li>
            <li>Konfirmasi dengan kode yang dikirim ke WhatsApp Anda (atau kata sandi).</li>
          </ol>

          <h4 class="fw-semibold mb-3">Tanpa aplikasi</h4>
          @if (filled(config('services.account_deletion.contact')))
            <p>Kirim permintaan ke <strong>{{ config('services.account_deletion.contact') }}</strong> dari nomor HP
              atau email yang terdaftar. Kami memastikan permintaan itu berasal dari pemilik akun sebelum menghapusnya.</p>
          @else
            <p>Hubungi pengurus RT atau masjid yang mendaftarkan Anda, dan minta akun Anda dihapus.</p>
          @endif

          <h4 class="fw-semibold mb-3">Yang dihapus</h4>
          <ul>
            <li>Akses masuk: semua sesi di HP berakhir, dan nomor HP Anda tidak lagi bisa dipakai untuk masuk ke akun ini.</li>
            <li>Email, nama pengguna, kata sandi, dan foto profil.</li>
            <li>Untuk akun yang Anda buat sendiri di aplikasi: nama dan nomor HP.</li>
          </ul>

          <h4 class="fw-semibold mb-3">Yang tetap disimpan, dan alasannya</h4>
          <ul>
            <li><strong>Catatan keuangan organisasi</strong>: pembayaran zakat/infaq, iuran RT, dan patungan qurban tetap ada
              di pembukuan masjid dan RT dengan nama yang dicatat saat pembayaran. Pembukuan organisasi tidak boleh berubah
              karena satu akun ditutup.</li>
            <li><strong>Data warga yang dicatat RT</strong> (nama, KK, alamat) adalah data kependudukan milik RT, bukan akun
              aplikasi. Menghapus akun tidak menghapus Anda dari data warga RT. Untuk itu, minta langsung ke pengurus RT.</li>
          </ul>

          <p class="text-muted">Pengurus yang masih memegang jabatan di sebuah organisasi perlu dilepas dari jabatannya
            lebih dulu sebelum akunnya bisa dihapus.</p>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
