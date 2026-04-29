# Seeders and Demo Data

## Seeder penting

### `LocationSeeder`
Menyiapkan master lokasi.
Untuk distribusi advanced location, ini adalah fondasi utama.

Saat ini termasuk:
- Jurang Mangu Barat
- RW 003 + RT 001-006
- RW 004 + RT 002-003

### `UserSeeder`
Menyiapkan akun user demo dasar.
Catatan saat ini:
- password default disamakan menjadi `passworrd`
- akun RT RW04 harus ada di sini agar bisa login

### `OrganizationSeeder`
Menyiapkan organisasi demo + membership + profile tambahan.
Penting:
- seeder ini tidak create user dari nol untuk setiap member
- dia akan attach member jika user dengan email itu sudah ada

### `OrganizationOfficerSeeder`
Menyiapkan batch petugas zakat masjid untuk organisasi Alamanah.
Password default juga mengikuti `passworrd`.

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
