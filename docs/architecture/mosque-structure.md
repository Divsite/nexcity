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
| `mosque-zakat` | **Petugas Zakat** | **Kepanitiaan** |
| `mosque-qurban` | **Petugas Qurban** | **Kepanitiaan** |

Dua hal yang tabiatnya berbeda dimasukkan ke satu kolom.

### Bukti dari data

**33 dari 56** anggota Islamic Center Al-amanah memegang level Petugas Zakat.

Angka itu janggal untuk sebuah jabatan struktural — masjid tidak punya 33 sekretaris. Tapi sangat
wajar untuk **panitia zakat saat Ramadan**: banyak orang, sementara, sebagian relawan.

Data itu sendiri sudah memberi tahu bahwa itu bukan jabatan.

> Slug-nya dulu `mosque-officer` — nama yang menyamarkan isinya. Sudah diganti jadi `mosque-zakat`
> pada 8 Agustus 2026.

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

Inilah kemungkinan besar alasan level Petugas Zakat menjadi kabur: ia jadi tempat penampungan untuk
"orang yang mengerjakan sesuatu", karena struktur datanya tidak bisa menyatakan rangkap peran.

## Yang sudah jelas dan tidak perlu diubah

Struktur DKM yang permanen sudah benar dan sudah dipetakan: Ketua, Sekretaris, Keuangan, Humas,
Inventaris, CRM/Donasi. Itu jabatan sungguhan dengan wewenang tetap.

## Cara merapikannya

Sebelum merancang tabel baru, satu hal perlu dilihat: **mekanismenya sudah ada.**

```
distribution_officers          : distribution_id + officer_id
qurban_distribution_officers   : qurban_distribution_batch_id + officer_id
```

Kedua tabel itu menugaskan orang ke **distribusi atau batch tertentu**. Itu persis kepanitiaan —
terikat program konkret, dan berakhir sendiri saat programnya selesai. Yang belum ada hanyalah
**sambungannya ke otorisasi**.

### Bentuk yang disarankan

| Konsep | Disimpan di | Memberi wewenang |
|---|---|---|
| **Jabatan** | `organization_user.level_slug` | Wewenang tetap selama menjabat |
| **Penugasan** | `distribution_officers`, `qurban_distribution_officers` | Wewenang operasional **untuk distribusi/batch itu saja** |

Capability seseorang = **jabatan ∪ penugasan yang masih berjalan**.

Ini lebih presisi daripada tabel kepanitiaan generik. Relawan yang ditugaskan ke batch qurban 1447 H
bisa memindai kupon **batch itu** — bukan setiap batch, selamanya. Wewenangnya berakhir bersama
programnya, tanpa perlu ada yang mencabut manual.

### Yang berubah bagi tiap orang

| Kasus | Sekarang | Setelah dirapikan |
|---|---|---|
| Bendahara merangkap panitia qurban | Tidak bisa dinyatakan | `mosque-finance` + ditugaskan ke batch |
| Relawan qurban dadakan | Diberi level `mosque-qurban` permanen | Tanpa level, cukup ditugaskan ke batch |
| Pengurus qurban tetap | `mosque-qurban` | Tetap `mosque-qurban` |

Perhatikan akibatnya: **level `mosque-zakat` dan `mosque-qurban` tidak lagi dibutuhkan untuk
relawan.** Keduanya cukup dipakai untuk **koordinator** panitia yang memang jabatan. Itu menjelaskan
kenapa 33 orang menumpuk di satu level — mereka dipaksa masuk ke sana karena penugasan tidak
tersambung ke wewenang.

### ✅ Sudah dikerjakan — 8 Agustus 2026

`AssignmentCapabilities` (`app/Services/Authorization/`) disambungkan ke `CapabilityResolver`.
Capability seseorang kini = **global ∪ jabatan ∪ penugasan yang masih berjalan**.

**Sifatnya menambah, tidak pernah mencabut.** Yang jabatannya sudah mencakup pekerjaan itu tidak
kehilangan apa pun; menyalakannya tidak bisa mengunci siapa pun.

Yang diberikan sebuah penugasan hidup:

```
browse-mosque-charity-distributions
read-mosque-charity-distributions
edit-mosque-charity-distributions
scan-qurban-coupon
scan-zakat-coupon
```

Berakhir sendiri: begitu `distributions.status` menjadi `completed`, pemberiannya berhenti. Tidak ada
yang perlu ingat mencabut relawan setelah Idul Adha.

Diuji (`tests/Feature/API/CapabilityResolverTest.php`):

| Kasus | Hasil |
|---|---|
| Relawan tanpa jabatan, ditugaskan | dapat `scan-qurban-coupon` |
| Bendahara merangkap panitia | keuangan **tetap** + dapat scan |
| Distribusi sudah `completed` | tidak dapat apa-apa |
| Ditugaskan di organisasi lain | tidak dapat apa-apa di sini |

**Belum berlaku untuk RT.** Kepanitiaan adalah pola masjid; distribusi RT dikerjakan pengurus yang
sudah punya jabatan. Memperluasnya ke RT adalah keputusan produk tersendiri.

⚠️ Satu catatan: `edit-mosque-charity-distributions` lebih luas dari yang dibutuhkan — itu sekadar
yang diminta endpoint penandaan saat ini. Permission `mark-distribution-recipient` yang lebih sempit
akan membuat relawan bisa mencatat penyerahan tanpa sekaligus bisa mengubah struktur distribusinya.

## Kaitan dengan level global

Level sudah dijadikan global pada 8 Agustus 2026 (langkah 3 audit) dan itu **tidak bertabrakan**
dengan rencana di atas. Level global menjawab *di mana definisi disimpan* — tetap benar untuk
jabatan maupun penugasan, karena keduanya ditentukan pemilik platform.

Yang diubah rencana penugasan adalah **berapa banyak** peran yang bisa dipegang seseorang dan
**berapa lama** — bukan siapa yang mendefinisikannya.
