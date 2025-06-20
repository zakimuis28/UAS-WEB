# Sistem Blog Artikel - Lapor Pak!

Aplikasi ini adalah sebuah sistem manajemen konten (CMS) atau blog sederhana yang dibangun secara native menggunakan PHP dan database MySQL. Proyek ini dibuat sebagai bagian dari tugas Ujian Akhir Semester (UAS) mata kuliah Pemrograman Web.

Sistem ini memungkinkan pengelolaan konten secara dinamis dengan tiga level pengguna yang berbeda (Pengunjung, Penulis, dan Admin), serta memiliki antarmuka yang modern dan responsif berkat Bootstrap 5.

## Fitur Utama

Sistem ini memiliki fitur yang dibedakan berdasarkan hak akses pengguna:

### 1. Pengunjung (Tidak Perlu Login)
- Melihat halaman utama (homepage) yang menampilkan 7 artikel terbaru beserta sidebar.
- Melihat halaman daftar semua artikel (`artikel.php`).
- Melihat daftar artikel berdasarkan kategori tertentu.
- Melihat daftar semua penulis yang terdaftar.
- Melakukan pencarian artikel.
- Melihat detail lengkap dari sebuah artikel.

### 2. Penulis (Memerlukan Login)
- Memiliki semua hak akses Pengunjung.
- Melakukan registrasi dan login ke dalam sistem.
- Mengakses halaman Dashboard personal.
- Menambah artikel baru.
- Mengedit dan menghapus **hanya artikel miliknya sendiri**.

### 3. Admin (Memerlukan Login)
- Memiliki semua hak akses Penulis.
- Mengelola (CRUD - Create, Read, Update, Delete) **semua artikel** dari semua penulis.
- Mengelola data **kategori** (tambah, edit, hapus).
- Mengelola data **penulis** (tambah, edit, hapus).
- Melihat statistik konten di halaman Dashboard.

## Struktur Folder Proyek
```
/Blog/
├── CRUD/
│   ├── Tambah.php      # (File multifungsi untuk menambah data)
│   ├── Edit.php        # (File multifungsi untuk mengedit data)
│   └── Hapus.php       # (File multifungsi untuk menghapus data)
├── images/             # (Untuk menyimpan gambar artikel & aset)
├── style.css           # (File CSS kustom untuk tema)
├── index.php           # (Halaman utama/dashboard multifungsi)
├── artikel.php         # (Halaman daftar semua artikel)
├── kategori.php        # (Halaman daftar artikel per kategori)
├── penulis.php         # (Halaman daftar penulis)
├── detail.php          # (Halaman detail artikel)
├── login.php
├── register.php
├── logout.php
└── koneksi.php         # (File konfigurasi koneksi database)
```

## Teknologi yang Digunakan
- **Backend:** PHP (Native)
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Framework & Library:**
    - Bootstrap 5
    - AOS (Animate On Scroll)
    - Chart.js

## Instalasi dan Konfigurasi
Untuk menjalankan proyek ini di lingkungan lokal (localhost), ikuti langkah-langkah berikut:

### Prasyarat
- Web Server (XAMPP, Laragon, dll.)
- PHP (versi 7.4 atau lebih baru direkomendasikan)
- MySQL atau MariaDB

### Langkah-langkah Instalasi
1.  **Clone atau Unduh Proyek**
    ```bash
    git clone [URL_REPOSITORY_ANDA]
    ```
    Atau unduh file ZIP dan ekstrak ke dalam folder `htdocs` (untuk XAMPP) atau `www` (untuk Laragon).

2.  **Database**
    - Buka **phpMyAdmin**.
    - Buat database baru dengan nama `dbcms`.
    - Impor file `dbcms.sql` yang ada di dalam repository ke dalam database yang baru Anda buat.

3.  **Konfigurasi Koneksi**
    - Buka file `koneksi.php`.
    - Sesuaikan `hostname`, `username`, `password`, dan `database name` jika diperlukan.
    ```php
    $conn = mysqli_connect('localhost', 'root', '', 'dbcms');
    ```

4.  **Jalankan Proyek**
    - Nyalakan Apache dan MySQL dari control panel XAMPP/Laragon Anda.
    - Buka browser dan akses `http://localhost/nama-folder-proyek/`.

## Developer

Proyek ini dibuat dan dikembangkan oleh:

- **Nama:** Muhammad Zaki Mu'is
- **Status:** Mahasiswa Teknik Informatika, UIN Maulana Malik Ibrahim Malang
- **GitHub:** [zakimuis28](https://github.com/zakimuis28)
