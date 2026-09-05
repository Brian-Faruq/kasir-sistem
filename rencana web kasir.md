\# 🛒 POS-UMKM: Sistem Kasir \& Manajemen Stok Warung / Toko Lokal



Sistem Kasir (Point of Sale) dan Manajemen Inventaris berbasis Web yang dirancang khusus untuk memenuhi kebutuhan operasional UMKM, warung kelontong, dan toko retail lokal. 



Aplikasi ini hadir sebagai solusi praktis, ringan, dan ekonomis (\*one-time payment\*) tanpa beban biaya langganan bulanan.



\---



\## 📊 Latar Belakang \& Masalah (Data-Driven)



Berdasarkan data studi UMKM dari Kementerian Koperasi dan UKM:

\* \*\*70% UMKM toko kelontong/retail lokal\*\* mengalami kegagalan pengelolaan finansial karena tidak memiliki pencatatan stok otomatis dan tidak mengetahui keuntungan bersih harian secara akurat.

\* \*\*Tinggi Biaya Operasional:\*\* Aplikasi kasir komersial skala besar menetapkan skema sewa bulanan yang memberatkan pengusaha skala mikro-kecil.

\* \*\*Ketergantungan Internet:\*\* Banyak sistem \*cloud-based\* tidak dapat digunakan saat jaringan internet lokal mengalami kendala.



\*\*Solusi POS-UMKM:\*\*

Aplikasi web berbasis \*\*PHP \& MySQL\*\* yang dapat dijalankan secara \*offline\* maupun \*local network (LAN/Wi-Fi)\*, sekali beli (\*one-time purchase\*), serta mudah digunakan dari perangkat \*smartphone\*, tablet, maupun komputer.



\---



\## ✨ Fitur Utama (Core Features)



\### 1. Transaksi Kasir (Point of Sale)

\* \*\*Pencarian Cepat:\*\* Cari barang berdasarkan nama atau pemindaian \*barcode\*.

\* \*\*Scan Barcode Camera:\*\* Pemindaian \*barcode\* barang langsung menggunakan kamera HP/Laptop tanpa alat \*scanner\* fisik tambahan.

\* \*\*Keranjang Interaktif:\*\* Tambah/kurang kuantitas barang dengan sekali klik.

\* \*\*Kalkulator Kembalian:\*\* Perhitungan total dan nominal kembalian secara otomatis.

\* \*\*Kwitansi / Struk:\*\* Cetak struk via printer thermal Bluetooth/USB atau bagikan Struk Digital (PDF/WhatsApp).



\### 2. Inventaris \& Manajemen Stok

\* \*\*Management Produk:\*\* Kelola data barang (Nama, Kategori, Harga Beli/HPP, Harga Jual, Stok).

\* \*\*Low Stock Alert:\*\* Peringatan visual otomatis jika stok barang berada pada ambang batas kritis ($\\le 3$).

\* \*\*Kategori Produk:\*\* Pengelompokan barang untuk mempermudah pencarian.



\---



\## 🚀 Fitur Lanjutan (Extended Features)



\* \*\*Manajemen Utang / Bon Pelanggan:\*\* Pencatatan transaksi belum lunas beserta tanggal jatuh tempo dan nama pelanggan.

\* \*\*Sistem Poin Member (Customer Loyalty):\*\* Pengumpulan poin dari setiap transaksi yang dapat ditukarkan menjadi potongan harga.

\* \*\*Multi-User (Role-Based Access Control):\*\*

&#x20; \* \*\*Owner/Admin:\*\* Akses penuh ke laporan keuangan, modal, edit harga, dan pengeluaran.

&#x20; \* \*\*Kasir:\*\* Akses terbatas hanya untuk melayani transaksi penjualan harian.

\* \*\*Multi-Payment Methods:\*\* Pencatatan pembayaran Tunai (Cash), QRIS, maupun Transfer Bank.



\---



\## 🗄️ Skema Database (MySQL)



Sistem ini didukung oleh 7 tabel utama yang efisien:



1\. `users` — Menampung akun Owner dan Kasir (RBAC).

2\. `categories` — Pengelompokan jenis produk.

3\. `products` — Data detail barang, stok, HPP, dan harga jual.

4\. `customers` — Data pelanggan, riwayat bon/utang, dan poin loyalty.

5\. `transactions` — Header transaksi (Total bayar, metode pembayaran, kasir).

6\. `transaction\_details` — Item/rincian produk yang dibeli per transaksi.

7\. `expenses` — Catatan pengeluaran operasional toko.



\---



\## 🛠️ Spesifikasi Teknologi (Tech Stack)



\* \*\*Backend:\*\* PHP 8.x

\* \*\*Database:\*\* MySQL / MariaDB

\* \*\*Frontend:\*\* HTML5, CSS3, JavaScript (Bootstrap / Tailwind CSS)

\* \*\*Server Environment:\*\* Apache / Nginx (XAMPP / Localhost / Cloud Hosting)



\---



\## 💡 Keunggulan Komersial untuk UMKM



1\. \*\*Tanpa Biaya Langganan:\*\* Beli sekali, pakai selamanya.

2\. \*\*Bisa Offline (LAN Network):\*\* Tetap berjalan normal meski internet padam.

3\. \*\*Ringan \& Responsif:\*\* Kompatibel dengan HP Android murah maupun laptop lama.

