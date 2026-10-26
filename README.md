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

Koneksi PHP menggunakan PDO PostgreSQL dan dikonfigurasi melalui environment
server PHP:

| Environment variable | Default lokal |
| --- | --- |
| `SERVITORY_DB_HOST` | `localhost` |
| `SERVITORY_DB_PORT` | `5432` |
| `SERVITORY_DB_NAME` | `servitory` |
| `SERVITORY_DB_USER` | `postgres` |
| `SERVITORY_DB_PASSWORD` | `postgres` |

Atur variabel tersebut di environment yang digunakan server PHP sebelum
menjalankan aplikasi. Contoh untuk PowerShell:

```powershell
$env:SERVITORY_DB_HOST = "localhost"
$env:SERVITORY_DB_PORT = "5432"
$env:SERVITORY_DB_NAME = "servitory"
$env:SERVITORY_DB_USER = "postgres"
$env:SERVITORY_DB_PASSWORD = "password-postgres-Anda"
```

Nilai default hanya untuk pengembangan lokal; gunakan password yang kuat dan
jangan gunakan kredensial default di lingkungan produksi. Jika koneksi gagal,
detail teknis dicatat di log server PHP dan aplikasi menampilkan pesan umum.
