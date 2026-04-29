# Distributions

## Domain

Distribusi terdiri dari:
- `distribution_classes`
- `distributions`
- `distribution_recipients`
- `distribution_officers`
- status log recipient

## Tipe flow

### 1. External / warga

- wajib lokasi RT/RW
- recipient dipilih dari resident
- officer dipilih terpisah

### 2. Internal / amil

- fokus ke petugas
- tidak perlu pilih resident
- recipient row dibuat dari officer yang dipilih

Perubahan terakhir sudah melonggarkan validasi create distribusi internal, jadi class `is_internal` tidak lagi memaksa `recipient_ids`.

## Constraint penting

### Duplikasi distribusi

Create distribusi dibatasi per:
- organisasi
- tahun
- distribution class
- RT (untuk non-manual / non-internal)

### Edit distribusi yang sudah berjalan

Kalau suatu distribusi sudah punya recipient dengan status `distributed`, distribusi itu tidak boleh diedit lagi.

Ini artinya:
- bukan semua distribusi global yang terkunci
- tapi distribusi spesifik itu yang terkunci

### Data warga nyusul

Kalau data resident baru masuk setelah distribusi dibuat:
- selama distribusinya belum benar-benar berjalan/final, perubahan masih bisa diakomodasi
- kalau distribusi itu sudah punya recipient `distributed`, core sekarang belum fleksibel untuk menambah penerima baru ke distribusi yang sama

## Recipient status

Status recipient dipakai untuk operasi lapangan dan evaluasi:
- `pending`
- `distributed`
- `failed`
- `rescheduled`
- `redirected`

`redirected` diperlakukan sebagai sudah tersalurkan, tapi dialihkan.

## Export PDF

Distribution recipient table sekarang punya bulk action export PDF.
PDF menampilkan:
- tahun
- RT/lokasi
- golongan
- petugas
- daftar penerima
- total uang
- total beras
