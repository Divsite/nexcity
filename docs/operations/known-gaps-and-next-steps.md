# Known Gaps and Next Steps

## Prioritas core yang masih pending

1. Neo Bintaro / RW04 end-to-end
   - master lokasi
   - organisasi RT
   - login akun RT
   - resident scope RT
   - distribusi data penerima

2. Dokumentasi foto distribusi
   - laporan lama: foto sudah dipilih, submit "berhasil", tapi fotonya tidak masuk
   - penyebab di form web (modal status penerima): foto diunggah begitu dipilih, dan tombol
     Simpan bisa ditekan sebelum unggahan selesai atau setelah gagal — status tersimpan tanpa foto.
     Sudah diperbaiki: tombol Simpan dikunci selama unggah, dan gagal unggah ditampilkan
   - aplikasi WebView `nexcity_mobile` sudah **dipensiunkan** (Oktober 2026). Penggantinya
     `nexcity-flutter-apps`, yang mengunggah foto langsung ke
     `POST /api/v1/distribution-recipients/{recipient}/photos`
   - sisa: uji foto dari aplikasi native di HP sampai terlihat di web

3. Sumber dana distribusi
   - perlu diuji setelah data distribusi utama lengkap

4. Pengeluaran / finance operasional
   - menunggu data nyata diinput

5. Keputusan sisa uang dan beras
   - belum final secara business rule

## Risiko yang perlu diingat

### Distribusi yang sudah berjalan
Kalau suatu distribusi sudah punya recipient `distributed`, edit struktur distribusi itu diblok.
Ini aman untuk menjaga histori, tapi kurang fleksibel untuk kasus data warga nyusul.

### Organization create belum membuat lokasi baru
Halaman create organisasi bukan pengganti master lokasi.
Kalau ada RT/RW baru, saat ini masih perlu seed/master lokasi lebih dulu.

### Resident visibility
Kalau resident tidak muncul di index, cek:
- role `resident`
- resident profile
- organization_id profile
- apakah datanya sebenarnya akun admin/partner

## Saran langkah kerja berikutnya

1. bereskan RW04 sampai login dan resident flow valid
2. uji upload dokumentasi foto dari aplikasi native
3. lanjut input distribusi amil/internal
4. setelah data inti masuk, baru verifikasi fund source dan finance summary
