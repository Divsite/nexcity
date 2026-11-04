# Seeders and Demo Data

## Seeder penting

### `LocationSeeder`
Menyiapkan master lokasi.
Untuk distribusi advanced location, ini adalah fondasi utama.

Saat ini termasuk:
- Jurang Mangu Barat
- RW 003 + RT 001-006
- RW 004 + RT 002-003

### Kata sandi bawaan

Seeder masih memberi `password` (kode) — dokumen ini dulu menyebut `passworrd`; keduanya dianggap
bawaan oleh `App\Support\DefaultPassword`. Sejak 2 November 2026:

- **Aplikasi menolak keduanya.** Warga diarahkan ke masuk lewat WhatsApp; pengurus diminta mengganti
  dulu lewat web.
- **Web**: warga ditolak; pengurus boleh masuk tapi tertahan di halaman ganti kata sandi sampai
  menggantinya.
- **Setelah deploy**, jalankan:

  ```bash
  php artisan users:default-passwords                  # laporkan, exit code 1 kalau masih ada
  php artisan users:default-passwords --retire-warga   # ganti punya warga dengan kata sandi acak
  ```

  Akun pengurus **tidak** diganti otomatis — banyak yang tidak punya nomor HP, dan kata sandi acak
  akan mengunci mereka dari web. Perintah itu mendaftar mereka supaya diminta mengganti sendiri.

Akun demo lokal tetap bisa dipakai di web: masuk dengan `password`, lalu ganti.

### `UserSeeder`
Menyiapkan akun user demo dasar.
Catatan saat ini:
- akun RT RW04 harus ada di sini agar bisa login

### `OrganizationSeeder`
Menyiapkan organisasi demo + membership + profile tambahan.
Penting:
- seeder ini tidak create user dari nol untuk setiap member
- dia akan attach member jika user dengan email itu sudah ada

### `OrganizationOfficerSeeder`
Menyiapkan batch petugas zakat masjid untuk organisasi Alamanah. Kata sandinya bawaan — lihat di atas.

## Urutan seed yang aman untuk RT RW04

1. `php artisan db:seed --class=LocationSeeder`
2. `php artisan db:seed --class=UserSeeder`
3. `php artisan db:seed --class=OrganizationSeeder`
4. `php artisan db:seed --class=OrganizationOfficerSeeder`

## Kenapa RW004 belum muncul padahal OrganizationSeeder sudah dijalankan?

Karena dropdown advanced location membaca tabel lokasi, bukan tabel organisasi.
Jadi `OrganizationSeeder` saja tidak cukup.

## Catatan resident demo

Resident demo bawaan yang pasti disiapkan sekarang lebih dominan untuk RW03 / RT existing.
Kalau butuh resident demo spesifik RW04, perlu ditambahkan eksplisit ke seeder.
