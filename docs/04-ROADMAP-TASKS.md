# Roadmap & Backlog Task — iPos Web

Setiap task dirancang agar bisa dikerjakan **satu agent dalam satu sesi** (±1–4 jam kerja). Kerjakan berurutan sesuai dependensi. Centang `[x]` setelah selesai dan lulus test.

**Legenda**: `Dep` = task yang harus selesai dulu · `PRD` = ID kebutuhan · `UAT` = skenario uji di `05-UAT.md`.

## Definition of Done (berlaku untuk semua task)
- [ ] Acceptance criteria terpenuhi dan dibuktikan dengan **feature test** (PHPUnit) — `php artisan test` hijau.
- [ ] Mengikuti skema & aturan di `02-TECHNICAL-SPEC.md` (atau spec diperbarui di commit yang sama).
- [ ] Otorisasi server-side untuk setiap route/aksi baru.
- [ ] UI berbahasa Indonesia, format Rupiah/tanggal sesuai NFR-06, tidak ada error di console browser.
- [ ] `vendor/bin/pint --dirty` dijalankan; tidak ada query N+1 di halaman daftar.
- [ ] `DatabaseSeeder` tetap berjalan (`php artisan migrate:fresh --seed`).

## Template Prompt untuk Agent

```text
Kamu mengerjakan proyek iPos Web di folder ipos-laravel/ (Laravel 13, Livewire 3 + Volt, Tailwind 3, Spatie Permission).
Baca dulu: docs/README.md, docs/01-PRD.md, docs/02-TECHNICAL-SPEC.md, dan task berikut di docs/04-ROADMAP-TASKS.md.

TASK: <T-xxx — judul>
Kerjakan HANYA scope task ini. Patuhi aturan wajib di Tech Spec §2 (stok hanya via StockService, harga dihitung di server, transaksi DB + lockForUpdate).
Tulis feature test untuk setiap acceptance criteria. Jalankan `php artisan test` dan `vendor/bin/pint --dirty` sampai bersih.
Di akhir: centang task di docs/04-ROADMAP-TASKS.md, sebutkan file yang diubah, dan UAT ID yang siap diuji manual.
```

---

## Fase 0 — Fondasi & Perbaikan Prototipe

Temuan pada prototipe saat ini yang ditutup di fase ini:
- `products.stock` integer & **tidak berkurang saat penjualan**; tidak ada kartu stok.
- Nomor invoice memakai `Str::random` (tidak berurutan, bisa bentrok).
- Harga keranjang disimpan di state Livewire publik → **bisa dimanipulasi dari browser**, dan dipakai langsung saat simpan.
- Spatie Permission terpasang tetapi migrasi belum dipublish, `User` belum `HasRoles`, tidak ada peran.
- Registrasi publik aktif; `APP_LOCALE=en`, timezone UTC; nama toko "Warung Putri" di-hardcode.
- `products.category_id` `cascadeOnDelete` (hapus kategori = hapus barang!).
- File Volt kosong `resources/views/components/pos/⚡cashier.blade.php`; dependensi `@tailwindcss/vite` v4 tidak terpakai (bentrok dengan Tailwind 3).
- Migrasi `add_stock_to_products_table` punya `down()` kosong; tidak ada test untuk POS.

### [x] T-001 — Konfigurasi dasar & bersih-bersih
- **Dep**: – · **PRD**: AUTH-02, NFR-06
- **Scope**: `.env.example`/`.env` (`APP_NAME="iPos Web"`, `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`), `config/app.php` timezone `Asia/Jakarta`; hapus route & view register (dan test registrasi); hapus `components/pos/⚡cashier.blade.php`; hapus `@tailwindcss/vite` dari `package.json`; tambah file terjemahan `lang/id` (validation, auth, pagination) — bisa via `laravel-lang` atau manual.
- **AC**: `/register` → 404; pesan validasi berbahasa Indonesia; `now()` berzona WIB; `npm run build` sukses.
- **UAT**: UAT-AUTH-03

### [x] T-002 — Skema database baru (Fase 1) + Enum + Model + Factory
- **Dep**: T-001 · **PRD**: seluruh Fase 1
- **Scope**: Ganti migrasi `create_pos_system_tables`, `add_stock_to_products_table`, `create_expenses_table` dengan migrasi baru sesuai Tech Spec §4.1–4.6 (tabel Fase 1 saja: locations, settings, document_sequences, kolom tambahan users, categories, brands, products, product_units, product_stocks, stock_movements, customers, suppliers, shifts, sales, sale_items, payments, held_carts, purchases, purchase_items, stock_opnames, stock_opname_items, stock_adjustments, stock_adjustment_items, cash_categories, cash_transactions). Buat Enum di `app/Enums`, Model dengan relasi/casts/soft deletes, Factory untuk semua model. Hapus model `Expense`. Buat `UatSeeder` berisi data uji persis seperti `05-UAT.md §2` (dipanggil dari `DatabaseSeeder` di lingkungan local/staging).
- **AC**: `php artisan migrate:fresh --seed` sukses di SQLite **dan** MySQL; semua FK sesuai spec (tidak ada cascade master→transaksi); test relasi dasar tiap model.
- **Catatan**: Komponen `Pos\Cashier` & `Dashboard` lama boleh rusak sementara — tandai dengan `// TODO T-108/T-117` atau nonaktifkan route-nya.

### [x] T-003 — RBAC & alur login
- **Dep**: T-002 · **PRD**: AUTH-01, 03, 05, 07
- **Scope**: publish migrasi/config Spatie; `HasRoles` di `User`; `RolePermissionSeeder` sesuai matriks Tech Spec §7; `Gate::before` untuk Owner; login menolak user `is_active=false`; update `last_login_at`; redirect setelah login (Kasir → `/pos`, lainnya → `/dashboard`); rate limit 5/menit (Breeze sudah punya — verifikasi); login dengan email **atau** username.
- **AC**: test per peran untuk akses `/pos`, `/dashboard`, `/settings` (403 bila tidak berhak); user nonaktif gagal login dengan pesan jelas.
- **UAT**: UAT-AUTH-01, 02, 04, 05, 06

### [x] T-004 — Layout backoffice & komponen UI
- **Dep**: T-001 · **PRD**: NFR-03, NFR-08
- **Scope**: `layouts/app.blade.php` baru: sidebar kiri (menu Tech Spec §6, item disembunyikan sesuai `@can`), topbar (nama toko dari setting, nama user, lokasi, logout), drawer di mobile. Komponen Blade: `x-button`, `x-input`, `x-select`, `x-money-input`, `x-table`, `x-modal` (sudah ada, rapikan), `x-badge`, `x-empty-state`, `x-stat-card`, `x-page-header`, toast global (event Livewire `notify`). Helper `App\Support\Money::format()` + Blade directive `@rupiah($x)`.
- **AC**: halaman contoh render di lebar 360 px & 1440 px tanpa scroll horizontal; `x-money-input` menampilkan "1.250.000" dan mengirim `1250000`.

### [x] T-005 — Pengaturan toko, lokasi, penomoran dokumen
- **Dep**: T-002, T-003, T-004 · **PRD**: SET-01, 02, 03, 05, 07, 08
- **Scope**: model `Setting` + helper `setting()` dengan cache; halaman `/settings` (tab Profil Toko, Struk, Kasir, Penomoran); upload logo; CRUD `/settings/locations`; `DocumentNumberService::next($type, $locationId)` sesuai Tech Spec §5.3.
- **AC**: test penomoran: 2 panggilan berurutan → `-0001`, `-0002`; hari berganti → reset; prefix bisa diubah; nomor unik di bawah transaksi paralel (simulasi). Setting tersimpan & ter-cache ulang.
- **UAT**: UAT-SET-01, 04

### [x] T-006 — Manajemen user
- **Dep**: T-003, T-004 · **PRD**: AUTH-04
- **Scope**: `/settings/users` daftar, tambah, ubah (nama, email, username, peran, lokasi default, aktif), reset password; Owner tidak bisa menonaktifkan dirinya sendiri; `/settings/roles` untuk Owner mencentang permission per peran.
- **AC**: test CRUD & aturan Owner; perubahan permission langsung berlaku.
- **UAT**: UAT-AUTH-07

---

## Fase 1 — MVP Operasional Toko (siap go-live 1 lokasi)

### [x] T-101 — StockService & format satuan
- **Dep**: T-002 · **PRD**: INV-01, SET-05
- **Scope**: `StockService::move()` (Tech Spec §5.1), `StockService::applyIncomingCost()` (moving average §5.2), `InsufficientStockException`, `UnitFormatter::breakdown()`.
- **AC**: test: masuk/keluar mengubah `product_stocks` & mencatat `stock_movements.qty_after`; produk jasa diabaikan; stok minus ditolak bila setting false; contoh HPP §5.2 (100 @2.500 + 80 @2.600 → 2.544,44); breakdown 85 pcs (Dus=40) → "2 Dus 5 Pcs".
- **UAT**: UAT-INV-03

### [x] T-102 — Kategori & Merek
- **Dep**: T-004 · **PRD**: MST-01, MST-02
- **Scope**: halaman `/categories` (dan merek dalam tab yang sama) — tabel + modal tambah/ubah, cari, hapus diblokir jika dipakai barang.
- **UAT**: UAT-MST-01, 02

### [x] T-103 — Master Barang multi-satuan
- **Dep**: T-101, T-102 · **PRD**: MST-03, 04, 10
- **Scope**: `/products` daftar (cari nama/kode/barcode, filter kategori/status/stok menipis, kolom stok dalam breakdown, harga jual satuan dasar, HPP hanya untuk `products.view-cost`); form create/edit: data utama + tabel satuan dinamis (nama satuan, konversi, barcode, harga jual, default jual; baris pertama = satuan dasar konversi 1 terkunci); foto; aktif/nonaktif; hapus permanen hanya jika belum ada `stock_movements`/`sale_items`/`purchase_items`. Generate kode otomatis jika kosong (mis. `BRG-00001`).
- **AC**: barcode unik lintas semua satuan (validasi dengan pesan jelas); tepat satu satuan dasar; margin (%) terlihat di form untuk yang berhak.
- **UAT**: UAT-MST-03, 04, 05, 06, 07

### [x] T-104 — Pelanggan & Supplier
- **Dep**: T-004 · **PRD**: MST-06, MST-07
- **Scope**: `/customers`, `/suppliers` CRUD + cari + nonaktif; kode otomatis (`PLG-0001`, `SUP-0001`); komponen `CustomerPicker` (cari nama/telepon + tambah cepat) yang dapat dipakai di POS.
- **UAT**: UAT-MST-08, 09

### [x] T-105 — Saldo awal stok
- **Dep**: T-103 · **PRD**: INV-03
- **Scope**: halaman `/stock/opening`: pilih lokasi, cari/scan barang, isi qty (satuan pilihan) & harga pokok per satuan → simpan sebagai movement `opening` + set `avg_cost` via `applyIncomingCost`. Barang yang sudah punya movement opening di lokasi itu ditandai.
- **UAT**: UAT-INV-01

### [x] T-106 — PricingService & SaleService (backend checkout & void)
- **Dep**: T-101, T-005 · **PRD**: POS-05, 06, 08, 09, 18, SET-03, SET-05
- **Scope**: `PricingService::priceFor()` (Fase 1: `price_sell`; siap untuk tier); `SaleService::checkout(CheckoutData $data)` — input hanya id/qty/diskon/pembayaran; hitung sesuai §5.4; simpan sale, items (snapshot + `unit_cost`), payments (tunai neto); movement `sale`; nomor dokumen; `SaleService::void($sale, $reason, $user)` sesuai §5.5. DTO `CheckoutData`, exception berbahasa Indonesia.
- **AC**: test: total & diskon item/total (Rp dan %); pembulatan 100 (12.345→12.300, 12.350→12.400); split tunai+QRIS; kembalian; non-tunai lebih bayar ditolak; kurang bayar pelanggan Umum ditolak; harga yang dikirim client diabaikan kecuali user punya `pos.override-price`; stok berkurang sesuai `qty_base`; void mengembalikan stok & mengecualikan dari omzet; checkout tanpa shift terbuka ditolak (untuk kasir).
- **UAT**: UAT-POS-11, 12, 13, 14, 20, 22, 31, UAT-SET-03, 05

### [x] T-107 — Shift kasir & kas laci
- **Dep**: T-106 · **PRD**: POS-12, POS-13
- **Scope**: `ShiftService::open/close/expectedCash` (§5.6); modal "Buka Shift" wajib muncul di `/pos` bila belum ada shift; halaman/modal "Tutup Shift" (input uang fisik, tampil rekap per metode, expected, selisih, catatan) + cetak laporan shift 58/80 mm; modal "Kas Masuk/Keluar Laci" di POS (membuat `cash_transactions` dengan `shift_id`).
- **AC**: satu user hanya satu shift terbuka; contoh angka UAT-POS-25 lolos di test.
- **UAT**: UAT-POS-01, 24, 25

### [x] T-108 — POS v2 bagian 1: keranjang, pencarian, scan
- **Dep**: T-103, T-104, T-106 · **PRD**: POS-01–07, 16, 19
- **Scope**: tulis ulang `Pos\Cashier` + view sesuai layout Tech Spec §8.2. Pencarian: barcode exact → langsung tambah (satuan sesuai barcode); kode exact → satuan default; selain itu daftar hasil (maks 20, ↑/↓/Enter). Format `3*kode`. Keranjang: ganti satuan (dropdown), +/−, edit qty (desimal untuk barang timbang), diskon item (modal), hapus. Diskon total (F6). `CustomerPicker` (F4). Badge peringatan stok ≤ 0 / ≤ min. Semua shortcut §8.2 via Alpine (`@keydown.window`), legend shortcut di footer. Mode sentuh: toggle grid produk per kategori dengan tombol besar. Harga di keranjang hanya untuk tampilan — total final dari `SaleService`.
- **AC**: test Livewire: scan barcode Pcs & Dus, scan ulang menambah qty, `3*BRD-001` menambah 3, ganti satuan mengubah harga, pencarian nama mengembalikan hasil; fokus kembali ke input setelah aksi.
- **UAT**: UAT-POS-02–10, 19, 21

### [x] T-109 — POS v2 bagian 2: pembayaran & struk
- **Dep**: T-108, T-107, T-005 · **PRD**: POS-08, 09, 11, SET-02
- **Scope**: modal bayar (F9) sesuai §8.2: metode sebagai tombol, uang cepat, split payment (daftar pembayaran, hapus baris), kembalian besar, Enter untuk proses; panggil `SaleService::checkout`; tampilkan error exception sebagai toast. Template struk `receipts/sale.blade.php` 58/80 mm (§8.3) dengan data setting; cetak otomatis via iframe tersembunyi bila `receipt.auto_print`; F10 cetak ulang struk terakhir (bertanda "SALINAN"). Panduan singkat setting printer & kiosk-printing di `docs/guides/printer.md`.
- **AC**: test: checkout via komponen menghasilkan sale benar & keranjang kosong; view struk memuat semua elemen wajib.
- **UAT**: UAT-POS-11–14, 17, 18, UAT-SET-02

### [x] T-110 — POS v2 bagian 3: tahan transaksi & riwayat shift
- **Dep**: T-109 · **PRD**: POS-10, 15
- **Scope**: F7 tahan (label default "Antrian #n" / nama pelanggan) → `held_carts`; F8 daftar tahan (panggil → harga dihitung ulang, hapus); cadangan keranjang di `localStorage` dengan pemulihan; panel "Riwayat" transaksi shift ini (lihat detail, cetak ulang, void — tombol void hanya jika `sales.void`; Kasir akan memakai PIN di T-201).
- **UAT**: UAT-POS-15, 16, 22, 23

### [x] T-111 — Pembelian (barang masuk)
- **Dep**: T-101, T-103, T-104, T-005 · **PRD**: PUR-01, 02, 06
- **Scope**: `/purchases` daftar (filter supplier, status, periode); form: supplier, no. faktur, tanggal, lokasi, item via cari/scan (barang, satuan, qty, harga beli — default harga terakhir × konversi, diskon), diskon faktur, ongkos lain, total; simpan draft / posting. `PurchaseService::post()` — landed cost §5.2, movement `purchase`, update `avg_cost` & `last_purchase_cost`; setelah posting tampilkan tabel saran harga jual (barang yang margin-nya turun di bawah margin sebelumnya) dengan tombol "Terapkan". `void()` sesuai PUR-06. Pembayaran Fase 1: hanya tunai/lunas (kredit di T-203).
- **AC**: test angka landed cost (UAT-PUR-03) & avg cost (UAT-PUR-02); draft tidak mengubah stok; void mengurangi stok.
- **UAT**: UAT-PUR-01–04, 07

### [x] T-112 — Posisi stok, kartu stok, stok minimum
- **Dep**: T-101 · **PRD**: INV-01, 02, 07, 08
- **Scope**: `/stock` posisi stok (cari, filter kategori/lokasi, stok dalam breakdown, nilai persediaan untuk yang berhak, total bawah); `/stock/card/{product}` kartu stok dengan filter periode & lokasi, saldo awal periode, link ke dokumen; `/stock/minimum` daftar barang ≤ min + saran beli (min × 2 − stok) + tombol "Buat draft pembelian".
- **UAT**: UAT-INV-02, 07, 09

### [x] T-113 — Stok opname
- **Dep**: T-101 · **PRD**: INV-04
- **Scope**: `/stock-opnames` daftar; buat sesi (lokasi, semua barang / per kategori) → item terisi `system_qty`; input `counted_qty` (tabel dengan cari + mode scan: scan = +1 atau input qty); simpan draft; posting (izin `stock.opname`) → refresh `system_qty`, hitung selisih, movement `opname`; cetak lembar hitung.
- **AC**: barang yang tidak diisi `counted_qty` tidak disesuaikan; draft tidak mengubah stok.
- **UAT**: UAT-INV-04, 05

### [x] T-114 — Barang keluar lain-lain (penyesuaian)
- **Dep**: T-101 · **PRD**: INV-05
- **Scope**: `/stock-adjustments` daftar & form (lokasi, alasan, item, catatan) → movement `adjustment` negatif dengan `avg_cost`.
- **UAT**: UAT-INV-06

### [x] T-115 — Kas masuk/keluar
- **Dep**: T-005 · **PRD**: FIN-01, FIN-02
- **Scope**: `/cash` daftar (filter periode, tipe, kategori; total masuk/keluar), form dengan upload foto bukti; kelola `cash_categories`. Data dari laci kasir (T-107) ikut tampil dengan badge "Laci Shift #n".
- **UAT**: UAT-FIN-01, 02

### [x] T-116 — Riwayat penjualan (backoffice)
- **Dep**: T-106 · **PRD**: POS-15, RPT-02
- **Scope**: `/sales` daftar (filter periode, kasir, metode, status, pelanggan, cari nomor), `/sales/{id}` detail (item, pembayaran, HPP & laba untuk yang berhak), cetak ulang, void dengan alasan.
- **UAT**: UAT-POS-22, UAT-RPT-03

### [x] T-117 — Dashboard v2
- **Dep**: T-106, T-111, T-115 · **PRD**: RPT-01
- **Scope**: kartu: omzet hari ini (vs kemarin %), jumlah transaksi, rata-rata/transaksi, laba kotor, kas di laci (shift aktif, semua kasir); grafik omzet 30 hari (Chart.js); top 10 barang terlaris (7 hari); kartu peringatan stok minimum (link ke `/stock/minimum`). Query diagregasi (tanpa N+1), cache 60 detik.
- **UAT**: UAT-RPT-01, 02

### [x] T-118 — Laporan inti
- **Dep**: T-116, T-111, T-112, T-107, T-115 · **PRD**: RPT-02–07, RPT-10, FIN-05
- **Scope**: `/reports` indeks + laporan: Penjualan (ringkasan harian, per kasir, per metode, per pelanggan), Penjualan per Barang/Kategori (qty, omzet, HPP, laba, margin), Pembelian, Posisi & Mutasi Stok (awal, masuk, keluar, akhir per periode), Shift, Kas, Laba Rugi Sederhana. Filter periode preset + lokasi; tampilan cetak (CSS print). Semua mengecualikan transaksi void.
- **AC**: test angka laba rugi (UAT-FIN-03) dan mutasi stok.
- **UAT**: UAT-RPT-03–06, 08, UAT-FIN-03

> 🎯 **Milestone Go-Live 1**: setelah T-118, jalankan UAT bertanda **[F1]** di `05-UAT.md`. Toko dapat berjalan paralel dengan iPos 1–2 minggu.

---

## Fase 2 — Menggantikan iPos Sepenuhnya

### [ ] T-201 — PIN supervisor & batas diskon kasir
- **Dep**: T-110 · **PRD**: AUTH-06, SET-06
- **Scope**: set PIN di profil (Owner/Admin); modal PIN di POS untuk void, diskon melebihi `pos.max_cashier_discount_percent`, override harga; catat siapa yang mengotorisasi (`authorized_by` di sale/sale_items — tambah kolom).
- **UAT**: UAT-POS-10, 23, UAT-AUTH-08

### [ ] T-202 — Penjualan kredit & piutang
- **Dep**: T-109 · **PRD**: POS-14, FIN-03, RPT-08
- **Scope**: izinkan kurang bayar untuk pelanggan dengan batas kredit (§5.4); `/receivables` daftar per pelanggan + umur; terima pembayaran multi-nota (alokasi otomatis ke nota tertua); bisa dari POS ("Bayar Piutang"); cetak bukti bayar; laporan piutang & umur; kartu peringatan jatuh tempo di dashboard.
- **UAT**: UAT-POS-26, 27, UAT-FIN-04, 05

### [ ] T-203 — Pembelian kredit & hutang
- **Dep**: T-111 · **PRD**: PUR-03, FIN-04
- **Scope**: pembelian tidak lunas + jatuh tempo; `/payables` daftar + umur; bayar hutang multi-faktur; laporan hutang; kartu peringatan dashboard.
- **UAT**: UAT-PUR-05, UAT-FIN-06

### [ ] T-204 — Retur penjualan
- **Dep**: T-202 · **PRD**: POS-20
- **Scope**: migrasi `sale_returns`(+items); dari POS/riwayat: pilih nota → qty retur ≤ qty terjual − sudah diretur; opsi restock; refund tunai (kurangi kas shift) / non-tunai / potong piutang; movement `sale_return`; laporan penjualan menghitung retur sebagai pengurang.
- **UAT**: UAT-POS-28

### [ ] T-205 — Retur pembelian
- **Dep**: T-203 · **PRD**: PUR-04
- **Scope**: `purchase_returns`(+items); pilih faktur → qty ≤ diterima; kurangi stok (movement `purchase_return` dengan landed cost asal) & hutang/uang kembali.
- **UAT**: UAT-PUR-06

### [ ] T-206 — Multi-lokasi & transfer stok
- **Dep**: T-112 · **PRD**: INV-06, SET-08
- **Scope**: pemilih lokasi aktif di topbar (user dengan >1 lokasi); POS memakai lokasi shift; `stock_transfers`(+items) draft → posting (movement `transfer_out` & `transfer_in` dengan `avg_cost`); cetak surat jalan; laporan stok per lokasi.
- **UAT**: UAT-INV-08

### [ ] T-207 — Harga grosir bertingkat
- **Dep**: T-108 · **PRD**: MST-05
- **Scope**: `product_price_tiers` di form barang (per satuan); `PricingService` memilih tier; POS menampilkan label "Grosir" saat tier aktif.
- **UAT**: UAT-MST-10

### [ ] T-208 — Import/Export Excel master data
- **Dep**: T-103, T-104, T-105 · **PRD**: MST-08, INV-03
- **Scope**: `spatie/simple-excel`; template unduh (barang+satuan+harga+barcode, pelanggan, supplier, saldo awal stok); import dengan pratinjau, validasi per baris, mode "tambah baru / perbarui berdasarkan kode", laporan baris gagal (unduh); export daftar sesuai filter. Import besar via job antrian.
- **UAT**: UAT-MST-11

### [ ] T-209 — Cetak label barcode
- **Dep**: T-103 · **PRD**: MST-09
- **Scope**: `/products/labels`: pilih barang/satuan + jumlah label, template 33×15 mm (1 kolom) dan 106×15 mm (3 kolom, setara `ROL_106x15` iPos), isi: nama, barcode (JsBarcode CODE128/EAN13), harga; cetak via browser.
- **UAT**: UAT-MST-12

### [ ] T-210 — Tampilan pelanggan (customer display)
- **Dep**: T-109 · **PRD**: POS-17
- **Scope**: `/pos/display` layar penuh; kasir mem-broadcast state keranjang/total/kembalian via `BroadcastChannel('ipos-pos')`; tampilan idle: logo + teks berjalan dari setting; tombol "Buka Tampilan Pelanggan" di menu POS.
- **UAT**: UAT-POS-29

### [ ] T-211 — Ekspor laporan & rekap bulanan/tahunan
- **Dep**: T-118 · **PRD**: RPT-09, RPT-10
- **Scope**: tombol Excel & PDF (dompdf) di semua laporan T-118; laporan Rekap Bulanan (per hari dalam bulan) & Tahunan (per bulan): omzet, HPP, laba kotor, biaya, laba bersih, jumlah transaksi.
- **UAT**: UAT-RPT-07, 09

### [ ] T-212 — Audit log
- **Dep**: T-103, T-106 · **PRD**: SYS-01
- **Scope**: `spatie/laravel-activitylog` pada Product, ProductUnit (terutama harga), Customer, Supplier, Setting, User, role/permission; event void/opname posting/override harga; halaman `/settings/activity` (Owner) dengan filter user/model/periode.
- **UAT**: UAT-SYS-01

### [ ] T-213 — Backup otomatis
- **Dep**: T-005 · **PRD**: SYS-02
- **Scope**: `spatie/laravel-backup` (DB + storage/app/public), jadwal harian 23:00, retensi 14 hari, opsi disk S3/Google Drive via env; halaman Owner: daftar backup, unduh, backup sekarang; panduan `docs/guides/deployment.md` (server lokal & VPS, scheduler/cron, queue worker).
- **UAT**: UAT-SYS-02

### [ ] T-214 — Importer data iPos 4.0
- **Dep**: T-208 · **PRD**: SYS-03
- **Scope**: sesuai `03-LEGACY-MAPPING.md §4.3`: koneksi `legacy`, `ipos:inspect-legacy` (tulis `docs/legacy-schema.md`), `ipos:import-legacy --only=products,customers,suppliers,stock,balances --dry-run`, tabel `legacy_id_maps`, laporan ringkasan.
- **UAT**: UAT-SYS-03

### [ ] T-215 — Pajak (PPN) opsional
- **Dep**: T-106 · **PRD**: SET-04
- **Scope**: setting pajak; perhitungan §5.4 (inklusif = informasi DPP/PPN di struk; eksklusif = ditambahkan ke total); tampil di struk & laporan.
- **UAT**: UAT-SET-06

> 🎯 **Milestone Go-Live 2**: iPos 4.0 dapat dimatikan setelah UAT **[F2]** lulus dan migrasi data (T-214 atau T-208) tervalidasi.

---

## Fase 3 — Nilai Tambah (opsional, sesuai kebutuhan)

| ID | Task | PRD | Ringkas |
|---|---|---|---|
| [ ] T-301 | Pesanan Pembelian (PO) | PUR-05 | PO → terima sebagian/penuh → pembelian; status PO. |
| [ ] T-302 | Poin pelanggan | – | Aturan poin per Rp X, tukar poin sebagai diskon, riwayat poin (pengganti `tbl_pointambil`). |
| [ ] T-303 | Akuntansi double-entry | FIN-06 | COA default toko, jurnal otomatis dari event, buku besar, neraca, laba rugi berbasis jurnal. |
| [ ] T-304 | PWA & offline kasir | SYS-04 | Manifest + service worker; antrian transaksi offline di IndexedDB, sinkron saat online (butuh desain konflik stok). |
| [ ] T-305 | Struk digital WhatsApp | POS-21 | Signed URL struk publik + tombol "Kirim WA" (wa.me). |
| [ ] T-306 | Dashboard multi-outlet | – | Perbandingan omzet antar lokasi untuk Owner. |
| [ ] T-307 | Barang paket/rakitan sederhana | – | Paket terdiri dari beberapa barang; stok komponen berkurang saat paket terjual. |
