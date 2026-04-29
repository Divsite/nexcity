# Organizations and Residents

## Organisasi RT/RW/Masjid

Halaman create organization memakai lokasi yang **sudah ada**.
Route `organizations/create` tidak membuat data RW/RT baru di master lokasi.

### Implikasi

Kalau mau menambahkan area baru seperti `RW 004`, urutannya harus:
1. tambah master lokasi (`LocationSeeder` atau fitur lokasi khusus)
2. baru create organisasi RT/RW atau jalankan `OrganizationSeeder`

## RT/RW04 Neo Bintaro / Jurang Mangu Barat

Seed yang saat ini sudah disiapkan:
- `RW 004`
- `RT 002`
- `RT 003`
- organisasi:
  - `RT 02 RW 04 Jurang Mangu Barat`
  - `RT 03 RW 04 Jurang Mangu Barat`

Akun superadmin RT RW04 harus ada di `UserSeeder`, lalu organisasi di-attach lewat `OrganizationSeeder`.
Kalau hanya menjalankan `OrganizationSeeder`, organisasi bisa muncul tetapi akun login belum tentu ada.

## Resident flow

Resident dibuat lewat `ResidentController` dan saat create akan:
- create record `users`
- assign role `resident`
- create `user_resident_profiles`
- attach ke `organization_user` jika konteks partner tersedia

### Resident index visibility

Superadmin melihat semua resident yang punya role `resident`.
Partner RT hanya melihat resident dengan `resident_profile.organization_id` yang sama dengan organisasi RT tersebut.

### Hal yang sering membingungkan

Kalau ada user yang tidak muncul di halaman residents, biasanya salah satu dari ini:
- user tidak punya role `resident`
- belum ada `resident_profile`
- `resident_profile.organization_id` tidak sesuai
- data itu sebenarnya user partner/admin, bukan resident
