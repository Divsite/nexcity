# Struktur Organisasi Masjid

Catatan desain, 8 Agustus 2026. Menjawab: **apakah qurban dan zakat masuk struktur organisasi
masjid atau tidak?**

## Temuan: dua hal berbeda ada dalam satu daftar

`user_levels` untuk masjid saat ini berisi delapan slug. Dibaca dari **nama tampilannya**, bukan
slug-nya, daftar itu terbelah:

| Slug | Nama sebenarnya | Sifat |
|---|---|---|
| `mosque-superadmin` | Ketua DKM | **Jabatan struktural** |
| `mosque-secretary` | Sekretaris Masjid | **Jabatan struktural** |
| `mosque-finance` | Keuangan Masjid | **Jabatan struktural** |
| `mosque-humas` | Humas/Media | **Jabatan struktural** |
| `mosque-inventory` | Petugas Inventaris | **Jabatan struktural** |
| `mosque-crm` | Petugas Donasi/CRM | **Jabatan struktural** |
| `mosque-officer` | **Petugas Zakat** | **Kepanitiaan** |
| `mosque-qurban` | **Petugas Qurban** | **Kepanitiaan** |

Dua hal yang tabiatnya berbeda dimasukkan ke satu kolom.

### Bukti dari data

**33 dari 56** anggota Islamic Center Al-amanah memegang `mosque-officer` (Petugas Zakat).

Angka itu janggal untuk sebuah jabatan struktural — masjid tidak punya 33 sekretaris. Tapi sangat
wajar untuk **panitia zakat saat Ramadan**: banyak orang, sementara, sebagian relawan.

Data itu sendiri sudah memberi tahu bahwa `mosque-officer` bukan jabatan.

## Perbedaan tabiatnya

| | Jabatan struktural | Kepanitiaan |
|---|---|---|
| Masa berlaku | Periode kepengurusan | Satu program / satu musim |
| Jumlah orang | Sedikit, satu per posisi | Banyak |
| Siapa | Pengurus DKM | Relawan **atau** pengurus |
| Muncul | Selalu ada | Saat ada program (Idul Adha, Ramadan) |
| Contoh | Sekretaris, Bendahara | Panitia Qurban 1447 H |

## Masalah yang ditimbulkan

`organization_user` menyimpan **satu `level_slug` per orang per organisasi**. Konsekuensinya nyata:

**Bendahara yang juga jadi panitia qurban tidak bisa dinyatakan.** Pilihannya hanya dua, dan
keduanya salah:

- ganti level-nya jadi `mosque-qurban` → dia **kehilangan** akses keuangannya
- biarkan `mosque-finance` → dia tidak bisa scan kupon qurban

Padahal di lapangan, pengurus merangkap panitia adalah hal biasa.

Inilah kemungkinan besar alasan `mosque-officer` menjadi kabur: ia jadi tempat penampungan untuk
"orang yang mengerjakan sesuatu", karena struktur datanya tidak bisa menyatakan rangkap peran.

## Yang sudah jelas dan tidak perlu diubah

Struktur DKM yang permanen sudah benar dan sudah dipetakan: Ketua, Sekretaris, Keuangan, Humas,
Inventaris, CRM/Donasi. Itu jabatan sungguhan dengan wewenang tetap.

## Yang perlu diputuskan

**Apakah kepanitiaan dijadikan konsep tersendiri, atau tetap sebagai level?**

### Pilihan A — biarkan sebagai level (perubahan paling kecil)

Hanya perbaiki penamaannya: `mosque-officer` → `mosque-zakat`, supaya sejajar dengan
`mosque-qurban` dan berhenti berbohong.

- Untung: tidak ada perubahan struktur, bisa dikerjakan sekarang
- Rugi: rangkap peran tetap tidak bisa dinyatakan; panitia tidak pernah kedaluwarsa, jadi relawan
  qurban tahun lalu tetap bisa scan kupon tahun ini

### Pilihan B — pisahkan kepanitiaan dari jabatan

Jabatan tetap di `organization_user.level_slug`. Kepanitiaan pindah ke tabelnya sendiri, misalnya
`organization_committee_members`: organisasi, user, jenis panitia, program/tahun, masa berlaku.

Capability seseorang = jabatan **∪** kepanitiaan yang masih berlaku.

- Untung: bendahara bisa merangkap panitia qurban tanpa kehilangan apa pun; kepanitiaan berakhir
  sendiri saat programnya selesai; relawan tidak menumpuk wewenang antar tahun
- Rugi: satu tabel dan satu migrasi baru; `CapabilityResolver` perlu menggabungkan dua sumber

### Rekomendasi

**Pilihan B**, tapi tidak sekarang.

Alasannya bukan teknis melainkan urutan: kepanitiaan baru benar-benar dipakai saat alur distribusi
di lapangan berjalan (Fase 3). Sebelum ada yang memindai kupon, kemampuan menyatakan "bendahara
merangkap panitia qurban 1447 H" belum menghasilkan apa-apa.

Yang layak dikerjakan sekarang adalah **bagian dari Pilihan A yang tidak merugikan**: mengganti nama
`mosque-officer` menjadi `mosque-zakat`. Slug yang berbohong akan menyesatkan setiap orang yang
membacanya, termasuk saat Pilihan B dikerjakan nanti.

> ⚠️ Mengganti slug menyentuh `organization_user.level_slug`, `user_levels.slug`, dan setiap
> pemeriksaan `level_slug` di kode (`like 'mosque-%'` aman; perbandingan persis tidak). Perlu
> migrasi, bukan sekadar ubah seeder.

## Kaitan dengan level global (langkah 3 audit)

Tidak bertabrakan. Menjadikan level global adalah soal **di mana definisinya disimpan** — dan itu
tetap benar untuk jabatan maupun kepanitiaan, karena keduanya ditentukan pemilik platform.

Pilihan B mengubah **berapa banyak** yang bisa dipegang seseorang dan **berapa lama**, bukan siapa
yang mendefinisikannya.
