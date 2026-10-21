# Servitory-PBL

## Mata Kuliah Kolaborator
- Desain dan Pemrograman Web
- Basis Data Lanjut
- Sistem Informasi Manajemen
- Manajemen Proyek

## Member:
- Agrisyandi Maraja Hutabarat - Shan0n73 - 254107020071
- Muhammad Anandito Palawarukka - MeAruka - 254107020220
- Muhammad Wasi' Nurul Ilmi - wasikwwasikm - 254107020135
- Rakha Madani Rizkullah - rakhamadani - 254107020073

## Menyiapkan database

Proyek menggunakan PostgreSQL. Jalankan `Halaman/sql/01_create_database.sql`
sebagai pengguna yang memiliki izin membuat database, lalu pilih database
`servitory` dan jalankan `Halaman/sql/02_schema.sql` untuk membuat tabel
pengguna dan laporan.

Untuk database yang sudah dibuat sebelum dukungan status penolakan laporan,
jalankan `Halaman/sql/04_tambah_status_ditolak.sql`. Data akun teknisi demo
dibuat lewat `Halaman/sql/03_seed_teknisi_dummy.sql`. Akun `teknisi01` sampai
`teknisi10` memakai password demo `Teknisi123!`; ganti password sebelum
menggunakan aplikasi di luar lingkungan lokal.

Konfigurasi koneksi PHP ada di `Halaman/includes/koneksi.php`. Sesuaikan host, port,
username, dan password dengan konfigurasi PostgreSQL lokal Anda.
