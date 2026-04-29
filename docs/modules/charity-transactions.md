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

## Daily report

Daily summary dirancang agar OpenClaw cukup menembak satu API.
Logic history harian sebaiknya tetap dihitung di Nexcity, bukan di layer notif.
