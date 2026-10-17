# Pemetaan Legacy iPos 4.0 → iPos Web

Sumber analisis: folder `ipos desktop/` — `log_create_db.txt` (log `pg_restore` berisi nama 65 tabel, 68 fungsi, 184 foreign key), `ipos4.conf` / `miniprint.confdb` / `custdisp.confdb` (SQLite konfigurasi), nama ikon menu di `iPos4.exe`, dan dokumen analisis/blueprint.

> ⚠️ Log restore hanya memuat **nama** tabel, FK, dan signature fungsi — **bukan nama kolom**. Nama kolom legacy harus diverifikasi dari database `db_pos4` yang asli (lihat §4) sebelum menulis importer.

## 1. Kamus Singkatan iPos

| Singkatan | Arti |
|---|---|
| `im` | Item Masuk = **pembelian** (juga retur jual masuk) |
| `ik` | Item Keluar = **penjualan** (juga retur beli) |
| `hd` / `dt` | Header / Detail dokumen |
| `itr` | Item Transfer antar kantor/gudang |
| `pesan` | Pesanan (PO / SO) |
| `rakitan` | Barang rakitan / paket (BOM) |
| `supel` | **Sup**plier + **Pel**anggan (satu tabel kontak) |
| `hupi` | **Hu**tang **Pi**utang |
| `rb` / `rj` | Retur Beli / Retur Jual |
| `kantor` | Kantor/cabang/gudang = lokasi stok |
| `perkiraan` | Bagan akun (Chart of Accounts) |
| `mu` | Mata uang |
| `sa` | Saldo awal |
| `hj` | Harga jual |
| `acc` | Akuntansi |

## 2. Pemetaan Tabel

| Tabel iPos 4.0 | Fungsi legacy | Tabel iPos Web | Keputusan |
|---|---|---|---|
| `tbl_user`, `tbl_userg` (grup), `tbl_userakses`, `tbl_usercus_acc` | User, grup user, hak akses per menu | `users`, `roles`, `permissions`, `model_has_roles` (Spatie) | **Keep, disederhanakan** ke 4 peran + permission |
| `tbl_kantor` | Kantor/cabang/gudang | `locations` | **Keep** |
| `tbl_formatnotr`, `tbl_formatnosp` | Format nomor transaksi / nomor surat | `settings` (`doc.prefix.*`) + `document_sequences` | **Simplify** |
| `tbl_conf`, `tbl_settingpel`, `tbl_perksetting` | Konfigurasi global, setting pelanggan, setting akun default | `settings` | **Simplify** |
| `tbl_item` | Master barang (+ akun persediaan/HPP/pendapatan per item) | `products` | **Keep** (tanpa akun per item) |
| `tbl_itemjenis` | Jenis/kategori | `categories` | **Keep** |
| `tbl_itemmerek` | Merek | `brands` | **Keep (opsional)** |
| `tbl_itemsatuan`, `tbl_itemsatuanjml` | Satuan & konversi/barcode per satuan | `product_units` | **Keep** |
| `tbl_itemhj` | Harga jual per level / per jumlah / per satuan (lihat fungsi `update_hj_level`, `update_hj_jumlah`, `update_hj_satuan`) | `product_units.price_sell` + `product_price_tiers` | **Simplify**: harga per satuan + tier grosir per qty. Level harga per pelanggan ditunda. |
| `tbl_itempotongan` | Potongan harga per grup pelanggan | — | **Ditunda** |
| `tbl_itemdisp`, `tbl_itemdispdt` | Diskon/promo per jenis/merek/item | — (promo P2 di masa depan) | **Ditunda** |
| `tbl_itemstok` | Stok per item per kantor | `product_stocks` | **Keep** |
| `tbl_item_im`, `tbl_item_ik`, `tbl_item_rekap` | Riwayat masuk/keluar & rekap untuk HPP (fungsi `proseshppin`, `proseshppoutavg`, `proseshppoutfifo`) | `stock_movements` | **Simplify**: satu buku besar stok, HPP moving average saja |
| `tbl_item_sa` | Saldo awal stok | `stock_movements` (type `opening`) | **Keep** |
| `tbl_itemopname` | Stok opname | `stock_opnames`, `stock_opname_items` | **Keep** |
| `tbl_imhd`, `tbl_imdt` | Pembelian (+ retur jual via `tbl_imhd_retur`) | `purchases`, `purchase_items` (+ `sale_returns`) | **Keep**, retur dipisah ke tabel sendiri |
| `tbl_ikhd`, `tbl_ikdt` | Penjualan (+ retur beli), sales1–4, akun, bank, pajak | `sales`, `sale_items`, `payments` (+ `purchase_returns`) | **Keep**, disederhanakan |
| `tbl_itrhd`, `tbl_itrdt` | Transfer antar kantor | `stock_transfers`, `stock_transfer_items` | **Keep (Fase 2)** |
| `tbl_pesanhd`, `tbl_pesandt` | Pesanan pembelian/penjualan | `purchase_orders` | **Fase 3** (PO saja) |
| `tbl_itemrakitan`, `tbl_imrakitan`, `tbl_ikrakitan`, `tbl_pesanrakitan` | Barang rakitan (BOM) | — | **Ditunda** |
| `tbl_itemserial`, `tbl_itemserialdt` | Serial number per unit | — | **Ditunda** |
| `tbl_supel`, `tbl_supelgrup`, `tbl_supel_wil`, `tbl_supel_subwil` | Supplier/pelanggan, grup, wilayah | `customers`, `suppliers` | **Split** jadi 2 tabel; grup & wilayah ditunda |
| `tbl_pointambil` | Pengambilan poin pelanggan | — | **Fase 3** |
| `tbl_hupi_sa` | Saldo awal hutang/piutang | `sales`/`purchases` bertipe saldo awal (lihat §4.3) | **Keep via import** |
| `tbl_rj_piutang`, `tbl_rb_hutang` | Retur yang memotong piutang/hutang | `sale_returns.refund_method = reduce_receivable`, `purchase_returns` | **Keep (Fase 2)** |
| `tbl_byrpiutanghd/dt` | Pembayaran piutang | `payments` (payable = Sale, `is_initial = false`) | **Keep (Fase 2)** |
| `tbl_byrhutanghd/dt/item` | Pembayaran hutang | `payments` (payable = Purchase, direction `out`) | **Keep (Fase 2)** |
| `tbl_bank` | Rekening bank/EDC | — (metode bayar + referensi) | **Simplify** |
| `tbl_acckashd`, `tbl_acckasdt` | Kas masuk/keluar/transfer kas | `cash_transactions`, `cash_categories` | **Keep, disederhanakan** |
| `tbl_perkiraan`, `tbl_accjurnal`, `tbl_acc_sa`, `tbl_acc_tmplrnr`, `tbl_acctmpns` | COA, jurnal, saldo awal akun, template laba rugi/neraca | `accounts`, `journal_entries`, `journal_lines` | **Fase 3 opsional**. Fase 1: laba rugi sederhana dari data operasional |
| `tbl_matauang`, `tbl_mu_ratesa` | Mata uang & kurs | — | **Drop** |
| `tbl_sandi`, `tbl_infodb`, `tbl_tmp` | Sandi/lisensi, versi DB, tabel temporer | — | **Drop** |

## 3. Pemetaan Konfigurasi Lokal (SQLite `ipos4.conf` → `settings`)

| Key iPos (`tbl_conf`) | Nilai contoh di instalasi ini | Key iPos Web |
|---|---|---|
| `KASIR_NOTE` | "Barang yang telah dibeli tidak dapat dikembalikan kecuali ada perjanjian / Terima kasih atas kunjungan anda" | `receipt.footer` |
| `KASIR_KERTAS` | "Kertas 32 Kolom" | `receipt.paper_width = 58` |
| `KASIR_PRINTER`, `KASIR_TIPE_MP`, `KASIR_MODE_PRINTER` | "POS58 Printer", "GENERIC ESC/POS", "PRINTER POS" | Driver OS + cetak browser (tidak disimpan) |
| `KASIR_COPY` | 1 | `receipt.copies` |
| `KASIR_LOGO_AKTIF` | 0 | `receipt.show_logo` |
| `KASIR_LBL_BULAT` | "Pembulatan" | `pos.rounding` (label) |
| `KASIR_AC` (auto cetak) | Ya | `receipt.auto_print` |
| `KASIR_RESET_JML` | Ya | (perilaku default: qty kembali 1 setelah scan) |
| `KASIR_OPSUPEL` | "TIDAK TAMPIL PELANGGAN DAN SALES" | Pelanggan opsional (default Umum) |
| `KASIR_CD`, `CFG_KASIR_CUSTDISP_*` | Ya, "Selamat Berbelanja", "Di Toko Kami" | `pos.customer_display_text` (POS-17) |
| `tbl_minip` (`miniprint.confdb`: cashdrawer, fullcut, partialcut) | perintah ESC/POS | Driver printer ("open drawer/cut on print") |
| `BARCODE_CTK_DESIGN` | "ROL_106x15" | Template label barcode 106×15 mm (3 kolom) di MST-09 |
| `CFG_HOST`, `tbl_hostlist` | Server `192.168.3.4` | Tidak perlu — cukup URL aplikasi |
| `CFG_TEMA` | Office 2010 Silver | — |

## 4. Rencana Migrasi Data (SYS-03)

### 4.1 Prinsip
- Yang dimigrasikan hanya **data master + saldo akhir**, bukan seluruh riwayat transaksi (riwayat lama tetap bisa dilihat di iPos lama / arsip ekspor Excel). Ini jauh lebih sederhana dan aman.
- Tanggal cut-off: tutup buku iPos di akhir hari, lakukan opname, lalu import saldo per tanggal tersebut.

### 4.2 Jalur A — Excel (direkomendasikan, tanpa akses teknis ke DB)
1. Ekspor dari iPos (menu Daftar Barang/Pelanggan/Supplier → Ekspor Excel; ikon `Excel-icon-16`).
2. Sesuaikan ke template iPos Web (`storage/app/templates/*.xlsx` — dibuat pada task T-208).
3. Import berurutan: Kategori → Barang + Satuan + Harga → Pelanggan → Supplier → Saldo awal stok (qty + harga pokok per lokasi) → Saldo awal piutang/hutang.

### 4.3 Jalur B — Koneksi langsung PostgreSQL (`php artisan ipos:import-legacy`)
1. Tambah koneksi `legacy` (pgsql) di `config/database.php` dari env `LEGACY_DB_*` (host iPos, db `db_pos4`, read-only user).
2. **Langkah pertama wajib**: jalankan `php artisan ipos:inspect-legacy` yang mencetak kolom setiap tabel sumber (`information_schema.columns`) untuk `tbl_item`, `tbl_itemsatuan`, `tbl_itemsatuanjml`, `tbl_itemhj`, `tbl_itemjenis`, `tbl_itemmerek`, `tbl_itemstok`, `tbl_supel`, `tbl_kantor`, `tbl_ikhd`, `tbl_imhd`, `tbl_hupi_sa`. Simpan hasilnya ke `docs/legacy-schema.md` lalu tetapkan pemetaan kolom.
3. Petakan ke tabel baru dengan tabel pemetaan `legacy_id_maps` (`source_table`, `source_key`, `target_type`, `target_id`) agar import idempoten (bisa diulang).
4. Stok: gunakan fungsi legacy `getstokakhir(kodeitem, kantor, ...)` atau `tbl_itemstok` untuk saldo; HPP dari `getlastupdhp(kodeitem)` / kolom HPP item → movement `opening`.
5. Hutang/piutang terbuka: buat `sales`/`purchases` bertanda `notes = 'Saldo awal iPos'` tanpa item (atau dengan satu item jasa "Saldo Awal") dengan `payment_status = unpaid`, `due_date` sesuai legacy.
6. Hasil: ringkasan jumlah record per entitas + file log baris yang gagal.

### 4.4 Validasi pasca-migrasi (lihat UAT-IMP)
- Jumlah barang aktif, pelanggan, supplier sama dengan iPos.
- Total nilai persediaan (Σ qty × HPP) per lokasi selisih < 0,5%.
- Total piutang & hutang per kontak sama persis.
- Sampel 20 barang: harga jual tiap satuan & barcode sama.
