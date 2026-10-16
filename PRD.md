# PRD: Sistem Manajemen Stok Warung Sembako (PWA)

Versi 0.1 (Draft) · 16 Oktober 2026 · Pemilik produk: Raswan (pemilik toko) · Status: perlu validasi asumsi dengan pemilik toko

## 1. Ringkasan

Aplikasi web progresif (PWA) yang dipakai lewat HP untuk mencatat pembelian, penjualan, dan stok di warung sembako, menggantikan buku catatan. Fokus fase 1 bukan menjadi kasir lengkap, tetapi menjawab empat masalah nyata: barang habis tanpa disadari, stok menumpuk, barang mendekati kedaluwarsa, dan stok fisik berbeda dengan catatan. Selisih uang kas ikut masuk scope dalam bentuk rekap kas harian.

## 2. Latar Belakang dan Masalah

Dasar: wawancara dengan pemilik toko. Seluruh pencatatan saat ini dilakukan di buku catatan.

| Masalah (fakta dari wawancara) | Penyebab yang disimpulkan | Respons produk |
| --- | --- | --- |
| Barang habis tanpa disadari; tahu dari cek langsung atau dari pembeli | Tidak ada batas minimum, tidak ada data laju jual | Stok minimum, daftar "perlu dibeli" |
| Stok menumpuk karena kurang laku | Tidak ada data perputaran | Laporan barang lambat laku |
| Sulit tahu barang mendekati kedaluwarsa | Tanggal exp tidak tercatat per kedatangan | Pencatatan batch dan exp, aturan FEFO |
| Stok fisik berbeda dengan catatan | Tidak ada rekonsiliasi berkala | Stok opname dan riwayat penyesuaian |
| Selisih kas sering terjadi | Penjualan tidak tercatat per transaksi | Rekap kas harian dan pencocokan |
| Rekap penjualan memakan sekitar 1 jam | Rekap manual dari buku | Rekap otomatis |

## 3. Tujuan dan Metrik Keberhasilan

**Tujuan fase 1**

1. Pemilik tahu barang apa yang harus dibeli sebelum stok habis.
2. Pemilik tahu barang apa yang akan kedaluwarsa dan barang apa yang menumpuk.
3. Selisih stok dan kas bisa diukur dan ditelusuri penyebabnya.
4. Pencatatan tidak lebih lambat dari buku catatan saat toko ramai.

**Metrik.** Angka target di bawah adalah usulan awal. Baseline belum diukur dan harus dicatat selama 1-2 minggu pertama sebelum target dikunci.

| Metrik | Baseline | Target usulan |
| --- | --- | --- |
| Waktu rekap penjualan harian | ±60 menit (wawancara) | Kurang dari 10 menit |
| Kejadian barang kosong yang diketahui dari pembeli | Belum diukur | Turun signifikan dalam 1 bulan; dicatat manual oleh pemilik |
| Selisih stok saat opname (persen item yang selisih) | Belum diukur | Tren menurun dari opname ke opname |
| Barang kedaluwarsa yang terbuang | Belum diukur | Turun; dicatat lewat fitur "buang barang" |
| Persentase hari dengan penjualan tercatat | Tidak ada | Di atas 90% hari buka |

Metrik terakhir penting: kalau data bolong, semua laporan lain tidak bisa dipercaya.

## 4. Bukan Tujuan (Non-Goals) Fase 1

- Multi-cabang atau multi-toko.
- Laporan laba rugi dan akuntansi lengkap.
- Integrasi supplier, pembayaran digital, atau marketplace.
- Prediksi berbasis AI atau machine learning.
- Cetak struk dan integrasi printer.
- Scan barcode (ditunda ke fase 2; lihat bagian 14).

Alasannya: semua ini menambah beban pembuatan dan pemeliharaan tanpa menjawab empat masalah inti.

## 5. Pengguna

| Peran | Deskripsi | Kebutuhan utama |
| --- | --- | --- |
| Pemilik (Raswan) | Pengambil keputusan belanja; mengakses dashboard dan laporan | Tahu apa yang dibeli, apa yang menumpuk, apa yang akan exp, mengecek kas |
| Pegawai (opsional, 1 orang) | Melayani pembeli dan mencatat penjualan | Input cepat, tidak bisa mengubah harga beli atau menghapus data |

Perangkat: HP Android Redmi 10, penggunaan satu tangan, koneksi internet stabil.

## 6. Asumsi dan Pertanyaan Terbuka

Asumsi ini belum terverifikasi dan memengaruhi desain.

| # | Asumsi / pertanyaan | Dampak jika salah |
| --- | --- | --- |
| A1 | Ratusan produk, bukan ribuan | Desain pencarian dan impor data |
| A2 | Pengguna 1-2 orang, satu toko | Model hak akses dan sinkronisasi |
| A3 | Ada penjualan kasbon (utang pelanggan) | Perlu modul piutang (saat ini ditandai fase 1.5) |
| A4 | Satuan campur: kg, liter, bungkus, karton, dan jual eceran dari karton | Perlu konversi satuan; kompleksitas naik |
| A5 | Semua barang bisa dibeli dalam sehari dari supplier/pasar | Menentukan lama pengiriman di rumus pesan ulang |
| A6 | Pemilik mau mencatat penjualan per transaksi, bukan hanya rekap akhir hari | Menentukan mode input default |
| A7 | Barang tanpa exp (beras curah, gula curah) tidak perlu batch | Mengurangi beban input |

Pertanyaan paling krusial untuk Raswan: apakah ada kasbon, apakah ada pembelian eceran dari karton, dan siapa yang akan mencatat saat toko ramai.

## 7. Prinsip Desain

1. **Kecepatan input mengalahkan kelengkapan fitur.** Penjualan maksimal 2-3 ketukan.
2. **Data adalah catatan kejadian, bukan angka yang ditimpa.** Stok dihitung dari riwayat pergerakan, sehingga selisih bisa ditelusuri.
3. **Gagal dengan aman.** Jika offline atau lupa mencatat, ada jalur pemulihan (rekap akhir hari, penyesuaian opname).
4. **Sederhana dulu.** Fitur yang tidak menjawab masalah di bagian 2 tidak masuk fase 1.

## 8. Kebutuhan Fungsional

Prioritas: **P0** wajib ada agar fase 1 berguna, **P1** sangat diinginkan, **P2** boleh ditunda.

### 8.1 Master Produk

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F-01 | Tambah/ubah/nonaktifkan produk: nama, kategori, satuan dasar, harga beli terakhir, harga jual, stok minimum | P0 |
| F-02 | Tandai produk "punya exp" atau tidak | P0 |
| F-03 | Tandai produk favorit untuk tampil di layar depan penjualan | P0 |
| F-04 | Satuan kedua dengan konversi (mis. 1 karton = 40 bungkus) | P1 |
| F-05 | Impor produk lewat CSV | P2 |

### 8.2 Pembelian (Stok Masuk)

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F-10 | Catat pembelian: tanggal, supplier (teks bebas), item, jumlah, harga beli | P0 |
| F-11 | Untuk produk ber-exp, isi tanggal kedaluwarsa per kedatangan (membentuk satu batch) | P0 |
| F-12 | Harga beli terakhir produk diperbarui otomatis dari pembelian | P1 |
| F-13 | Koreksi/batalkan pembelian yang salah input, tercatat di riwayat | P1 |

### 8.3 Penjualan

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F-20 | Layar penjualan cepat: produk favorit, pencarian, jumlah default 1, total otomatis | P0 |
| F-21 | Stok berkurang otomatis memakai batch dengan exp terdekat lebih dulu (FEFO); produk tanpa exp memakai urutan masuk | P0 |
| F-22 | Metode bayar: tunai (default). Kasbon sebagai opsi jika A3 terkonfirmasi | P0 (tunai), P1 (kasbon) |
| F-23 | Batalkan/koreksi transaksi, tercatat di riwayat, tidak menghapus data | P0 |
| F-24 | Mode rekap akhir hari: input total terjual per produk tanpa per transaksi (cadangan jika toko ramai) | P1 |
| F-25 | Harga jual bisa diubah per transaksi (tawar-menawar) dengan alasan opsional | P1 |

### 8.4 Dashboard dan Peringatan

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F-30 | Daftar "Perlu Dibeli": produk dengan stok di bawah titik pesan ulang, diurutkan dari paling mendesak | P0 |
| F-31 | Daftar "Mendekati Exp": batch dengan exp dalam N hari (default 14, dapat diatur per kategori) dan yang sudah lewat | P0 |
| F-32 | Daftar "Menumpuk": produk dengan hari stok tersisa di atas ambang dan penjualan rendah | P1 |
| F-33 | Aksi cepat dari daftar: tambah ke daftar belanja, tandai "sudah dibuang", atau tandai "diskon/obral" | P1 |
| F-34 | Notifikasi push untuk stok menipis dan exp mendekat | P2 |

### 8.5 Stok Opname

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F-40 | Sesi opname: pilih produk/kategori, input jumlah fisik, sistem menampilkan selisih | P0 |
| F-41 | Konfirmasi penyesuaian dengan alasan (hilang, rusak, salah catat, kedaluwarsa, lainnya) | P0 |
| F-42 | Riwayat opname dan tren selisih per produk | P1 |
| F-43 | Opname awal sebagai saldo awal sistem (titik nol) | P0 |

### 8.6 Kas dan Rekap Harian

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F-50 | Rekap harian otomatis: total penjualan, jumlah transaksi, produk terlaris | P0 |
| F-51 | Kas seharusnya = kas awal + penjualan tunai − pengeluaran tunai yang dicatat | P0 |
| F-52 | Input kas fisik akhir hari, sistem menampilkan selisih | P0 |
| F-53 | Catat pengeluaran tunai sederhana (mis. beli barang di pasar, ongkos) | P1 |

### 8.7 Pengguna dan Data

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| F-60 | Login dengan PIN atau email+kata sandi; dua peran: pemilik dan pegawai | P0 |
| F-61 | Pegawai tidak dapat melihat harga beli, mengubah master produk, atau membatalkan transaksi lama | P1 |
| F-62 | Ekspor data ke CSV/Excel dan cadangan otomatis | P1 |

## 9. Aturan Bisnis

1. **Stok** = jumlah seluruh pergerakan (masuk, keluar, penyesuaian). Stok tidak diedit langsung; perubahan selalu lewat transaksi atau penyesuaian opname.
2. **FEFO**: penjualan mengambil dari batch dengan exp paling awal yang masih layak jual. Batch yang sudah lewat exp tidak dipakai otomatis dan masuk daftar "buang".
3. **Laju jual harian** = rata-rata penjualan N hari terakhir (default 28). Hari saat produk kosong sebaiknya dikecualikan agar laju tidak terlalu rendah; ini perlu diverifikasi dengan data nyata.
4. **Titik pesan ulang** = laju jual harian × lama pengiriman (hari) + stok pengaman. Lama pengiriman dan stok pengaman default per kategori, dapat diubah per produk. Stok minimum manual bisa menimpa hasil hitung.
5. **Hari stok tersisa** = stok ÷ laju jual harian. Ambang "menumpuk" default 60 hari. Angka ini tebakan awal; barang kering tahan lama dan barang segar jelas berbeda, jadi ambang harus per kategori.
6. **Transaksi tidak dihapus**, hanya dibatalkan dengan catatan (jejak audit).
7. **Rekap akhir hari** hanya boleh dipakai untuk hari tanpa transaksi per item, dan ditandai agar laporan tahu akurasinya lebih rendah.

## 10. Alur Pengguna Utama

**Menjual.** Buka aplikasi → layar depan menampilkan produk favorit → ketuk produk (jumlah default 1, ketuk lagi untuk menambah) → ketuk "Bayar tunai" → selesai. Target: kurang dari 10 detik untuk transaksi dengan 1-3 item.

**Menerima barang.** Menu Pembelian → pilih produk → isi jumlah dan harga → jika ber-exp, isi tanggal exp → simpan.

**Mengecek pagi hari.** Buka dashboard → lihat "Perlu Dibeli" dan "Mendekati Exp" → ketuk item untuk menambahkan ke daftar belanja atau menandai tindakan.

**Opname.** Menu Opname → pilih kategori → hitung fisik dan input → lihat selisih → pilih alasan → konfirmasi.

**Tutup toko.** Menu Tutup Hari → lihat rekap → input kas fisik → lihat selisih → simpan.

## 11. Model Data (Tingkat Tinggi)

| Entitas | Atribut utama |
| --- | --- |
| Produk | id, nama, kategori, satuan dasar, harga jual, harga beli terakhir, stok minimum, punya\_exp, favorit, aktif |
| KonversiSatuan | produk\_id, satuan, faktor ke satuan dasar |
| Batch | id, produk\_id, tanggal\_masuk, tanggal\_exp (opsional), jumlah\_awal, harga\_beli |
| PergerakanStok | id, produk\_id, batch\_id, jenis (masuk / jual / penyesuaian / buang / batal), jumlah (+/−), waktu, referensi, pengguna, alasan |
| Pembelian dan ItemPembelian | tanggal, supplier, total, item |
| Penjualan dan ItemPenjualan | waktu, metode\_bayar, total, status, item (dengan harga jual saat itu) |
| Opname dan ItemOpname | tanggal, produk, jumlah\_sistem, jumlah\_fisik, alasan |
| KasHarian | tanggal, kas\_awal, kas\_seharusnya, kas\_fisik, selisih, catatan |
| Pengeluaran | tanggal, jumlah, keterangan |
| Pengguna | id, nama, peran, kredensial |

Catatan desain: harga jual dan harga beli disimpan di setiap transaksi agar laporan masa lalu tidak berubah ketika harga produk diubah. Batch hanya dibuat untuk produk ber-exp; produk tanpa exp memakai satu batch implisit.

## 12. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
| --- | --- |
| Perangkat | Mobile-first, nyaman satu tangan, tombol besar, kontras tinggi, dapat dipasang ke layar utama (installable PWA) |
| Kinerja | Layar penjualan terbuka kurang dari 2 detik pada HP kelas menengah ke bawah; transaksi tersimpan lokal seketika |
| Offline | Penjualan dan pembelian dapat dicatat tanpa internet, masuk antrean, dan disinkronkan otomatis saat online. Dashboard dan laporan boleh menampilkan data terakhir yang tersimpan |
| Sinkronisasi | Karena stok dihitung dari pergerakan yang hanya ditambah (append-only), konflik sinkron untuk 1-2 pengguna kecil risikonya; aturan sederhana: server menerima semua pergerakan berurutan menurut waktu |
| Keamanan | HTTPS, kata sandi/PIN ter-hash, peran pemilik dan pegawai, sesi dengan masa berlaku |
| Keandalan data | Cadangan harian otomatis, ekspor manual, jejak audit untuk pembatalan dan penyesuaian |
| Bahasa | Bahasa Indonesia, format rupiah, zona waktu WIB |
| Aksesibilitas | Ukuran huruf mudah dibaca, tidak bergantung warna saja untuk status |

## 13. Arsitektur dan Stack (Rekomendasi)

Stack dapat disesuaikan dengan keahlian pembuat. Rekomendasi default:

- **Frontend**: HTML, CSS, JavaScript, Blade, PWA (service worker, manifest), penyimpanan lokal IndexedDB untuk antrean offline.
- **Backend**: Laravel.
- **Database**: MySQL.
- **Hosting**: railway.

Trade-off yang perlu diputuskan:

| Keputusan | Opsi A | Opsi B | Rekomendasi |
| --- | --- | --- | --- |
| Offline | Penuh (baca dan tulis lokal, sinkron dua arah) | Hanya antrean tulis untuk penjualan dan pembelian | Opsi B untuk fase 1; jauh lebih sederhana dan cukup untuk satu toko |
| Auth | PIN lokal | Email + kata sandi | PIN untuk pegawai, kata sandi untuk pemilik |
| Batch | Semua produk | Hanya produk ber-exp | Hanya produk ber-exp |

## 14. Rencana Rilis

| Fase | Isi | Kriteria selesai |
| --- | --- | --- |
| 0. Validasi | Konfirmasi asumsi A1-A7 dengan Raswan; ukur baseline selama 1-2 minggu; pilih 20-30 produk terlaris | Daftar produk awal dan jawaban asumsi tersedia |
| 1. MVP | Seluruh item P0 dan P1 yang ringan (F-12, F-24, F-62) | Dipakai paralel dengan buku catatan selama 2 minggu tanpa kehilangan data |
| 1.5 | Kasbon (jika A3 terkonfirmasi), satuan ganda (F-04), laporan barang menumpuk (F-32), pengeluaran (F-53) | Raswan berhenti memakai buku untuk penjualan |
| 2 | Barcode, notifikasi push, impor CSV, daftar belanja per supplier | Berdasarkan umpan balik pemakaian nyata |

Peralihan dari buku catatan: jalankan paralel 2 minggu, bandingkan total penjualan harian di buku vs aplikasi, baru beralih penuh.

## 15. Risiko dan Mitigasi

| Risiko | Kemungkinan | Dampak | Mitigasi |
| --- | --- | --- | --- |
| Input lebih lambat dari buku sehingga ditinggalkan | Tinggi | Sangat tinggi | Layar favorit, 2-3 ketukan, mode rekap akhir hari, uji langsung saat jam ramai |
| Saldo awal salah sehingga semua selisih tidak bermakna | Sedang | Tinggi | Opname awal wajib sebelum mulai memakai |
| Data bolong membuat laju jual dan peringatan tidak akurat | Sedang | Tinggi | Tampilkan persentase hari tercatat; beri peringatan jika data kurang |
| Pemilik kurang nyaman dengan teknologi | Sedang | Tinggi | Pelatihan singkat, antarmuka sederhana, bahasa sehari-hari |
| Satuan jual-beli campur (karton vs eceran) membuat stok salah | Sedang | Sedang | Tunda ke F-04 dengan konversi yang jelas; di MVP gunakan satu satuan dasar per produk |
| Internet buruk | Sedang | Sedang | Antrean offline untuk transaksi |
| Kehilangan data | Rendah | Sangat tinggi | Cadangan otomatis dan ekspor |
| Pegawai atau pemilik mengubah data tanpa jejak | Sedang | Sedang | Jejak audit, pembatalan tanpa penghapusan |

## 16. Rencana Pengujian dan Penerimaan

Kriteria penerimaan MVP:

1. Transaksi penjualan 1-3 item dapat diselesaikan dalam waktu wajar oleh pengguna nyata pada jam ramai (diukur dengan stopwatch pada beberapa sampel).
2. Penjualan dari produk ber-exp selalu menurunkan batch dengan exp paling awal.
3. Stok sistem = saldo awal + pembelian − penjualan ± penyesuaian, terverifikasi pada data uji.
4. Daftar "Perlu Dibeli" memunculkan produk yang sengaja diturunkan di bawah titik pesan ulang.
5. Transaksi yang dicatat saat offline masuk ke server tanpa duplikat setelah koneksi pulih.
6. Selisih kas dan selisih opname dapat dilihat beserta alasan dan penggunanya.
7. Hasil paralel dua minggu: selisih total penjualan harian aplikasi vs buku dapat dijelaskan.

## 17. Keputusan yang Perlu Segera Diambil

1. Jawaban Raswan untuk A3, A4, dan A6 (menentukan apakah kasbon dan satuan ganda masuk MVP).
2. Stack final yang dipilih pembuat.
3. Ambang default hari kedaluwarsa dan hari stok menumpuk per kategori.
4. Siapa yang bertanggung jawab melakukan opname dan seberapa sering (usulan: mingguan untuk 20-30 produk teratas, bulanan untuk sisanya).
