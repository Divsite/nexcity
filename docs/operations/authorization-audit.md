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

2. ✅ **Ubah pemeriksaan jadi level-authoritative — selesai 8 Agustus 2026.**

   Middleware `capability:` (`app/Http/Middleware/RequireCapability.php`) memakai ulang
   `CapabilityResolver`. Bentuk pemakaiannya sama dengan Spatie, termasuk bentuk ATAU:

   ```php
   $this->middleware('capability:browse-qurban');
   $this->middleware('capability:add-rt-residents|edit-rt-residents');
   ```

   Diterapkan pada 18 guard di 5 controller yang di-scope organisasi: CharityTransaction,
   CharityDistribution, Resident, Membership, QurbanDistribution. Guard yang bukan ber-scope
   organisasi (`browse-users`, `my-account`, dll) **tetap** memakai `permission:` — itu memang
   wewenang role.

   Superadmin tidak pernah di-scope level.

   Hasil terhadap data nyata:

   | Level | scan-qurban | delete-qurban | browse-amal |
   |---|---|---|---|
   | `mosque-superadmin` | boleh | boleh | boleh |
   | `mosque-officer` | boleh | ditolak | boleh |
   | `mosque-qurban` | boleh | ditolak | ditolak |
   | `mosque-finance` | ditolak | ditolak | boleh |
   | `mosque-secretary` | ditolak | ditolak | boleh |
   | `mosque-inventory` / `crm` / `humas` | ditolak | ditolak | ditolak |

   Diuji: `tests/Feature/Authorization/CapabilityMiddlewareTest.php`.

3. ✅ **Jadikan level global — selesai 8 Agustus 2026.**

   Migrasi `2026_08_08_000100_make_user_levels_global`. Aman dijalankan karena setiap salinan sebuah
   slug sudah memuat permission yang identik — diperiksa lebih dulu.

   | | Sebelum | Sesudah |
   |---|---|---|
   | `user_levels` | 56 | **15** |
   | `user_level_permissions` | 501 | **131** |
   | Ber-`organization_id` null | 0 | **15** |

   `CapabilityResolver` mendahulukan definisi milik organisasi kalau ada, lalu jatuh ke global. Jadi
   override per-partner tetap mungkin nanti tanpa menulis ulang apa pun.

   Menambah permission ke sebuah level sekarang **satu baris, satu kali** — bukan sekali per masjid.

   Sekalian: `mosque-officer` → **`mosque-zakat`** (35 baris `organization_user`). Slug lama
   berbohong; namanya selalu "Petugas Zakat", dan kodenya bahkan sudah menerjemahkannya jadi
   `messages.zakat_officer`.

4. **Anggap `organization_user.role` mati.** Jangan dibaca, jangan dijadikan acuan.

## Yang perlu Anda periksa setelah langkah 2

Pencabutan berikut **disengaja**, tapi satu di antaranya patut ditinjau ulang karena menyangkut
alur harian.

⚠️ **`mosque-officer` kehilangan `print-mosque-charity-transactions`** — dan ini level yang dipakai
**33 dari 56** pengurus masjid. Mereka boleh *mencatat* transaksi amal tapi tidak bisa mencetak
kuitansinya. Mencatat tanpa bisa mencetak adalah alur yang patah.

Kalau memang seharusnya boleh, tambahkan ke definisi level di `OrganizationSeeder`, lalu:

```bash
php artisan levels:audit        # perintah ini hanya memeriksa level superadmin
```

Untuk level non-superadmin belum ada perintahnya — tambahkan permission-nya di seeder dan jalankan
ulang seeder organisasi, atau tambahkan manual lewat `user_level_permissions`.

Pencabutan lain yang sudah diperiksa dan **benar**:

- `mosque-qurban` kehilangan seluruh akses transaksi amal — petugas qurban bukan bendahara
- `mosque-officer` dan `mosque-qurban` kehilangan `delete-qurban` — menghapus batch tetap wewenang Ketua DKM
- `rt-finance`, `rt-humas`, `rt-field-officer` kehilangan `browse-rt-residents` — level mereka memang
  tidak mencakup pengelolaan warga. Khusus `rt-field-officer` yang hanya punya `scan-resident-qr`,
  ini konsisten: tugasnya memindai di lapangan, bukan membuka daftar

## Temuan tambahan — permission hantu

`ResidentController` merujuk `add-residents`, `edit-residents`, dan `delete-residents`. **Ketiganya
tidak pernah ada** di tabel permission, jadi selama ini hanya varian `-rt-` yang benar-benar
mencocokkan. Sudah dihapus dari guard.

## Yang sudah aman

Sisi **API mobile** tidak terkena temuan 1 dan 2. `CapabilityResolver` sudah level-authoritative
sejak awal, dan `ResolveActiveOrganization` memverifikasi header konteks organisasi. Diuji: 18 test
di `tests/Feature/API/`.

Sejak langkah 2 selesai, **web dan mobile sudah sepakat**: keduanya membaca level lewat
`CapabilityResolver` yang sama. Bendahara tidak melihat qurban di mobile, dan tidak bisa membukanya
di web.
