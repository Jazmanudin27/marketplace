# ERP Marketplace - Agent Rules & Guidelines (system_prompt.md)

## 1. Project Architecture & Directory Mapping
Proyek ini adalah sistem ERP Marketplace berbasis **Laravel 12** (PHP 8.2+) untuk sinkronisasi inventaris, transaksi, settlement, dan keuangan multi-marketplace (TikTok Shop, Shopee, Tokopedia, dll.).

Pemetaan folder utama:
- `app/Http/Controllers/` : Handler request HTTP, memproses masukan pengguna, dan merespons view/JSON.
- `app/Models/`            : Model Eloquent ORM, relasi tabel database, cast, dan query scopes.
- `app/Services/`          : Service layer untuk logika bisnis utama (penghitungan settlement/escrow, kalkulasi stok, dan API wrapper marketplace).
- `app/Jobs/`              : Queue Jobs async untuk sinkronisasi massal dan pemrosesan latar belakang.
- `app/Console/`           : Artisan Console Commands & scheduler task.
- `routes/web.php`         : Pendaftaran rute web & endpoint aplikasi.
- `routes/console.php`     : Registrasi scheduled cron & custom CLI commands.
- `resources/views/`       : Blade View Templates terorganisir per modul (`orders/`, `inventory/`, `finance/`, `marketplace/`, `reports/`, `dashboard/`, `master/`, `fulfillment/`, `returns/`, `settings/`).
- `resources/css/` & `js/` : Styling Tailwind CSS v4 & asset JavaScript/React yang di-compile menggunakan Vite.
- `config/`                : Konfigurasi Laravel dan variabel environment marketplace.
- `database/`              : Migrasi skema database (`migrations/`), seeders (`seeders/`), dan factories.

---

## 2. Tech Stack & Conventions
- **Backend Core**: Laravel 12 (PHP ^8.2)
- **Database & ORM**: Eloquent ORM (Utamakan Eager Loading `with()` untuk mencegah N+1 Query).
- **Role & Permission**: Spatie Laravel-Permission (`spatie/laravel-permission`).
- **PDF Generation**: FPDF (`fpdf/fpdf`) & FPDI (`setasign/fpdi`).
- **Frontend**: Blade Views, Tailwind CSS v4 (`@tailwindcss/vite`), Vite (`laravel-vite-plugin`), Axios, React 19.
- **Standar Kode**:
  - **PHP PSR-12**: Class (PascalCase), Method/Properti (camelCase), DB/Tabel (snake_case).
  - **Service Pattern**: Pindahkan logika bisnis berukuran sedang/besar dari Controller ke Service Class (`app/Services/`).
  - **Blade Reusability**: Pakai `@include`, `@extends`, `@component`, atau Blade Components untuk modul UI.

---

## 3. Strict Agent Execution Rules (Token Efficiency & Performance)

1. **Fokus File Langsung (`@file` / Tab Aktif)**:
   - Kerjakan tugas secara LANGSUNG pada file yang disebutkan (`@file`) atau tab yang sedang terbuka.
   - **DILARANG** melakukan pencarian luas (`search`/`grep` ke seluruh direktori) atau membaca file lain jika tidak relevan/tidak diminta secara eksplisit.

2. **To-The-Point & Code Snippets Only**:
   - Jangan pernah mencetak/menulis ulang seluruh isi file jika hanya melakukan perubahan/revisi kecil.
   - Cukup tampilkan bagian kode yang diubah (snippet baris yang dimodifikasi beserta konteks atas/bawah 2-3 baris).

3. **Respons Ringkas & Tanpa Basa-Basi**:
   - Berikan penjelasan perubahan secara singkat, lurus pada poinnya, dan langsung ke solusi tanpa penjelasan latar belakang berlebihan.

4. **Preserve Existing Code**:
   - Pertahankan skema database, signature fungsi, serta komentar bawaan proyek yang tidak berkaitan dengan perubahan.
