# Qurban Domain Model

## Pendekatan

Qurban dibagi menjadi 3 bounded domain:

1. **Commerce / participation**
2. **Livestock operations**
3. **Meat distribution**

Tujuannya agar core data tidak campur aduk antara:
- siapa yang bayar
- hewan mana yang dipotong
- daging dibagikan ke siapa

---

## ERD konseptual

### 1. Program & package

#### `qurban_programs`
Representasi program qurban suatu masjid.

Kolom utama:
- `id`
- `organization_id`
- `title`
- `slug`
- `year`
- `period_start_at`
- `period_end_at`
- `description`
- `status` (`draft/open/closed/archived`)
- `is_public`
- `created_by`
- timestamps

#### `qurban_program_packages`
Paket yang ditawarkan di dalam program.

Contoh:
- Patungan Sapi 1/7
- Domba Reguler 20-25kg
- Kambing Premium

Kolom utama:
- `id`
- `qurban_program_id`
- `animal_type` (`cow/goat/sheep/buffalo/other`)
- `package_type` (`full/share/savings_target`)
- `share_count` nullable
- `title`
- `description`
- `target_weight_min`
- `target_weight_max`
- `price`
- `quota`
- `remaining_quota`
- `is_active`
- timestamps

---

### 2. Order & payment

#### `qurban_orders`
Header order pekurban.

Kolom utama:
- `id`
- `organization_id`
- `qurban_program_id`
- `user_id` nullable
- `customer_name`
- `customer_phone`
- `customer_email`
- `source_type` (`resident/manual/public-app/admin-input`)
- `order_code`
- `status` (`draft/pending_payment/partial_paid/paid/cancelled/refunded/completed`)
- `notes`
- `created_by`
- timestamps

#### `qurban_order_items`
Item di dalam order.

Kolom utama:
- `id`
- `qurban_order_id`
- `qurban_program_package_id`
- `qty`
- `share_qty`
- `price`
- `subtotal`
- `status`
- timestamps

#### `qurban_order_payments`
Pembayaran order.

Kolom utama:
- `id`
- `qurban_order_id`
- `payment_method` (`cash/transfer/qris/other`)
- `paid_at`
- `amount`
- `reference_number`
- `bank_name`
- `account_name`
- `proof_path` nullable
- `status`
- `received_by`
- timestamps

---

### 3. Savings

#### `qurban_savings_accounts`
Akun tabungan qurban per user/customer.

Kolom utama:
- `id`
- `organization_id`
- `user_id` nullable
- `owner_name`
- `owner_phone`
- `owner_email`
- `target_package_id` nullable
- `target_amount`
- `current_balance`
- `status`
- timestamps

#### `qurban_savings_transactions`
Mutasi tabungan.

Kolom utama:
- `id`
- `qurban_savings_account_id`
- `type` (`deposit/withdrawal/adjustment/conversion_to_order`)
- `amount`
- `description`
- `reference_number`
- `processed_by`
- timestamps

---

### 4. Livestock

#### `qurban_animals`
Entity hewan per ekor.

Kolom utama:
- `id`
- `organization_id`
- `vendor_id` nullable
- `animal_type`
- `animal_code`
- `ear_tag_code` nullable
- `qr_code` nullable
- `gender`
- `weight`
- `estimated_meat_weight` nullable
- `color` nullable
- `age_months` nullable
- `health_status`
- `procurement_type` (`owned/consignment/vendor-supplied/partner-supplied`)
- `purchase_price` nullable
- `purchase_date` nullable
- `status` (`available/allocated/scheduled/slaughtered/processed/distributed`) 
- `notes`
- timestamps

#### `qurban_animal_allocations`
Relasi hewan ke order/program/share.

Kolom utama:
- `id`
- `qurban_animal_id`
- `qurban_order_item_id` nullable
- `qurban_program_id`
- `share_index` nullable
- `allocated_weight` nullable
- `notes`
- timestamps

---

### 5. Workflow operasional

#### `qurban_workflow_logs`
Timeline proses hewan/order.

Kolom utama:
- `id`
- `qurban_program_id`
- `qurban_animal_id` nullable
- `qurban_order_id` nullable
- `stage` (`booked/paid/scheduled/out_of_pen/slaughtered/skinned/chopped/packed/delivered/other`)
- `stage_note` nullable
- `media_path` nullable
- `performed_by`
- `performed_at`
- timestamps

Catatan:
- lebih baik pakai log table daripada satu kolom status final
- satu hewan bisa punya banyak log
- satu order juga bisa dihubungkan ke log tertentu

---

### 6. Distribution / beneficiary

#### `qurban_distribution_batches`
Batch distribusi daging.

Kolom utama:
- `id`
- `organization_id`
- `qurban_program_id`
- `title`
- `distribution_date`
- `location_label`
- `status` (`draft/open/closed/completed`)
- `created_by`
- timestamps

#### `qurban_beneficiaries`
Penerima manfaat, bisa resident atau manual.

Kolom utama:
- `id`
- `organization_id`
- `resident_id` nullable
- `name_snapshot`
- `phone_snapshot` nullable
- `address_snapshot` nullable
- `category` (`resident/manual/external/institution`) 
- `neighborhood_association_id` nullable
- `citizens_association_id` nullable
- `notes`
- timestamps

#### `qurban_coupons`
Kupon daging per penerima.

Kolom utama:
- `id`
- `qurban_distribution_batch_id`
- `qurban_beneficiary_id`
- `coupon_code`
- `qr_code`
- `package_label`
- `meat_weight`
- `status` (`issued/claimed/void`) 
- timestamps

#### `qurban_coupon_claims`
Log klaim kupon.

Kolom utama:
- `id`
- `qurban_coupon_id`
- `claimed_by_user_id` nullable
- `claimed_at`
- `scan_result` (`valid/invalid/already_claimed/not_found`) 
- `scanner_user_id`
- `notes`
- timestamps

---

## Entity tambahan untuk V3

### `qurban_vendors`
Vendor hewan / mitra peternak.

### `qurban_affiliate_partners`
Partner campaign / KOL / komunitas.

### `qurban_commissions`
Revenue sharing / komisi jika model marketplace dihidupkan.

Catatan penting:
- tabel ini **boleh disiapkan belakangan**
- jangan memaksa V1 bergantung padanya

---

## Relasi ke modul existing

### reuse resident
- `qurban_orders.user_id` bisa mengarah ke user resident
- `qurban_beneficiaries.resident_id` bisa mengarah ke resident existing

### reuse organization
- semua data qurban harus scoping ke `organization_id`

### reuse partner users
- petugas scan / petugas operasional memakai user internal existing

---

## Prinsip migration

1. buat tabel inti dulu
2. jangan ikat ke mobile app dulu
3. hindari polymorphic berlebihan di fase awal
4. simpan snapshot nama/telepon/email walau ada foreign key user
5. buat status final sederhana, detail proses di log table
