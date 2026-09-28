# ASPARTECH ERP Marketplace - V2 (React JS & Node API)

Aplikasi **V2** adalah sistem ERP Marketplace generasi baru yang dibuat **100% mandiri dan terpisah dari Laravel**. Dibangun menggunakan **React JS (Single Page Application)** untuk frontend dan **Node.js (Express + MySQL)** untuk backend REST API super ringan & cepat.

---

## 📁 Struktur Folder & Arsitektur

Berikut adalah struktur file dan folder yang sudah dirapikan secara modular agar mudah dipahami, dikembangkan, dan dipelihara oleh setiap programmer:

```text
v2/
├── .env                  # Konfigurasi Environment (Port & MySQL)
├── .env.example          # Template konfigurasi environment
├── package.json          # Dependencies & script npm
├── vite.config.js        # Konfigurasi bundler Vite
├── server.js             # Entrypoint server (mengarahkan ke server/index.js)
│
├── server/               # 🚀 BACKEND NODE.JS & REST API
│   ├── index.js          # Inisialisasi Express & serve static frontend
│   ├── config/
│   │   └── database.js   # Koneksi MySQL Connection Pool (dengan auto-fallback lokal)
│   ├── controllers/      # Logika pemrosesan database & API
│   │   ├── dashboardController.js # Handler statistik & pesanan terbaru
│   │   └── produkController.js    # Handler katalog master produk & pencarian
│   └── routes/
│       └── api.js        # Definisi rute endpoint REST API (/api/...)
│
├── src/                  # ⚛️ FRONTEND REACT JS (SPA)
│   ├── main.jsx          # Entrypoint React DOM
│   ├── App.jsx           # Routing & Layout Shell (React Router)
│   ├── index.css         # Custom CSS Design System (Compact, table-sm, btn-sm)
│   ├── components/       # Komponen UI Reusable
│   │   ├── Header.jsx    # Topbar compact (Jam realtime, notifikasi, tenant badge)
│   │   └── Sidebar.jsx   # Navigasi compact navy (#0b132b)
│   ├── pages/            # Halaman Antarmuka (Views)
│   │   ├── Dashboard.jsx # Overview omset, statistik channel & recent orders
│   │   └── Produk.jsx    # Data master produk compact dengan aksi edit/hapus
│   └── services/
│       └── api.js        # Client Axios untuk fetch data ke API backend
│
└── dist/                 # Hasil build produksi React (otomatis digenerate Vite)
```

---

## ⚙️ Konfigurasi Environment (`.env`)

Buat file `.env` di dalam folder `v2/` dengan konfigurasi database MySQL Anda:

```env
PORT=5008
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=marketplace
DB_PASSWORD=Jazman@271998
DB_NAME=marketplace
```

> **Catatan Smart Fallback:** Jika dijalankan di komputer developer lokal yang menggunakan user `root` tanpa password, server otomatis menggunakan kredensial root jika kredensial utama ditolak.

---

## 🚀 Cara Menjalankan

### 1. Mode Produksi (Fullstack Terintegrasi di Port 5008)
Frontend React dan Backend API berjalan bersamaan di port yang sama:
```bash
cd v2
npm run build     # Compile React ke folder dist/
npm start         # Menjalankan server Node.js di http://127.0.0.1:5008
```
Akses di browser: 👉 **`http://localhost:5008`**

### 2. Mode Pengembangan Frontend (Vite Dev Server)
Jika ingin mengembangkan tampilan React dengan fitur Hot-Reloading cepat:
```bash
cd v2
npm run dev
```

---

## 📡 Daftar REST API Endpoints

Semua API mengembalikan respon JSON berstandar `{ success: true, data: { ... } }`:

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/api/dashboard` | Statistik total omset, order, breakdown channel (Shopee/TikTok/Lazada), dan 10 order terbaru |
| `GET` | `/api/produk` | Daftar data master produk (mendukung query parameter `?search=keyword&page=1&limit=15`) |

---

## 🎨 Panduan Desain UI (Compact Dense System)

Aplikasi V2 mengadopsi standar **High Density / Compact UI** yang mengutamakan kecepatan kerja operasional:
- Ukuran teks utama: `0.78rem - 0.8rem`
- Tabel data: `.table-sm`, header abu-abu terang (`#f8fafc`), padding ramping.
- Tombol aksi: Kotak compact `26px x 26px` (biru untuk edit, merah untuk hapus).
- Sidebar: Warna navy pekat (`#0b132b`) dengan badge institusi/tenant di bagian atas.
