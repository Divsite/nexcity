# Peta Fitur Warga

Ditulis 3 November 2026, dari arahan pemilik produk. Pelengkap
[system-map.md](system-map.md) §6 "Warga adalah pusat". Dokumen ini menjawab: **fitur apa yang
dimiliki warga, siapa yang boleh melihat apa, dan apa yang sudah ada atau masih rencana.**

---

## 1. Dua jenis fitur

| | Fitur internal | Fitur eksternal |
|---|---|---|
| Artinya | Urusan warga dengan **RT tempat dia terdaftar** | Hubungan warga dengan **organisasi mana pun**: masjid lain, RT lain |
| Contoh | Iuran, data warga, kartu QR, keluhan, bansos RT, posyandu | Amal/zakat di masjid mana saja, patungan qurban, kupon daging, kegiatan terbuka RT lain |
| Siapa yang melihat | Warga itu sendiri (pribadi), atau warga RT itu (internal RT) | Siapa pun, atau sesuai yang dipilih penerbitnya |
| Ditentukan oleh | `user_resident_profiles.organization_id` | Pilihan warga sendiri |

Iuran adalah contoh paling jelas dari internal yang pribadi. Hanya warga itu yang tahu tagihannya;
tetangga sebelah tidak. Iuran juga hanya dari RT-nya sendiri, bukan RT lain.

## 2. Lokasi: dipisah per tingkat

Warga, organisasi (masjid, RT), distribusi, dan batch qurban masing-masing menyimpan lokasinya
sebagai **kolom terpisah**, bukan satu teks alamat:

```
negara → provinsi → kota/kabupaten → kecamatan → kelurahan/desa → RW → RT
country_id  province_id  city_id       district_id   village_id  citizens_association_id  neighborhood_association_id
```

Tabelnya `loc_*`. Pemisahan ini sengaja, supaya informasi bisa disaring **satu tingkat demi satu
tingkat**: sekelurahan, sekecamatan, sekota, seprovinsi.

**Sudah dipakai:** feed pengumuman `GET /api/v1/announcements?area=village|district|city|province`
menambahkan pengumuman **umum** dari organisasi yang lokasinya sama dengan warga pada tingkat itu.
Lokasi warga diambil dari data warganya; kalau kosong, dari RT-nya.

**Belum dipakai:** Temukan (cari masjid/RT/program) belum disaring per wilayah. Urutan menurut jarak
GPS adalah keputusan lain (butuh izin lokasi) dan belum diambil.

## 3. Siapa melihat pengumuman

| Penerbit memilih | Masjid | RT |
|---|---|---|
| `public` (umum) | Siapa pun | Siapa pun: warga RT lain juga boleh tahu kegiatan terbukanya |
| `members` (anggota) | Jamaah/anggota masjid itu | **Hanya warga RT itu**. Warga RT lain tidak mendapatkannya |
| `staff` (pengurus) | Pengurus masjid itu | Pengurus RT itu |

- **Diputuskan 3 November 2026:** RT boleh membuat pengumuman umum. Sebelumnya hanya masjid yang
  boleh (`Announcement::PUBLIC_PUBLISHERS`).
- **Boleh dibaca tidak sama dengan dikirim.** Pengumuman umum tidak masuk sendiri ke feed orang
  asing. Pengumuman umum tampil:
  - di halaman organisasinya, untuk siapa pun;
  - di feed warga yang memilih wilayah (`area`) yang mencakup organisasi itu.
- Ditolak dengan 404, bukan 403, supaya tidak bocor bahwa RT sedang mengumumkan sesuatu.

## 4. Peta fitur warga

Status: ✅ ada · 🟡 sebagian · 📋 placeholder (menu sudah ada di web, isinya belum) · ⬜ belum ada sama sekali.

### Internal: dengan RT sendiri

| Fitur | Pribadi / internal RT | Status | Di mana |
|---|---|---|---|
| Iuran saya | Pribadi | ✅ API + aplikasi; 📋 menu portal web `resident-dues` | `GET /v1/me/dues`, Aktivitas saya |
| Data saya + minta koreksi | Pribadi | ✅ | `GET /v1/me/profile` |
| Kartu QR warga | Pribadi | ✅ (bisa tanpa internet) | Beranda |
| Bansos / sembako RT | Pribadi (penerima) | ✅ penerimaan; 📋 menu RT `rt-social-distribution` | Hak saya, Aktivitas saya |
| Santunan warga | Pribadi (penerima) | 📋 `rt-social-charity` | — |
| Pengumuman RT anggota | Internal RT | ✅ | Beranda, halaman RT |
| Informasi / Berita RT | Internal atau umum | 📋 `rt-news`, `resident-information` | — |
| Keluhan / kritik & saran | Pribadi ke RT | 📋 `rt-feedback`, `resident-complaints` | — |
| Kegiatan / acara | Internal atau umum | 🟡 pengumuman berisi waktu & tempat; 📋 `rt-events`, `resident-events` | — |
| Kerja bakti | Internal RT | 📋 `rt-cleanliness` | — |
| Siskamling / ronda | Internal RT (jadwal per warga) | 📋 `rt-security-watch` | — |
| Posyandu | Pribadi (balita/lansia di KK) | 📋 `rt-health-posyandu` | — |
| Inventaris & pengajuan | Internal RT (warga mengajukan pinjam) | 📋 `rt-inventory` | — |
| Keanggotaan | Internal RT | ✅ web pengurus | — |
| Arisan / pengajian RT | Internal RT | 🟡 setoran bisa jadi skema iuran; undian arisan belum dimodelkan | — |

### Eksternal: dengan organisasi mana pun

| Fitur | Status | Di mana |
|---|---|---|
| Daftar masjid & RT, program, papan patungan | ✅ | Temukan, halaman organisasi |
| Amal / zakat / infaq di masjid mana saja | ✅ (ditautkan pengurus di loket) | Riwayat amal, Aktivitas saya |
| Patungan qurban, beberapa masjid sekaligus | ✅ | Qurban saya, Aktivitas saya |
| Kupon daging (per masjid; dua masjid = dua kupon) | ✅ | Hak saya, Aktivitas saya |
| Pengumuman umum masjid / RT lain | ✅ halaman organisasi + `area` | Pengumuman |
| CRM & donatur masjid (warga sebagai donatur) | 📋 `mosque-crm` | — |
| Ajakan jamaah | ⬜ (ada di mockup v0.4) | — |
| Notifikasi peristiwa ("daging siap diambil") | ⬜ | — |

### Gabungan

**Aktivitas saya** (`GET /api/v1/me/activity`) mengumpulkan semua yang tercatat atas akun warga,
dari masjid dan RT mana pun, dalam satu daftar. Isinya amal, patungan, kupon, bantuan, dan iuran.
Ditambah:

- `needs_action`: kupon siap diambil, iuran belum bayar, patungan belum lunas;
- `summary`: amal tahun ini dan di berapa masjid, iuran lunas dari berapa tagihan.

## 5. Menu placeholder di backend

Rencana ke depan yang sengaja dibuat sebagai menu kosong (`UserMenuSeeder`,
`ModulePlaceholderController`). Kolom "Warga" menjelaskan apa yang nanti dilihat warga.

| Konteks | Menu | Slug | Warga nanti |
|---|---|---|---|
| RT | Inventaris & Pengajuan | `rt-inventory` | Mengajukan pinjam tenda, kursi |
| RT | Siskamling / Ronda | `rt-security-watch` | Jadwal rondanya sendiri |
| RT | Kerja Bakti | `rt-cleanliness` | Jadwal + konfirmasi hadir |
| RT | Distribusi Sembako / Bansos | `rt-social-distribution` | Hak saya (sudah, lewat distribusi) |
| RT | Santunan Warga | `rt-social-charity` | Hak saya |
| RT | Kegiatan Masyarakat | `rt-events` | Kegiatan internal atau terbuka |
| RT | Posyandu | `rt-health-posyandu` | Jadwal + catatan untuk anggota KK |
| RT | Berita | `rt-news` | Pengumuman (sebagian sudah) |
| RT | Kritik & Saran | `rt-feedback` | Kirim keluhan, lihat tanggapan |
| RT | Laporan RT | `rt-reports` | Tidak; untuk pengurus |
| Masjid | Inventaris | `mosque-inventory` | Tidak; untuk pengurus |
| Masjid | CRM & Donatur | `mosque-crm` | Riwayat donasi (sebagian lewat Riwayat amal) |
| Masjid | Laporan Amal / Distribusi | `mosque-report-*` | Tidak; transparansi publik bisa jadi fitur nanti |
| Portal warga | Iuran | `resident-dues` | ✅ di aplikasi |
| Portal warga | Informasi | `resident-information` | Pengumuman |
| Portal warga | Keluhan | `resident-complaints` | = Kritik & Saran RT |
| Portal warga | Acara | `resident-events` | = Kegiatan RT |

## 6. Yang masih perlu diputuskan

1. **Area bawaan feed.** Sekarang feed hanya berisi organisasi warga sendiri sampai warga memilih
   wilayah. Perlu diputuskan apakah "sekelurahan" ikut tampil otomatis.
2. **Kegiatan RT**: cukup sebagai pengumuman yang memuat waktu dan tempat, atau perlu fitur
   tersendiri dengan konfirmasi hadir?
3. **Arisan**: dimodelkan sebagai undian bergilir, atau cukup setorannya saja?
4. **Transparansi masjid**: apakah laporan amal ringkas boleh dibuka untuk umum?
