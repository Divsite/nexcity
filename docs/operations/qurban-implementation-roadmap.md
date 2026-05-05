# Qurban Implementation Roadmap

## Fase kerja yang disarankan

### Phase 0 — Foundation design
Output:
- docs scope
- daftar tabel final
- migration plan
- keputusan mana V1 vs V2 vs V3

### Phase 1 — Database core (V1)
Prioritas migration:
1. `qurban_programs`
2. `qurban_program_packages`
3. `qurban_orders`
4. `qurban_order_items`
5. `qurban_order_payments`
6. `qurban_animals`
7. `qurban_animal_allocations`
8. `qurban_workflow_logs`
9. `qurban_distribution_batches`
10. `qurban_beneficiaries`
11. `qurban_coupons`
12. `qurban_coupon_claims`

Catatan:
- `qurban_savings_*` boleh masuk phase 1.5 kalau mau cepat
- vendor/affiliate jangan dulu

### Phase 2 — Admin UI masjid
Urutan paling aman:
1. Program
2. Package
3. Order peserta
4. Hewan
5. Workflow log
6. Distribusi daging
7. Scan kupon
8. Laporan dasar

### Phase 3 — Resident / public experience
1. order qurban self-service
2. tabungan qurban
3. dashboard progress order
4. media/timeline qurban

### Phase 4 — Ecosystem expansion
1. vendor
2. affiliate
3. leaderboard
4. campaign/KOL
5. komisi

---

## V1 wajib

Berikut yang menurut saya **wajib ada** agar sistem qurban sudah bernilai nyata:

- program qurban
- paket patungan/full
- order peserta
- pembayaran order
- data hewan per ekor
- alokasi hewan ke peserta/program
- timeline operasional dasar
- distribusi daging ke penerima
- kupon QR dan scan claim
- laporan dasar per program

Tanpa ini, qurban belum benar-benar operasional.

---

## V2 yang kuat tapi boleh ditunda

- tabungan qurban
- portal warga / resident order view
- notifikasi otomatis
- dokumentasi media yang rapi per hewan/order
- dashboard progress real-time

---

## V3 yang ditahan dulu

- vendor marketplace
- affiliate
- komisi
- leaderboard
- campaign KOL
- ranking group/masjid

---

## Keputusan desain penting sebelum coding

Sebelum migration dibuat, pastikan keputusan ini final:

1. apakah qurban payment reuse tabel charity payments atau dibuat terpisah?
   - saran: terpisah tapi pola serupa

2. apakah tabungan qurban wajib masuk V1?
   - saran: tidak wajib, kecuali memang sudah ada demand kuat

3. apakah distribusi daging mau reuse modul distribution existing?
   - saran: **jangan reuse mentah**
   - lebih aman buat modul qurban distribution sendiri, tapi adapt pattern status/scan dari charity distribution

4. apakah pekurban selalu user internal/resident?
   - saran: tidak
   - harus support guest/manual buyer

5. apakah kupon penerima hanya untuk resident?
   - saran: tidak
   - tetap support manual beneficiary

---

## Implementasi pertama yang saya sarankan

Kalau langsung masuk coding, urutan terbaik:

1. migrations core V1
2. models + relations
3. seed master kecil jika perlu (`animal_type`, `workflow_stage` opsional)
4. admin UI untuk `Program`
5. admin UI untuk `Order`
6. admin UI untuk `Ternak`
7. admin UI untuk `Distribusi Daging`
8. QR scan endpoint
9. report dasar

---

## Hubungan dengan roadmap existing Nexcity

Qurban tidak boleh mengganggu pekerjaan fondasi yang masih kritikal:
- RT/RW Neo Bintaro
- resident core
- distribusi zakat lapangan
- dokumentasi foto distribusi
- keputusan sisa uang/beras

Saran praktik:
- desain qurban dulu sekarang
- implementasi database mulai setelah core charity/distribution cukup stabil
