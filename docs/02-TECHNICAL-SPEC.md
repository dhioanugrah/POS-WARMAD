# Spesifikasi Teknis — iPos Web

Dokumen ini mengikat seluruh implementasi. Referensi kebutuhan memakai ID dari `01-PRD.md`.

---

## 1. Stack & Paket

| Lapisan | Pilihan | Catatan |
|---|---|---|
| Framework | **Laravel 13**, PHP ≥ 8.3 | Sudah terpasang. |
| UI | **Livewire 3** (class component) + **Volt** untuk halaman kecil, **Alpine.js**, **Tailwind CSS 3** + `@tailwindcss/forms` | Sudah terpasang. Hapus dependensi `@tailwindcss/vite` v4 yang tidak dipakai (bentrok dengan Tailwind 3). **Tidak memakai Filament** — konsisten dengan kode yang ada. |
| Auth | Breeze (Livewire) | Registrasi publik dimatikan. |
| RBAC | `spatie/laravel-permission` v8 | Publish migrasi & config; `HasRoles` di `User`. |
| Audit log | `spatie/laravel-activitylog` | Fase 2. |
| Excel | `spatie/simple-excel` (ringan, streaming) | Import/export Fase 2. |
| PDF | `barryvdh/laravel-dompdf` | Ekspor laporan Fase 2. |
| Backup | `spatie/laravel-backup` | Fase 2. |
| Grafik | `chart.js` (npm) via Alpine component | Dashboard. |
| Barcode label | `jsbarcode` (npm) | Cetak label di browser. |
| Database | Dev: SQLite. Produksi: **MySQL 8** atau **PostgreSQL 15+** | Semua query harus portable (hindari fungsi DB spesifik; gunakan Query Builder). |
| Test | PHPUnit 12 (bawaan) + Livewire testing | |

## 2. Arsitektur Kode

```
app/
  Enums/                 # PHP backed enums (StockMovementType, PaymentMethod, SaleStatus, ...)
  Models/                # Eloquent, relasi, casts, scopes — TANPA logika bisnis berat
  Services/              # Logika bisnis transaksional (satu-satunya tempat mengubah stok)
    StockService.php         # move(), recalcAverageCost()
    SaleService.php          # checkout(), void()
    PurchaseService.php      # post(), void()
    StockOpnameService.php   # post()
    DocumentNumberService.php# next('PJ', $locationId)
    ShiftService.php         # open(), close(), expectedCash()
    PricingService.php       # priceFor($unit, $qty, $customer)
  Actions/               # (opsional) aksi kecil sekali pakai
  Livewire/
    Pos/                 # Cashier, CustomerDisplay, ShiftOpen, ShiftClose, HeldCarts, PaymentModal
    Products/ Customers/ Suppliers/ Purchases/ Inventory/ Finance/ Reports/ Settings/ Users/
  Policies/
  Support/Money.php      # format Rupiah, pembulatan
resources/views/
  layouts/app.blade.php       # backoffice: sidebar kiri + topbar, responsif (drawer di mobile)
  layouts/pos.blade.php       # layar penuh kasir
  receipts/sale.blade.php     # template struk 58/80mm
  livewire/...
```

### Aturan wajib
1. **Perubahan stok hanya lewat `StockService`**. Dilarang `Product::increment('stock')` atau update `product_stocks` langsung di komponen.
2. Semua operasi posting (checkout, pembelian, opname, transfer, retur, void) dibungkus `DB::transaction()` dan mengunci baris `product_stocks` terkait dengan `lockForUpdate()` dalam urutan `product_id` naik (hindari deadlock).
3. Komponen Livewire hanya memegang *state UI*; kirim input mentah (id, qty, diskon) ke Service. Service **menghitung ulang harga & total dari database** (PRD POS-18).
4. Nilai uang: `decimal(15,2)`; qty: `decimal(15,3)` (mendukung barang timbang). Di PHP gunakan cast `decimal:2`/`decimal:3` dan hitung dengan pembulatan eksplisit `round(..., 2)`.
5. Dokumen yang sudah `posted`/`completed` **tidak boleh di-update** selain kolom status void. Koreksi = void / retur / penyesuaian.
6. Setiap query daftar pakai paginasi & eager loading. Daftar produk kasir memakai index pada `code`, `name`, `product_units.barcode`.
7. Otorisasi di server via `Gate`/Policy + `can:` middleware; `@can` di Blade hanya untuk tampilan.

## 3. Konfigurasi Aplikasi

- `.env`: `APP_NAME="iPos Web"`, `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`, `APP_TIMEZONE`/`config/app.php` `timezone` → `Asia/Jakarta`.
- Pengaturan toko disimpan di tabel `settings` (key-value, cache `settings.all` di-*flush* saat disimpan) dan diakses via helper `setting('receipt.paper_width', 58)`.

Daftar key pengaturan awal:

| Key | Default | PRD |
|---|---|---|
| `store.name` / `store.address` / `store.phone` / `store.logo_path` / `store.tax_id` | "Toko Saya" / "" / "" / null / "" | SET-01 |
| `receipt.paper_width` | `58` (opsi 58/80) | SET-02 |
| `receipt.footer` | "Barang yang telah dibeli tidak dapat dikembalikan kecuali ada perjanjian\nTerima kasih atas kunjungan anda" | SET-02 |
| `receipt.show_logo` / `receipt.copies` / `receipt.auto_print` | `false` / `1` / `true` | SET-02 |
| `pos.rounding` | `0` (opsi 0/100/500/1000) | SET-03 |
| `pos.allow_negative_stock` | `true` | SET-05 |
| `pos.max_cashier_discount_percent` | `10` | SET-06 |
| `pos.customer_display_text` | "Selamat Berbelanja di Toko Kami" | POS-17 |
| `tax.enabled` / `tax.rate` / `tax.inclusive` | `false` / `11` / `true` | SET-04 |
| `doc.prefix.{type}` | lihat §5.3 | SET-07 |

## 4. Skema Database

Konvensi: `id` bigint auto increment, `timestamps()` di semua tabel, FK `constrained()` dengan `restrictOnDelete()` kecuali disebut lain. **Jangan** gunakan `cascadeOnDelete` dari master ke transaksi (prototipe saat ini `products.category_id` cascade — wajib diubah).

> Karena proyek masih prototipe tanpa data produksi, Fase 0 boleh **menulis ulang migrasi** `create_pos_system_tables` menjadi migrasi-migrasi baru yang bersih (`migrate:fresh`).

### 4.1 Sistem & Pengguna

**users** (tambahan dari Breeze)
| Kolom | Tipe | Catatan |
|---|---|---|
| username | string(50) unique nullable | login alternatif |
| default_location_id | FK locations nullable | |
| supervisor_pin | string nullable | hash bcrypt, 6 digit (AUTH-06) |
| is_active | boolean default true | user nonaktif tidak bisa login |
| last_login_at | timestamp nullable | |

**settings**: `key` string unique, `value` json nullable.

**locations**: `code` string(10) unique, `name`, `type` enum(`store`,`warehouse`), `address` text nullable, `is_active` bool. Seeder: `TKO` "Toko Utama" (store).

**document_sequences**: `doc_type` string(10), `location_id` FK, `period` string(8) (mis. `260926` untuk harian), `last_number` unsigned int. Unique (`doc_type`,`location_id`,`period`).

### 4.2 Master Data

**categories**: `name` unique, `description` nullable. (sudah ada)

**brands** (opsional, MST-02): `name` unique.

**products**
| Kolom | Tipe | Catatan |
|---|---|---|
| code | string(50) unique | SKU/kode item (iPos: `kodeitem`) |
| name | string(150) index | |
| category_id | FK categories nullable, restrict | |
| brand_id | FK brands nullable | |
| type | enum(`stock`,`service`) default `stock` | `service` tidak menggerakkan stok |
| base_unit | string(20) | nama satuan dasar, mis. "Pcs" (redundan dengan unit `is_base`, untuk tampilan cepat) |
| min_stock | decimal(15,3) default 0 | dalam satuan dasar |
| avg_cost | decimal(15,2) default 0 | harga pokok rata-rata per satuan dasar |
| last_purchase_cost | decimal(15,2) default 0 | per satuan dasar |
| image_path | string nullable | |
| notes | text nullable | |
| is_active | boolean default true | |
| deleted_at | softDeletes | |

> Kolom prototipe `products.stock` **dihapus** — digantikan `product_stocks`.

**product_units**
| Kolom | Tipe | Catatan |
|---|---|---|
| product_id | FK products, cascadeOnDelete | (satuan ikut terhapus hanya bila produk dihapus permanen) |
| unit_name | string(20) | "Pcs", "Dus", "Pak" |
| conversion_factor | decimal(15,3) default 1 | jumlah satuan dasar per satuan ini |
| is_base | boolean | tepat satu per produk, conversion = 1 |
| barcode | string(50) nullable **unique** | |
| price_sell | decimal(15,2) | harga jual normal satuan ini |
| is_default_sale | boolean default false | satuan default saat dicari dengan nama/kode |
Unique (`product_id`,`unit_name`). Kolom prototipe `price_buy` dihapus (harga pokok dihitung dari `products.avg_cost × conversion_factor`).

**product_price_tiers** (MST-05, Fase 2): `product_unit_id` FK cascade, `min_qty` decimal(15,3), `price` decimal(15,2). Unique (`product_unit_id`,`min_qty`).

**customers**: `code` string(20) unique, `name`, `phone` nullable index, `email` nullable, `address` text nullable, `credit_limit` decimal(15,2) default 0 (0 = tidak boleh kredit), `notes`, `is_active`, softDeletes. *Tidak ada* baris "Umum" — penjualan umum memakai `customer_id = null`.

**suppliers**: `code` unique, `name`, `contact_person` nullable, `phone` nullable, `address` nullable, `notes`, `is_active`, softDeletes.

### 4.3 Persediaan

**product_stocks**: `product_id` FK, `location_id` FK, `qty` decimal(15,3) default 0. Unique (`product_id`,`location_id`). *Cache* saldo — selalu sama dengan `SUM(stock_movements.qty_change)`.

**stock_movements** (buku besar stok, sumber kebenaran — INV-02)
| Kolom | Tipe | Catatan |
|---|---|---|
| product_id | FK | index (`product_id`,`location_id`,`occurred_at`) |
| location_id | FK | |
| type | string(20) — enum `StockMovementType`: `opening`, `purchase`, `purchase_return`, `purchase_void`, `sale`, `sale_return`, `sale_void`, `transfer_in`, `transfer_out`, `adjustment`, `opname` | |
| qty_change | decimal(15,3) | + masuk / − keluar, satuan dasar |
| qty_after | decimal(15,3) | saldo lokasi setelah mutasi |
| unit_cost | decimal(15,2) | harga pokok per satuan dasar saat mutasi |
| reference_type / reference_id | morphs | dokumen sumber (Sale, Purchase, StockOpname, ...) |
| note | string nullable | |
| user_id | FK users | |
| occurred_at | timestamp | tanggal dokumen (bukan created_at) |

**stock_opnames**: `number` unique, `location_id`, `status` enum(`draft`,`posted`,`cancelled`), `scope_category_id` nullable, `notes`, `user_id`, `posted_at` nullable, `posted_by` nullable.
**stock_opname_items**: `stock_opname_id` cascade, `product_id`, `system_qty` decimal(15,3) (diisi saat item dibuat, di-*refresh* saat posting), `counted_qty` decimal(15,3) nullable, `difference` decimal(15,3), `unit_cost`. Unique (`stock_opname_id`,`product_id`).

**stock_adjustments** (INV-05): `number`, `location_id`, `reason` enum(`damaged`,`lost`,`expired`,`own_use`,`other`), `notes`, `user_id`, `adjusted_at`. **stock_adjustment_items**: `product_id`, `product_unit_id`, `qty`, `qty_base`, `unit_cost`.

**stock_transfers** (INV-06, Fase 2): `number`, `from_location_id`, `to_location_id`, `status` enum(`draft`,`posted`,`cancelled`), `notes`, `user_id`, `transferred_at`. **stock_transfer_items**: `product_id`, `product_unit_id`, `qty`, `qty_base`.

### 4.4 Penjualan & Kasir

**shifts** (POS-12): `user_id`, `location_id`, `opened_at`, `opening_cash` decimal, `closed_at` nullable, `counted_cash` nullable, `expected_cash` nullable, `difference` nullable, `notes`, `status` enum(`open`,`closed`). Aturan: satu user maksimal satu shift `open`.

**sales**
| Kolom | Tipe | Catatan |
|---|---|---|
| number | string(30) unique | mis. `PJ-TKO-260926-0001` (ganti `invoice_number` prototipe) |
| location_id, user_id, shift_id | FK | shift_id nullable untuk penjualan backoffice |
| customer_id | FK customers nullable | null = Umum |
| sold_at | timestamp | |
| subtotal | decimal | Σ item subtotal |
| discount_type / discount_value | enum(`amount`,`percent`) nullable / decimal | input diskon total |
| discount_amount | decimal | hasil rupiah |
| tax_amount | decimal default 0 | |
| rounding_amount | decimal default 0 | bisa negatif/positif |
| total | decimal | subtotal − discount + tax + rounding |
| paid_amount | decimal | Σ pembayaran yang diterima (maks = total) |
| change_amount | decimal | kembalian tunai |
| payment_status | enum(`paid`,`partial`,`unpaid`) | |
| due_date | date nullable | wajib jika tidak lunas |
| status | enum(`completed`,`void`) | |
| cost_total | decimal | Σ HPP item (untuk laba) |
| notes | string nullable | |
| voided_at / voided_by / void_reason | nullable | |

**sale_items**: `sale_id` cascade, `product_id`, `product_unit_id`, `product_name` (snapshot), `unit_name` (snapshot), `conversion_factor`, `qty` decimal(15,3), `qty_base` decimal(15,3), `price` (harga satuan sebelum diskon), `discount_type`/`discount_value`/`discount_amount`, `subtotal`, `unit_cost` (HPP per satuan jual saat transaksi), `price_overridden` bool.

**payments** (pembayaran penjualan, pembelian, hutang/piutang — polimorfik)
| Kolom | Tipe | Catatan |
|---|---|---|
| payable_type / payable_id | morphs | Sale atau Purchase |
| direction | enum(`in`,`out`) | in = uang masuk (penjualan), out = keluar (pembelian) |
| method | enum `PaymentMethod`: `cash`,`qris`,`transfer`,`debit_card`,`credit_card`,`other` | |
| amount | decimal | |
| reference | string nullable | no. ref QRIS/transfer |
| paid_at | timestamp | |
| user_id, shift_id (nullable), location_id | FK | |
| is_initial | boolean | true = dibayar saat transaksi; false = pelunasan kemudian |
| voided_at | nullable | |

**held_carts** (POS-10): `user_id`, `location_id`, `label` string, `customer_id` nullable, `payload` json (hanya `product_unit_id`, `qty`, diskon — harga dihitung ulang saat dipanggil), timestamps.

**sale_returns** (POS-20, Fase 2): `number`, `sale_id`, `location_id`, `user_id`, `shift_id`, `returned_at`, `total`, `refund_method` enum(`cash`,`qris`,`transfer`,`reduce_receivable`), `reason`. **sale_return_items**: `sale_item_id`, `product_id`, `product_unit_id`, `qty`, `qty_base`, `price`, `subtotal`, `unit_cost`, `restock` bool (false = barang rusak, tidak kembali ke stok).

### 4.5 Pembelian

**purchases**: `number`, `supplier_id`, `location_id`, `user_id`, `supplier_invoice_no` nullable, `purchased_at`, `subtotal`, `discount_amount`, `other_cost` (ongkos kirim, dibebankan ke HPP proporsional), `tax_amount`, `total`, `paid_amount`, `payment_status`, `due_date`, `status` enum(`draft`,`posted`,`void`), `notes`, `posted_at`, `voided_at`/`voided_by`/`void_reason`.
**purchase_items**: `purchase_id` cascade, `product_id`, `product_unit_id`, `unit_name`, `conversion_factor`, `qty`, `qty_base`, `price` (harga beli per satuan), `discount_amount`, `subtotal`, `landed_unit_cost` (per satuan dasar setelah alokasi diskon & ongkos — dipakai untuk HPP).
**purchase_returns / purchase_return_items**: analog dengan sale_returns (Fase 2).
**purchase_orders / purchase_order_items**: Fase 3.

### 4.6 Keuangan

**cash_categories**: `name`, `type` enum(`in`,`out`), `is_active`. Seeder: Keluar — Listrik & Air, Gaji, Sewa, Transport, Perlengkapan Toko, Lain-lain; Masuk — Modal, Pendapatan Lain.
**cash_transactions** (menggantikan `expenses`): `number`, `location_id`, `user_id`, `shift_id` nullable (terisi bila dari laci kasir → mempengaruhi kas shift), `cash_category_id`, `type` in/out, `method` enum(`cash`,`bank`), `amount`, `description`, `occurred_at`, `attachment_path` nullable.

### 4.7 Akuntansi (Fase 3, opsional)
`accounts` (kode, nama, tipe: asset/liability/equity/revenue/expense, parent), `journal_entries` (number, date, description, reference morph), `journal_lines` (entry_id, account_id, debit, credit). Dibuat oleh listener event `SaleCompleted`, `PurchasePosted`, dst. Tidak dikerjakan sebelum Fase 1–2 stabil.

## 5. Aturan Bisnis

### 5.1 Stok
- `qty_base = qty × conversion_factor`.
- `StockService::move(product, location, qtyChange, type, reference, unitCost, occurredAt, note)`:
  1. Lewati jika `product.type = service`.
  2. `lockForUpdate` baris `product_stocks` (buat jika belum ada).
  3. Jika `qtyChange < 0` dan `setting('pos.allow_negative_stock') = false` dan saldo tidak cukup → lempar `InsufficientStockException` (pesan: "Stok {nama} tidak cukup. Tersedia {x}").
  4. Update saldo, insert `stock_movements` dengan `qty_after`.
- Tampilan stok multi-satuan: helper `UnitFormatter::breakdown(product, qtyBase)` → "2 Dus 5 Pcs".

### 5.2 Harga Pokok (Moving Average, per produk, gabungan semua lokasi)
Saat barang masuk dengan biaya (pembelian posted, saldo awal):
```
total_qty_sebelum = Σ product_stocks.qty (semua lokasi, sebelum mutasi; jika < 0 anggap 0)
avg_baru = (total_qty_sebelum × avg_lama + qty_masuk × landed_unit_cost) / (total_qty_sebelum + qty_masuk)
jika total_qty_sebelum + qty_masuk <= 0 → avg_baru = landed_unit_cost
```
- `landed_unit_cost = (subtotal_item − alokasi_diskon_faktur + alokasi_ongkos) / qty_base` (alokasi proporsional terhadap subtotal item).
- Penjualan: `sale_items.unit_cost = products.avg_cost × conversion_factor` saat checkout. Retur jual masuk kembali dengan `unit_cost` asli dan **tidak** mengubah avg. Opname/penyesuaian memakai `avg_cost` saat itu.
- Void pembelian **tidak** menghitung ulang avg historis (sederhana); tampilkan peringatan bahwa HPP tidak dikoreksi mundur.

### 5.3 Penomoran Dokumen
Format: `{PREFIX}-{KODE_LOKASI}-{YYMMDD}-{NNNN}` (urut per hari per lokasi, reset harian). Contoh: `PJ-TKO-260926-0007`.
`DocumentNumberService::next($type, $locationId)` memakai `document_sequences` + `lockForUpdate` di dalam transaksi pemanggil (bukan random seperti prototipe).

| Tipe | Prefix default |
|---|---|
| Penjualan | PJ |
| Retur Jual | RJ |
| Pembelian | PB |
| Retur Beli | RB |
| Transfer | TR |
| Opname | OP |
| Penyesuaian | PS |
| Kas Masuk / Kas Keluar | KM / KK |

### 5.4 Perhitungan Penjualan (dihitung di `SaleService`, bukan di browser)
```
harga_satuan    = PricingService::priceFor(unit, qty, customer)   // tier grosir tertinggi yang min_qty ≤ qty, else price_sell
                  (atau harga override jika user punya izin pos.override-price)
diskon_item     = amount → min(value, harga×qty) ; percent → round(harga×qty×value/100, 2)
subtotal_item   = harga×qty − diskon_item
subtotal        = Σ subtotal_item
diskon_total    = sama seperti diskon item, terhadap subtotal
pajak           = tax.enabled ? (inclusive ? 0 [info saja] : round((subtotal−diskon)×rate/100)) : 0
sebelum_bulat   = subtotal − diskon_total + pajak
total           = rounding>0 ? round(sebelum_bulat / rounding) × rounding : sebelum_bulat
rounding_amount = total − sebelum_bulat
```
- Kasir: jika `(diskon_item_total + diskon_total) / (Σ harga×qty) × 100 > pos.max_cashier_discount_percent` → butuh izin `pos.discount-unlimited` atau PIN supervisor.
- Pembayaran tunai boleh melebihi sisa tagihan → selisih jadi `change_amount`. Non-tunai tidak boleh melebihi sisa tagihan.
- Jika Σ pembayaran < total: hanya boleh bila `customer_id` terisi, user punya izin `pos.credit-sale`, dan (piutang berjalan + sisa) ≤ `credit_limit` → `payment_status = partial/unpaid`, `due_date` wajib (default +30 hari).

### 5.5 Void
- Void penjualan: izin `sales.void` (atau PIN supervisor), wajib alasan. Membalik stok (type `sale_void`, qty +), menandai pembayaran `voided_at`, status `void`. Penjualan void tetap tampil di riwayat (dicoret) dan dikecualikan dari laporan omzet.
- Hanya penjualan dalam shift yang masih terbuka yang boleh di-void oleh Kasir (dengan PIN); Owner/Admin boleh kapan saja.

### 5.6 Shift
```
expected_cash = opening_cash
              + Σ payments(method=cash, direction=in, shift, voided_at=null)   // nilai NETO (lihat catatan)
              + Σ cash_transactions(type=in, method=cash, shift)
              − Σ cash_transactions(type=out, method=cash, shift)
              − Σ refund tunai retur (shift)
difference    = counted_cash − expected_cash
```
Catatan implementasi: simpan `payments.amount` untuk tunai sebagai **nilai neto yang masuk laci** (dibayar − kembalian), sehingga Σ payments = total yang diterima.

## 6. Rute & Navigasi

| Grup | URL | Komponen | Izin |
|---|---|---|---|
| Kasir | `/pos` | `Pos\Cashier` | `pos.access` |
| | `/pos/display` | `Pos\CustomerDisplay` (tanpa layout, sinkron via `BroadcastChannel('ipos-pos')`) | `pos.access` |
| | `/pos/receipt/{sale}` | view struk (cetak) | `pos.access` |
| Dashboard | `/dashboard` | `Dashboard` | `dashboard.view` |
| Master | `/products`, `/products/create`, `/products/{id}/edit`, `/categories`, `/customers`, `/suppliers` | | `products.*`, `customers.*`, `suppliers.*` |
| Pembelian | `/purchases`, `/purchases/create`, `/purchases/{id}` | | `purchases.*` |
| Stok | `/stock` (posisi), `/stock/card/{product}`, `/stock-opnames`, `/stock-adjustments`, `/stock-transfers` | | `stock.*` |
| Penjualan | `/sales` (riwayat semua), `/sales/{id}`, `/receivables` | | `sales.view`, `receivables.*` |
| Keuangan | `/cash`, `/payables` | | `cash.*`, `payables.*` |
| Laporan | `/reports/{sales,sales-by-product,purchases,stock,stock-movement,shifts,cash,profit-loss}` | | `reports.view` |
| Pengaturan | `/settings`, `/settings/users`, `/settings/roles`, `/settings/locations` | | `settings.manage`, `users.manage` |

Sidebar backoffice (urutan): Dashboard · Kasir (buka POS) · Penjualan · Pembelian · Barang (Barang, Kategori, Label Barcode) · Stok (Posisi Stok, Opname, Penyesuaian, Transfer) · Kontak (Pelanggan, Supplier) · Keuangan (Kas, Piutang, Hutang) · Laporan · Pengaturan.

## 7. Matriks Hak Akses (seeder `RolePermissionSeeder`)

| Permission | Owner | Admin | Kasir | Gudang |
|---|:-:|:-:|:-:|:-:|
| `dashboard.view` | ✔ | ✔ | – | ✔ |
| `pos.access` | ✔ | ✔ | ✔ | – |
| `pos.override-price` | ✔ | ✔ | – | – |
| `pos.discount-unlimited` | ✔ | ✔ | – | – |
| `pos.credit-sale` | ✔ | ✔ | ✔ | – |
| `sales.view` (semua) | ✔ | ✔ | shift sendiri | – |
| `sales.void` | ✔ | ✔ | PIN | – |
| `sales.return` | ✔ | ✔ | ✔ | – |
| `products.view` | ✔ | ✔ | ✔ | ✔ |
| `products.manage` | ✔ | ✔ | – | – |
| `products.view-cost` (lihat HPP/margin) | ✔ | ✔ | – | – |
| `customers.manage` | ✔ | ✔ | ✔ (tambah cepat) | – |
| `suppliers.manage` | ✔ | ✔ | – | ✔ |
| `purchases.manage` | ✔ | ✔ | – | ✔ |
| `purchases.void` | ✔ | ✔ | – | – |
| `stock.view` | ✔ | ✔ | ✔ | ✔ |
| `stock.opname` / `stock.adjust` / `stock.transfer` | ✔ | ✔ | – | ✔ |
| `cash.manage` | ✔ | ✔ | laci shift | – |
| `receivables.manage` / `payables.manage` | ✔ | ✔ | terima bayar piutang | – |
| `reports.view` | ✔ | ✔ | – | stok saja |
| `settings.manage` / `users.manage` | ✔ | – | – | – |

Owner memakai `Gate::before` → semua izin.

## 8. UI/UX

### 8.1 Gaya visual
- Palet: Emerald (aksi utama), Slate (netral/teks), Rose (bahaya), Amber (peringatan), Sky (info). Font Figtree (sudah ada).
- Komponen Blade seragam: `x-button`, `x-input`, `x-money-input` (format ribuan saat mengetik, nilai integer ke Livewire), `x-table`, `x-modal`, `x-badge`, `x-empty-state`, `x-stat-card`, toast notifikasi (Alpine, event `notify`).
- Semua tabel: pencarian di atas, filter, paginasi 25, state kosong yang jelas, angka rata kanan, format Rupiah.
- Konfirmasi untuk aksi destruktif (void/hapus) dengan modal dan input alasan.

### 8.2 Layar kasir
```
┌──────────────────────────────── Topbar: Toko · Kasir: Budi · Shift #12 (sejak 08:01) · [Riwayat] [Tahan (2)] [Tutup Shift] [Menu]
├─────────────────────────────────────────────┬────────────────────────────────────────┐
│ [🔍 Scan barcode / cari nama... (F2)]       │ Pelanggan: Umum  [F4]                  │
│ Hasil pencarian (list, ↑↓ Enter) /          │ ┌────────────────────────────────────┐ │
│ Grid produk favorit/kategori (mode sentuh)  │ │ Indomie Goreng   [Pcs▾]  [-] 3 [+] │ │
│                                             │ │ @3.000  disc 0          9.000   🗑 │ │
│                                             │ └────────────────────────────────────┘ │
│                                             │ Subtotal              9.000            │
│                                             │ Diskon (F6)               0            │
│                                             │ TOTAL           Rp 9.000  (besar)      │
│                                             │ [ Tahan F7 ]   [   BAYAR  F9   ]       │
├─────────────────────────────────────────────┴────────────────────────────────────────┤
│ F2 Cari  F4 Pelanggan  F6 Diskon  F7 Tahan  F8 Daftar Tahan  F9 Bayar  F10 Cetak Ulang  Esc Batal │
└──────────────────────────────────────────────────────────────────────────────────────┘
```
- Fokus otomatis kembali ke kolom scan setelah setiap aksi.
- Shortcut lain: `Ctrl+Del` kosongkan keranjang (konfirmasi), `↑/↓` pilih baris keranjang saat fokus di keranjang, `+`/`-` ubah qty baris terpilih, `Del` hapus baris terpilih.
- Modal bayar: total besar, daftar metode (tombol), input nominal (auto-fokus), tombol uang cepat, daftar pembayaran (split), kembalian besar hijau; `Enter` = proses bila lunas.
- Setelah sukses: toast + struk dicetak (iframe tersembunyi `window.print()`), keranjang dikosongkan.
- Keranjang aktif juga disimpan di `localStorage` sebagai cadangan jika browser tertutup (dipulihkan dengan konfirmasi).

### 8.3 Struk
Template Blade murni HTML + CSS `@page { size: 58mm auto; margin: 0 }`, font monospace 12px; lebar 32 kolom (58mm) / 48 kolom (80mm). Isi: logo (opsional), nama toko, alamat, telepon, no. nota, tanggal-jam, kasir, pelanggan, daftar item (nama; qty × harga; subtotal; diskon), subtotal, diskon, pajak, pembulatan, total, pembayaran per metode, kembalian, footer. Cash drawer dibuka lewat pengaturan driver printer ("open drawer on print").

## 9. Pengujian

- **Unit/Feature test wajib**: `StockService` (masuk/keluar/minus/lock), HPP moving average (lihat contoh angka di UAT INV), `SaleService::checkout` (total, diskon, pembulatan, split, kredit, harga dari server), void, penomoran berurutan & unik, shift expected cash, opname posting, policy per peran.
- Test Livewire untuk komponen kasir: scan barcode, `3*kode`, hold/resume, bayar.
- Factory untuk semua model; `DatabaseSeeder` membuat data demo (lihat data uji di `05-UAT.md §2`).
