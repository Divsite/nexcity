# Data warga (API mobile)

Sisi aplikasi dan alasannya: `nexcity-flutter-apps/docs/modules/residents.md`.

| Endpoint | Hak | Isi |
|---|---|---|
| `GET /api/v1/rt/residents` | `browse-rt-residents` | warga RT aktif, terpaginasi; `?search`, `?filter=incomplete`; `summary` se-RT |
| `GET /api/v1/rt/corrections` | `browse-rt-residents` | antrean koreksi yang menunggu |
| `POST /api/v1/rt/corrections/{id}/resolve` | `edit-rt-residents` | `applied` / `rejected` — **tidak** mengubah data warga |
| `GET /api/v1/me/profile` | warga (punya resident profile) | data sendiri + yang belum lengkap + permintaan koreksi |
| `POST /api/v1/me/profile/corrections` | warga | satu permintaan terbuka per isian; NIK tidak bisa dikoreksi |

- "Belum lengkap" = salah satu dari nomor KK, alamat, nomor HP yang bisa dinormalisasi, kartu QR
  (`App\Support\ResidentCompleteness`).
- Yang tidak pernah dikirim: NIK, nomor KK lengkap (hanya `•••• 1234`), nomor HP warga lain.
- Koreksi adalah permintaan. Pengurus mengubah data di web, lalu menandainya — supaya nilai dari HP
  tidak masuk ke data yang dipakai masjid menyusun daftar zakat tanpa ditinjau.

Tes: `tests/Feature/API/ResidentDataTest.php`.
