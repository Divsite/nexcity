# Known Gaps and Next Steps

## Prioritas core yang masih pending

1. Neo Bintaro / RW04 end-to-end
   - master lokasi
   - organisasi RT
   - login akun RT
   - resident scope RT
   - distribusi data penerima

2. Dokumentasi foto distribusi
   - ada laporan upload foto submit tapi data tidak masuk
   - perlu cek backend `nexcity` dan app `nexcity_mobile`

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
2. cek upload dokumentasi foto dari mobile
3. lanjut input distribusi amil/internal
4. setelah data inti masuk, baru verifikasi fund source dan finance summary
