# Qurban Module

## Tujuan

Modul qurban di Nexcity dirancang untuk menghubungkan tiga domain yang saling berkaitan:

1. **Program qurban masjid**
2. **Operasional hewan dan penyembelihan**
3. **Distribusi daging ke penerima manfaat**

Target utama fase awal bukan marketplace nasional, tetapi **core qurban masjid yang tertib, terukur, dan transparan**.

---

## Kenapa qurban cocok di Nexcity

Qurban masih selaras dengan Nexcity karena:
- ada konteks organisasi/masjid yang kuat
- ada data warga/RT yang bisa dipakai untuk distribusi penerima manfaat
- ada petugas/partner roles yang sudah ada
- ada pengalaman existing di charity/distribution yang bisa direuse
- ada kebutuhan scan QR, audit claim, dan timeline operasional

Dengan kata lain, qurban bukan modul asing. Ia masih satu ekosistem dengan:
- charity
- resident
- organization
- distribution
- reporting

---

## Scope yang disarankan

### V1 — Wajib

Fokus pada **operasional qurban masjid lokal**.

Cakupan:
- program qurban
- paket qurban (full / patungan)
- order peserta qurban
- pembayaran dasar
- manajemen hewan
- workflow operasional penyembelihan
- distribusi daging ke warga / penerima manual
- kupon QR + validasi klaim
- laporan dasar

### V2 — Bertumbuh

Fokus pada **pengalaman warga/pekurban**.

Cakupan:
- tabungan qurban
- histori transaksi qurban
- portal warga / dashboard order
- timeline status order lebih detail
- notifikasi pembayaran dan progres qurban
- dokumentasi media per order / per hewan / per batch

### V3 — Ekspansi ecosystem

Fokus pada **network effect**, bukan kebutuhan inti awal.

Cakupan:
- vendor hewan / pemasok
- affiliate / referral / campaign partner
- leaderboard masjid / group qurban
- KOL campaign
- komisi / revenue sharing
- kerja sama lintas masjid / lintas vendor

---

## Sisi baik V3

### Manfaat untuk masjid

Kalau V3 suatu saat dihidupkan, potensi manfaat untuk masjid:
- memperluas kanal akuisisi pekurban
- mendapat akses vendor hewan yang lebih rapi dan terkurasi
- membuka peluang komisi / revenue share
- menaikkan trust jika ada rating, dokumentasi, dan transparansi
- memungkinkan program qurban kolektif lebih besar daripada kemampuan masjid sendiri

### Manfaat untuk warga / pekurban

Manfaat potensial untuk warga:
- lebih banyak pilihan program qurban
- bisa bandingkan masjid/program/vendor dengan lebih transparan
- progress qurban lebih jelas
- lebih mudah ikut patungan atau campaign bersama
- trust meningkat kalau ada riwayat distribusi dan dokumentasi penerima manfaat

### Risiko V3

Alasan V3 saya sarankan ditahan dulu:
- sudah masuk arah **ecommerce / marketplace**
- kompleksitas settlement dan komisi tinggi
- tanggung jawab kurasi vendor besar
- butuh fraud control, quality control, reputasi, dan SLA
- fokus core distribusi daging bisa buyar

---

## Posisi vendor / affiliate terhadap Nexcity

**Masih nyambung**, tapi bukan inti awal.

Kalau Nexcity tetap diposisikan sebagai:
- civic/community platform
- mosque operating system
- resident/community data platform

maka vendor/affiliate bisa dianggap sebagai **lapisan ekspansi bisnis** di atas core sosial-keagamaan.

Jadi selaras, tapi hanya kalau:
- core masjid dan warga sudah stabil
- operasional qurban dasar sudah kuat
- data distribusi dan audit sudah akurat

Kalau belum, V3 justru akan terasa terlalu “marketplace” dan menjauh dari kekuatan inti Nexcity.

---

## Integrasi dengan modul existing

### Organizations
- masjid menjadi owner program qurban
- RT/RW bisa menjadi basis distribusi penerima

### Residents
- warga bisa jadi peserta qurban
- warga bisa jadi penerima kupon daging
- data RT/RW bisa dipakai untuk targeting distribusi

### Distribution
- konsep distribusi bisa direuse untuk distribusi daging
- status claim / scan bisa adapt dari pengalaman distribusi zakat

### Finance / Charity
- tidak disatukan mentah-mentah dengan charity
- tetapi pola summary, payment log, dan reporting bisa direuse

---

## Prinsip desain

1. **Pekurban tidak selalu resident**
   - tetap butuh guest/manual buyer
2. **Penerima daging tidak selalu resident**
   - tetap butuh manual beneficiary
3. **Animal adalah entity utama**
   - bukan sekadar field order
4. **Workflow harus berbasis log**
   - bukan hanya satu kolom status
5. **Claim kupon harus auditable**
   - scan, waktu, petugas, hasil validasi, duplicate attempt

---

## Rekomendasi menu `/mosque/qurban`

Untuk fase awal, menu yang paling masuk akal:
- `Program`
- `Peserta / Order`
- `Ternak`
- `Operasional`
- `Distribusi Daging`
- `Laporan`
