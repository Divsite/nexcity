# Charity Transactions

## Cakupan

Modul ini menangani:
- transaksi amal/zakat
- package payer / family flow
- receipt detail per charity type
- invoice print
- webhook OpenClaw untuk transfer / QRIS
- daily summary API/report

## Komponen utama

- `app/Http/Controllers/Charities/CharityTransactionController.php`
- `app/Services/Charities/CharityTransactionService.php`
- `app/Services/Notifications/OpenclawWebhookService.php`

## Catatan desain

- uang/beras final sering dihitung dari detail receipts, bukan selalu dari kolom summary transaksi
- payment method penting untuk reporting dan notifikasi
- transfer / qris mengirim webhook ke OpenClaw berdasarkan group organisasi
- **nominal hanya ada di kuitansi.** Kolom `total_money`, `total_rice`, `received_at`, `notes`
  di `charity_transactions` sudah dihapus (migrasi `..._000024`). Jenis amal menentukan tabel
  kuitansinya lewat `CharityTransactionService::receiptClassFor($slug)`: `zakat-fitrah`, `fidyah`,
  `zakat-mal`, `infaq`, `sedekah`, `waqf`. Jenis lain tidak punya tempat untuk nominal.

## Catat amal dari aplikasi (`POST /api/v1/charity-transactions`)

`API/Charities/QuickCharityController`. Jalur pendek untuk loket: jenis, pembayar, nominal.

- Nominal ditulis ke kuitansi jenisnya, sama seperti web. Jenis tanpa kuitansi **ditolak** (422,
  "Catat lewat web") supaya tidak tersimpan Rp 0.
- **Bug lama (sampai 3 Nov 2026):** endpoint ini menulis nominal ke kolom yang sudah dihapus, dan
  Laravel membuang atribut yang kolomnya tidak ada tanpa error. Akibatnya setiap amal yang dicatat
  dari aplikasi tersimpan **tanpa nominal**. Untuk mencari baris yang terdampak:

  ```sql
  SELECT t.id, t.organization_id, t.payer_name, t.created_at
  FROM charity_transactions t
  WHERE t.received_by IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM charity_fitrah_receipts r WHERE r.charity_transaction_id = t.id)
    AND NOT EXISTS (SELECT 1 FROM charity_fidya_receipts r WHERE r.charity_transaction_id = t.id)
    AND NOT EXISTS (SELECT 1 FROM charity_mal_receipts r WHERE r.charity_transaction_id = t.id)
    AND NOT EXISTS (SELECT 1 FROM charity_donation_receipts r WHERE r.charity_transaction_id = t.id)
    AND NOT EXISTS (SELECT 1 FROM charity_alms_receipts r WHERE r.charity_transaction_id = t.id)
    AND NOT EXISTS (SELECT 1 FROM charity_endowment_receipts r WHERE r.charity_transaction_id = t.id);
  ```

  Nominalnya tidak bisa dipulihkan dari database; harus dicocokkan dengan catatan kertas atau mutasi.

## Akun warga di loket

Pengurus bisa menautkan pembayaran ke akun warga, supaya muncul di aplikasi warga itu
("Riwayat amal", "Qurban saya").

- `POST /api/v1/accounts/lookup` (`add-mosque-charity-transactions` atau `add-qurban`, 30/menit):
  cari lewat `phone`, `qr_token` kartu warga, atau `self: true`. Balasan hanya nama dipendekkan
  ("Citra L."), 4 digit akhir HP, dan `ref`. Tidak ada id, alamat, atau nama lengkap. Nomor yang
  dipakai dua akun → `ambiguous`, dicatat dengan nama saja.
- `ref` (`App\Support\AccountRef`) terenkripsi, hanya berlaku di organisasi yang mencari dan selama
  30 menit. Id akun mentah tidak diterima, jadi pengurus tidak bisa menempelkan transaksi ke akun
  sembarang.
- Dipakai sebagai `payer_ref` (catat amal → `charity_transactions.payer_user_id`) dan `owner_ref`
  (`qurban-slots`, `qurban-participants` → `qurban_orders.user_id`). Kalau ada `ref`, nama dan HP
  boleh kosong; diisi dari akun.
- Pengurus yang mengambil bagian patungan untuk dirinya sendiri sekarang harus memilih "Saya
  sendiri". Sebelumnya bagiannya selalu tanpa pemilik.
- Warga membaca riwayatnya di `GET /api/v1/me/charity` (dua musim terakhir).

## Daily report

Daily summary dirancang agar OpenClaw cukup menembak satu API.
Logic history harian sebaiknya tetap dihitung di Nexcity, bukan di layer notif.
