# Architecture Overview

## Stack

- Backend: Laravel
- UI server-rendered: Blade
- Interactive forms/tables: Livewire, Vue kecil per halaman/form
- Table listing: Rappasoft Livewire Tables
- PDF export: DomPDF
- Auth & roles: Spatie Permission + membership organisasi

## Domain utama

### 1. Organizations

Organisasi menjadi konteks partner. Tipe yang aktif di project saat ini:
- `mosque`
- `rt`
- tipe lain sudah ada di model, tapi fokus operasional sekarang masih masjid dan RT

Data organisasi tersebar di:
- `organizations`
- `organization_profiles`
- profile turunan seperti `organization_mosque_profiles`, `organization_rt_profiles`
- `organization_user` untuk membership
- `user_levels` + `user_level_permissions`

### 2. Users & Profiles

`users` menyimpan akun utama.
Profile domain dipisah per konteks:
- `user_resident_profiles`
- `user_mosque_profiles`
- `user_rt_profiles`

Per user bisa punya:
- role global (contoh `superadmin`, `resident`, `mosque_admin`, `rt_admin`)
- membership organisasi lewat pivot `organization_user`
- level partner seperti `mosque-superadmin`, `mosque-officer`, `rt-superadmin`, dll

### 3. Charity domain

Modul charity berpusat di:
- `charity_types`
- `charity_transactions`
- receipts/detail table sesuai tipe amal
- `distribution_classes`
- `distributions`
- `distribution_recipients`
- `distribution_fund_sources`
- `charity_expenses`

## Pola partner context

Banyak modul membaca konteks organisasi dari membership user login.
Artinya:
- superadmin biasanya melihat semua data
- partner user RT/masjid hanya melihat data organisasinya

Dampaknya:
- login partner yang belum punya membership primary atau profile organisasi sering terlihat seperti “data kosong”
- seed user saja tidak cukup; membership organisasi juga harus benar

## UI flow penting

- daftar besar memakai Livewire table
- form distribusi, resident, organization memakai Blade + Vue helper
- filter lokasi memakai endpoint AJAX `ajax.locations.*`
- dropdown advanced location tidak membaca organisasi, tapi membaca master lokasi (`loc_*`)
