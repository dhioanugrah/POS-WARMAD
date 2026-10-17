# PRD — iPos Web: POS & Manajemen Toko Berbasis Web

| Atribut | Nilai |
|---|---|
| Versi dokumen | 1.0 |
| Tanggal | 2026-09-26 |
| Sumber | Analisis iPos 4.0 Desktop (`ipos desktop/`), Blueprint Migrasi, prototipe `ipos-laravel/` |
| Status | Disetujui untuk dikembangkan bertahap (Fase 0 → 3) |

---

## 1. Latar Belakang

Program Toko iPos 4.0 adalah aplikasi kasir desktop Windows (.NET 4.0 WinForms + DevExpress 10.2) dengan database PostgreSQL. Aplikasi ini lengkap (±65 tabel: stok multi-gudang, multi-satuan, multi-harga, hutang-piutang, pesanan, rakitan, serial number, multi mata uang, akuntansi double-entry), tetapi:

- **Hanya berjalan stabil di Windows**; Linux/macOS bermasalah (Wine/VM).
- **Lisensi terkunci hardware** (`imkrep.dll` membaca serial HDD/CPU) — ganti PC = aktivasi ulang via SMS.
- **Instalasi berat**: PostgreSQL server, konfigurasi `pg_hba.conf`, port 5432, .NET runtime, driver COM printer.
- **Tidak bisa diakses dari luar toko** (pemilik tidak bisa memantau omzet dari HP).
- **Terlalu banyak fitur** yang tidak dipakai toko kecil–menengah sehingga kurva belajar tinggi.
- **UI generasi 2010** (Ribbon Office 2010), tidak ramah layar sentuh/tablet.

## 2. Visi Produk

> **"Kasir secepat desktop, dikelola semudah aplikasi HP."**

Aplikasi web POS yang bisa dijalankan di browser apa pun (PC kasir, tablet, HP), dengan fitur inti iPos yang *benar-benar dipakai* toko sehari-hari — jual, beli, stok, kas, hutang-piutang, laporan — tanpa instalasi rumit dan tanpa lisensi hardware.

### Prinsip desain
1. **Sederhana dulu** — fitur dipilih berdasarkan pemakaian nyata, bukan kelengkapan. Fitur legacy yang jarang dipakai ditunda atau dihapus (lihat §6).
2. **Kasir tidak boleh menunggu** — layar kasir dioptimalkan untuk scan barcode + keyboard; setiap aksi < 300 ms.
3. **Stok selalu bisa dilacak** — setiap perubahan stok tercatat di kartu stok (tidak ada angka stok yang "tiba-tiba berubah").
4. **Pemilik tahu kondisi toko dari mana saja** — dashboard & laporan responsif di HP.
5. **Data aman** — hak akses per peran, audit log, backup otomatis.

## 3. Tujuan & Metrik Keberhasilan

| Tujuan | Metrik | Target |
|---|---|---|
| Kasir cepat | Waktu dari scan item pertama sampai struk tercetak (5 item, tunai) | ≤ 20 detik |
| Mudah dipelajari | Kasir baru bisa transaksi tanpa bantuan | ≤ 15 menit pelatihan |
| Akurasi stok | Selisih stok sistem vs fisik saat opname | < 2% item |
| Visibilitas pemilik | Omzet & laba hari ini dapat dilihat dari HP | Real-time (tanpa proses "rekap") |
| Bebas instalasi | Setup toko baru (server siap) hingga transaksi pertama | ≤ 1 jam termasuk import barang |

## 4. Persona & Peran

| Peran | Siapa | Kebutuhan utama | Perangkat |
|---|---|---|---|
| **Owner** (Pemilik) | Pemilik toko | Pantau omzet/laba/stok dari mana saja, atur harga, lihat laporan, kelola user | HP, laptop |
| **Admin** (Backoffice) | Staf administrasi | Input pembelian, kelola master barang, pelanggan, supplier, hutang-piutang, laporan | PC |
| **Kasir** | Kasir toko | Transaksi cepat, cetak struk, buka/tutup shift, terima pembayaran piutang sederhana | PC kasir + scanner + printer thermal, atau tablet |
| **Gudang** | Staf gudang | Terima barang, transfer antar lokasi, stok opname, cek kartu stok | PC/tablet/HP |

## 5. Ruang Lingkup Fungsional

Prioritas: **P0** = wajib untuk go-live (MVP), **P1** = wajib sebelum menggantikan iPos sepenuhnya, **P2** = nilai tambah / opsional.
Kolom *Fase* merujuk ke `04-ROADMAP-TASKS.md`.

### 5.1 Autentikasi, User & Hak Akses (AUTH)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| AUTH-01 | Login dengan email/username + password; logout; "ingat saya". | P0 | 0 |
| AUTH-02 | Registrasi publik **dinonaktifkan**; user dibuat oleh Owner/Admin. | P0 | 0 |
| AUTH-03 | 4 peran bawaan: Owner, Admin, Kasir, Gudang, dengan matriks izin (lihat Tech Spec §7). Owner dapat mengubah izin per peran. | P0 | 0 |
| AUTH-04 | Kelola user: tambah, ubah, nonaktifkan, reset password, set peran dan lokasi default. | P0 | 0 |
| AUTH-05 | Setelah login, Kasir langsung diarahkan ke layar POS; peran lain ke Dashboard. | P0 | 0 |
| AUTH-06 | **PIN Supervisor** (6 digit) untuk otorisasi aksi sensitif di kasir (void, diskon di atas batas, ubah harga) tanpa logout kasir. | P1 | 2 |
| AUTH-07 | Batas percobaan login (5x/menit) dan log login. | P0 | 0 |

### 5.2 Pengaturan Toko (SET)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| SET-01 | Profil toko: nama, alamat, telepon, logo, NPWP (opsional) — tampil di struk & laporan. | P0 | 0 |
| SET-02 | Pengaturan struk: lebar kertas 58 mm / 80 mm, catatan kaki (default iPos: *"Barang yang telah dibeli tidak dapat dikembalikan kecuali ada perjanjian. Terima kasih atas kunjungan anda"*), tampilkan logo, jumlah salinan, cetak otomatis setelah bayar. | P0 | 1 |
| SET-03 | Pembulatan total (tidak ada / ke 100 / ke 500 / ke 1.000). | P1 | 1 |
| SET-04 | Pajak PPN opsional (non-aktif default; persen; harga termasuk/belum termasuk pajak). | P2 | 2 |
| SET-05 | Izinkan stok minus (default: **Ya**, dengan peringatan di kasir). | P0 | 1 |
| SET-06 | Batas diskon maksimal Kasir (% per transaksi) tanpa PIN supervisor. | P1 | 2 |
| SET-07 | Prefix nomor dokumen per jenis transaksi (PJ, PB, RJ, RB, TR, OP, KM, KK). | P1 | 0 |
| SET-08 | Kelola lokasi (Toko / Gudang). MVP minimal 1 lokasi; multi-lokasi di Fase 2. | P0 | 0 |

### 5.3 Master Data (MST)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| MST-01 | Kategori (Jenis) barang: CRUD, tidak boleh dihapus jika masih dipakai barang. | P0 | 1 |
| MST-02 | Merek barang (opsional di form barang). | P2 | 1 |
| MST-03 | Barang: kode/SKU, nama, kategori, merek, tipe (**Barang Stok** / **Jasa** — jasa tidak mengurangi stok), stok minimum, foto, status aktif. Pencarian instan (nama/kode/barcode), filter kategori & status, paginasi. | P0 | 1 |
| MST-04 | **Multi-satuan**: satu barang punya satuan dasar (konversi 1) dan satuan lain (mis. Dus = 40 Pcs). Tiap satuan punya **barcode sendiri** dan **harga jual sendiri**. | P0 | 1 |
| MST-05 | **Harga grosir bertingkat** per satuan (mis. ≥ 10 pcs = Rp 2.800). | P1 | 2 |
| MST-06 | Pelanggan: kode, nama, telepon, alamat, batas kredit, catatan. Ada pelanggan default "Umum" (tanpa data). | P0 | 1 |
| MST-07 | Supplier: kode, nama, kontak, telepon, alamat, catatan. | P0 | 1 |
| MST-08 | **Import/Export Excel** untuk barang (termasuk satuan & harga), pelanggan, supplier; dengan template dan laporan baris gagal. | P1 | 2 |
| MST-09 | **Cetak label barcode** barang (pilih barang + jumlah label, ukuran label umum 33×15 / 106×15 mm "3 kolom"), via browser print. | P1 | 2 |
| MST-10 | Barang dapat dinonaktifkan (tidak muncul di kasir) tanpa menghapus riwayat. Hapus permanen hanya jika belum punya transaksi. | P0 | 1 |

### 5.4 Kasir / POS (POS)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| POS-01 | Layar kasir penuh (tanpa navigasi backoffice), tata letak: kiri = pencarian + daftar/grid produk, kanan = keranjang + total + tombol bayar. Responsif untuk PC (≥1024 px) dan tablet (mode sentuh: tombol besar). | P0 | 1 |
| POS-02 | **Scan barcode** (scanner USB = input keyboard + Enter) langsung menambah item dengan satuan sesuai barcode. Scan item yang sama menambah qty. | P0 | 1 |
| POS-03 | Pencarian barang ketik nama/kode dengan hasil instan (≤ 300 ms), navigasi panah ↑/↓ + Enter. | P0 | 1 |
| POS-04 | Input qty cepat: format `3*kode` (qty × kode) seperti iPos, serta edit qty/satuan langsung di keranjang; tombol +/−. | P0 | 1 |
| POS-05 | Ganti satuan item di keranjang (Pcs ↔ Dus) — harga otomatis mengikuti satuan (dan harga grosir bila P1 aktif). | P0 | 1 |
| POS-06 | Diskon per item (Rp atau %) dan diskon total transaksi (Rp atau %). | P0 | 1 |
| POS-07 | Pilih pelanggan (cari nama/telepon, atau tambah cepat). Default "Umum". | P0 | 1 |
| POS-08 | **Pembayaran**: Tunai (tombol uang cepat: Uang Pas, 10rb, 20rb, 50rb, 100rb, 200rb), QRIS, Transfer, Kartu Debit/Kredit; tampil kembalian besar. | P0 | 1 |
| POS-09 | **Split payment** (mis. Rp 50.000 tunai + sisa QRIS). | P1 | 1 |
| POS-10 | **Hold / Tahan transaksi**: simpan keranjang dengan label, layani pelanggan lain, panggil kembali. Keranjang yang ditahan tersimpan di server (aman dari refresh). | P0 | 1 |
| POS-11 | **Cetak struk** thermal 58/80 mm via dialog cetak browser (atau cetak otomatis dengan setting kiosk). Cetak ulang struk terakhir / dari riwayat. | P0 | 1 |
| POS-12 | **Shift kasir**: buka shift dengan modal awal; tutup shift dengan input uang fisik → sistem menampilkan uang seharusnya, selisih, rekap per metode bayar; cetak laporan shift. Transaksi tidak bisa dilakukan tanpa shift terbuka. | P0 | 1 |
| POS-13 | **Kas masuk/keluar dari laci** saat shift (mis. ambil uang untuk beli galon) — tercatat & masuk hitungan shift. | P1 | 1 |
| POS-14 | **Penjualan kredit (piutang)**: jika pelanggan bukan "Umum", transaksi boleh dibayar sebagian/tidak dibayar dengan jatuh tempo; dicek terhadap batas kredit. | P1 | 2 |
| POS-15 | **Riwayat transaksi** di kasir (shift ini): lihat, cetak ulang, void (dengan izin/PIN). | P0 | 1 |
| POS-16 | **Shortcut keyboard** (lihat Tech Spec §8.2): F2 cari, F4 pelanggan, F6 diskon, F7 tahan, F8 daftar tahan, F9 bayar, F10 cetak ulang, Esc batal/tutup. Legend shortcut selalu terlihat. | P0 | 1 |
| POS-17 | **Tampilan pelanggan (customer display)**: halaman kedua yang bisa dibuka di monitor kedua, menampilkan item, total, kembalian, dan teks berjalan (default "Selamat Berbelanja di Toko Kami"). | P2 | 2 |
| POS-18 | Harga **tidak pernah diambil dari browser** — server menghitung ulang harga, diskon, total saat checkout. Ubah harga manual hanya dengan izin `pos.override-price`. | P0 | 1 |
| POS-19 | Peringatan visual jika stok barang ≤ 0 atau di bawah minimum saat ditambahkan ke keranjang. | P0 | 1 |
| POS-20 | Retur penjualan dari kasir (pilih nomor struk → pilih item & qty → uang kembali / potong piutang). | P1 | 2 |
| POS-21 | Kirim struk digital via WhatsApp (link struk publik bertanda-tangan). | P2 | 3 |

### 5.5 Pembelian & Supplier (PUR)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| PUR-01 | Input pembelian (barang masuk dari supplier): supplier, no. faktur supplier, tanggal, lokasi tujuan, item (barang, satuan, qty, harga beli, diskon), diskon & ongkos total. Status Draft → **Posted** (stok bertambah). | P0 | 1 |
| PUR-02 | Harga pokok rata-rata (moving average) dihitung ulang otomatis saat pembelian di-posting. Opsi "update harga jual" jika margin berubah (tampilkan saran). | P0 | 1 |
| PUR-03 | Pembelian tunai atau **kredit (hutang)** dengan jatuh tempo; pembayaran hutang sebagian/lunas, multi faktur sekaligus. | P1 | 2 |
| PUR-04 | Retur pembelian ke supplier (mengurangi stok & hutang/uang kembali). | P1 | 2 |
| PUR-05 | Pesanan pembelian (PO) → konversi menjadi pembelian. | P2 | 3 |
| PUR-06 | Batalkan pembelian yang sudah posted (void) — stok dikurangi kembali, hanya Owner/Admin, dengan alasan. | P1 | 1 |

### 5.6 Persediaan / Stok (INV)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| INV-01 | **Stok per lokasi** disimpan dalam satuan dasar; tampil juga dalam satuan besar (mis. "2 Dus 5 Pcs"). | P0 | 1 |
| INV-02 | **Kartu stok** per barang per lokasi: tanggal, jenis transaksi, no. dokumen, masuk, keluar, saldo; klik no. dokumen membuka transaksinya. | P0 | 1 |
| INV-03 | **Saldo awal stok** (untuk go-live): input manual atau import Excel, dengan harga pokok awal. | P0 | 1 |
| INV-04 | **Stok opname**: buat sesi opname per lokasi (semua barang / per kategori), input stok fisik (bisa via scan), sistem hitung selisih, posting → penyesuaian otomatis. | P0 | 1 |
| INV-05 | **Barang keluar lain-lain** (rusak, hilang, kadaluarsa, pemakaian sendiri) dengan alasan. | P1 | 1 |
| INV-06 | **Transfer antar lokasi** (Toko ↔ Gudang). | P1 | 2 |
| INV-07 | Daftar **stok minimum** (barang di bawah min. stok) + saran jumlah beli, bisa dijadikan draft pembelian. | P0 | 1 |
| INV-08 | Nilai persediaan (qty × harga pokok) per lokasi/kategori. | P1 | 1 |

### 5.7 Keuangan & Kas (FIN)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| FIN-01 | **Kas masuk / kas keluar** (biaya operasional: listrik, gaji, sewa, dll.) dengan kategori, metode (Tunai/Bank), lampiran foto bukti. Menggantikan tabel `expenses` prototipe. | P0 | 1 |
| FIN-02 | Kategori kas (dapat dikelola), dibedakan Masuk/Keluar. | P0 | 1 |
| FIN-03 | **Piutang pelanggan**: daftar piutang per pelanggan, umur piutang (0–30, 31–60, 61–90, >90 hari), terima pembayaran (sebagian/lunas, multi nota). | P1 | 2 |
| FIN-04 | **Hutang supplier**: daftar, umur hutang, bayar hutang. | P1 | 2 |
| FIN-05 | **Laba rugi sederhana** (Penjualan bersih − HPP = Laba kotor − Biaya operasional = Laba bersih) per periode, tanpa perlu jurnal. | P0 | 1 |
| FIN-06 | Akuntansi double-entry penuh (Bagan akun, jurnal otomatis, buku besar, neraca). | P2 | 3 |

### 5.8 Dashboard & Laporan (RPT)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| RPT-01 | **Dashboard**: omzet hari ini vs kemarin, jumlah transaksi, rata-rata per transaksi, laba kotor, kas di laci (shift aktif), grafik omzet 30 hari, 10 barang terlaris, kartu peringatan (stok minimum, piutang/hutang jatuh tempo). Mobile-friendly. | P0 | 1 |
| RPT-02 | Laporan penjualan: per periode, per hari, per kasir, per metode bayar, per pelanggan; detail per nota. | P0 | 1 |
| RPT-03 | Laporan penjualan per barang / per kategori (qty, omzet, HPP, laba, margin). | P0 | 1 |
| RPT-04 | Laporan pembelian per periode / per supplier / per barang. | P1 | 1 |
| RPT-05 | Laporan stok: posisi stok, nilai persediaan, mutasi stok per periode (awal, masuk, keluar, akhir). | P0 | 1 |
| RPT-06 | Laporan shift kasir (per shift: modal, penjualan per metode, kas masuk/keluar, seharusnya, fisik, selisih). | P0 | 1 |
| RPT-07 | Laporan kas masuk/keluar & laba rugi sederhana. | P0 | 1 |
| RPT-08 | Laporan hutang/piutang & umur. | P1 | 2 |
| RPT-09 | Rekap bulanan & tahunan (omzet, laba per bulan) — pengganti menu "Rekap Bulanan/Tahunan" iPos. | P1 | 2 |
| RPT-10 | Semua laporan: filter periode (preset: Hari ini, Kemarin, 7 hari, Bulan ini, Bulan lalu, Kustom), filter lokasi, **ekspor Excel & PDF**, tampilan cetak. | P0 | 1 (Excel/PDF: 2) |

### 5.9 Sistem (SYS)

| ID | Kebutuhan | Prio | Fase |
|---|---|---|---|
| SYS-01 | **Audit log**: siapa melakukan apa & kapan untuk data master, harga, void, opname, pengaturan. | P1 | 2 |
| SYS-02 | **Backup database otomatis** harian + unduh backup manual oleh Owner; retensi 14 hari. | P1 | 2 |
| SYS-03 | **Import data dari iPos 4.0** (barang+satuan+harga, pelanggan, supplier, stok akhir, saldo hutang/piutang) via Excel ekspor iPos atau koneksi langsung ke PostgreSQL `db_pos4`. | P1 | 2 |
| SYS-04 | PWA (ikon di home screen tablet), mode offline terbatas untuk kasir. | P2 | 3 |

## 6. Di Luar Ruang Lingkup (sengaja tidak dibawa dari iPos 4.0)

| Fitur iPos 4.0 | Keputusan | Alasan |
|---|---|---|
| Aktivasi lisensi SMS / Hardware ID (`imkrep.dll`) | **Dihapus** | Tidak relevan untuk aplikasi web milik sendiri. |
| Multi mata uang & kurs (`tbl_matauang`, `tbl_mu_ratesa`) | **Dihapus** | Toko ritel lokal bertransaksi dalam Rupiah. |
| Serial number per unit (`tbl_itemserial*`) | **Ditunda (di luar v1)** | Hanya relevan untuk toko elektronik; kompleks. |
| Barang rakitan / paket (`tbl_itemrakitan`, `*rakitan`) | **Ditunda (P2+)** | Jarang dipakai; bisa diganti "barang paket" sederhana nanti. |
| Konsinyasi | **Dihapus v1** | Proses jarang, bisa dicatat sebagai pembelian biasa. |
| Pembayaran Cek/BG | **Disederhanakan** | Menjadi metode "Transfer/Lainnya" + nomor referensi. |
| Sales/salesman ganda (sales1–4 di `tbl_ikhd`) | **Disederhanakan** | Satu kolom "sales" opsional (P2) — kasir sudah tercatat. |
| Poin pelanggan (`tbl_pointambil`, "Tutup Point") | **Ditunda (P2, Fase 3)** | Nilai tambah, bukan inti. |
| HPP metode FIFO | **Disederhanakan** | Hanya Moving Average (lebih sederhana & cukup untuk ritel). |
| Customer display VFD RS232, printer COM port, BarTender | **Diganti** | Diganti tampilan pelanggan di browser, cetak via browser/driver OS, label barcode via browser. |
| Desain laporan (DevExpress Report Designer) | **Dihapus** | Laporan standar yang rapi + ekspor Excel. |
| Wilayah/sub-wilayah pelanggan, grup pelanggan | **Ditunda** | Cukup kolom alamat + catatan untuk v1. |

## 7. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan |
|---|---|---|
| NFR-01 | Performa | Tambah item ke keranjang ≤ 300 ms (p95) di server lokal/LAN; checkout ≤ 1 dtk; pencarian produk ≤ 300 ms untuk 20.000 barang. |
| NFR-02 | Konkurensi | Minimal 5 kasir bersamaan tanpa selisih stok (transaksi DB + lock baris). |
| NFR-03 | Kompatibilitas | Chrome/Edge 2 versi terakhir (PC & Android), Safari iPadOS terbaru. Layar kasir min. 1024×768; backoffice responsif sampai 360 px. |
| NFR-04 | Keamanan | Semua halaman butuh login; otorisasi via Policy/permission di server (bukan hanya menyembunyikan tombol); CSRF; password bcrypt; rate limit login; tidak ada nilai uang yang dipercaya dari client. |
| NFR-05 | Integritas data | Transaksi yang sudah posted tidak diedit — koreksi via void/retur/penyesuaian (jejak audit). Soft delete untuk master. |
| NFR-06 | Lokalisasi | Bahasa Indonesia, zona waktu `Asia/Jakarta`, format `Rp 12.500`, tanggal `26 Sep 2026`, pemisah ribuan titik. |
| NFR-07 | Ketersediaan | Deploy opsi A: server lokal di toko (mini PC, LAN — seperti iPos multi-kasir); opsi B: VPS cloud. Backup harian. |
| NFR-08 | Aksesibilitas UI | Kontras cukup, ukuran sentuh ≥ 44 px di mode sentuh, semua aksi kasir bisa dengan keyboard. |
| NFR-09 | Kualitas kode | Feature test untuk setiap aturan bisnis (stok, HPP, total, penomoran); Pint; tidak ada query N+1 di daftar/laporan. |

## 8. Alur Utama (User Journey)

1. **Buka toko** — Kasir login → diarahkan ke POS → diminta buka shift (modal awal Rp 200.000).
2. **Transaksi** — scan barang → (opsional pilih pelanggan / diskon) → F9 → pilih metode → masukkan uang → Enter → struk tercetak → keranjang kosong, fokus kembali ke kolom scan.
3. **Barang datang** — Admin/Gudang input pembelian dari faktur supplier → posting → stok & harga pokok terbarui → sistem menyarankan update harga jual bila margin turun.
4. **Tutup toko** — Kasir tutup shift → hitung uang fisik → sistem tampilkan selisih → cetak laporan shift.
5. **Pemilik** — buka dashboard di HP: omzet, laba kotor, barang terlaris, stok menipis, piutang jatuh tempo.
6. **Akhir bulan** — stok opname → posting selisih → laporan laba rugi & nilai persediaan.

## 9. Asumsi & Risiko

| # | Asumsi / Risiko | Mitigasi |
|---|---|---|
| R1 | Internet toko putus bila memakai server cloud → kasir berhenti. | Rekomendasikan server lokal (LAN) untuk toko dengan internet tidak stabil; PWA offline di Fase 3. |
| R2 | Cetak thermal via browser menampilkan dialog print. | Panduan setting Chrome `--kiosk-printing` / printer default; opsi QZ Tray di masa depan. |
| R3 | Kolom detail database iPos lama belum terdokumentasi (hanya nama tabel dari log restore). | Task import legacy dimulai dengan introspeksi skema `db_pos4` (lihat 03-LEGACY-MAPPING §4). |
| R4 | Kebiasaan kasir dengan shortcut iPos lama. | Shortcut dibuat mirip & legend selalu tampil; `3*kode` dipertahankan. |
| R5 | Stok minus karena input pembelian terlambat. | Default izinkan minus + peringatan; laporan "stok minus" untuk koreksi. |
