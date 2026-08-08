# Audit Otorisasi — 8 Agustus 2026

Diperiksa terhadap data nyata **Islamic Center Al-amanah** (organisasi id=2).

## Ringkasan

Struktur datanya sehat. Yang tidak sehat adalah **cara permission diperiksa**.

Level dan permission sudah terpasang benar di database, dan menu tampil sesuai — itu yang Anda lihat
dan itu memang benar. Tapi menu yang benar **tidak berarti route-nya terlindungi**.

## Data: sehat

8 level, semuanya punya permission:

| Level | Permission | Anggota |
|---|---|---|
| `mosque-superadmin` | 44 | 1 |
| `mosque-officer` | 11 | 33 |
| `mosque-finance` | 10 | 1 |
| `mosque-secretary` | 6 | 1 |
| `mosque-qurban` | 5 | 1 |
| `mosque-inventory` | 4 | 1 |
| `mosque-crm` | 4 | 1 |
| `mosque-humas` | 4 | 1 |

Setiap anggota punya `level_slug` — tidak ada yang null. Ini bagus.

## Temuan 1 — Route bisa ditembus lewat URL manual

**Ini menjawab pertanyaan "apakah kalau akses via route manual inject bisa?" — Ya, bisa.**

Controller dijaga dengan permission Spatie:

```php
// QurbanDistributionController
$this->middleware('permission:browse-qurban')->only(['index', 'residents']);
$this->middleware('permission:add-qurban')->only(['storeBatch', 'updateBatch', …]);
$this->middleware('permission:delete-qurban')->only('deleteBatch');
$this->middleware('permission:scan-qurban-coupon')->only('scanCoupon');
```

`permission:` memeriksa **role Spatie**, bukan level. Dan role `mosque_admin` memberikan semua
permission qurban kepada **setiap** staf masjid.

Diuji pada Mira Rahma, bendahara (`mosque-finance`):

```
can('browse-qurban')       = TRUE   ← lewat role
can('scan-qurban-coupon')  = TRUE   ← lewat role
can('add-qurban')          = TRUE   ← lewat role
can('delete-qurban')       = TRUE   ← lewat role
```

Sedangkan level `mosque-finance` **tidak** memberikan satu pun dari keempatnya.

Artinya bendahara bisa mengetik URL qurban dan masuk — termasuk `deleteBatch`, yang menghapus.

### Kenapa menunya tetap terlihat benar

`MenuBuilder::isVisibleForUser()` memakai `role ATAU level`. Untuk menu seperti Inventaris dan CRM,
role `mosque_admin` **tidak** punya permission-nya, jadi menu tersembunyi dengan benar — itu yang
Anda lihat. Tapi untuk qurban dan amal, role **punya**, jadi perlindungannya bergantung pada aturan
lain di menu, bukan pada permission.

Menu yang benar menyembunyikan pintunya. Ia tidak mengunci pintunya.

## Temuan 2 — Besarnya kebocoran

Permission yang diberikan role tapi tidak diberikan level, per level:

| Level | Bocor |
|---|---|
| `mosque-humas` | 37 |
| `mosque-crm` | 37 |
| `mosque-inventory` | 37 |
| `mosque-secretary` | 33 |
| `mosque-qurban` | 32 |
| `mosque-finance` | 29 |
| `mosque-officer` | 27 |
| `mosque-superadmin` | 6 |

Setiap level, tanpa kecuali.

## Temuan 3 — `mosque-superadmin` justru kekurangan permission

Kebocoran 6 pada `mosque-superadmin` berbeda sifatnya dari yang lain. Isinya:

```
print-mosque-charity-transactions
browse/read/edit/add/delete-mosque-charity-distribution-recipients
```

Superadmin masjid **memang seharusnya** punya ini. Yang salah bukan role-nya, melainkan **definisi
level-nya yang belum lengkap** — tidak ada satu pun level yang memberikan permission
`*-distribution-recipients`.

**Ini penting untuk urutan perbaikan.** Kalau pemeriksaan langsung diubah ke level-authoritative
tanpa melengkapi definisi level dulu, superadmin masjid akan **kehilangan akses** ke penerima
distribusi. Memperbaiki keamanan tidak boleh mematahkan orang yang memang berhak.

## Temuan 4 — `organization_user.role` adalah kolom mati

Isinya duplikat persis dari role Spatie (superadmin 3, mosque_admin 56, rt_admin 30, resident 207),
dan tidak dibaca di mana pun untuk otorisasi. Dua sumber kebenaran untuk fakta yang sama, tanpa
aturan siapa yang menang kalau berbeda.

Jangan dijadikan acuan. Tidak perlu dihapus sekarang.

## Temuan 5 — Level diduplikasi per organisasi

56 baris `user_levels` untuk hanya **15 slug unik**; 480 baris `user_level_permissions`.
`rt-finance` punya 6 salinan.

Padahal role, level, dan permission adalah milik pemilik platform — partner tidak boleh mengubahnya.
Definisi terpusat yang disalin per organisasi berarti: menambah satu permission ke `mosque-qurban`
harus dilakukan di setiap masjid. Satu terlewat, petugas di masjid itu **diam-diam** kehilangan
wewenang.

Schema sudah menyiapkan jalan keluarnya — `user_levels.organization_id` boleh null dan ada flag
`is_global` — tapi belum dipakai (0 baris ber-`organization_id` null).

## Urutan perbaikan yang disarankan

Urutannya penting; membalik nomor 2 dan 1 akan mengunci orang yang berhak.

1. ✅ **Lengkapi definisi level — selesai 8 Agustus 2026.**

   `mosque-superadmin` kurang 6 permission (`print-mosque-charity-transactions` dan lima
   `*-distribution-recipients`); `rt-superadmin` sudah lengkap. Seeder diperbaiki, dan 18 baris
   ditambahkan ke data yang sudah ada (6 × 3 masjid). Ketua DKM Al-amanah: 44 → 50 capability.

   Perkakasnya tetap ada dan bisa dijalankan kapan saja:

   ```bash
   php artisan levels:audit          # laporkan selisih, exit code 1 kalau ada
   php artisan levels:audit --fix    # tambahkan yang kurang
   ```

   Perintah ini **hanya menambah**, tidak pernah mencabut — jadi tidak bisa mengunci siapa pun.
   Exit code-nya bukan nol saat ada selisih, supaya bisa dipasang di CI atau pemeriksaan deploy.

2. **Ubah pemeriksaan jadi level-authoritative.** ← berikutnya, dan sekarang sudah aman dikerjakan.

   Ganti `permission:` pada route yang di-scope organisasi dengan pemeriksaan yang membaca level.
   `CapabilityResolver` sudah melakukannya untuk API dan bisa dipakai ulang lewat sebuah Gate atau
   middleware `capability:`.

   Setelah langkah ini, **uji ulang tiap level** — inilah langkah yang bisa mencabut akses.

3. **Jadikan level global** (`organization_id` null). Menghapus duplikasi dan membuat langkah 1 cukup
   dilakukan sekali, bukan per organisasi.

4. **Anggap `organization_user.role` mati.** Jangan dibaca, jangan dijadikan acuan.

## Yang sudah aman

Sisi **API mobile** tidak terkena temuan 1 dan 2. `CapabilityResolver` sudah level-authoritative
sejak awal, dan `ResolveActiveOrganization` memverifikasi header konteks organisasi. Diuji: 18 test
di `tests/Feature/API/`.

Artinya web dan mobile **berbeda perilaku** sampai langkah 2 dikerjakan: bendahara bisa membuka
qurban di web, tapi tidak melihatnya di mobile.
