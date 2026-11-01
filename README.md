# SIMPROKON EVM — Sistem Informasi Manajemen Proyek Konstruksi
### Berbasis Web dengan Metode Earned Value Management (EVM)
### PT. Pesona Tamanindo | Proyek BXC-2 Bintaro Jaya

---

## 📦 Isi Paket Aplikasi

```
evm_proyek/
├── index.php                   ← Router utama
├── config.php                  ← Konfigurasi database & aplikasi
├── database/
│   └── evm_db.sql              ← Skema + data awal MySQL ★
├── includes/
│   ├── functions.php           ← Helper, EVM calculator, DB utils
│   └── layout/
│       ├── header.php          ← HTML head + topbar
│       ├── sidebar.php         ← Navigasi sidebar
│       └── footer.php          ← Script closing
├── pages/
│   ├── login.php               ← Halaman login
│   ├── logout.php              ← Handler logout
│   ├── dashboard.php           ← Dashboard utama EVM
│   ├── proyek/                 ← CRUD Proyek
│   │   ├── index.php
│   │   ├── create.php
│   │   └── edit.php
│   ├── wbs/                    ← CRUD WBS & Anggaran
│   │   ├── index.php
│   │   └── create.php
│   ├── progres/                ← Input Progres Fisik
│   │   ├── index.php
│   │   └── input.php
│   ├── biaya/                  ← Input Biaya Aktual
│   │   ├── index.php
│   │   └── input.php
│   ├── evm/                    ← Analisis EVM lengkap
│   │   └── index.php
│   ├── laporan/                ← Laporan print-ready
│   │   └── index.php
│   └── users/                  ← Manajemen Pengguna
│       ├── index.php
│       └── create.php
└── assets/
    ├── css/style.css           ← Stylesheet utama
    └── js/app.js               ← JavaScript utama
```

---

## 🚀 CARA INSTALASI (XAMPP / Laragon)

### Langkah 1 — Siapkan Folder
```
1. Salin folder evm_proyek/ ke:
   XAMPP  : C:\xampp\htdocs\evm_proyek\
   Laragon: C:\laragon\www\evm_proyek\
```

### Langkah 2 — Import Database
```
1. Buka phpMyAdmin: http://localhost/phpmyadmin
2. Klik "New" → buat database bernama: evm_db
3. Pilih database evm_db → tab "Import"
4. Pilih file: database/evm_db.sql → klik "Import"
```

### Langkah 3 — Konfigurasi (opsional)
```
Buka config.php, sesuaikan jika perlu:

define('DB_HOST', 'localhost');    // host MySQL
define('DB_NAME', 'evm_db');      // nama database
define('DB_USER', 'root');        // username MySQL
define('DB_PASS', '');            // password (kosong = XAMPP default)
define('APP_URL',  'http://localhost/evm_proyek');  // URL aplikasi
```

### Langkah 4 — Jalankan Aplikasi
```
Buka browser: http://localhost/evm_proyek
```

---

## 🔐 AKUN LOGIN DEMO

| Role              | Username    | Password    |
|-------------------|-------------|-------------|
| Administrator     | admin       | password123 |
| Manajer Proyek    | manager     | password123 |
| Staf Lapangan     | lapangan    | password123 |
| Staf Keuangan     | keuangan    | password123 |

---

## 🗂️ MODUL & FITUR

| Modul              | Fitur                                                    | Role             |
|--------------------|----------------------------------------------------------|------------------|
| Dashboard EVM      | KPI cards, Kurva-S, progress bar, tabel WBS              | Semua            |
| Data Proyek        | CRUD proyek, informasi kontrak & jadwal                  | Admin, Manager   |
| WBS & Anggaran     | CRUD paket pekerjaan, bobot %, anggaran per WBS          | Admin, Manager   |
| Input Progres      | Batch input % progres per WBS per minggu                 | Admin, Manager, Lapangan |
| Input Biaya Aktual | Batch input biaya aktual per WBS per periode             | Admin, Manager, Keuangan |
| Analisis EVM       | Tabel EVM lengkap, kurva-S, tren SPI/CPI, historis      | Admin, Manager   |
| Laporan            | Laporan print-ready, tanda tangan, format A4             | Admin, Manager   |
| Manajemen User     | CRUD pengguna, role RBAC, toggle status                  | Admin            |

---

## 📐 RUMUS EVM YANG DIIMPLEMENTASIKAN

```
PV  = (% Bobot Rencana Kumulatif) × BAC
EV  = (% Realisasi Kumulatif) × BAC  
AC  = Σ Biaya Aktual s.d. periode

SV  = EV - PV          (Schedule Variance)
CV  = EV - AC          (Cost Variance)
SPI = EV / PV          (Schedule Performance Index)
CPI = EV / AC          (Cost Performance Index)
EAC = BAC / CPI        (Estimate at Completion)
ETC = EAC - AC         (Estimate to Complete)
VAC = BAC - EAC        (Variance at Completion)
TCPI= (BAC-EV)/(BAC-AC)(To-Complete Performance Index)
```

---

## 🛠️ TEKNOLOGI

- **Backend**  : PHP 7.4+ (Pure PHP, tanpa framework)
- **Database** : MySQL 5.7+ / MariaDB 10.x (InnoDB)
- **Frontend** : Bootstrap 5.3, Bootstrap Icons 1.11
- **Charts**   : Chart.js 4.4
- **Fonts**    : Plus Jakarta Sans (Google Fonts)
- **Server**   : XAMPP 8.x / Laragon

---

## 📊 DATA PROYEK TERSEMAT

Aplikasi dilengkapi data aktual dari:
- **BQ Landscape BXC-2 Mall & Hotel** — 22 item WBS lengkap
- **Monitoring Progress Minggu 108** — 14 periode historis (Feb 2022 - Jan 2024)
- BAC Total: Rp 24.800.000.000 | Nilai Kontrak (incl. PPN): Rp 27.280.000.000

---

## 📝 UNTUK KEPERLUAN SKRIPSI

Sistem ini memenuhi:
✅ Modul Manajemen Proyek & WBS (CRUD)
✅ Input Progres Fisik Mingguan
✅ Input Biaya Aktual
✅ Perhitungan EVM Otomatis (PV, EV, AC, SV, CV, SPI, CPI, EAC, ETC, VAC, TCPI)
✅ Dashboard Interaktif dengan Kurva-S (Chart.js)
✅ Role-Based Access Control (4 aktor)
✅ Laporan Print-Ready
✅ Data Aktual PT. Pesona Tamanindo Proyek BXC-2
✅ Black Box Testing Ready (fungsionalitas terpisah per modul)

---

*Dikembangkan untuk Skripsi: "Rancang Bangun Sistem Informasi Manajemen Proyek Kontraktor*
*Berbasis Web Menggunakan Metode EVM di PT. Pesona Tamanindo"*
