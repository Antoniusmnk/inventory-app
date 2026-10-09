# Inventory Management System (Mini Project)

Sistem Informasi Manajemen Inventaris Berbasis Web Menggunakan PHP Native dan PDO MySQL.

## Fitur Utama

- **Autentikasi & Otorisasi:** Login, Logout, Session Regeneration, serta Pembatasan Hak Akses (Role Admin & Staff).
- **Dashboard Ringkasan:** Menampilkan statistik total kategori, produk, supplier, serta peringatan (*early warning*) stok menipis.
- **Master Data Kategori:** Kelola data kategori barang (Khusus Admin).
- **Master Data Produk:** CRUD Produk, validasi harga & stok non-negatif, keunikan SKU, pencarian, filter kategori, dan pagination.
- **Transaksi & Histori Stok:** Pencatatan stok masuk/keluar dengan proteksi stok negatif (*negative stock prevention*), serta riwayat pergerakan stok terintegrasi.
- **Keamanan:** Prepared Statements (PDO) untuk pencegahan SQL Injection, serta Output Encoding (`e()`) untuk pencegahan XSS.

## Persyaratan Sistem

- PHP 8.0 atau versi terbaru
- MySQL / MariaDB
- Web Server (Apache via XAMPP atau PHP Built-in Server)

## Petunjuk Instalasi & Penggunaan (XAMPP)

1. **Clone / Salin Folder Project:**
   Pindahkan folder `inventory-app` ke dalam direktori `htdocs` XAMPP kamu:
   `C:/xampp/htdocs/inventory-app`

2. **Import Database:**
   - Buka phpMyAdmin (`http://localhost/phpmyadmin`).
   - Buat database baru bernama `inventory_db`.
   - Import file `schema.sql` yang berada di dalam folder project (`inventory-app/database/schema.sql`).

3. **Konfigurasi Database:**
   Sesuaikan kredensial koneksi database pada file `config/database.php` jika diperlukan (default: host `localhost`, dbname `inventory_db`, user `root`, password ``).

4. **Menjalankan Aplikasi:**
   - Jalankan Apache dan MySQL melalui XAMPP Control Panel.
   - Buka browser dan akses alamat:
     `http://localhost/inventory-app/public/login.php`

## Kredensial Pengujian (Default)

- **Admin:** Username `admin` | Password `admin123`
- **Staff:** Username `staff` | Password `staff123`
