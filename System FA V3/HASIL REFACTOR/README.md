# SISTEM FINAL ASSEMBLY & TRACEABILITY P9 (HASIL REFACTOR)

Dokumen ini merupakan panduan lengkap arsitektur, instalasi, dan pengoperasian sistem hasil refaktor dari monolithic Python/SQLite ke **Laravel 11 (MVC Backend) + Vue.js 3 (Frontend SPA) + MySQL 8.0 (InnoDB Database)**.

---

## 1. Masalah Utama yang Telah Diselesaikan
1. **Pemberantasan Bottleneck Multi-User Concurrency**:
   - Sistem lama menggunakan `ThreadingHTTPServer` dengan lock global Python `_db_lock = threading.Lock()` dan file-level lock SQLite (`database.db`). Saat 5–10 operator menginput data bersamaan, server langsung freeze/hang (*database is locked*).
   - Sistem refaktor menggunakan **Laravel + MySQL 8.0 InnoDB** dengan *Row-Level Locking* (`lockForUpdate()`) dan *ACID Transactions*, menjamin zero downtime dan mampu menangani puluhan operator secara simultan tanpa freeze.
2. **Penghapusan Pembatasan Kuota Stok (Un-capped 18.000+ Items)**:
   - Sistem lama memotong data dengan `limit=1500` / `limit=5000`.
   - Sistem baru mengindeks kolom `part_type, status, allocated_tray_id` secara komposit, memungkinkan seluruh 18.000+ item master stok (Part 1, Part 12, Part 15) diakses secara instan dengan navigasi bersih.
3. **Penyempurnaan Alur TF 1 vs TF 2 (Quality Control)**:
   - **TF 1 (Meja Perakitan)**: Menjalankan dan menginput hasil pengujian checklist 17 parameter teknis.
   - **TF 2 (Quality Control)**: **TIDAK ADA 17 parameter checklist di QC**. Inspector QC melihat hasil TF 1 dalam mode *read-only* dan hanya memberikan keputusan satu klik: **Good** atau **Not Good (Hold)**.
4. **Pembersihan Rute Duplikat & Dead Code**:
   - Menghilangkan rute ganda (`/api/delivery-orders/export-data`, `/api/assemblies/pending-reassembly`, dll.).
   - Menghilangkan dead code di frontend (fungsi duplikat `getStatusColorConfig`, `pad()`, fungsi ZPL printer yang tidak terikat, dll.).
5. **Portal Pemeliharaan & Diagnostik Terpusat (`/admin`)**:
   - **3 Fitur Pembersihan Database**:
     1. Reset Database dari Semua Tray & Pulihkan 18.000 Stok Master ke status `'Tersedia'`.
     2. Bersihkan Riwayat Jeda / Downtime Operator.
     3. Bersihkan Seluruh Riwayat Audit Trail Sistem.
   - **Pengaturan Global Delivery Order & Dokumen**: Konfigurasi terpusat data pengirim, penerima, dan penandatangan.
   - **Backend Error Logger**: Realtime exception logger otomatis menangkap error tanpa membuat browser pengguna crash.
   - **Slow Query & Lag Monitor (>500ms)**: Pelacak query database lambat otomatis mencatat SQL query yang memakan waktu >500ms.
   - **Pemindai Anomali & 1-Click Auto Repair**: Pemindai orphan parts, sesi menggantung >8 jam, desinkronisasi status stok, dan duplikasi part dengan tombol pemulihan otomatis sekali klik.

---

## 2. Struktur Direktori `HASIL REFACTOR/`

```
HASIL REFACTOR/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminCmsController.php       # Portal /admin: 3 alat reset & pengaturan DO
│   │   │   ├── AssemblyController.php       # Workstation, TF1 17 checklist, downtime
│   │   │   ├── AuthController.php           # Autentikasi token, RBAC, sesi pengguna
│   │   │   ├── Controller.php               # Base JSON response helpers
│   │   │   ├── DeliveryOrderController.php  # DO, Packing List, Box, Cloudinary PDF
│   │   │   ├── DiagnosticController.php     # Error Logger, Slow Query & Anomaly Scanner
│   │   │   ├── MasterStockController.php    # Stok 18.000+ un-capped, summary, part detail
│   │   │   ├── QcController.php             # TF2 QC: Keputusan Good / Not Good sekali klik
│   │   │   ├── ReportController.php         # 4 Laporan: WO Traveler, TF1, TF2 QC, Gudang
│   │   │   └── WarehouseController.php      # Supply 22 set, verifikasi fisik, penerimaan QC
│   │   └── Middleware/
│   │       ├── DeveloperAdminMiddleware.php
│   │       └── RoleMiddleware.php
│   ├── Models/                              # Eloquent Models (User, MasterStock, Tray, dll.)
│   ├── Providers/
│   │   └── AppServiceProvider.php           # DB::listen Slow Query Logger (>500ms)
│   └── Services/
│       ├── AnomalyDetectionService.php      # Scanner & 1-Click Auto Repair Engine
│       ├── CloudinaryService.php            # Upload & manajemen Cloud PDF DO
│       └── DatabaseMaintenanceService.php   # Logika reset database & pemulihan 18.000 stok
├── bootstrap/
│   ├── app.php                              # Exception reporter & route configuration
│   └── providers.php
├── database/
│   ├── migrations/                          # Skema relasional MySQL InnoDB lengkap
│   └── seeders/
│       ├── DatabaseSeeder.php               # Seeder utama
│       ├── DoSettingSeeder.php              # Default Dokumen Pengiriman
│       ├── MasterStockSeeder.php            # Seeder 16.921 master stok nyata
│       ├── TestingPointSeeder.php           # 17 Checklist Parameter TF 1
│       ├── UserSeeder.php                   # Akun pengguna resmi perusahaan
│       └── data/
│           └── master_stocks.sql            # Dump 16.921 baris master stock dari database.db
├── public/                                  # Aset statis (logo, emblem, exceljs, html2pdf, qrcode)
│   ├── index.php                            # Entrypoint Laravel
│   ├── login.html                           # Halaman login standalone
│   └── ...
├── resources/
│   └── views/
│       └── app.blade.php                    # Frontend Vue 3 SPA (100% UI/UX Identik)
├── routes/
│   ├── api.php                              # Seluruh endpoint REST API bersih
│   ├── console.php
│   └── web.php                              # Entrypoint SPA & /admin
├── .env.example                             # Template konfigurasi environment
├── composer.json                            # Dependensi PHP / Laravel
└── package.json                             # Dependensi Vite / Frontend
```

---

## 3. Langkah Instalasi & Menjalankan Sistem

### Prasyarat
- **PHP** >= 8.2 (dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `curl`)
- **Composer**
- **MySQL Server** >= 8.0 (XAMPP, Laragon, Docker, atau MySQL Server terdedikasi)

### Langkah-langkah
1. **Masuk ke folder refactor**:
   ```bash
   cd "HASIL REFACTOR"
   ```

2. **Pasang Dependensi Composer**:
   ```bash
   composer install
   ```

3. **Konfigurasi Database `.env`**:
   Salin `.env.example` menjadi `.env` (sudah tersedia):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_sistem_fa
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   *Pastikan database `db_sistem_fa` sudah dibuat di MySQL (`CREATE DATABASE db_sistem_fa;`).*

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Jalankan Migrasi & Database Seeder**:
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Perintah ini akan secara otomatis membuat seluruh tabel relasional, menyuntikkan 17 parameter checklist TF1, akun pengguna resmi, dan mengimpor seluruh 16.921 data master stock nyata dari `master_stocks.sql`.*

6. **Jalankan Web Server**:
   ```bash
   php artisan serve --port=8000
   ```

7. **Akses Sistem**:
   - Aplikasi Operasional: `http://localhost:8000/`
   - Portal Khusus Admin CMS & Diagnostik: `http://localhost:8000/admin`
   - Halaman Login: `http://localhost:8000/login.html`

---

## 4. Akun Default Pengguna

| Username | Password | Role | Hak Akses |
|---|---|---|---|
| **admin** | `admin123` | super_admin | Seluruh modul sistem + Portal CMS & Diagnostik `/admin` |
| **miranti** | `miranti123` | super_admin | Seluruh modul sistem + Portal CMS & Diagnostik `/admin` |
| **yasin** | `yasin123` | admin | Verifikasi Admin, Master Data, Kelola Pengguna |
| **gudang** | `gudang123` | gudang | Alokasi Supply 22 Set Part, Verifikasi Fisik, Penerimaan QC |
| **assembly** | `assembly123` | assembly | Workstation Perakitan, Uji Fungsional 1 (TF 1), Downtime |
| **qc** | `qc123` | qc | Quality Control (TF 2): Keputusan Good / Not Good |

---

## 5. Fitur Baru pada Portal `/admin`

Saat login sebagai admin dan membuka `http://localhost:8000/admin`, Anda akan disambut oleh **Portal CMS & Diagnostik Server**:
1. **Alat Pembersihan Database**:
   - Tombol **Reset Tray & Pulihkan 18.000 Stok**: Mengosongkan data batch berjalan dan mereset status part menjadi 'Tersedia'.
   - Tombol **Bersihkan Riwayat Downtime**: Menghapus catatan jeda workstation.
   - Tombol **Bersihkan Seluruh Audit Log**: Mengosongkan riwayat audit trail.
2. **Backend Error Logger**:
   - Menampilkan realtime exception log tanpa membuat frontend crash. Tombol 1-Click Clear tersedia.
3. **Slow Query & Lag Monitor (>500ms)**:
   - Menangkap query lambat yang berpotensi menyebabkan lag. Menampilkan durasi dalam milidetik (ms) dan teks query SQL.
4. **Pemindai Anomali & 1-Click Auto Repair**:
   - Mendeteksi *orphan parts*, sesi menggantung (>8 jam), ketidaksinkronan status stok, dan duplikasi nomor seri part aktif.
   - Tombol **1-Click Auto Repair** memperbaiki seluruh integritas database secara otomatis dengan sekali klik.
5. **Pengaturan Global Delivery Order**:
   - Memperbarui identitas penandatangan, pengirim, penerima, dan nomor dokumen secara terpusat.
