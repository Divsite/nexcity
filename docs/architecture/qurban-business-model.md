# Model bisnis qurban

Dokumen ini merekam **keputusan bisnis**, bukan rancangan tabel. Untuk struktur
data lihat `qurban-domain-model.md`; untuk urutan pengerjaan lihat
`../operations/qurban-implementation-roadmap.md`.

Ditulis 12 Agustus 2026, setelah pembahasan yang tuntas soal siapa menjual apa
kepada siapa. Sebelum ini yang tercatat hanya nama tabel — `qurban_vendors`,
`qurban_affiliate_partners`, `qurban_commissions` — tanpa satu pun keputusan
tentang bentuk transaksinya.

---

## Keputusan inti

**Masjid yang menjual.** Masjid menerima titipan hewan dari vendor, menagih
pembeli, menyetor harga dasar ke vendor, dan menahan biaya koordinasinya.

**Pembeli tidak berhubungan dengan vendor.** Itu disengaja. Kalau aplikasi
menampilkan beberapa vendor dan meminta pembeli memilih, beban verifikasi
berpindah ke orang yang tidak punya alat untuk memverifikasi: umur hewan cukup?
tidak cacat? sah untuk qurban? Ketika masjid yang menjual, **masjid menjadi
penjamin** — dan jaminan itulah barang dagangannya.

**Mulai dari patungan, katalog belakangan.** Alasannya di bawah.

---

## Kenapa patungan lebih dulu, bukan katalog hewan utuh

Kambing di Masjid A dan kambing di Masjid B adalah barang yang **sama persis**.
Dua toko yang menjual barang identik hanya bisa dibedakan oleh harga, jadi
masjid akan saling menurunkan harga sampai komisinya habis — dan yang lebih
merusak, **takmir jadi saling sikut**. Kepercayaan yang membuat masjid berharga
justru yang pertama hilang.

Patungan berbeda secara struktural:

1. **1/7 sapi di Al-amanah bukan barang yang sama dengan 1/7 sapi di Darul
   Muminin.** Yang dibeli bukan hanya daging, tapi **ke mana dagingnya pergi**.
   Itu tidak bisa dibandingkan harganya karena memang bukan barang yang sama.
2. **Patungan butuh koordinator yang dipercaya tujuh orang asing** untuk
   memegang uang mereka dan menjamin hasilnya. Itu persis peran sosial masjid.
   Vendor tidak bisa. Marketplace tidak bisa.
3. Karena itu, patungan adalah **satu-satunya bagian dari model ini yang hanya
   masjid yang bisa kerjakan** — dan satu-satunya yang tidak bisa direbut
   pemain besar begitu idenya terbukti jalan.

Katalog hewan utuh boleh menyusul setelah patungan terbukti. Memulai dari
katalog berarti memulai dari pertarungan harga yang tidak bisa dimenangkan.

---

## Harga dipecah, tidak digabung

Ini yang membunuh perang harga tanpa melarang apa pun.

```
Kambing Reguler 20–25 kg
  Harga hewan (dari vendor)        Rp 2.500.000
  Biaya koordinasi masjid          Rp   250.000
  ─────────────────────────────────────────────
  Total                            Rp 2.750.000
```

**Harga hewan datang dari vendor dan sama di semua masjid.** Masjid tidak bisa
menurunkannya — bukan miliknya. Yang berbeda antar masjid hanya biaya
koordinasi, dan itu tampil sebagai baris tersendiri.

Akibatnya membandingkan dua masjid berarti membandingkan **jasanya**: masjid
mana yang memberi video penyembelihan, laporan penerima, kabar per tahap. Itu
persaingan yang memperbaiki produk, bukan yang menjatuhkan harga.

Konsekuensi teknis: `qurban_program_packages.price` perlu dipecah jadi
`base_price` (dari vendor) dan `service_fee` (milik masjid). Total adalah
turunan, bukan kolom.

---

## Yang dijual sebenarnya bukan hewan

Vendor menjual hewan lebih murah, dan akan selalu lebih murah. Bersaing di sana
tidak ada gunanya. Yang dijual lewat masjid:

| Beli langsung ke vendor | Lewat masjid di aplikasi |
|---|---|
| Hewan | Hewan **+ sembelih + salur + bukti** |
| Sisanya urus sendiri | Selesai setelah bayar |
| Daging jadi urusan pembeli | Daging ke mustahik lingkungan |
| Tidak ada bukti | Foto, video, daftar penerima |

**Pelanggan utamanya perantau** — orang yang bekerja jauh dan ingin berqurban di
kampungnya atau di masjid dekat rumah tanpa bisa hadir. Mereka tidak bisa beli
langsung ke vendor sama sekali, dan mereka yang paling butuh bukti.

Karena itu pelacakan tahap hewan bukan fitur tambahan. **Itu produknya.**

---

## Affiliate: dua hal berbeda yang selama ini disebut satu nama

Kata "affiliate" menyembunyikan dua pelaku yang sangat berbeda, dan
menyamakannya adalah cara tercepat merusak model ini.

### A. Ajakan jamaah — tanpa uang

Siapa saja membagikan tautan program. Kalau ada yang berqurban lewat tautan itu,
yang bersangkutan mendapat **pengakuan, bukan komisi**:

> "12 orang berqurban lewat ajakan Anda"

Papan peringkat di dalam masjid, bukan lintas masjid.

**Kenapa tidak dibayar:** di dalam kerangka agama, mengajak orang berbuat baik
sudah membawa ganjarannya sendiri. Menempelkan uang ke situ bukan menaikkan
nilainya — menurunkannya. Dan secara praktis: qurban setahun sekali, satu orang
mungkin mengajak lima temannya. Uang bukan yang menggerakkan mereka.

Dari sinilah sebagian besar volume akan datang.

### B. Mitra penggalang — dibayar, sedikit, berkontrak

Sejumlah kecil mitra formal: paguyuban perantau, komunitas, perusahaan yang
menjalankan CSR. Mereka membawa puluhan sampai ratusan pesanan dan melakukan
kerja nyata — mengorganisir, menagih, menindaklanjuti.

Mereka dibayar, dengan aturan yang tidak boleh dilanggar:

1. **Komisi diambil dari biaya koordinasi masjid**, tidak pernah dari harga
   hewan. Uang yang pembeli anggap sebagai uang qurban tidak boleh dipotong
   untuk komisi siapa pun.
2. **Nominal tetap per pesanan, bukan persentase.** Rp 50.000 per pesanan, sama
   untuk kambing maupun sapi utuh. Persentase memberi mitra alasan mendorong
   orang ke ibadah yang lebih mahal, dan itu tidak boleh ada.
3. **Terlihat oleh pembeli.** Kalau ada yang dibayar, sebut di rincian harga.
4. **Pengurus masjid tidak boleh jadi mitra berbayar.** Mereka sudah bertugas;
   membayar mereka lagi mengaburkan batas antara amanah dan penghasilan.
5. **Berkontrak dan tercatat** — muncul namanya di laporan program.

### Kenapa dibatasi begitu ketat

Komisi atas ibadah itu sensitif. Satu ustadz yang mempersoalkannya di media
sosial bisa menghabisi produk ini, dan kekhawatirannya tidak akan sepenuhnya
keliru — memotong uang qurban untuk komisi mengulang persoalan yang sama dengan
memotong zakat untuk komisi.

Batasan di atas menempatkan bayaran pada **jasa pemasaran yang nyata**, dibiayai
dari **jasa koordinasi masjid**, bukan dari ibadahnya. Tetap perlu dimintakan
pendapat dari yang berwenang secara syar'i sebelum dijalankan; dokumen ini
merekam niatnya, bukan menghakimi kehalalannya.

### Soal model Shopee

Mekanisnya boleh sama — tautan ber-kode, pesanan terhubung, komisi terhitung.
Yang tidak boleh ditiru adalah **membuka pendaftaran affiliate untuk siapa
saja**. Marketplace bisa karena barangnya barang. Di sini barangnya ibadah, dan
setiap mitra berbayar harus bisa disebut namanya.

---

## Risiko per pihak

| Pihak | Untungnya | Risikonya | Penanganan |
|---|---|---|---|
| **Pembeli** | Kepastian + bukti | Lebih mahal dari beli langsung | Jangan bersaing di harga; jual kepastiannya |
| **Masjid** | Pemasukan tanpa modal | Masjid besar melindas masjid kecil | Urutkan berdasarkan **kedekatan**, bukan popularitas |
| **Vendor** | Jangkauan tanpa biaya pasar | Masjid telat menyetor | Setoran ditahan sampai hewan diserahkan |
| **Mitra** | Penghasilan dari kerja nyata | Dianggap mengomersialkan ibadah | Nominal tetap, dari jasa masjid, terbuka |
| **Platform** | Marketplace komunitas | Dituduh memotong uang ibadah | Harga terpecah, tidak ada potongan tersembunyi |

Risiko yang paling perlu dijaga: **peringkat masjid**. Begitu ada daftar "masjid
terlaris", masjid kecil kalah selamanya dan model ini berubah jadi mesin
pemusatan. Urutan default harus jarak, dan papan peringkat tetap di dalam satu
masjid.

---

## Yang belum diputuskan

- **Besaran biaya koordinasi** — ditetapkan masjid sendiri, atau ada batas
  atas dari platform?
- **Potongan platform** — apakah Nexcity mengambil bagian, dari mana?
- **Nominal komisi mitra** — Rp 50.000 di atas hanya contoh.
- **Uang mengendap** — pembayaran masuk ke rekening masjid langsung, atau
  ditahan platform sampai hewan diserahkan? Yang kedua melindungi pembeli tapi
  membuat Nexcity memegang uang orang, dengan segala konsekuensi hukumnya.

Empat ini keputusan bisnis dan hukum, bukan teknis. Tidak menghalangi V1:
patungan, pelacakan, dan bukti bisa dibangun penuh tanpa satu pun dari ini
terjawab, selama kolom `base_price` dan `service_fee` sudah terpisah sejak awal.
