# SIMBAKO 🏪
**Sistem Manajemen Stok & Kas Warung Sembako (PWA)**

[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![PWA Ready](https://img.shields.io/badge/PWA-Installable-059669?logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)
[![Tests](https://img.shields.io/badge/Tests-18%20passed-brightgreen?logo=phpunit&logoColor=white)](https://phpunit.de)

---

## 📖 Tentang Aplikasi

**SIMBAKO** adalah aplikasi web progresif (*Progressive Web App / PWA*) yang dirancang khusus untuk penggunaan di smartphone (target uji: **Redmi 10**) untuk mencatat pembelian, penjualan, dan stok di warung sembako secara cepat guna menggantikan buku catatan konvensional.

Dirancang berdasarkan spesifikasi teknis ketat pada [PRD.md](file:///d:/Code/SIWARAS/PRD.md) dan [SRS.md](file:///d:/Code/SIWARAS/SRS.md) untuk menyelesaikan 4 masalah inti pemilik warung:
1. **Barang habis tanpa disadari** &rarr; Notifikasi daftar pesan ulang (*reorder point* & batas minimum).
2. **Barang menumpuk / lambat laku** &rarr; Pelacakan laju jual harian (*daily rate*).
3. **Barang mendekati kedaluwarsa** &rarr; Sistem pencatatan kedatangan per-batch dan aturan **FEFO** (*First Expired, First Out*).
4. **Selisih uang kas & stok fisik** &rarr; Rekap kas harian otomatis, rekonsiliasi kas laci, dan sesi stok opname terstruktur.

---

## 🚀 Fitur Unggulan

### 1. Kasir Penjualan Cepat (Mobile-First)
- **Selesai dalam 2-3 ketukan**: Transaksi 1 produk favorit selesai kurang dari 5 detik.
- **Grid 24 Produk Favorit**: Disusun secara alfabetis permanen agar posisi tombol di layar tidak berpindah-pindah.
- **Pencarian Cepat Sisi Klien**: Pencarian instan untuk ratusan produk sembako.
- **Toleransi Stok Minus (JUL-04)**: Penjualan kasir **tidak pernah diblokir** oleh ketidaksesuaian stok sistem saat toko sedang ramai; kekurangan batch dicatat sebagai stok minus dan diberi tanda peringatan di dashboard.
- **Tawar-menawar (Price Override)**: Harga jual dapat disesuaikan per transaksi langsung dari keranjang.
- **Keranjang Tahan Refresh**: Disimpan lokal di `localStorage` perangkat.
- **Idempotensi Transaksi**: Menggunakan Client UUID untuk mencegah order dobel saat koneksi lambat.
- **Mode Rekap Akhir Hari (JUL-07)**: Opsi input total terjual harian jika saat toko ramai tidak sempat mencatat per pembeli.

### 2. Manajemen Batch & Aturan FEFO (StockService)
- **Single Writer Rule**: Seluruh mutasi stok hanya boleh ditulis melalui `StockService` dengan penguncian baris database (`lockForUpdate`).
- **Alokasi FEFO Otomatis**: Penjualan otomatis memotong batch yang memiliki tanggal kedaluwarsa terdekat yang masih layak konsumsi (batch lewat exp otomatis dikesampingkan).
- **Audit Ledger**: Tabel `stock_movements` bersifat *append-only* (tidak ada angka stok yang ditimpa sembarangan; pembatalan dicatat via pergerakan lawan `void`).

### 3. Dashboard Peringatan & Aksi Buang Barang
- 3 kartu ringkasan metrik: **Perlu Dibeli** (Habis/Menipis), **Mendekati Exp** (Expired/Expiring soon), dan **Stok Minus**.
- Perhitungan rumus titik pesan ulang:
  $$\text{threshold} = \text{daily\_rate} \times \text{lead\_time} + \text{safety\_stock}$$
- Aksi **Buang Barang (Waste)**: Mengeluarkan batch kedaluwarsa atau barang rusak dari stok layak jual dengan pencatatan alasan.

### 4. Stok Opname & Saldo Awal
- **Mode Hitung Buta (Blind Count)**: Stok sistem disembunyikan sampai penghitungan fisik diisi agar bebas bias.
- **Deteksi Konflik 409**: Mencegah konfirmasi opname jika terjadi transaksi di tengah-tengah sesi penghitungan.
- **Saldo Awal Sistem**: Modul khusus untuk menetapkan titik nol produk baru yang belum pernah bergerak.

### 5. Rekonsiliasi Kas & Tutup Toko Harian
- Rumus kas harian otomatis:
  $$\text{expected\_cash} = \text{opening\_cash} + \text{penjualan\_tunai} - \text{pengeluaran\_kas} - \text{pembelian\_dari\_kas}$$
- Selisih fisik dihitung *real-time* ("kurang Rp X" / "lebih Rp X") dengan kewajiban mengisi alasan jika ada selisih.
- Modul pencatatan pengeluaran kas operasional (belanja pasar, plastik, ongkos).

### 6. Ekspor Data CSV & PWA
- Ekspor satu-klik untuk Produk, Penjualan, Pembelian, Ledger Stok, Tutup Kas, dan Pengeluaran.
- Dilengkapi **UTF-8 BOM** dan pemisah titik koma (`;`) agar langsung rapi saat dibuka di Microsoft Excel berbahasa Indonesia.
- **PWA Ready**: Dapat dipasang (*Add to Home Screen*) di Android Chrome dengan service worker caching app-shell.

---

## 👥 Peran Pengguna & Hak Akses

| Fitur / Modul | Pemilik (Raswan) | Pegawai (Budi) |
|---|:---:|:---:|
| Login Masuk | Email & Kata Sandi | Pilih Nama & PIN 6 Digit |
| Kasir Penjualan Cepat | ✅ | ✅ |
| Void Penjualan | ✅ Kapan saja | ⚠️ Hari yang sama sebelum kas ditutup |
| Lihat Harga Beli / HPP | ✅ | ❌ Disembunyikan dari UI & API |
| Catat & Void Pembelian | ✅ | ❌ (HTTP 403) |
| Dashboard Peringatan | ✅ Lengkap | ✅ Tanpa harga modal |
| Buang Barang Rusak/Exp | ✅ | ❌ |
| Isi Hitungan Opname | ✅ | ✅ (Draft saja) |
| Konfirmasi Stok Opname | ✅ | ❌ (HTTP 403) |
| Tutup Kas & Pengeluaran | ✅ | ❌ |
| Ekspor Data & Pengaturan | ✅ | ❌ |

---

## ⚙️ Persyaratan Sistem

- **PHP**: Versi `8.2` atau lebih baru (direkomendasikan PHP 8.5)
- **Ekstensi PHP**: `pdo_mysql`, `bcmath`, `mbstring`, `gd`
- **Composer**: Versi `2.x`
- **Node.js & npm**: Node.js `18+` & npm `9+`
- **Database**: MySQL `8.0+` (atau MariaDB 10.4+)

---

## 🛠️ Panduan Instalasi Lokal

### 1. Clone Repositori
```sh
cd /path/to/project
```

### 2. Pasang Dependensi Backend & Frontend
```sh
composer install
npm install
```

### 3. Konfigurasi Lingkungan (.env)
Salin berkas `.env.example` menjadi `.env`:
```sh
cp .env.example .env
php artisan key:generate
```

Pastikan pengaturan database di `.env` sesuai dengan MySQL lokal Anda:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siwaras
DB_USERNAME=root
DB_PASSWORD=
```

Buat database di MySQL jika belum ada:
```sql
CREATE DATABASE IF NOT EXISTS siwaras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS siwaras_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Jalankan Migrasi & Data Contoh (Seeder)
Perintah ini akan membuat semua tabel dan mengisi **30 produk contoh sembako**, kategori, serta saldo awal:
```sh
php artisan migrate:fresh --seed
```

### 5. Kompilasi Aset Frontend (Tailwind CSS v4 & PWA)
```sh
npm run build
```

### 6. Jalankan Server Pengembangan
```sh
php artisan serve --port=8000
```
Buka browser dan akses: **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Akun Default untuk Pengujian

Aplikasi sudah dilengkapi akun bawaan dari seeder:

| Peran | Kredensial Masuk | Keterangan |
|---|---|---|
| **Pemilik** | Email: `raswan@simbako.test`<br>Password: `password123` | Akses penuh seluruh menu |
| **Pegawai** | Pilih Nama: `Budi`<br>PIN: `123456` | Khusus penjualan kasir & draft hitungan |

> **Catatan Keamanan**: Jika salah memasukkan kata sandi/PIN sebanyak 5 kali berturut-turut, akun akan otomatis terkunci selama 5 menit.

---

## 🧪 Menjalankan Pengujian Otomatis

Aplikasi ini dilengkapi rangkaian pengujian unit dan fitur komprehensif yang memvalidasi skenario SRS (TC-01 s/d TC-20):

```sh
php artisan test
```

Rincian modul tes:
- `DomainRulesTest`: Alokasi FEFO murni, formula laju jual harian, threshold pesan ulang, dan rekonsiliasi kas.
- `StockServiceTest`: Invarian ledger, idempotensi, void pembelian/penjualan, dan pembatasan saldo awal.
- `AuthAndRoleTest`: Proteksi hak akses pegawai, masking harga beli (TC-16), dan sistem kunci akun 5 menit (TC-17).
- `WebFlowsTest`: Alur penjualan kasir, peringatan dashboard, dan unduhan ekspor CSV.

---

## 💾 Prosedur Cadangan & Pemulihan (Backup & Restore)

### Cadangan Manual (Database Dump)
Gunakan utilitas `mysqldump`:
```sh
mysqldump -u root -p simbako > backup_simbako_$(date +%Y%m%d).sql
```

### Pemulihan Data (Restore)
Untuk memulihkan data ke database kosong:
```sh
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS simbako;"
mysql -u root -p simbako < backup_simbako_YYYYMMDD.sql
```

---

## 🚢 Panduan Deploy ke Railway / VPS

1. **Tambahkan Layanan MySQL** di Railway atau VPS Anda.
2. Atur variabel lingkungan (*Environment Variables*) di dashboard hosting:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY=base64:...`
   - `APP_URL=https://domain-anda.up.railway.app`
   - `DB_CONNECTION=mysql`
   - `DB_HOST=${{MYSQLHOST}}`
   - `DB_PORT=${{MYSQLPORT}}`
   - `DB_DATABASE=${{MYSQLDATABASE}}`
   - `DB_USERNAME=${{MYSQLUSER}}`
   - `DB_PASSWORD=${{MYSQLPASSWORD}}`
   - `SESSION_DRIVER=file`
   - `SESSION_LIFETIME=43200`
3. **Build & Release Command**:
   ```sh
   composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan migrate --force
   ```

---

## 📁 Struktur Direktori Utama

```text
SIMBAKO/
├── app/
│   ├── Domain/                 # Fungsi murni domain (FEFO, Inventory, Cash Calculator)
│   ├── Http/
│   │   ├── Controllers/        # Controller (Auth, Sale, Purchase, Stocktake, Cash, Export, Setting)
│   │   └── Middleware/         # Middleware role & account lock check
│   ├── Models/                 # Model Eloquent dengan UUID (Product, Batch, StockMovement, dll.)
│   └── Services/
│       └── StockService.php    # Single-writer append-only untuk stock_movements
├── database/
│   ├── migrations/             # Skema tabel database (Users, Categories, Products, Batches, Ledger, dll.)
│   └── seeders/                # Seeder 30 produk sembako, kategori, akun pemilik & pegawai
├── public/
│   ├── icons/                  # PWA Icons (192px, 512px)
│   ├── manifest.json           # Web App Manifest
│   └── sw.js                   # Service Worker shell caching
├── resources/
│   ├── css/                    # Tailwind CSS v4 styling & Google Fonts
│   └── views/                  # Blade Views (Sales, Purchases, Dashboard, Stocktake, Cash, Export)
├── tests/
│   ├── Feature/                # Feature integration tests
│   └── Unit/                   # Domain logic unit tests
├── PRD.md                      # Product Requirements Document
├── SRS.md                      # Software Requirements Specification
└── README.md                   # Dokumentasi Aplikasi
```

---

## 📄 Lisensi

Dikembangkan untuk Warung Sembako SIMBAKO. Hak Cipta dilindungi.
