# Stats summary API

`GET /api/v1/stats/summary` memberi angka gabungan pemakaian Nexcity untuk Proof Register samsulhadiss.com.

- Auth: middleware `openclaw.key`, header `X-Api-Key` atau `Authorization: Bearer`, memakai `OPENCLAW_API_KEY` yang sama dengan `summary/daily`.
- Organisasi demo/seed dikeluarkan lewat `STATS_EXCLUDED_ORGANIZATION_IDS` (id dipisah koma). Setelah mengubahnya jalankan `php artisan config:cache` bila config di-cache.
- Respons hanya berisi COUNT, MIN/MAX tanggal, dan jumlah per tahun. Tidak ada nama, kontak, NIK/KK, alamat, nama organisasi, atau nominal.

| Field | Aturan |
| --- | --- |
| `organizations.mosque` / `organizations.rt` | `type` mosque/rt, `status = active`, bukan demo |
| `charity_transactions` | `status = paid`, organisasi bukan demo; tanggal `created_at` (sama dengan rekap harian); `per_year` dari kolom `year`, `unknown` bila kosong |
| `distribution_recipients` | `status = distributed`, organisasi distribusi bukan demo; tanggal `distributed_at` |
| `residents` | `user_resident_profiles` organisasi bukan demo; `heads_of_family` = `is_head_family = 1` |

Riwayat (transaksi, distribusi, warga) ikut menghitung organisasi yang sudah tidak aktif, karena pemakaian lamanya tetap nyata. Baris tanpa organisasi tidak dihitung. Data aplikasi lama `mosque-management` (Yii2) tidak termasuk.

Contoh: `curl -H "X-Api-Key: $OPENCLAW_API_KEY" https://nexcity.samsulhadiss.com/api/v1/stats/summary`

Tes: `vendor/bin/phpunit tests/Feature/API/StatsSummaryTest.php` (SQLite in-memory).
