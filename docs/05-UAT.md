# UAT — User Acceptance Test iPos Web

## 1. Panduan

- **Tujuan**: memastikan aplikasi memenuhi kebutuhan PRD dari sudut pandang pengguna toko (Owner, Admin, Kasir, Gudang).
- **Lingkungan**: staging dengan data uji §2 (`php artisan migrate:fresh --seed --seeder=UatSeeder`), browser Chrome terbaru, scanner barcode USB, printer thermal 58 mm (opsional 80 mm), satu tablet untuk uji mode sentuh.
- **Tag fase**: **[F1]** wajib lulus untuk Go-Live 1 (MVP), **[F2]** wajib lulus untuk mematikan iPos 4.0, **[Tek]** diuji oleh QA/developer (butuh devtools).
- **Status**: isi kolom Status dengan `Lulus` / `Gagal (#bug)` / `N/A`.
- **Severity bug**: **S1** kritis (salah uang/stok, tidak bisa transaksi) · **S2** mayor (fitur tidak jalan, ada jalan pintas) · **S3** minor (tampilan/teks). Go-live hanya jika **0 bug S1 & S2** terbuka pada skenario fase terkait.
- Setiap skenario diasumsikan dimulai dari data uji awal kecuali disebut lain. Angka Rupiah ditulis tanpa desimal.

## 2. Data Uji (dibuat oleh `UatSeeder`)

**Pengguna** (password semua: `password`)
| Email | Peran | Catatan |
|---|---|---|
| owner@pos.com | Owner | PIN supervisor `123456` |
| admin@pos.com | Admin | PIN supervisor `654321` |
| kasir@pos.com | Kasir | |
| kasir2@pos.com | Kasir | |
| gudang@pos.com | Gudang | |
| nonaktif@pos.com | Kasir | `is_active = false` |

**Lokasi**: `TKO` Toko Utama (store). [F2] `GDG` Gudang (warehouse).

**Barang** (saldo awal di TKO)
| Kode | Nama | Kategori | Satuan (konversi · barcode · harga jual) | Min | Saldo awal @ HPP |
|---|---|---|---|---|---|
| BRD-001 | Indomie Goreng Special | Makanan | Pcs (1 · 8998866200301 · 3.000); Dus (40 · 18998866200308 · 115.000) | 40 | 100 Pcs @ 2.500 |
| BRD-002 | Aqua Botol 600ml | Minuman | Botol (1 · 8886008101053 · 4.000); Karton (24 · 18886008101050 · 90.000) | 24 | 48 Botol @ 3.000 |
| BRD-003 | Gula Pasir Curah | Sembako | Kg (1 · – · 17.000) | 10 | 25 Kg @ 14.500 |
| BRD-004 | Sabun Mandi 85g | Kebutuhan Rumah | Pcs (1 · 8999999003538 · 4.500) | 10 | 5 Pcs @ 3.600 |
| JSA-001 | Jasa Bungkus Kado | Jasa | Kali (1 · – · 5.000) — tipe **Jasa** | – | – |

**Pelanggan**: PLG-0001 Bu Sari (batas kredit 500.000) · PLG-0002 Pak Joko (batas kredit 0).
**Supplier**: SUP-0001 CV Sumber Rejeki · SUP-0002 UD Makmur Jaya.
**Pengaturan**: pembulatan 0, izinkan stok minus = Ya, batas diskon kasir 10%, kertas 58 mm, cetak otomatis = Ya, prefix default (PJ, PB, …).
**Nilai persediaan awal TKO** = 100×2.500 + 48×3.000 + 25×14.500 + 5×3.600 = **774.500**.

---

## 3. Skenario Uji

### 3.1 Autentikasi & Hak Akses (AUTH)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-AUTH-01 | F1 | Kasir login | Login `kasir@pos.com` | Langsung masuk ke `/pos`; menu backoffice tidak tampil selain yang diizinkan | |
| UAT-AUTH-02 | F1 | Owner login | Login `owner@pos.com` | Masuk ke `/dashboard`; semua menu tampil | |
| UAT-AUTH-03 | F1 | Registrasi publik mati | Buka `/register` | Halaman 404; di halaman login tidak ada link "Daftar" | |
| UAT-AUTH-04 | F1 | User nonaktif | Login `nonaktif@pos.com` | Ditolak dengan pesan "Akun Anda nonaktif, hubungi pemilik toko" | |
| UAT-AUTH-05 | F1 | Batas akses peran | Sebagai Kasir buka URL `/settings`, `/purchases`, `/reports` langsung | Semua 403 (halaman "Tidak punya akses"), menu tidak tampil | |
| UAT-AUTH-06 | F1 | Rate limit | Salah password 6× berturut-turut | Percobaan ke-6 ditolak "Terlalu banyak percobaan, coba lagi dalam X detik" | |
| UAT-AUTH-07 | F1 | Owner membuat user | Owner → Pengaturan → User → tambah "Rudi" peran Gudang → login sebagai Rudi | Rudi hanya melihat Dashboard, Barang (lihat), Stok, Pembelian, Supplier; tidak bisa buka `/pos` | |
| UAT-AUTH-08 | F2 | PIN supervisor salah | Kasir memicu aksi butuh PIN (void) → masukkan `000000` | Ditolak "PIN salah"; aksi tidak terjadi | |

### 3.2 Pengaturan (SET)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-SET-01 | F1 | Profil toko | Owner ubah nama toko "Toko Berkah", alamat, telepon, upload logo → simpan → buat transaksi | Topbar & struk menampilkan data baru | |
| UAT-SET-02 | F1 | Kertas 80 mm | Ubah kertas ke 80 mm → cetak ulang struk | Struk lebar 80 mm, baris tidak terpotong, footer sesuai setting | |
| UAT-SET-03 | F1 | Pembulatan 100 | Set pembulatan 100 → kasir jual Gula 0,726 Kg | Subtotal 12.342; Pembulatan −42; **Total 12.300** | |
| UAT-SET-04 | F1 | Prefix nomor | Ubah prefix penjualan jadi `INV` → transaksi | Nomor `INV-TKO-{YYMMDD}-0001` (urutan lanjut per hari) | |
| UAT-SET-05 | F1 | Stok minus dilarang | Set "Izinkan stok minus" = Tidak → jual Sabun 6 Pcs (stok 5) → bayar | Transaksi ditolak: "Stok Sabun Mandi 85g tidak cukup. Tersedia 5 Pcs"; stok tetap 5 | |
| UAT-SET-06 | F2 | PPN | (a) Eksklusif 11%: jual Karton Aqua 90.000 + Gula 10 Kg (170.000) → (b) ubah ke inklusif, ulangi | (a) Subtotal 260.000, PPN 28.600, Total 288.600. (b) Total 260.000, struk menampilkan DPP 234.234 & PPN 25.766 | |

### 3.3 Master Data (MST)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-MST-01 | F1 | Tambah kategori | Tambah kategori "Rokok" | Tersimpan, muncul di pilihan form barang | |
| UAT-MST-02 | F1 | Hapus kategori terpakai | Hapus kategori "Makanan" | Ditolak: "Kategori masih dipakai 1 barang"; barang tidak terhapus | |
| UAT-MST-03 | F1 | Barang multi-satuan | Tambah "Kopi Kapal Api Sachet": Pcs (1 · barcode 8991002101234 · 1.500), Renteng (10 · 18991002101231 · 14.000) | Tersimpan; di kasir scan kedua barcode menghasilkan satuan & harga yang benar | |
| UAT-MST-04 | F1 | Barcode duplikat | Tambah barang baru dengan barcode `8998866200301` | Ditolak: "Barcode sudah dipakai oleh Indomie Goreng Special (Pcs)" | |
| UAT-MST-05 | F1 | Barang jasa | Jual JSA-001 1 Kali | Transaksi sukses; tidak ada mutasi di kartu stok; laporan penjualan mencatat 5.000 | |
| UAT-MST-06 | F1 | Nonaktifkan barang | Nonaktifkan BRD-004 → cari "sabun" / scan barcodenya di kasir | Tidak ditemukan / pesan "Barang nonaktif"; riwayat & laporan lama tetap ada | |
| UAT-MST-07 | F1 | Hapus barang bertransaksi | Hapus permanen BRD-001 | Ditolak; ditawarkan "Nonaktifkan" | |
| UAT-MST-08 | F1 | Pelanggan | Tambah pelanggan "Bu Rina", telp 0812xxxx; cari di kasir dengan nomor telp | Kode otomatis `PLG-0003`; ditemukan di kasir | |
| UAT-MST-09 | F1 | Supplier | Tambah supplier "Toko Grosir Abadi" | Kode `SUP-0003`; muncul di form pembelian | |
| UAT-MST-10 | F2 | Harga grosir | Set tier Indomie Pcs: ≥10 = 2.800 → di kasir qty 9 lalu ubah jadi 10 | Qty 9: @3.000 (27.000); qty 10: @2.800 (28.000) dengan label "Grosir" | |
| UAT-MST-11 | F2 | Import Excel | Unduh template barang → isi 3 barang (1 baris barcode duplikat) → import | Pratinjau menandai 1 baris gagal dengan alasan; 2 barang tersimpan lengkap dengan satuan & harga; laporan gagal bisa diunduh | |
| UAT-MST-12 | F2 | Label barcode | Pilih Indomie Pcs × 6 label, template 106×15 → cetak | Pratinjau 2 baris × 3 label, barcode terbaca scanner, nama & harga tampil | |

### 3.4 Kasir / POS (POS)
Prasyarat umum: login Kasir, shift sudah dibuka dengan modal 200.000 (kecuali UAT-POS-01).

| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-POS-01 | F1 | Wajib buka shift | Kasir login pertama kali hari itu | Modal "Buka Shift" muncul; tidak bisa menambah barang sebelum isi modal awal 200.000 | |
| UAT-POS-02 | F1 | Scan barcode Pcs | Scan `8998866200301` | Baris Indomie · Pcs · qty 1 · 3.000; fokus kembali ke kolom scan | |
| UAT-POS-03 | F1 | Scan barcode Dus | Scan `18998866200308` | Baris Indomie · Dus · qty 1 · 115.000 | |
| UAT-POS-04 | F1 | Scan berulang | Scan barcode Pcs 3× | Satu baris qty 3 · 9.000 (bukan 3 baris) | |
| UAT-POS-05 | F1 | Cari nama | Ketik "aqua" → ↓ → Enter | Daftar hasil ≤ 300 ms; Aqua Botol masuk keranjang 4.000 | |
| UAT-POS-06 | F1 | Format qty*kode | Ketik `3*BRD-001` → Enter | Indomie Pcs qty 3 · 9.000 | |
| UAT-POS-07 | F1 | Ganti satuan | Pada baris Indomie Pcs qty 2 ubah satuan ke Dus | Harga jadi 115.000, subtotal 230.000 | |
| UAT-POS-08 | F1 | Diskon item | Indomie Pcs qty 3 → diskon item 10% | Diskon 900; subtotal baris 8.100 | |
| UAT-POS-09 | F1 | Diskon total | Keranjang: Karton Aqua 90.000 + Gula 1 Kg 17.000 → F6 diskon Rp 7.000 | Subtotal 107.000; Diskon 7.000; Total 100.000 | |
| UAT-POS-10 | F2 | Batas diskon kasir | Keranjang 100.000 → Kasir beri diskon 15% | Muncul permintaan PIN supervisor; PIN `654321` → diskon diterapkan dan tercatat diotorisasi Admin | |
| UAT-POS-11 | F1 | Bayar tunai | Indomie 3 Pcs (9.000) → F9 → tombol "10 Ribu" → Enter | Kembalian **1.000** tampil besar; struk tercetak; nomor `PJ-TKO-{YYMMDD}-0001`; stok Indomie 97; keranjang kosong | |
| UAT-POS-12 | F1 | Kurang bayar (Umum) | Total 9.000, pelanggan Umum, bayar tunai 5.000 | Tombol proses nonaktif / pesan "Pembayaran kurang 4.000"; tidak ada transaksi tersimpan | |
| UAT-POS-13 | F1 | Split payment | Indomie 1 Dus (115.000) → Tunai 50.000 + QRIS 65.000 | Lunas, kembalian 0; detail nota menampilkan 2 pembayaran; struk menampilkan kedua metode | |
| UAT-POS-14 | F1 | Non-tunai lebih bayar | Total 9.000 → QRIS 20.000 | Ditolak: "Pembayaran non-tunai tidak boleh melebihi sisa tagihan" | |
| UAT-POS-15 | F1 | Tahan & panggil | Isi 2 barang → F7 label "Bu Sari" → layani transaksi lain sampai selesai → F8 → pilih "Bu Sari" | Keranjang Bu Sari kembali utuh (harga dihitung ulang); daftar tahan berkurang | |
| UAT-POS-16 | F1 | Tahan aman refresh | Tahan 1 keranjang → refresh browser / login di PC lain dengan user sama | Keranjang tertahan tetap ada di F8 | |
| UAT-POS-17 | F1 | Isi struk | Periksa struk UAT-POS-13 | Memuat: nama/alamat/telp toko, no. nota, tanggal-jam WIB, kasir, item (nama, qty × harga, subtotal), subtotal, diskon, total, pembayaran per metode, kembalian, footer | |
| UAT-POS-18 | F1 | Cetak ulang | Tekan F10 | Struk transaksi terakhir tercetak ulang (bertanda "SALINAN") | |
| UAT-POS-19 | F1 | Shortcut | Uji F2, F4, F6, F7, F8, F9, F10, Esc | Setiap tombol menjalankan aksi sesuai legend; Esc menutup modal aktif | |
| UAT-POS-20 | Tek | Manipulasi harga | Dengan devtools ubah harga item di state Livewire menjadi 1 → bayar | Nota tersimpan dengan harga dari database (3.000), bukan 1 | |
| UAT-POS-21 | F1 | Peringatan stok | Tambah Sabun (stok 5 < min 10) | Badge kuning "Stok menipis: 5"; bila stok ≤ 0 badge merah "Stok habis" (transaksi tetap boleh jika setting stok minus = Ya) | |
| UAT-POS-22 | F1 | Void oleh Admin | Admin → Riwayat Penjualan → nota UAT-POS-11 → Void, alasan "Salah input" | Status VOID (dicoret); stok Indomie kembali 100; omzet dashboard & laporan tidak menghitung nota ini; tercatat siapa & alasan | |
| UAT-POS-23 | F1/F2 | Void oleh Kasir | Kasir buka riwayat shift → coba void | [F1] tombol void tidak tersedia. [F2] diminta PIN supervisor; PIN benar → void berhasil dan tercatat otorisasinya | |
| UAT-POS-24 | F1 | Kas keluar laci | Menu → Kas Keluar Laci 20.000 "Beli galon" | Tercatat di Kas dengan badge "Laci Shift"; masuk hitungan shift | |
| UAT-POS-25 | F1 | Tutup shift | Shift: modal 200.000; penjualan tunai 150.000 (neto), QRIS 50.000; kas keluar 20.000 → Tutup Shift, input uang fisik 325.000 | Uang seharusnya **330.000**, selisih **−5.000**; rekap per metode (Tunai 150.000, QRIS 50.000); laporan shift tercetak; kasir diminta buka shift baru untuk transaksi berikutnya | |
| UAT-POS-26 | F2 | Jual kredit | Pelanggan Bu Sari, Indomie 1 Dus 115.000, bayar tunai 15.000 | Tersimpan status "Sebagian", jatuh tempo +30 hari; piutang Bu Sari 100.000 | |
| UAT-POS-27 | F2 | Kredit ditolak | (a) Pak Joko (limit 0) bayar kurang. (b) Bu Sari transaksi baru 450.000 tanpa bayar (piutang berjalan 100.000) | (a) Ditolak "Pelanggan tidak memiliki fasilitas kredit". (b) Ditolak "Melebihi batas kredit (sisa 400.000)" | |
| UAT-POS-28 | F2 | Retur penjualan | Nota Indomie 3 Pcs (9.000) → Retur 2 Pcs, restock, refund tunai | Nomor `RJ-…`; refund 6.000; stok +2; uang seharusnya shift berkurang 6.000; laporan penjualan bersih berkurang 6.000 | |
| UAT-POS-29 | F2 | Tampilan pelanggan | Buka Tampilan Pelanggan di jendela kedua → scan barang → bayar | Layar pelanggan menampilkan item & total secara langsung, lalu kembalian; saat idle menampilkan teks berjalan | |
| UAT-POS-30 | Tek | Dua kasir bersamaan | Stok minus = Tidak; Sabun stok 1; kasir & kasir2 menjual Sabun 1 Pcs dan menekan bayar bersamaan | Hanya satu transaksi berhasil; yang lain mendapat pesan stok tidak cukup; stok akhir 0 (tidak pernah −1) | |
| UAT-POS-31 | F1 | Nomor berurutan | kasir membuat 2 transaksi, lalu kasir2 1 transaksi (lokasi sama) | Nomor …-0001, …-0002, …-0003 tanpa lompat/duplikat | |

### 3.5 Pembelian (PUR)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-PUR-01 | F1 | Draft tidak mengubah stok | Admin buat pembelian dari CV Sumber Rejeki, simpan draft | Status Draft; stok & HPP tidak berubah | |
| UAT-PUR-02 | F1 | Posting & HPP rata-rata | Pembelian 2 Dus Indomie @104.000 → Posting | Nomor `PB-…`; stok Indomie **180 Pcs** ("4 Dus 20 Pcs"); HPP rata-rata **2.544,44** (tampil Rp 2.544) | |
| UAT-PUR-03 | F1 | Ongkos & landed cost | Pembelian 1 Dus Indomie @104.000 + 1 Karton Aqua @72.000; ongkos kirim 8.800 → Posting | Subtotal 176.000, Total 184.800; biaya pokok masuk: Indomie **2.730/Pcs**, Aqua **3.150/Botol** (lihat detail pembelian / kartu stok) | |
| UAT-PUR-04 | F1 | Void pembelian | Owner void pembelian UAT-PUR-02 dengan alasan | Stok kembali 100; status VOID; peringatan bahwa HPP tidak dikoreksi mundur | |
| UAT-PUR-05 | F2 | Pembelian kredit | Pembelian total 500.000, bayar 200.000, jatuh tempo +14 hari → Hutang → bayar 300.000 | Hutang CV Sumber Rejeki 300.000 lalu lunas 0; riwayat pembayaran tercatat | |
| UAT-PUR-06 | F2 | Retur pembelian | Dari pembelian UAT-PUR-02 retur 1 Dus → potong hutang / uang kembali | Nomor `RB-…`; stok −40 Pcs; hutang atau kas bertambah 104.000 | |
| UAT-PUR-07 | F1 | Saran harga jual | Setelah posting pembelian dengan harga beli naik (Indomie 1 Dus @124.000) | Muncul saran: margin Indomie Pcs turun; masukkan harga baru 3.200 → Terapkan → harga di kasir 3.200 | |

### 3.6 Persediaan (INV)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-INV-01 | F1 | Saldo awal | (Pada DB kosong) input saldo awal sesuai tabel §2 | Posisi stok & HPP sesuai; kartu stok berisi baris "Saldo Awal" | |
| UAT-INV-02 | F1 | Kartu stok | Setelah UAT-POS-11, UAT-POS-22, UAT-PUR-02: buka kartu stok Indomie | Baris: Saldo Awal +100 (100) → Penjualan −3 (97) → Void +3 (100) → Pembelian +80 (180); saldo akhir = posisi stok; klik nomor dokumen membuka transaksinya | |
| UAT-INV-03 | F1 | Tampilan multi-satuan | Posisi stok Indomie saat 85 Pcs | Tampil "2 Dus 5 Pcs" (dan 85 Pcs) | |
| UAT-INV-04 | F1 | Stok opname | Gudang buat opname TKO kategori Minuman → Aqua fisik 45 → Posting | Selisih −3; stok Aqua 45; kartu stok baris "Opname −3"; nilai selisih −9.000 | |
| UAT-INV-05 | F1 | Opname draft & kosong | Buat opname semua barang, isi hanya Gula = 24 → simpan draft → cek stok → posting | Saat draft stok tidak berubah; setelah posting hanya Gula disesuaikan (−1), barang lain tetap | |
| UAT-INV-06 | F1 | Barang rusak | Penyesuaian: Sabun 2 Pcs alasan "Rusak" | Stok Sabun 3; kartu stok "Penyesuaian −2 (Rusak)" | |
| UAT-INV-07 | F1 | Stok minimum | Buka Stok Minimum | Sabun (5 < 10) tampil dengan saran beli 15; tombol "Buat draft pembelian" menghasilkan draft berisi Sabun 15 Pcs | |
| UAT-INV-08 | F2 | Transfer lokasi | Transfer 1 Dus Indomie TKO → GDG → Posting | TKO −40 Pcs, GDG +40 Pcs; nomor `TR-…`; surat jalan bisa dicetak | |
| UAT-INV-09 | F1 | Nilai persediaan | Buka Posisi Stok (data awal) sebagai Owner | Total nilai persediaan **774.500**; Kasir/Gudang tidak melihat kolom nilai/HPP | |

### 3.7 Keuangan (FIN)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-FIN-01 | F1 | Kas keluar | Admin catat Kas Keluar "Listrik & Air" 350.000, metode Bank, lampirkan foto | Nomor `KK-…`; foto dapat dibuka; tidak mempengaruhi kas shift kasir | |
| UAT-FIN-02 | F1 | Kategori kas | Tambah kategori keluar "Iuran Kebersihan" → gunakan | Kategori muncul di form; kategori terpakai tidak bisa dihapus (hanya nonaktif) | |
| UAT-FIN-03 | F1 | Laba rugi sederhana | Pada hari uji: jual 10 Pcs Indomie (30.000) + 1 Karton Aqua (90.000); 1 nota lain di-void; kas keluar Listrik 10.000 → Laporan Laba Rugi hari ini | Penjualan 120.000; HPP 97.000 (25.000 + 72.000); Laba kotor 23.000; Biaya 10.000; **Laba bersih 13.000**; nota void tidak dihitung | |
| UAT-FIN-04 | F2 | Bayar piutang | Bu Sari punya nota A (lebih lama) sisa 100.000 & nota B sisa 50.000 → terima bayar 120.000 | Nota A lunas; nota B sisa 30.000; bukti bayar tercetak; jika dibayar tunai di POS masuk kas shift | |
| UAT-FIN-05 | F2 | Umur piutang | Nota kredit bertanggal 45 hari lalu | Laporan umur piutang menaruhnya di kolom 31–60 hari; dashboard menampilkan peringatan jatuh tempo | |
| UAT-FIN-06 | F2 | Bayar hutang | Bayar 2 faktur hutang CV Sumber Rejeki sekaligus | Kedua faktur terlunasi sesuai alokasi; laporan hutang terbarui | |

### 3.8 Dashboard & Laporan (RPT)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-RPT-01 | F1 | Angka dashboard | Setelah skenario UAT-FIN-03, buka dashboard | Omzet hari ini 120.000; jumlah transaksi 2 (tanpa void); laba kotor 23.000; top barang memuat Indomie & Aqua; kartu stok minimum memuat Sabun | |
| UAT-RPT-02 | F1 | Dashboard di HP | Owner buka dashboard di HP (lebar 360 px) | Semua kartu & grafik terbaca tanpa geser horizontal; menu via tombol hamburger | |
| UAT-RPT-03 | F1 | Laporan penjualan | Filter "Hari ini", per kasir & per metode | Total sesuai jumlah nota non-void; rincian per kasir & metode menjumlah ke total; klik nota membuka detail | |
| UAT-RPT-04 | F1 | Penjualan per barang | Laporan per barang periode hari uji UAT-FIN-03 | Indomie: qty 10, omzet 30.000, HPP 25.000, laba 5.000, margin 16,7%; Aqua Karton: omzet 90.000, HPP 72.000, laba 18.000 | |
| UAT-RPT-05 | F1 | Mutasi stok | Laporan mutasi stok periode hari ini | Untuk setiap barang: awal + masuk − keluar = akhir = posisi stok | |
| UAT-RPT-06 | F1 | Laporan shift | Buka laporan shift UAT-POS-25 | Angka sama dengan saat tutup shift (seharusnya 330.000, fisik 325.000, selisih −5.000) | |
| UAT-RPT-07 | F2 | Ekspor | Ekspor laporan penjualan per barang ke Excel & PDF | File terbuka; angka & judul/periode sama dengan layar | |
| UAT-RPT-08 | F1 | Akses laporan | Kasir buka `/reports`; Gudang buka laporan penjualan | Kasir 403; Gudang hanya melihat laporan stok | |
| UAT-RPT-09 | F2 | Rekap bulanan | Rekap bulan berjalan | Baris per hari dengan omzet, HPP, laba kotor, biaya, laba bersih; total bulan = jumlah baris | |

### 3.9 Sistem & Migrasi (SYS)
| ID | Fase | Skenario | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|---|---|
| UAT-SYS-01 | F2 | Audit log | Admin ubah harga Indomie Pcs 3.000 → 3.200; Owner buka Log Aktivitas | Tercatat: user Admin, waktu, "Harga jual Pcs: 3.000 → 3.200" | |
| UAT-SYS-02 | F2 | Backup | Owner klik "Backup sekarang" → unduh → restore di staging | File zip berisi dump DB; restore berhasil & data sama | |
| UAT-SYS-03 | F2 | Migrasi dari iPos | Jalankan import dari data iPos toko (Excel atau DB) | Sesuai `03-LEGACY-MAPPING.md §4.4`: jumlah barang/pelanggan/supplier sama; nilai persediaan selisih < 0,5%; hutang/piutang per kontak sama; 20 sampel harga & barcode cocok | |

### 3.10 Non-Fungsional (spot check)
| ID | Fase | Skenario | Hasil yang diharapkan | Status |
|---|---|---|---|---|
| UAT-NFR-01 | Tek | Seed 20.000 barang → cari nama & scan di kasir | Hasil ≤ 300 ms (p95, server lokal) | |
| UAT-NFR-02 | F1 | Transaksi 5 item tunai, dihitung stopwatch dari scan pertama sampai struk keluar | ≤ 20 detik oleh kasir terlatih | |
| UAT-NFR-03 | F1 | Mode sentuh di tablet | Semua tombol kasir mudah ditekan (≥ 44 px), transaksi dapat diselesaikan tanpa keyboard fisik | |
| UAT-NFR-04 | F1 | Format lokal | Semua tanggal WIB & format Indonesia; semua uang "Rp 12.500" | |

---

## 4. Lembar Sign-Off

| Fase | Jumlah skenario | Lulus | Gagal | Bug S1/S2 terbuka | Disetujui oleh (Owner) | Tanggal |
|---|---|---|---|---|---|---|
| Go-Live 1 (F1 + Tek) | | | | | | |
| Go-Live 2 (F2) | | | | | | |

Catatan penguji:

