# Qurban V1 Migration Sequence

Dokumen ini adalah daftar **final urutan migration V1** untuk core qurban Nexcity.
Tujuannya agar implementasi database berjalan aman dan tidak salah dependency.

## Prinsip penamaan

Saran timestamp contoh:
- mulai dari blok baru, misalnya `2026_05_01_0001xx_*`
- jangan campur dengan migration charity/distribution lama
- jaga urutan foreign key dari parent ke child

Contoh prefix:
- `2026_05_01_000100_...`
- `2026_05_01_000110_...`
- dst

---

## Urutan migration V1 final

### 1. Programs

#### `2026_05_01_000100_create_qurban_programs_table.php`
Membuat tabel program utama.

Dependency:
- `organizations`
- `users`

Kenapa paling awal:
- hampir semua domain qurban mengacu ke program

Kolom inti:
- `organization_id`
- `title`, `slug`, `year`
- `period_start_at`, `period_end_at`
- `description`
- `status`
- `is_public`
- `created_by`

---

### 2. Program packages

#### `2026_05_01_000110_create_qurban_program_packages_table.php`
Paket patungan/full qurban per program.

Dependency:
- `qurban_programs`

Kolom inti:
- `qurban_program_id`
- `animal_type`
- `package_type`
- `share_count`
- `title`, `description`
- `target_weight_min`, `target_weight_max`
- `price`
- `quota`, `remaining_quota`
- `is_active`

---

### 3. Orders

#### `2026_05_01_000120_create_qurban_orders_table.php`
Header order peserta/pekurban.

Dependency:
- `organizations`
- `qurban_programs`
- `users` (nullable)

Kolom inti:
- `organization_id`
- `qurban_program_id`
- `user_id` nullable
- `customer_name`, `customer_phone`, `customer_email`
- `source_type`
- `order_code`
- `status`
- `notes`
- `created_by`

Catatan:
- simpan snapshot customer walau ada `user_id`
- jangan paksa semua order harus resident

---

### 4. Order items

#### `2026_05_01_000130_create_qurban_order_items_table.php`
Item detail dalam satu order.

Dependency:
- `qurban_orders`
- `qurban_program_packages`

Kolom inti:
- `qurban_order_id`
- `qurban_program_package_id`
- `qty`
- `share_qty`
- `price`
- `subtotal`
- `status`

---

### 5. Order payments

#### `2026_05_01_000140_create_qurban_order_payments_table.php`
Log pembayaran qurban.

Dependency:
- `qurban_orders`
- `users` (`received_by` nullable/non-null tergantung keputusan)

Kolom inti:
- `qurban_order_id`
- `payment_method`
- `paid_at`
- `amount`
- `reference_number`
- `bank_name`
- `account_name`
- `proof_path`
- `status`
- `received_by`

Catatan:
- struktur mirip charity payment, tapi **tetap tabel terpisah**

---

### 6. Animals

#### `2026_05_01_000150_create_qurban_animals_table.php`
Master hewan per ekor.

Dependency:
- `organizations`
- `users` (`created_by` optional jika mau audit)

Kolom inti:
- `organization_id`
- `animal_type`
- `animal_code`
- `ear_tag_code`
- `qr_code`
- `gender`
- `weight`
- `estimated_meat_weight`
- `color`
- `age_months`
- `health_status`
- `procurement_type`
- `purchase_price`
- `purchase_date`
- `status`
- `notes`

Catatan:
- `vendor_id` **jangan dipaksa di V1** kalau vendor belum dibangun
- sementara simpan `vendor_name_snapshot` nullable kalau perlu

---

### 7. Animal allocations

#### `2026_05_01_000160_create_qurban_animal_allocations_table.php`
Menghubungkan hewan ke order item / program.

Dependency:
- `qurban_animals`
- `qurban_orders` optional kalau mau direct reference
- `qurban_order_items`
- `qurban_programs`

Kolom inti:
- `qurban_animal_id`
- `qurban_order_item_id` nullable
- `qurban_program_id`
- `share_index`
- `allocated_weight`
- `notes`

Catatan:
- untuk sapi patungan, satu hewan bisa punya beberapa allocation rows

---

### 8. Workflow logs

#### `2026_05_01_000170_create_qurban_workflow_logs_table.php`
Timeline operasional hewan/order.

Dependency:
- `qurban_programs`
- `qurban_animals` nullable
- `qurban_orders` nullable
- `users` (`performed_by`)

Kolom inti:
- `qurban_program_id`
- `qurban_animal_id` nullable
- `qurban_order_id` nullable
- `stage`
- `stage_note`
- `media_path`
- `performed_by`
- `performed_at`

Catatan:
- ini log, bukan status final tunggal
- satu hewan bisa punya banyak stage

---

### 9. Distribution batches

#### `2026_05_01_000180_create_qurban_distribution_batches_table.php`
Batch pembagian daging.

Dependency:
- `organizations`
- `qurban_programs`
- `users` (`created_by`)

Kolom inti:
- `organization_id`
- `qurban_program_id`
- `title`
- `distribution_date`
- `location_label`
- `status`
- `created_by`

---

### 10. Beneficiaries

#### `2026_05_01_000190_create_qurban_beneficiaries_table.php`
Daftar penerima daging.

Dependency:
- `organizations`
- `users` (`resident_id` nullable)
- `loc_citizens_associations` nullable
- `loc_neighborhood_associations` nullable

Kolom inti:
- `organization_id`
- `resident_id` nullable
- `name_snapshot`
- `phone_snapshot`
- `address_snapshot`
- `category`
- `citizens_association_id` nullable
- `neighborhood_association_id` nullable
- `notes`

Catatan:
- jangan paksa semua beneficiary adalah resident

---

### 11. Coupons

#### `2026_05_01_000200_create_qurban_coupons_table.php`
Kupon daging per beneficiary.

Dependency:
- `qurban_distribution_batches`
- `qurban_beneficiaries`

Kolom inti:
- `qurban_distribution_batch_id`
- `qurban_beneficiary_id`
- `coupon_code`
- `qr_code`
- `package_label`
- `meat_weight`
- `status`

---

### 12. Coupon claims

#### `2026_05_01_000210_create_qurban_coupon_claims_table.php`
Audit klaim kupon via scan.

Dependency:
- `qurban_coupons`
- `users` (`scanner_user_id`, `claimed_by_user_id` nullable)

Kolom inti:
- `qurban_coupon_id`
- `claimed_by_user_id` nullable
- `claimed_at`
- `scan_result`
- `scanner_user_id`
- `notes`

Catatan:
- log duplicate attempt juga di sini
- jangan cuma update status coupon tanpa audit row

---

## Optional V1.5

Kalau mau langsung siapkan tabungan qurban setelah V1 stabil:

### 13. Savings accounts
#### `2026_05_01_000220_create_qurban_savings_accounts_table.php`

### 14. Savings transactions
#### `2026_05_01_000230_create_qurban_savings_transactions_table.php`

Saran:
- jangan masukkan ke batch pertama kalau targetmu sekarang distribusi daging dulu

---

## Yang sengaja tidak masuk V1

Jangan dibuat dulu di batch migration awal:
- `qurban_vendors`
- `qurban_affiliate_partners`
- `qurban_commissions`
- leaderboard / ranking tables
- campaign / KOL tables

Alasan:
- dependency bisnis belum final
- bukan blocker untuk operasional qurban masjid lokal

---

## Build order setelah migration

Urutan coding setelah DB siap:
1. models + relations
2. seed/status enums ringan kalau perlu
3. CRUD program
4. CRUD package
5. CRUD order
6. CRUD animal
7. workflow log UI
8. distribution batch
9. coupon issuance
10. coupon scan/claim
11. report dasar

---

## Keputusan final yang saya rekomendasikan

Kalau mau mengunci V1 sekarang, saya sarankan kita tetapkan:
- payment qurban tabel terpisah
- beneficiary support resident + manual
- order support resident + manual buyer
- vendor ditunda
- savings ditunda ke V1.5
- distribution qurban modul sendiri, **tidak reuse mentah** charity distribution
