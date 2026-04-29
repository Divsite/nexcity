# Finance and Reports

## Finance tab

Cakupan yang sudah disentuh:
- summary income / distribution / expense / remaining
- tabel alokasi per charity type
- chart distribusi & recipient status
- CRUD expense dasar

## Hal yang masih perlu perhatian

- data pengeluaran operasional masih bergantung input manual user
- final report sangat dipengaruhi apakah distribusi dan sumber dana sudah lengkap
- keputusan sisa uang/beras belum final

## Daily report / OpenClaw

Notif harian zakat dan transfer notification memakai OpenClaw webhook.
Agar stabil:
- webhook URL harus bisa diakses dari server
- group WA per organisasi disimpan di `organization_whatsapp_groups`

## Decision gap

Masih perlu keputusan final untuk:
- sisa uang tidak terdistribusi
- sisa beras tidak terdistribusi
- apakah dialihkan, disimpan, atau dilaporkan terpisah
