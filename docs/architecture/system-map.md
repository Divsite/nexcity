# Peta Sistem Nexcity

Ditulis 1 November 2026. Satu halaman untuk memahami Nexcity secara utuh — backend, web, aplikasi,
peran, data, dan rencana rilis — dan **keputusan yang sudah diambil beserta alasannya**. Detail tiap
bagian ada di dokumen yang ditautkan.

---

## 1. Bentuknya

```
                 ┌───────────────────────────────┐
                 │  Backend Laravel (repo ini)   │  ← satu-satunya sumber data & aturan
                 │  DB terpusat + API /api/v1    │
                 └──────┬─────────────────┬──────┘
          Web (Livewire)│                 │API (Sanctum)
                        ▼                 ▼
          pengurus di laptop      Aplikasi Flutter (nexcity-flutter-apps)
          data master, laporan,   warga: kartu QR, iuran, data saya, program masjid
          ekspor, pengaturan      pengurus: scan, foto, catat amal, qurban, data warga
```

| Repo | Isi | Status |
|---|---|---|
| `Divsite/nexcity` | Backend + web | Aktif |
| `samsul97/nexcity-flutter-apps` | Aplikasi mobile native | Aktif, satu-satunya aplikasi mobile |
| `Divsite/nexcity-mobile` | Pembungkus WebView | **Dipensiunkan** Oktober 2026 |

## 2. Keputusan: satu aplikasi, dua sisi, pemilih mode

**Satu aplikasi di Play Store**, bukan tiga (warga / masjid / RT). Di dalamnya ada **mode**:

```
[ Warga · RT 03 ]  [ Pengurus · RT 03 ]  [ Pengurus · Masjid Al-amanah ]
```

Alasannya:

1. **Orangnya tumpang tindih.** Ketua RT adalah warga RT itu. Bendahara masjid adalah warga di RT-nya
   dan mungkin ikut patungan qurban. Tiga aplikasi berarti orang yang sama memasang dua-tiga aplikasi
   dan login berulang.
2. **Biaya Play Store dikali tiga.** Listing, Data Safety, akun uji reviewer, review tiap rilis — dan
   karena akun developer **pribadi**, setiap aplikasi baru wajib *closed testing* 12 penguji × 14 hari
   sebelum produksi. Tiga aplikasi = tiga kali.
3. **Masjid dan RT bukan "sisi" yang berbeda.** Gojek memisah per sisi (pembeli, driver, merchant),
   bukan per layanan (GoFood dan GoRide satu aplikasi). Di Nexcity sisinya dua: warga dan pengurus.
4. **Backend sudah dirancang begitu**: capability dihitung per organisasi, bukan per aplikasi.

Keputusan ini tidak mengunci. Selama kode per mode tetap terpisah rapi, satu kode bisa di-*build*
menjadi beberapa aplikasi (Flutter flavor) kalau nanti memang perlu.

Mode di aplikasi: `Session.modes` / `Session.activeMode` — lihat
`nexcity-flutter-apps/docs/architecture/auth-and-capabilities.md` bagian "Mode".

## 3. Peran dan hak akses

| Lapis | Isi | Fungsinya |
|---|---|---|
| Role global (Spatie) | `superadmin`, `mosque_admin`, `rt_admin`, `resident` | jenis akun; menu web, hal di luar organisasi |
| Keanggotaan | `organization_user` | data organisasi mana |
| Level (jabatan) | masjid: Ketua DKM, sekretaris, keuangan, zakat, qurban, inventaris, CRM, humas · RT: ketua, sekretaris, bendahara, humas, petugas lapangan | aksi apa |
| Penugasan | relawan di satu distribusi / batch qurban | hak sementara, berhenti sendiri |

Server menghitung **capability** (`CapabilityResolver`) per organisasi; web dan aplikasi membaca
hasil yang sama. Level didefinisikan sekali oleh pemilik platform; partner hanya memilih level untuk
orangnya.

**Warga adalah fakta, bukan role.** Siapa pun yang punya `resident_profile` mendapat portal warga
(iuran, kartu, informasi), apa pun role akunnya — tanpa itu Ketua RT dengan role `rt_admin` tidak bisa
membuka iurannya sendiri (`CapabilityResolver::residentPortal`).

Detail: [../operations/authorization-audit.md](../operations/authorization-audit.md),
[mosque-structure.md](mosque-structure.md).

## 4. Akun dan masuk

| Cara masuk | Untuk | Catatan |
|---|---|---|
| Nomor HP / username / email + kata sandi | pengurus | `POST /api/auth/login` |
| **Kode WhatsApp** | semua warga | `POST /api/auth/otp/request` + `/verify`. Nomor yang belum punya akun mendapat `registration_token` setelah kodenya benar, lalu `POST /api/auth/register` (nama) membuat akun tingkat 1 |

Nomor HP disimpan dalam berbagai bentuk (`0812…`, `+62 812 …`, dan `+60812…` dari bug format
Malaysia). Pencocokan memakai `users.phone_normalized` (`App\Support\IndonesianPhone`).

**Masalah yang masih terbuka:**

- **Satu orang bisa punya dua akun.** Akun pengurus selalu dibuat baru (`PartnerUserController`), jadi
  Ketua RT yang juga warga punya akun warga *dan* akun pengurus. Login WhatsApp menolak nomor yang
  dipakai dua akun (`409`). Perbaikannya: form "tambah pengurus" di web perlu bisa **memilih warga
  yang sudah ada** dan memberinya level, bukan membuat akun baru.
- ~~**Kata sandi bawaan.**~~ Ditutup 2 November 2026. Warga baru tanpa kata sandi mendapat kata
  sandi acak. Login dengan `password`/`passworrd` ditolak di aplikasi; di web warga ditolak dan
  pengurus tertahan di halaman ganti kata sandi. `php artisan users:default-passwords` melaporkan
  sisanya dan `--retire-warga` mengganti punya warga. Sisa risiko: siapa pun yang tahu kata sandi
  bawaan seorang **pengurus** masih bisa masuk ke web dan menggantinya lebih dulu — sampai pengurus
  itu menggantinya sendiri. Karena itu perintah tadi mendaftar mereka.

## 5. Data per domain

| Domain | Tabel utama | Dokumen |
|---|---|---|
| Identitas | `users`, `user_resident_profiles` (+ `qr_token`), profil masjid/RT | [../modules/organizations-and-residents.md](../modules/organizations-and-residents.md) |
| Organisasi | `organizations` + profil per jenis, `organization_user`, lokasi | sama |
| Otorisasi | role/permission Spatie, `user_levels` (global), `user_level_permissions` | §3 |
| Amal/zakat | `charity_transactions`, pembayar, kuitansi per jenis, `charity_expenses` | [../modules/charity-transactions.md](../modules/charity-transactions.md) |
| Distribusi | `distributions`, `distribution_recipients`, `distribution_officers` | [../modules/distributions.md](../modules/distributions.md) |
| Qurban | program → paket → order → pembayaran; hewan → alokasi; batch → penerima → kupon → klaim | [qurban-domain-model.md](qurban-domain-model.md), [qurban-business-model.md](qurban-business-model.md) |
| RT | `rt_dues_*` (iuran), `announcements`, `resident_data_corrections` | — |
| Masuk | `phone_otps` | §4 |

## 6. Kaitan RT ↔ warga ↔ masjid

### Warga adalah pusat

Ditegaskan pemilik produk, 3 November 2026. Semua yang dilihat seseorang di sisi warga berpusat pada
**satu akun warga**. Tidak ada role "pekurban", "muzakki", atau "penerima". Itu **peristiwa** yang
terjadi pada akun warga (dia ikut patungan, dia membayar zakat, dia terdaftar sebagai penerima),
bukan jenis akun.

Aplikasi warga berisi dua jenis informasi yang tercampur di satu tempat:

| | Informasi umum | Informasi pribadi |
|---|---|---|
| Isinya | Setiap masjid: profil, program, papan patungan qurban, pengumuman terbuka | Iuran RT-nya, data dirinya, kartu QR, "Qurban saya", "Riwayat amal", "Hak saya" |
| Ditentukan oleh | Tidak ada. Warga **bebas memilih** masjid mana untuk amal, qurban, zakat | **Siapa dia**: RT tempat dia terdaftar, dan apa yang tercatat atas akunnya |
| Batas | Satu masjid tidak melihat apa yang warga lakukan di masjid lain | Tampil otomatis, tanpa memilih organisasi |

Aturan per domain:

| Domain | Aturannya | Di kode |
|---|---|---|
| Iuran RT | Hanya RT tempat warga terdaftar (`user_resident_profiles`). Satu RT bisa punya beberapa pungutan sekaligus (iuran bulanan, HUT RI, pengajian, dll) sebagai **skema** terpisah | `rt_dues_schemes`, `GET /v1/me/dues` |
| Amal / zakat | Di masjid mana saja. Tercatat ke akun hanya kalau pengurus menautkannya di loket ("Akun warga") | `charity_transactions.payer_user_id`, `GET /v1/me/charity` |
| Patungan qurban | Di masjid mana saja; satu warga boleh ikut di beberapa masjid | `qurban_orders.user_id`, `GET /v1/me/qurban` |
| Kupon daging | **Per masjid.** Kupon dari Masjid A hanya bisa dipindai di Masjid A. Kalau warga terdaftar sebagai penerima di A **dan** B, dia punya dua kupon dan boleh mengambil di keduanya. Hak di satu masjid tidak membuka hak di masjid lain | `QurbanDistributionService::scanCoupon` menolak kupon dari organisasi lain sebagai `invalid`; `EntitlementsTest` |
| Zakat / bansos | Per distribusi; diambil dengan kartu warga di organisasi yang mendaftarkannya | `ScanResolver` |

**Tidak semua pekurban atau penerima punya akun warga.** Orang dari luar daerah bisa ikut patungan,
dan penerima daging bisa dicatat manual (prinsip di [../modules/qurban.md](../modules/qurban.md)).
Pengurus tetap bisa mencatat mereka dengan nama saja. Yang tidak punya akun tidak melihat apa pun di
aplikasi sampai **daftar terbuka** (§8) ada: akun tingkat 1 tanpa RT, yang bisa ikut patungan dan
amal tetapi tidak punya informasi pribadi RT.

Belum dimodelkan: **arisan**. Arisan adalah undian bergilir, bukan tagihan. Kalau dicatat sebagai
skema iuran, hanya setorannya yang tercatat, tidak dengan giliran siapa yang menerima.

Fitur per warga (internal/eksternal), siapa melihat apa, lokasi per tingkat, dan menu placeholder:
[warga-features.md](warga-features.md).

### Alur data

```
RT mendata warga (web) ──► data warga: KK, alamat, HP, kartu QR
                              │
          ┌───────────────────┼─────────────────────────┐
          ▼                   ▼                         ▼
   Warga: iuran,       Masjid: pilih penerima      Warga: ikut patungan
   kartu QR, data saya  zakat & qurban per RT/KK    qurban, lihat buktinya
          │                   │
          └── koreksi ──► RT meninjau ──► data lebih benar ──► daftar penerima lebih tepat
```

Karena masjid menyusun daftar penerima dari data RT, **kelengkapan data warga** adalah hal yang paling
menentukan. Empat hal dihitung (`App\Support\ResidentCompleteness`): nomor KK, alamat, nomor HP yang
bisa dicocokkan, dan kartu QR. Pengurus RT melihatnya di tab Warga; warga melihat datanya sendiri di
"Data saya" dan bisa minta koreksi — **permintaan**, bukan edit langsung.

## 7. Web atau aplikasi

| Di aplikasi | Di web |
|---|---|
| Kartu QR, iuran saya, data saya, pengumuman | Data master, tambah/ubah warga, level pengurus |
| Scan kupon/kartu, tandai penyaluran, foto | Setup program, paket, batch, distribusi |
| Catat amal di loket, cicilan qurban | Laporan, ekspor PDF, rekonsiliasi |
| Daftar warga RT (baca), antrean koreksi | Pengaturan organisasi |

Aturannya: butuh kamera atau dikerjakan sambil berdiri → aplikasi; butuh keyboard dan layar lebar →
web. Detail: `nexcity-flutter-apps/docs/web-vs-mobile.md`.

## 8. Pendaftaran warga

| Tahap | Kapan | Bentuk |
|---|---|---|
| **Aktivasi** | rilis RT | warga yang sudah diinput RT masuk dengan kode WhatsApp — **sudah dibangun** |
| **Daftar terbuka** | rilis masjid | akun tingkat 1 (nomor HP + kode WhatsApp + nama): lihat program masjid, ikut patungan, amal yang ditautkan — **sudah dibangun 4 Nov 2026**. Naik ke tingkat 2 (warga RT) terjadi ketika RT mendata nomor itu; persetujuan dari aplikasi belum ada |
| **Hapus akun** | wajib Play Store | `DELETE /api/v1/me/account` (konfirmasi kode WhatsApp atau kata sandi) + halaman web `/hapus-akun` — **sudah dibangun**. Rinciannya di bawah |

Syarat daftar terbuka:

- ✅ **Tidak pernah menjawab apakah sebuah nomor terdaftar.** Kode dikirim ke setiap nomor yang
  valid, dengan pesan yang sama.
- ✅ **Tidak meminta NIK atau KK.**
- ⬜ **Baris patungan yang belum dibayar perlu kedaluwarsa** — belum dibangun.
- ✅ **Hapus akun.**

**Hapus akun** (`App\Actions\Accounts\CloseOwnAccount`):

- *Dihapus:*
  - akses masuk (semua token, kata sandi, nomor HP tidak lagi cocok);
  - email, username, avatar;
  - untuk akun daftar sendiri, juga nama dan HP.
- *Disimpan:*
  - catatan keuangan organisasi (amal, iuran, patungan), dengan nama yang dicatat di loket;
  - data warga yang dicatat RT. Itu data kependudukan RT; penghapusannya keputusan RT.
- *Ditolak:* pengurus yang masih memegang level, sampai jabatannya dilepas.

Halaman web `/hapus-akun` menampilkan kontak dari `ACCOUNT_DELETION_CONTACT` untuk orang yang tidak
punya aplikasi.

⚠️ **Kebijakan privasi**: halaman `privacy` dan `privacy-policy` di web ini masih berisi kebijakan
untuk *samsulhadiss.com*, bukan Nexcity. Play Store butuh kebijakan privasi Nexcity sendiri.

## 9. Rencana rilis

Tanggal Islam perkiraan: Ramadan 1448 H sekitar awal Februari 2027, Iduladha sekitar pertengahan
Mei 2027.

| Kapan | Apa |
|---|---|
| Nov 2026 | Inti: mode, login WhatsApp, data warga — **sudah dibangun**. Merge PR, staging, uji di HP |
| Des 2026 | **Rilis RT**: iuran + data warga + kartu QR di satu RT nyata (RW04). Kunci tanda tangan rilis, Play Store internal testing, mulai closed testing ≥ 12 penguji |
| Jan 2027 | Closed testing 14 hari. Uji tangan semua fitur masjid yang belum diuji |
| Feb–Mar 2027 | **Musim zakat**: catat amal, distribusi. Buka daftar terbuka dan papan patungan qurban |
| Mei 2027 | **Iduladha**: scan kupon, foto penyaluran, bukti untuk pekurban |

## 10. Pekerjaan terbuka yang menyangkut inti

1. Tambah pengurus dari warga yang sudah ada (§4) — mencegah akun ganda
2. ~~Tutup login kata sandi bawaan (§4)~~ — selesai; tinggal pengurus mengganti sendiri
3. Antrean koreksi data warga di web (sekarang hanya di aplikasi)
4. Uji kirim WhatsApp lewat OpenClaw ke nomor pribadi (`WHATSAPP_OTP_DRIVER=openclaw`): format
   tujuan `628…@s.whatsapp.net` belum pernah dicoba
5. ~~Daftar terbuka + hapus akun (§8)~~ — selesai 4 Nov 2026. Tersisa: kedaluwarsa baris patungan
   yang belum dibayar, dan **kebijakan privasi Nexcity** (yang ada milik samsulhadiss.com)
6. Server staging dan CI untuk backend (repo ini belum punya CI)
7. ~~Amal dan bagian qurban dari loket tidak tersambung ke akun~~ — selesai lewat "Akun warga"
   di form loket ([charity-transactions.md](../modules/charity-transactions.md)). Sekaligus
   ditemukan: amal yang dicatat dari aplikasi sebelum 3 Nov 2026 tersimpan tanpa nominal
