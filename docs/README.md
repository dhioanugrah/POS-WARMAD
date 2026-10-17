# Dokumentasi Proyek — iPos Web (Migrasi iPos 4.0 Desktop → Laravel)

Folder ini adalah **sumber kebenaran tunggal** untuk membangun aplikasi POS web pengganti Program Toko iPos 4.0 (WinForms + PostgreSQL). Semua agent/developer WAJIB membaca dokumen sesuai urutan di bawah sebelum menulis kode.

| # | Dokumen | Isi | Kapan dibaca |
|---|---------|-----|--------------|
| 1 | [01-PRD.md](01-PRD.md) | Visi produk, persona, ruang lingkup, kebutuhan fungsional ber-ID (mis. `POS-05`), prioritas, NFR | Selalu, sebelum task apa pun |
| 2 | [02-TECHNICAL-SPEC.md](02-TECHNICAL-SPEC.md) | Arsitektur, konvensi kode, skema database final, aturan bisnis (stok, HPP, penomoran), matriks hak akses | Sebelum menyentuh model/migrasi/service |
| 3 | [03-LEGACY-MAPPING.md](03-LEGACY-MAPPING.md) | Pemetaan tabel & fitur iPos 4.0 → web, keputusan *keep/simplify/drop*, rencana migrasi data | Saat mengerjakan import data / butuh konteks legacy |
| 4 | [04-ROADMAP-TASKS.md](04-ROADMAP-TASKS.md) | Backlog task siap-kerja per fase (T-xxx), dependensi, acceptance criteria, template prompt agent | Untuk memilih task berikutnya |
| 5 | [05-UAT.md](05-UAT.md) | Skenario User Acceptance Test ber-ID, data uji, lembar sign-off | Setelah fitur selesai / sebelum rilis |

## Status saat ini (per 2026-09-26)

**Fase 0 (Fondasi) dan Fase 1 (MVP Operasional Toko) selesai** — semua task T-001 s/d T-118 ditandai `[x]` di [04-ROADMAP-TASKS.md](04-ROADMAP-TASKS.md), dengan 138 feature/unit test hijau (`php artisan test`).

Tersedia di `ipos-laravel/`:
- Auth (login email/username, rate limit, user nonaktif), RBAC 4 peran (Owner/Admin/Kasir/Gudang) via `spatie/laravel-permission`, manajemen user & hak akses.
- Layout backoffice (sidebar + topbar responsif) dan kit komponen Blade (`x-button`, `x-input`, `x-money-input`, `x-table`, `x-modal`, `x-badge`, dll).
- Pengaturan toko, lokasi, penomoran dokumen otomatis.
- Master data: kategori/merek, barang multi-satuan + multi-harga per satuan, pelanggan, supplier, saldo awal stok.
- Kasir (`/pos`): scan barcode, cari barang, format `qty*kode`, ganti satuan, diskon item/total, split payment, tahan transaksi, shift + kas laci, riwayat & void, cetak struk 58/80mm.
- Pembelian (draft → posting dengan HPP rata-rata & landed cost), void pembelian, saran harga jual.
- Stok: posisi, kartu stok, stok minimum + draft pembelian otomatis, stok opname, penyesuaian.
- Keuangan: kas masuk/keluar + kategori.
- Riwayat penjualan backoffice + void.
- Dashboard (omzet, laba kotor, grafik 30 hari, top barang, peringatan stok minimum) dan laporan inti (penjualan, per barang, pembelian, mutasi stok, shift, kas, laba rugi).

Belum dikerjakan (Fase 2, di luar cakupan sesi ini): PIN supervisor, penjualan/pembelian kredit penuh + halaman piutang/hutang, retur jual/beli, transfer lokasi, harga grosir bertingkat, import/export Excel, cetak label barcode, tampilan pelanggan, audit log, backup otomatis, importer iPos legacy, PPN. Lihat [04-ROADMAP-TASKS.md → Fase 2](04-ROADMAP-TASKS.md).

## Aturan kerja untuk agent

1. **Satu task = satu T-ID** dari `04-ROADMAP-TASKS.md`. Jangan mengerjakan di luar scope task.
2. Patuhi skema & aturan di `02-TECHNICAL-SPEC.md`. Jika harus menyimpang, **perbarui dokumen spesifikasi di commit yang sama** dan jelaskan alasannya.
3. Setiap task wajib menyertakan **feature test PHPUnit** untuk acceptance criteria-nya, dan `php artisan test` harus hijau.
4. Jalankan `vendor/bin/pint --dirty` sebelum selesai.
5. Setelah selesai, centang task di `04-ROADMAP-TASKS.md` dan catat UAT ID terkait yang siap diuji.
6. Bahasa UI: **Bahasa Indonesia**. Bahasa kode (nama kelas, kolom, variabel): **Inggris**.
