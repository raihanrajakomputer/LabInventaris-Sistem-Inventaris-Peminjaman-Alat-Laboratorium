# LabInventaris — Sistem Inventaris & Peminjaman Alat Laboratorium

Aplikasi web berbasis **Laravel** dan **MySQL** untuk mengelola inventaris peralatan laboratorium serta transaksi peminjaman dan pengembalian alat secara terstruktur.

## Fitur Utama
* **Autentikasi Multi-Role:**
  * **Peminjam (Mahasiswa):** Melihat katalog alat, mengajukan peminjaman baru, dan memantau riwayat peminjaman pribadi.
  * **Petugas / Asisten Lab:** Mengelola data master (Alat, Laboratorium, Peminjam, Petugas), memantau seluruh transaksi, serta memproses pengembalian alat.
* **Manajemen Stok Otomatis:** Pengurangan stok saat peminjaman dan pemulihan stok saat pengembalian ditangani langsung oleh *Trigger* di tingkat database MySQL.
* **Laporan Terintegrasi:** Menggunakan *Database View* (`v_daftar_alat` dan `v_laporan_peminjaman`) untuk penyajian data inventaris dan riwayat transaksi.

## Prasyarat
* PHP >= 8.1
* Composer
* MySQL / MariaDB (Laragon atau XAMPP)

## Cara Instalasi

1. **Clone repository**
   ```bash
   git clone [https://github.com/username/inventaris-lab.git](https://github.com/username/inventaris-lab.git)
   cd inventaris-lab
