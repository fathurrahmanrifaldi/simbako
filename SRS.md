# SRS: Sistem Manajemen Stok Warung Sembako (PWA)

Versi 0.1 (Draft) · 16 Oktober 2026 · Turunan dari PRD v0.1 · Dibangun dengan Antigravity (vibe coding) · Status: draft untuk ditinjau sebelum implementasi

## 1. Pendahuluan

### 1.1 Tujuan dokumen

PRD menjelaskan apa yang dibutuhkan dan mengapa. SRS ini menjelaskan bagaimana sistem harus berperilaku secara tepat dan bisa diuji: aturan, data, antarmuka, dan kriteria penerimaan. Dokumen ini ditulis agar bisa diberikan langsung ke AI coding agent (Antigravity) sebagai spesifikasi, dan dipakai pemilik produk untuk memeriksa hasilnya. Jika SRS dan PRD berbeda, bagian 1.5 menjelaskan alasannya.

### 1.2 Ruang lingkup

**Fase 1 (MVP)**: semua kebutuhan P0 di PRD, ditambah F-12, F-24, F-62, dan lima penyesuaian di bagian 1.5. **Fase 1.5**: kasbon, satuan ganda, laporan barang menumpuk (F-32), aksi diskon dan daftar belanja (F-33), antrean offline. **Di luar scope**: semua non-goals di PRD bagian 4.

### 1.3 Konvensi

- **WAJIB**: harus ada di MVP. **SEBAIKNYA**: dikerjakan jika tidak mengganggu yang WAJIB. **BOLEH**: opsional.
- ID kebutuhan: `AUTH`, `PRO` (produk), `BEL` (pembelian), `JUL` (penjualan), `DSH` (dashboard), `OPN` (opname), `KAS` (kas), `EXP` (ekspor dan cadangan). Skenario uji memakai ID `TC-xx` (bagian 10).
- Nama teknis (tabel, kolom, endpoint, variabel, kode) memakai bahasa Inggris. Teks yang tampil ke pengguna memakai bahasa Indonesia.

### 1.4 Definisi

| Istilah | Arti |
| --- | --- |
| Satuan dasar | Satu-satunya satuan stok sebuah produk (kg, liter, bungkus, pcs). Semua kuantitas disimpan dalam satuan ini. |
| Batch | Satu kedatangan barang (satu baris pembelian) dengan tanggal exp (jika ada) dan jumlah awal. |
| Pergerakan stok | Satu catatan perubahan stok bertanda plus atau minus. Hanya boleh ditambah, tidak boleh diubah atau dihapus. |
| Stok total | Jumlah semua pergerakan sebuah produk, termasuk batch yang sudah lewat exp dan belum dibuang. |
| Stok layak jual | Sisa batch yang belum lewat exp, ditambah pergerakan tanpa batch (nilainya negatif, lihat JUL-04). Dipakai untuk peringatan dan penjualan. |
| FEFO | First Expired, First Out: batch dengan exp paling awal dipakai lebih dulu. Batch tanpa exp diurutkan menurut tanggal masuk. |
| Void | Pembatalan transaksi dengan membuat pergerakan lawan. Data asli tetap ada. |
| Hari toko | Tanggal kalender di zona waktu Asia/Jakarta (WIB), 00:00 sampai 23:59. |

### 1.5 Penyesuaian terhadap PRD

| Topik | PRD | SRS | Alasan |
| --- | --- | --- | --- |
| Konektivitas | Bagian 5 sudah disunting menjadi internet stabil, tetapi bagian 12, 13, dan 15 masih mewajibkan antrean offline | Antrean offline diturunkan ke Fase 1.5. MVP hanya: cache app shell, keranjang tersimpan di perangkat, dan kirim ulang yang aman (idempoten) | Internet stabil; sinkronisasi offline menambah kompleksitas besar untuk manfaat kecil |
| Perangkat | HP Android kelas menengah ke bawah | Target uji: Redmi 10 dengan Chrome Android | Sesuai suntingan di PRD bagian 5 |
| Batch | Batch hanya untuk produk ber-exp; produk tanpa exp punya satu batch implisit | Setiap pembelian membuat batch; expiry\_date kosong untuk produk tanpa exp | Menghindari percabangan logika FEFO; tidak menambah beban input |
| Buang barang | F-33 (P1) | Aksi minimal Buang masuk MVP; diskon dan daftar belanja tetap di 1.5 | Batch lewat exp harus bisa keluar dari stok; metrik PRD bagian 3 bergantung padanya |
| Pengeluaran tunai | F-53 (Fase 1.5) | Masuk MVP | Rumus kas F-51 tidak akurat tanpa pengeluaran |
| Batalkan pembelian | F-13 (P1) | Masuk MVP dengan batas (hanya jika barang belum terjual) | Salah input pembelian pasti terjadi |
| Hak akses | F-61 (P1) | Pemeriksaan peran di server wajib sejak awal | Menambah belakangan mahal dan rawan celah |

Tindakan: sesuaikan PRD bagian 12, 13, dan 15 agar tidak bertentangan dengan keputusan konektivitas di atas. Lima baris terakhir (Buang, Pengeluaran, Batalkan pembelian, Hak akses, dan harga jual yang bisa diubah per transaksi F-25) adalah tambahan; jika ingin MVP lebih kecil, merekalah yang pertama dipotong.

## 2. Deskripsi Umum

### 2.1 Konteks sistem

Pemilik dan pegawai memakai aplikasi lewat Chrome di HP Android (dipasang sebagai PWA). Aplikasi berkomunikasi lewat HTTPS dengan satu server aplikasi, yang menyimpan semua data di satu database PostgreSQL. Tidak ada integrasi dengan sistem luar di MVP.

### 2.2 Pengguna dan hak akses

| Kemampuan | Pemilik | Pegawai |
| --- | --- | --- |
| Catat penjualan dan rekap akhir hari | Ya | Ya |
| Void penjualan | Semua, kapan saja | Milik sendiri, hari toko yang sama, kas belum ditutup |
| Lihat harga beli dan biaya | Ya | Tidak |
| Catat dan void pembelian | Ya | Tidak |
| Kelola produk, kategori, harga jual | Ya | Hanya lihat |
| Lihat dashboard peringatan | Ya | Ya (tanpa harga beli) |
| Buang barang | Ya | Tidak |
| Isi hitungan opname (draft) | Ya | Ya |
| Konfirmasi opname dan saldo awal | Ya | Tidak |
| Rekap harian, kas, pengeluaran | Ya | Tidak |
| Ekspor, kelola pengguna, pengaturan | Ya | Tidak |

### 2.3 Batasan

- Satu toko, 1-2 pengguna aktif bersamaan, ratusan produk (desain tetap berlaku sampai sekitar 1.000 produk).
- Mata uang hanya rupiah, tanpa desimal.
- Antarmuka hanya bahasa Indonesia.
- Perangkat utama Android dengan Chrome; browser desktop dipakai untuk melihat laporan.
- Internet dianggap stabil (lihat 1.5).

### 2.4 Asumsi default

Asumsi di bawah ini belum diverifikasi dengan pemilik toko. SRS memakai nilai default agar pembuatan bisa jalan; jika jawabannya berbeda, bagian yang terdampak perlu diubah.

| # | Asumsi default | Jika salah, yang terdampak |
| --- | --- | --- |
| A1 | Ratusan produk | Daftar produk dimuat penuh ke klien dan dicari di sisi klien; perlu pencarian di server jika melebihi sekitar 1.000 |
| A3 | Tidak ada kasbon di MVP | payment\_method hanya cash; kasbon butuh tabel pelanggan dan piutang (bagian 4, JUL, KAS) |
| A4 | Tidak ada satuan ganda di MVP | Satu satuan dasar per produk; barang eceran dari karton didaftarkan sebagai produk terpisah dan dibeli dalam satuan eceran. Satuan ganda butuh tabel konversi (bagian 4, BEL, JUL) |
| A6 | Pencatatan per transaksi adalah mode utama | Rekap akhir hari hanya cadangan (JUL-07) |
| A7 | Produk tanpa exp tidak butuh tanggal exp | Flag has\_expiry bernilai false |
| A8 | Hari toko mengikuti tanggal kalender WIB | Penutupan kas dan rekap harian (KAS) |

## 3. Arsitektur dan Keputusan Teknis

### 3.1 Stack (usulan)

| Lapisan | Usulan | Catatan |
| --- | --- | --- |
| Aplikasi | Next.js (App Router) dengan TypeScript | Satu proyek untuk UI dan API. Boleh diganti dengan stack yang lebih dikuasai; aturan di bagian 4 sampai 8 tetap berlaku |
| Gaya | Tailwind CSS | Mobile-first |
| Validasi | Zod | Skema sama untuk klien dan server |
| Database | PostgreSQL (layanan terkelola) | Transaksi, constraint, dan penguncian baris dibutuhkan oleh FEFO |
| ORM dan migrasi | Prisma | Migrasi berversi, bisa dijalankan dari database kosong |
| PWA | Web app manifest dan service worker | Hanya cache app shell. Pilih library PWA yang masih dipelihara atau tulis manual; verifikasi sebelum memilih |
| Autentikasi | Sesi berbasis cookie; hash kata sandi dan PIN dengan argon2id atau bcrypt |  |
| Pengujian | Vitest untuk aturan domain, Playwright untuk alur utama di viewport HP |  |
| Hosting | Belum dipilih | Syarat: HTTPS, cadangan otomatis harian, PostgreSQL terkelola |

### 3.2 Keputusan teknis wajib

1. **Uang**: bilangan bulat rupiah (bigint). Tidak ada float.
2. **Kuantitas**: `numeric(12,3)` di database. Di kode jangan memakai tipe angka biasa untuk menjumlah stok; pakai Decimal atau bilangan bulat per seperseribu.
3. **Waktu**: simpan dalam UTC (timestamptz); tampilkan dan hitung hari toko dengan zona Asia/Jakarta.
4. **ID**: UUID. ID penjualan dan pembelian dibuat oleh klien (dasar idempotensi).
5. **Ledger**: `stock_movements` hanya ditambah. Stok selalu hasil penjumlahan; tidak ada kolom stok yang diedit.
6. **Transaksi DB**: setiap operasi yang menyentuh stok berjalan dalam satu transaksi dengan penguncian baris batch dan produk terkait.
7. **Tanpa hapus**: data transaksi tidak pernah di-DELETE; pakai status dan is\_active.
8. **Pembatalan**: selalu lewat pergerakan lawan dan catatan di `audit_log`.
9. **Konstanta bernama**: angka kebijakan (28 hari, 14 hari, 60 hari, 5 percobaan login, 30 hari sesi) disimpan sebagai konstanta atau pengaturan, tidak tersebar di kode.

### 3.3 Struktur kode (usulan)

- `src/domain/`: fungsi murni (alokasi FEFO, laju jual, threshold, kas) beserta tes unit.
- `src/server/stock-service.ts`: satu-satunya kode yang boleh menulis ke `stock_movements`.
- `src/server/modules/<modul>/`: handler, skema Zod, dan query per modul.
- `src/app/`: halaman dan komponen UI.
- `prisma/`: skema, migrasi, dan seed.

## 4. Model Data

Semua tabel punya `id` (uuid) dan `created_at` (timestamptz). Uang bertipe bigint, kuantitas bertipe numeric(12,3).

| Tabel | Kolom utama | Aturan dan constraint |
| --- | --- | --- |
| users | name, role (owner atau staff), email (kosong untuk staff), password\_hash, pin\_hash, is\_active, failed\_attempts, locked\_until | Owner wajib email dan password\_hash; staff wajib pin\_hash; email unik jika ada |
| categories | name, lead\_time\_days (default 1), safety\_stock (default 0), expiry\_warning\_days (default 14), overstock\_days (default 60) | name unik. Nilai default per kategori; produk boleh menimpa lead\_time\_days dan safety\_stock |
| products | name, category\_id, base\_unit, sell\_price, last\_buy\_price, min\_stock (boleh kosong), lead\_time\_days (boleh kosong), safety\_stock (boleh kosong), has\_expiry, is\_favorite, is\_active | name unik di antara produk aktif tanpa membedakan huruf besar kecil; harga dan min\_stock >= 0 |
| batches | product\_id, received\_on (date), expiry\_date (boleh kosong), initial\_qty, unit\_cost, source (purchase, opening, atau adjustment), purchase\_item\_id (boleh kosong) | initial\_qty > 0; expiry\_date wajib jika produk has\_expiry saat batch dibuat |
| stock\_movements | product\_id, batch\_id (boleh kosong), type (purchase, opening, sale, void\_sale, void\_purchase, adjustment, waste), qty (bertanda, bukan 0), ref\_type, ref\_id, reason, created\_by | Append-only: UPDATE dan DELETE diblokir trigger database. batch\_id kosong hanya untuk pergerakan tanpa batch (stok minus). Index pada product\_id dan batch\_id |
| purchases | purchased\_on, supplier (boleh kosong), total, paid\_from\_cash (default false), status (active atau void), void\_reason, voided\_by, voided\_at, created\_by | id dibuat klien; total dihitung server dari item |
| purchase\_items | purchase\_id, product\_id, batch\_id, qty, unit\_price, expiry\_date (boleh kosong) | qty > 0; unit\_price >= 0 |
| sales | sold\_at, payment\_method (cash; credit dicadangkan), total, status (active atau void), is\_summary, summary\_date (boleh kosong), void\_reason, voided\_by, voided\_at, created\_by | id dibuat klien. is\_summary true untuk rekap akhir hari |
| sale\_items | sale\_id, product\_id, qty, unit\_price, unit\_cost, price\_overridden | qty > 0; unit\_price adalah harga saat transaksi; unit\_cost adalah harga beli batch yang terpakai (rata-rata tertimbang), 0 jika tanpa batch |
| stocktakes | scope (teks), status (draft atau confirmed), counted\_by, confirmed\_by, confirmed\_at | Setelah confirmed tidak bisa diubah |
| stocktake\_items | stocktake\_id, product\_id, system\_qty, physical\_qty, reason (boleh kosong), note | Selisih = physical\_qty - system\_qty (dihitung). reason wajib jika selisih bukan 0: lost, damaged, input\_error, expired, atau other |
| cash\_closings | business\_date (unik), opening\_cash, expected\_cash, actual\_cash, diff, note, closed\_by, closed\_at | Menyimpan snapshot saat ditutup |
| expenses | spent\_on, amount, description | amount > 0 |
| audit\_log | actor\_id, action, entity, entity\_id, before (jsonb), after (jsonb) | Ditulis untuk: void, penyesuaian, buang, konfirmasi opname, tutup dan buka ulang kas, perubahan harga jual, perubahan pengguna |

**Turunan (view atau query)**: sisa batch = jumlah pergerakan per batch; stok total produk = jumlah pergerakan per produk; stok layak jual = sisa batch yang belum lewat exp ditambah pergerakan tanpa batch.

**Invarian yang harus selalu benar** (diuji di TC-19): sisa setiap batch >= 0; stok total setiap produk sama dengan jumlah pergerakannya; tidak ada pergerakan yang diubah atau dihapus.

## 5. Kebutuhan Fungsional

Setiap baris punya ID, kebutuhan (dengan tingkat WAJIB atau SEBAIKNYA), dan kriteria penerimaan yang bisa diuji. Rujukan F-xx menunjuk ke PRD bagian 8.

### 5.1 Autentikasi dan peran (F-60, F-61)

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| AUTH-01 | WAJIB: Pemilik login dengan email dan kata sandi (minimal 8 karakter). Pegawai login dengan memilih nama dan memasukkan PIN 6 digit. | Login benar masuk ke beranda; login salah menampilkan pesan umum tanpa membocorkan bagian mana yang salah. |
| AUTH-02 | WAJIB: Lima kegagalan berturut-turut pada satu akun mengunci akun itu selama 5 menit. | Percobaan ke-6 dalam 5 menit ditolak walau PIN benar; setelah 5 menit bisa login. |
| AUTH-03 | WAJIB: Sesi memakai cookie HttpOnly, Secure, SameSite=Lax, berlaku 30 hari sejak aktivitas terakhir. | Akun yang dinonaktifkan pemilik langsung tidak bisa melanjutkan walau sesinya masih ada. |
| AUTH-04 | WAJIB: Setiap endpoint memeriksa peran di server sesuai matriks 2.2. Menyembunyikan tombol di UI tidak cukup. | Pegawai yang memanggil endpoint terlarang mendapat 403. Respons API untuk pegawai tidak memuat last\_buy\_price dan unit\_cost. |
| AUTH-05 | WAJIB: Pemilik dapat membuat pegawai, menonaktifkan pegawai, dan mengatur ulang PIN. | Pegawai nonaktif tidak muncul di daftar login. |

### 5.2 Produk (F-01, F-02, F-03)

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| PRO-01 | WAJIB: Tambah dan ubah produk sesuai tabel products. Nama 1-100 karakter dan unik di antara produk aktif tanpa membedakan huruf besar kecil. Satuan dasar dipilih dari daftar (kg, liter, bungkus, pcs, botol, sachet) atau diisi bebas. Harga bilangan bulat >= 0. | Nama kembar ditolak; harga desimal ditolak; produk yang baru disimpan langsung muncul di pencarian layar jual. |
| PRO-02 | WAJIB: Nonaktifkan produk, bukan hapus. Produk nonaktif tidak muncul di layar jual dan beli, tetapi riwayatnya utuh. Jika stok total bukan 0, tampilkan peringatan sebelum konfirmasi. | Tidak ada endpoint DELETE untuk produk. |
| PRO-03 | WAJIB: Flag has\_expiry menentukan apakah tanggal exp wajib saat pembelian. Mengubahnya hanya berlaku untuk pembelian berikutnya. | Batch lama tidak berubah setelah flag diubah. |
| PRO-04 | WAJIB: Pemilik menandai favorit. Layar jual menampilkan maksimal 24 favorit, urut abjad (tetap, agar posisi tombol tidak berpindah-pindah). | Urutan sama setiap kali layar dibuka. |
| PRO-05 | WAJIB: Perubahan sell\_price dicatat di audit\_log dan tidak mengubah transaksi lama. | Laporan penjualan lama tetap memakai harga lama. |
| PRO-06 | SEBAIKNYA: min\_stock, lead\_time\_days, dan safety\_stock bisa diisi per produk (kosong berarti mengikuti kategori). | Perubahan langsung memengaruhi daftar Perlu Dibeli. |

### 5.3 Pembelian (F-10, F-11, F-12, F-13)

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| BEL-01 | WAJIB: Pembelian berisi tanggal (default hari ini, tidak boleh di masa depan), supplier (teks bebas, opsional), minimal 1 item, dan opsi paid\_from\_cash. Item berisi produk, qty (maksimal 3 desimal, > 0), unit\_price (bulat >= 0), dan expiry\_date jika produk has\_expiry. | Simpan tanpa item ditolak; produk has\_expiry tanpa tanggal exp ditolak; tanggal exp di masa lalu ditolak. |
| BEL-02 | WAJIB: Saat simpan, dalam satu transaksi database: buat purchase dan item, satu batch per item, satu pergerakan type=purchase bernilai +qty per batch, dan perbarui last\_buy\_price produk dari unit\_price item (F-12). Total dihitung server. | Stok total naik tepat sebesar qty; kegagalan di tengah proses tidak meninggalkan data setengah jadi. |
| BEL-03 | WAJIB: Tombol simpan nonaktif selama request berjalan, dan id pembelian dibuat klien. | Dua ketukan cepat atau kirim ulang menghasilkan satu pembelian. |
| BEL-04 | WAJIB (F-13): Hanya pemilik yang dapat void pembelian, dengan alasan wajib. Void hanya boleh jika semua batch dari pembelian itu belum punya pergerakan selain pembelian awal. Void membuat pergerakan type=void\_purchase bernilai -initial\_qty, menandai status=void, dan menghitung ulang last\_buy\_price dari pembelian aktif terakhir produk. | Jika sebagian sudah terjual, void ditolak dengan pesan agar koreksi lewat opname (TC-04). |
| BEL-05 | SEBAIKNYA: Pembelian tidak bisa diedit; koreksi dengan void lalu input ulang. | Tidak ada endpoint edit pembelian. |

### 5.4 Penjualan (F-20 sampai F-25)

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| JUL-01 | WAJIB: Layar jual berisi grid favorit, kolom pencarian, dan keranjang. Ketuk produk menambah 1; jumlah bisa diubah (desimal untuk kg). Total otomatis dalam rupiah. | Transaksi 1 produk favorit selesai dalam 2 ketukan (produk, Bayar tunai). |
| JUL-02 | WAJIB: Bayar tunai mengirim satu request dengan id penjualan (UUID dari klien). Server idempoten: id yang sama mengembalikan hasil yang sama dan tidak membuat transaksi kedua. | TC-05. |
| JUL-03 | WAJIB (FEFO): Dalam satu transaksi database, server mengunci batch produk (SELECT ... FOR UPDATE), lalu mengalokasikan qty ke batch dengan sisa > 0 yang exp-nya belum lewat, urut expiry\_date naik (kosong terakhir), lalu received\_on naik, lalu id. Satu pergerakan type=sale bernilai -qty dibuat per batch yang terpakai. | TC-01, TC-02, TC-18. |
| JUL-04 | WAJIB: Jika sisa batch layak jual tidak cukup, penjualan tetap berhasil. Kekurangan dicatat sebagai pergerakan sale dengan batch\_id kosong (stok minus), dan layar menampilkan peringatan bahwa stok di sistem tidak cukup. Produk itu diberi penanda stok minus di dashboard. | Penjualan tidak pernah diblokir oleh data stok yang keliru (keputusan terbuka nomor 1). |
| JUL-05 | WAJIB: unit\_price default adalah sell\_price saat transaksi, disalin ke sale\_items. Harga bisa diubah per item (F-25) dengan alasan opsional; item itu diberi price\_overridden=true. | Mengubah sell\_price produk sesudahnya tidak mengubah penjualan lama. |
| JUL-06 | WAJIB (F-23): Void penjualan memerlukan alasan. Pemilik boleh void kapan saja; pegawai hanya void penjualan buatannya sendiri pada hari toko yang sama dan sebelum kas hari itu ditutup. Void membuat pergerakan type=void\_sale (+qty) pada batch yang sama dengan alokasi asal (dibaca dari pergerakan sale) dan menandai status=void. Koreksi dilakukan dengan void lalu transaksi baru. | TC-06; pegawai ditolak setelah kas ditutup. |
| JUL-07 | WAJIB (F-24): Rekap akhir hari. Pilih tanggal (tidak di masa depan), isi qty terjual per produk dengan harga default sell\_price. Server membuat satu sale dengan is\_summary=true dan summary\_date, dialokasikan FEFO seperti JUL-03 hanya dari batch yang received\_on-nya tidak setelah summary\_date dan exp-nya belum lewat pada tanggal itu. Baris produk ditolak jika pada tanggal tersebut produk itu sudah punya penjualan per transaksi. Satu rekap per tanggal. | TC-15; hari yang berisi rekap ditandai data rekap di laporan. |
| JUL-08 | SEBAIKNYA: Isi keranjang tersimpan di perangkat dan dikosongkan setelah penjualan sukses. | Memuat ulang halaman di tengah transaksi tidak menghilangkan keranjang. |
| JUL-09 | WAJIB: payment\_method di MVP hanya cash. | Kasbon ada di Fase 1.5. |

### 5.5 Dashboard dan peringatan (F-30, F-31)

Perhitungan yang dipakai:

1. **D** (jumlah hari data) = minimum dari 28 dan jumlah hari sejak pergerakan pertama produk, dihitung termasuk hari ini. Jika D < 7, status produk adalah data belum cukup.
2. **daily\_rate** = total qty terjual dalam D hari terakhir (penjualan bukan void, termasuk rekap) dibagi D.
3. **threshold** = min\_stock jika diisi. Jika kosong: daily\_rate x lead\_time\_days + safety\_stock, dengan lead\_time\_days dan safety\_stock dari produk, atau dari kategori jika kosong. Jika data belum cukup dan min\_stock kosong, produk tidak dievaluasi dan diberi label atur stok minimum.
4. **days\_left** = stok layak jual dibagi daily\_rate (hanya jika daily\_rate > 0).

Catatan: mengecualikan hari saat produk kosong dari perhitungan daily\_rate adalah perbaikan Fase 1.5, karena butuh riwayat stok harian.

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| DSH-01 | WAJIB: Daftar Perlu Dibeli berisi produk aktif dengan stok layak jual <= 0 (status Habis) atau <= threshold (status Menipis). Urutan: Habis lebih dulu, lalu days\_left naik, lalu yang tanpa days\_left. Baris menampilkan nama, stok layak jual dengan satuan, threshold, dan days\_left. | TC-07, TC-08, TC-09. |
| DSH-02 | WAJIB: Daftar Mendekati Exp berisi batch dengan sisa > 0 dan selisih hari ke expiry\_date <= expiry\_warning\_days kategori (default 14), termasuk yang sudah lewat. Urut expiry naik. Baris menampilkan produk, tanggal masuk, sisa, dan sisa hari (yang lewat tampil sebagai Lewat N hari). | TC-10; batch yang sisanya 0 tidak tampil. |
| DSH-03 | WAJIB: Aksi Buang pada batch (hanya pemilik): isi jumlah (default seluruh sisa) dan alasan (kedaluwarsa, rusak, lainnya). Membuat pergerakan type=waste bernilai negatif pada batch itu. | Sisa batch berkurang; tercatat di audit\_log dan di ekspor. |
| DSH-04 | WAJIB: Produk dengan stok layak jual < 0 diberi penanda stok minus di daftar Perlu Dibeli, dengan tautan untuk memulai opname produk itu. | TC-02. |
| DSH-05 | SEBAIKNYA: Beranda peringatan menampilkan tiga angka (jumlah Perlu Dibeli, Mendekati Exp, Stok Minus); ketuk untuk membuka daftar. Dihitung dengan query saat diminta, tanpa cache. | Waktu muat sesuai bagian 8. |
| DSH-06 | Fase 1.5 (F-32): Daftar Menumpuk berisi produk dengan stok layak jual > 0 dan days\_left > overstock\_days kategori (default 60). | Di luar MVP. |

### 5.6 Stok opname dan saldo awal (F-40, F-41, F-43)

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| OPN-01 | WAJIB: Mulai sesi opname dengan cakupan semua produk, satu kategori, atau produk terpilih. Sesi berstatus draft disimpan di server sehingga bisa dilanjutkan setelah halaman ditutup. | Memuat ulang tidak menghilangkan hitungan yang sudah diisi. |
| OPN-02 | WAJIB: Setiap baris berisi physical\_qty (>= 0, hasil hitung). SEBAIKNYA stok sistem disembunyikan sampai angka fisik diisi (hitung buta) untuk mengurangi bias. Saran operasional: lakukan opname saat tidak ada transaksi. | Selisih tampil setelah physical\_qty diisi. |
| OPN-03 | WAJIB: Konfirmasi hanya oleh pemilik. Layar konfirmasi memuat stok total terkini sebagai system\_qty. Baris dengan selisih bukan 0 wajib punya alasan (lost, damaged, input\_error, expired, other). Klien mengirim system\_qty yang ditampilkan; jika berbeda dengan stok total saat konfirmasi, server menolak dengan 409 dan klien memuat ulang. | TC-13; setelah konfirmasi, stok total sama dengan physical\_qty. |
| OPN-04 | WAJIB: Selisih negatif dialokasikan FEFO tanpa mengecualikan batch yang sudah lewat exp (batch dengan exp paling awal habis lebih dulu); sisa yang tidak tertutup batch dicatat tanpa batch. Selisih positif membuat batch baru (source=adjustment) dengan expiry\_date (wajib jika has\_expiry) dan unit\_cost sama dengan last\_buy\_price. Pergerakan bertipe adjustment. | TC-11, TC-12. |
| OPN-05 | WAJIB: Opname yang sudah terkonfirmasi tidak bisa diubah; koreksi lewat opname baru. Riwayat opname dapat dilihat (tanggal, jumlah produk berselisih, siapa). | Tidak ada endpoint edit atau hapus opname terkonfirmasi. |
| OPN-06 | WAJIB (F-43): Saldo awal hanya untuk produk yang belum punya pergerakan sama sekali. Input qty, expiry (jika has\_expiry), dan unit\_cost (default last\_buy\_price atau 0). Membuat batch source=opening dan pergerakan type=opening. Hanya pemilik. | TC-20; produk yang sudah punya pergerakan diarahkan ke opname. |
| OPN-07 | SEBAIKNYA: Pegawai boleh mengisi hitungan (draft); hanya pemilik yang mengonfirmasi. | Tombol konfirmasi tidak tersedia untuk pegawai dan endpoint-nya memberi 403. |

### 5.7 Kas dan rekap harian (F-50, F-51, F-52, F-53)

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| KAS-01 | WAJIB: Rekap harian untuk tanggal terpilih menampilkan jumlah transaksi (bukan void), total penjualan, 5 produk terlaris menurut qty, total void, dan penanda hari berisi rekap manual. | Angka cocok dengan ekspor penjualan tanggal yang sama. |
| KAS-02 | WAJIB: expected\_cash = opening\_cash + total penjualan tunai hari itu (bukan void) - total pengeluaran hari itu - total pembelian hari itu dengan paid\_from\_cash=true. opening\_cash default adalah actual\_cash penutupan terakhir; penutupan pertama diisi manual. | TC-14. |
| KAS-03 | WAJIB: Pemilik menutup hari dengan mengisi actual\_cash (bulat >= 0). Selisih = actual\_cash - expected\_cash ditampilkan dengan teks kurang atau lebih, bukan hanya warna. Catatan wajib jika selisih bukan 0. Satu penutupan per business\_date. | Penutupan kedua untuk tanggal yang sama ditolak kecuali setelah dibuka ulang. |
| KAS-04 | WAJIB: Penutupan menyimpan snapshot. Transaksi baru bertanggal hari itu setelah tutup tetap boleh dicatat, tidak mengubah snapshot, dan hari itu ditandai berubah setelah tutup. Pemilik bisa membuka ulang lalu menutup lagi; tercatat di audit\_log. | Pegawai tidak bisa void setelah tutup (JUL-06). |
| KAS-05 | WAJIB (F-53): Pengeluaran tunai berisi tanggal, jumlah (> 0), dan keterangan; hanya pemilik. Tidak memengaruhi stok. | Pengeluaran 30.000 menurunkan expected\_cash sebesar 30.000. |

### 5.8 Ekspor dan cadangan (F-62)

| ID | Kebutuhan | Kriteria penerimaan |
| --- | --- | --- |
| EXP-01 | WAJIB: Pemilik mengunduh CSV untuk produk, penjualan (per item), pembelian (per item), pergerakan stok, opname, kas harian, dan pengeluaran, dengan rentang tanggal untuk data bertanggal. Encoding UTF-8 dengan BOM, uang dalam rupiah bulat tanpa pemisah ribuan, tanggal format YYYY-MM-DD. Sediakan pilihan pemisah koma atau titik koma, karena Excel berlokal Indonesia umumnya memakai titik koma. | File terbuka di Excel pemilik tanpa karakter rusak dan kolom terbaca terpisah; jumlah baris sama dengan di database. |
| EXP-02 | WAJIB: Cadangan database otomatis harian oleh penyedia hosting atau database, disimpan minimal 14 hari (usulan). Prosedur pemulihan ditulis di README dan diuji satu kali sebelum dipakai nyata. | Pemulihan ke database kosong berhasil dan stok total tiap produk sama dengan sebelumnya. |
| EXP-03 | SEBAIKNYA: Pengingat di beranda pemilik jika ekspor terakhir lebih dari 7 hari lalu. | Pengingat hilang setelah ekspor. |

## 6. Antarmuka Pengguna

### 6.1 Navigasi dan layar

Navigasi utama berupa tab di bagian bawah layar (dijangkau satu tangan): Jual, Barang Masuk, Peringatan, Opname, Lainnya. Pegawai hanya melihat tab yang sesuai haknya (Jual, Peringatan, Opname, Lainnya).

| Rute | Layar | Peran | Elemen kunci |
| --- | --- | --- | --- |
| /login | Login | Publik | Pemilik: email dan kata sandi. Pegawai: pilih nama lalu PIN |
| /jual | Jual (beranda) | Semua | Grid favorit 3 kolom, pencarian, keranjang di bawah, tombol Bayar tunai selebar layar |
| /jual/riwayat | Riwayat penjualan | Semua (pegawai: hari ini) | Daftar transaksi, aksi Void |
| /jual/rekap | Rekap akhir hari | Semua | Pilih tanggal, isi qty per produk |
| /beli | Barang masuk | Pemilik | Form pembelian, tanggal exp per item |
| /beli/riwayat | Riwayat pembelian | Pemilik | Daftar, aksi Void |
| /peringatan | Dashboard | Semua | Tiga angka ringkas, tab Perlu Dibeli dan Mendekati Exp, aksi Buang (pemilik) |
| /opname | Daftar dan mulai opname | Semua | Sesi draft, riwayat |
| /opname/\[id\] | Hitung dan konfirmasi | Semua (konfirmasi: pemilik) | Baris produk, input fisik, ringkasan selisih, alasan |
| /opname/saldo-awal | Saldo awal | Pemilik | Input qty, exp, harga beli |
| /produk | Produk dan kategori | Pemilik (pegawai: lihat) | Daftar, form, flag favorit dan exp |
| /kas | Rekap harian dan tutup kas | Pemilik | Ringkasan, expected, input kas fisik, selisih |
| /kas/pengeluaran | Pengeluaran | Pemilik | Daftar dan form |
| /ekspor | Ekspor | Pemilik | Pilih data, rentang tanggal, pemisah CSV |
| /pengaturan | Pengguna dan pengaturan | Pemilik | Pegawai, PIN, ambang per kategori |

### 6.2 Aturan antarmuka

- Target sentuh minimal 48 x 48 piksel CSS, teks isi minimal 16 piksel, kontras teks minimal 4,5:1.
- Input kuantitas memakai `inputmode=decimal` dan menerima koma atau titik sebagai pemisah desimal. Input rupiah memakai `inputmode=numeric`.
- Rupiah tampil sebagai Rp 12.500 (format id-ID). Tanggal tampil dd/mm/yyyy, input memakai pemilih tanggal bawaan browser.
- Status tidak hanya memakai warna: gunakan ikon dan teks (Habis, Menipis, Lewat N hari, Stok minus).
- Aksi yang sulit dibatalkan (void, buang, konfirmasi opname, tutup kas) memakai dialog konfirmasi yang memuat ringkasan akibatnya.
- Setelah penjualan sukses: toast singkat, keranjang kosong, layar siap untuk transaksi berikutnya tanpa berpindah halaman.
- Pesan error berbahasa Indonesia, spesifik, dan ditampilkan dekat kolom yang bermasalah.
- Saat tidak ada koneksi: banner Tidak ada koneksi, transaksi belum tersimpan; tombol simpan atau bayar dinonaktifkan; keranjang dipertahankan.
- Keadaan kosong: jika belum ada produk, layar jual menampilkan ajakan menambah produk pertama.

## 7. Kontrak API

REST JSON di bawah `/api`. Semua metode yang mengubah data memerlukan sesi dan perlindungan CSRF. Error memakai satu bentuk objek berisi `code`, `message`, dan `fields` (opsional). Status: 400 validasi, 401 belum login, 403 tidak berhak, 404 tidak ada, 409 konflik (misalnya stok berubah), 500 galat server. Detail skema request dan respons ditentukan dengan Zod saat implementasi dan harus sesuai aturan di bagian 5.

| Endpoint | Peran | Fungsi | Catatan |
| --- | --- | --- | --- |
| POST /api/auth/login, /api/auth/logout | Publik, semua | Masuk dan keluar | Pembatasan laju pada login (AUTH-02) |
| GET /api/products | Semua | Daftar produk | Tanpa last\_buy\_price untuk pegawai |
| POST /api/products, PATCH /api/products/:id | Pemilik | Tambah, ubah | PRO-01 sampai PRO-06 |
| POST /api/products/:id/deactivate | Pemilik | Nonaktifkan | PRO-02 |
| GET, POST, PATCH /api/categories | Pemilik (GET: semua) | Kategori dan ambang |  |
| POST /api/purchases | Pemilik | Buat pembelian | Id dari klien; idempoten |
| GET /api/purchases | Pemilik | Riwayat | Filter tanggal |
| POST /api/purchases/:id/void | Pemilik | Void pembelian | BEL-04 |
| POST /api/sales | Semua | Buat penjualan | Id dari klien; idempoten; FEFO |
| POST /api/sales/summary | Semua | Rekap akhir hari | JUL-07 |
| GET /api/sales | Semua | Riwayat | Pegawai: hari toko ini saja |
| POST /api/sales/:id/void | Pemilik, pegawai terbatas | Void penjualan | JUL-06 |
| GET /api/dashboard/summary, /reorder, /expiring | Semua | Data peringatan | DSH-01 sampai DSH-05 |
| POST /api/batches/:id/waste | Pemilik | Buang barang | DSH-03 |
| POST /api/stocktakes | Semua | Mulai sesi opname | OPN-01 |
| GET /api/stocktakes/:id | Semua | Baca sesi |  |
| PUT /api/stocktakes/:id/items | Semua | Simpan hitungan (draft) | Hanya jika status draft |
| POST /api/stocktakes/:id/confirm | Pemilik | Konfirmasi | 409 jika stok berubah (OPN-03) |
| POST /api/opening-balance | Pemilik | Saldo awal | OPN-06 |
| GET /api/cash/daily | Pemilik | Rekap dan expected | KAS-01, KAS-02 |
| POST /api/cash/close, /api/cash/reopen | Pemilik | Tutup dan buka ulang | KAS-03, KAS-04 |
| GET, POST /api/expenses | Pemilik | Pengeluaran | KAS-05 |
| GET /api/export/:dataset | Pemilik | Unduh CSV | Parameter from, to, delimiter |
| GET, POST, PATCH /api/users | Pemilik | Kelola pengguna | AUTH-05 |

## 8. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan terukur | Cara verifikasi |
| --- | --- | --- |
| Kinerja layar | Layar /jual terbuka dan bisa diketuk dalam 2 detik pada kunjungan berulang (aset sudah di-cache) di Redmi 10 | Ukur 3 kali di perangkat nyata (Chrome remote debugging atau stopwatch), ambil median |
| Kinerja server | POST /api/sales dengan 10 item selesai dalam 500 ms (persentil 95) dan dashboard dalam 1 detik, pada data seed 1.000 produk dan 100.000 pergerakan | Uji beban ringan dengan data seed |
| Integritas data | Invarian di bagian 4 selalu benar; constraint database (CHECK, FOREIGN KEY, UNIQUE) menjaga aturan dasar, bukan hanya kode aplikasi | TC-19 dan skrip pemeriksaan invarian yang bisa dijalankan kapan saja |
| Keamanan | HTTPS saja; kata sandi dan PIN di-hash; cookie sesi HttpOnly, Secure, SameSite=Lax; perlindungan CSRF; validasi semua input dengan Zod; query terparameter lewat ORM; rahasia di variabel lingkungan dan tidak di repositori; pembatasan laju login | Tinjauan kode dan TC-16, TC-17 |
| PWA | Manifest berisi nama, ikon 192 dan 512 piksel, display standalone, start\_url /jual, theme color. Service worker hanya meng-cache app shell dan tidak meng-cache respons API berisi data transaksi. Versi baru menampilkan pesan Pembaruan tersedia | Aplikasi bisa dipasang dari menu Chrome dan terbuka layar penuh; setelah rilis baru, pesan pembaruan muncul |
| Kompatibilitas | Chrome Android dua versi mayor terakhir; Chrome desktop untuk laporan | Uji manual di Redmi 10 |
| Lokalisasi | id-ID, rupiah, zona waktu Asia/Jakarta | Format di UI dan ekspor sesuai bagian 6.2 |
| Aksesibilitas | Kontras teks minimal 4,5:1; status tidak hanya warna; target sentuh 48 piksel | Pemeriksaan manual dan alat audit kontras |
| Observabilitas | Log galat server dengan id request; PIN dan kata sandi tidak pernah dicatat; audit\_log untuk aksi sensitif | Tinjauan log pada uji |
| Keterpeliharaan | Migrasi berversi, seed 30 produk contoh, `.env.example`, README berisi cara menjalankan, menguji, memulihkan cadangan, dan deploy | Orang lain bisa menjalankan proyek dari repositori kosong dengan mengikuti README |
| Privasi | Tidak ada data pelanggan di MVP; hanya nama pegawai | Tinjauan skema |

## 9. Panduan untuk AI Coding Agent (Antigravity)

### 9.1 Aturan main

1. **Satu penulis stok.** Hanya `StockService` yang menulis ke `stock_movements`. Tidak ada kode yang mengubah angka stok langsung.
2. **Tes dulu untuk aturan hitung.** Alokasi FEFO, daily\_rate, threshold, dan expected\_cash ditulis sebagai fungsi murni di `src/domain/` dengan tes unit yang memakai skenario di bagian 10. Tulis tesnya sebelum implementasinya.
3. **Angka aman.** Uang bilangan bulat rupiah; kuantitas memakai Decimal atau bilangan bulat per seperseribu. Jangan memakai float untuk uang atau stok.
4. **Satu transaksi.** Setiap operasi stok berjalan dalam satu transaksi database dengan penguncian baris.
5. **Jangan menambah fitur.** Kerjakan hanya yang ada di SRS. Jika ada yang ambigu atau bertentangan, berhenti dan tanyakan, jangan menebak.
6. **Satu slice per giliran.** Setiap slice selesai jika tesnya lulus, migrasi bisa dijalankan dari database kosong, dan perubahan di-commit terpisah.
7. **Data contoh saja.** Pengembangan memakai seed, bukan data asli toko.
8. **Uji di HP.** Setelah tiap slice, jalankan alur utamanya di Redmi 10, bukan hanya di laptop.

### 9.2 Urutan pembangunan

| Slice | Isi | Selesai bila |
| --- | --- | --- |
| S0 | Scaffold proyek, skema database (bagian 4), migrasi, seed 30 produk, autentikasi dan peran, kerangka navigasi | AUTH-01 sampai AUTH-05; TC-16, TC-17 lulus |
| S1 | Produk dan kategori | PRO-01 sampai PRO-06 |
| S2 | StockService, pembelian, void pembelian | BEL-01 sampai BEL-05; TC-03, TC-04 lulus |
| S3 | Penjualan dengan FEFO, idempotensi, void, rekap akhir hari | JUL-01 sampai JUL-09; TC-01, TC-02, TC-05, TC-06, TC-15, TC-18 lulus |
| S4 | Dashboard dan aksi Buang | DSH-01 sampai DSH-05; TC-07 sampai TC-10 lulus |
| S5 | Opname dan saldo awal | OPN-01 sampai OPN-07; TC-11, TC-12, TC-13, TC-20 lulus |
| S6 | Kas, pengeluaran, rekap harian | KAS-01 sampai KAS-05; TC-14 lulus |
| S7 | Ekspor, PWA, pengukuran kinerja, README dan uji pemulihan cadangan | EXP-01 sampai EXP-03; bagian 8 terpenuhi; TC-19 lulus |

Urutan ini disengaja: S2 dan S3 memuat logika paling berisiko (ledger dan FEFO). Jika keduanya benar, sisanya sebagian besar adalah tampilan dan query.

### 9.3 Yang perlu Anda periksa sendiri setelah tiap slice

- Apakah ada kode yang mengubah stok di luar `StockService` (cari di repositori).
- Apakah tes untuk aturan di slice itu benar-benar ada dan lulus, bukan hanya aplikasi yang tampak jalan.
- Apakah agen menambah fitur atau tabel yang tidak ada di SRS.

## 10. Skenario Uji

Tanggal acuan: hari ini 16 Oktober 2026. Skenario ini dipakai sebagai tes otomatis dan sebagai uji manual.

| ID | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| TC-01 | FEFO dasar. Produk has\_expiry. Batch A exp 10 Nov 2026 sisa 5; batch B exp 20 Nov 2026 sisa 10. Jual 7. | A sisa 0, B sisa 8. Dua pergerakan sale: -5 pada A dan -2 pada B. |
| TC-02 | Batch lewat exp tidak dipakai. Batch A exp 15 Okt 2026 sisa 3; batch B exp 20 Nov 2026 sisa 2. Jual 4. | B sisa 0; satu pergerakan tanpa batch bernilai -2; A tetap 3. Stok layak jual -2, stok total 1. Produk tampil Habis dengan penanda stok minus; A tampil di Mendekati Exp sebagai Lewat 1 hari. |
| TC-03 | Pembelian 20 bungkus harga 2.800 dengan exp 1 Des 2026. | Stok total naik 20; satu batch baru; last\_buy\_price menjadi 2.800. |
| TC-04 | Void pembelian. (a) Setelah TC-03 tanpa penjualan, void. (b) Ulangi TC-03, jual 1, lalu void. | (a) Berhasil, stok kembali ke semula, last\_buy\_price dihitung ulang. (b) Ditolak dengan pesan agar koreksi lewat opname. |
| TC-05 | Idempotensi. Kirim POST /api/sales dengan id yang sama dua kali. | Satu penjualan, stok berkurang sekali, respons kedua sama dengan pertama. |
| TC-06 | Void penjualan. Dari TC-01, jual 3 lalu void. | Batch kembali ke sisa semula, status void; void kedua ditolak. |
| TC-07 | Threshold hasil hitung. D = 28, terjual 56 dalam 28 hari (daily\_rate 2), lead\_time 2 hari, safety\_stock 4 (threshold 8). | Stok layak jual 8: tampil Menipis dengan days\_left 4. Stok 9: tidak tampil. |
| TC-08 | min\_stock menimpa. Seperti TC-07 tetapi min\_stock = 5. | Stok 6: tidak tampil. Stok 5: tampil. |
| TC-09 | Data belum cukup. Produk dengan riwayat 3 hari dan min\_stock kosong. | Tidak masuk Perlu Dibeli; berlabel atur stok minimum. |
| TC-10 | Mendekati exp. expiry\_warning\_days = 14. Batch exp 29 Okt 2026 (13 hari) sisa 4; batch exp 31 Okt 2026 (15 hari) sisa 4; batch exp 1 Nov 2026 sisa 0. | Hanya batch pertama yang tampil. |
| TC-11 | Opname negatif. Stok total 10 (batch A 4 dengan exp lebih awal, batch B 6). Fisik 7, alasan lost. | Penyesuaian -4 pada A dan -3 pada B; stok total 7. |
| TC-12 | Opname positif. Stok total 10, fisik 12, produk has\_expiry, exp 1 Jan 2027. | Batch baru source=adjustment qty 2 exp 1 Jan 2027; stok total 12. |
| TC-13 | Konflik opname. Setelah hitungan diisi, terjadi penjualan sebelum konfirmasi. | Konfirmasi ditolak 409; setelah memuat ulang, selisih dihitung ulang. |
| TC-14 | Kas. opening\_cash 100.000; penjualan tunai 450.000 (bukan void); pengeluaran 30.000; pembelian paid\_from\_cash 120.000; kas fisik 395.000. | expected\_cash 400.000; selisih -5.000 tampil sebagai kurang Rp 5.000. |
| TC-15 | Rekap akhir hari. Pada tanggal T produk beras sudah punya penjualan per transaksi; rekap untuk T memuat baris beras dan baris gula. | Baris beras ditolak, baris gula diterima. |
| TC-16 | Peran. Pegawai memanggil POST /api/purchases dan GET /api/products. | 403 untuk pembelian; respons produk tanpa last\_buy\_price. |
| TC-17 | Kunci akun. Lima PIN salah berturut-turut. | Percobaan ke-6 ditolak selama 5 menit walau PIN benar. |
| TC-18 | Konkurensi. Dua penjualan bersamaan untuk produk yang sama dengan sisa batch 5, masing-masing 4. | Sisa batch tidak pernah negatif: satu penjualan mengambil 4 dari batch, yang lain 1 dari batch dan 3 tanpa batch. |
| TC-19 | Invarian. Setelah seluruh skenario di atas dijalankan berurutan. | Untuk setiap produk jumlah pergerakan sama dengan stok total; sisa setiap batch >= 0; tidak ada pergerakan yang diubah. |
| TC-20 | Saldo awal. Produk baru qty 12. Lalu coba saldo awal pada produk yang sudah punya pergerakan. | Pertama: batch source=opening. Kedua: ditolak dan diarahkan ke opname. |

Uji manual tambahan (kriteria PRD bagian 16): ukur dengan stopwatch beberapa transaksi 1-3 item pada jam ramai, dan bandingkan total penjualan harian aplikasi dengan buku catatan selama dua minggu paralel.

## 11. Keputusan Terbuka

| # | Keputusan | Default di SRS | Perlu jawaban dari | Bagian terdampak |
| --- | --- | --- | --- | --- |
| 1 | Boleh jual saat stok di sistem 0 atau kurang (stok minus)? | Boleh, ditandai dan muncul di dashboard | Raswan | JUL-04, DSH-04 |
| 2 | Pembelian kadang dibayar dari uang laci? | Opsi per pembelian, default tidak | Raswan | BEL-01, KAS-02 |
| 3 | Apakah toko buka melewati tengah malam? | Tidak; hari toko = tanggal kalender WIB | Raswan | KAS, rekap harian |
| 4 | Ada kasbon (A3)? | Fase 1.5 | Raswan | JUL-09, tabel baru |
| 5 | Ada barang dijual eceran dari karton (A4)? | Fase 1.5; sementara didaftarkan sebagai produk terpisah | Raswan | BEL, JUL, tabel baru |
| 6 | Boleh pegawai membatalkan penjualannya sendiri? | Ya, hari yang sama sebelum kas ditutup | Raswan | JUL-06 |
| 7 | Hosting dan penyedia database | Belum dipilih; syarat HTTPS, cadangan otomatis, PostgreSQL terkelola, server dekat Indonesia bila tersedia | Pembuat | Bagian 3, EXP-02 |
| 8 | Lama penyimpanan cadangan | 14 hari | Pembuat dan Raswan | EXP-02 |
| 9 | Panjang PIN dan aturan kunci | 6 digit; 5 kali salah; kunci 5 menit | Pembuat | AUTH-01, AUTH-02 |

Keputusan 1, 2, dan 6 paling berpengaruh pada kepercayaan terhadap data dan sebaiknya dijawab sebelum slice S3.
