# Data uji di database lokal

Semua data yang dibuat untuk keperluan pengujian — bukan catatan asli Al-amanah —
diberi penanda **`[UJI]`** di kolom namanya. Aturannya satu: kalau sebuah baris
tidak berasal dari pemakaian nyata, namanya diawali `[UJI]`.

Ini ada karena pernah tidak ada. Dua distribusi uji dibuat tanpa penanda, salah
satunya memuat Baba Rudin dengan status `pending`. Di aplikasi ia muncul di
daftar "Belum tersalurkan", padahal di web ia sudah `distributed` pada distribusi
yang asli — dan tidak ada cara membedakan "bug" dari "data yang kita taruh
sendiri" selain menelusuri database.

## Apa yang ada sekarang

| Jenis | Penanda | Jumlah | Untuk menguji |
|---|---|---|---|
| Transaksi amal | `[UJI] <nama>` pada `payer_name` | 13 | Pemilih tahun (2024/2025/2026), amal hari ini, lintas masjid |
| Distribusi | `[UJI]` pada `title` | 2 | Pindai lapangan, penolakan lintas masjid |
| Program qurban | `[UJI]` pada `title` | 1 | Seluruh layar qurban |
| Paket qurban | — (milik program `[UJI]`) | 3 | Daftar paket, kuota |
| Hewan qurban | `[UJI]` pada `animal_code` | 5 | Tahapan hewan (tersedia → disembelih → disalurkan) |
| Pengumuman | tidak ditandai | 11 | Umpan pengumuman |

Pengumuman sengaja tidak ditandai: seluruh tabelnya memang kosong sebelum ini,
jadi tidak ada catatan asli yang bisa tertukar. Kalau nanti pengurus mulai
menulis pengumuman sungguhan, yang uji perlu ditandai juga.

## Sebaran yang sengaja dibuat

Data uji tidak diacak. Tiap baris menjawab satu pertanyaan:

- **Tiga tahun** (2024, 2025, 2026) — supaya pemilih tahun punya isi, dan tren
  `↑ x% dari tahun lalu` punya pembanding.
- **Dua transaksi hari ini** — supaya "Amal hari ini" di beranda tidak selamanya
  nol, karena data asli Al-amanah semuanya bertanggal Maret.
- **Beras tanpa uang** — zakat fitrah 4 kg tanpa rupiah, memastikan layar tidak
  menulis "Rp 0" untuk sekarung beras.
- **Masjid kedua (Darul Muminin)** — satu transaksi bernama
  `[UJI] Jangan Muncul di Alamanah`. Kalau nama itu muncul saat masuk sebagai
  pengurus Al-amanah, ada kebocoran lintas organisasi.
- **Lima hewan di lima tahap berbeda** — supaya layar pelacakan menampilkan
  sebaran nyata, bukan satu baris yang diulang.

## Menghapus semuanya

```php
// php artisan tinker
App\Models\Charities\CharityTransaction::where('payer_name', 'like', '[UJI]%')->delete();
App\Models\Distributions\Distribution::where('title', 'like', '[UJI]%')->delete();
App\Models\Qurbans\QurbanAnimal::where('animal_code', 'like', '[UJI]%')->delete();
App\Models\Qurbans\QurbanProgram::where('title', 'like', '[UJI]%')->delete();
```

Skrip pembuatnya idempoten (`firstOrCreate`), jadi menjalankannya dua kali tidak
menggandakan apa pun.
