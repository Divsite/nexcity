# Nexcity Docs

Dokumentasi kerja untuk memahami arsitektur, modul utama, alur data, seeder, dan gap yang masih perlu diselesaikan.

## Struktur

- `docs/architecture/overview.md`
  Ringkasan arsitektur backend, domain model, dan pola UI.
- `docs/modules/organizations-and-residents.md`
  Alur organisasi RT/RW/masjid dan data resident.
- `docs/modules/charity-transactions.md`
  Alur input transaksi amal/zakat dan notifikasi.
- `docs/modules/distributions.md`
  Alur distribution classes, distribusi, recipient status, dan export PDF.
- `docs/modules/finance-and-reports.md`
  Ringkasan finance tab, summary, expense, dan daily report.
- `docs/modules/qurban.md`
  Scope produk qurban, alignment dengan Nexcity, dan pembagian V1/V2/V3.
- `docs/architecture/qurban-domain-model.md`
  Desain domain, ERD konseptual, dan relasi antar entitas qurban.
- `docs/operations/seeders-and-demo-data.md`
  Seeder penting, akun default, dan urutan seed yang benar.
- `docs/operations/qurban-implementation-roadmap.md`
  Roadmap implementasi qurban, V1 wajib, dan migration plan.
- `docs/operations/qurban-v1-migration-sequence.md`
  Daftar final nama file migration V1 dan urutan dependency-nya.
- `docs/architecture/mosque-structure.md`
  Struktur organisasi masjid: jabatan struktural vs kepanitiaan, dan kenapa
  `mosque-officer` (sebenarnya Petugas Zakat) terasa kabur.
- `docs/operations/authorization-audit.md`
  Audit otorisasi 8 Agustus 2026: route qurban bisa ditembus URL manual karena
  `permission:` memeriksa role, bukan level. Berisi urutan perbaikan.
- `docs/operations/known-gaps-and-next-steps.md`
  Known issues, keputusan tertunda, dan backlog core.

## Fokus saat ini

Prioritas fondasi yang masih aktif:

1. Master lokasi RW/RT Neo Bintaro dan turunannya
2. Alur organisasi RT/RW dan login akun partner
3. Alur resident per RT
4. Distribusi amil/internal
5. Dokumentasi foto distribusi dari mobile
6. Rekap akhir uang/beras tidak terdistribusi
