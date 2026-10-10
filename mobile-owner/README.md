# ASPARTECH ERP - Mobile Owner Portal (React JS)

Portal Mobile khusus Business Owner / Super Admin ERP Marketplace, dirancang berbasis React JS + Vite dengan arsitektur UI mobile modern yang mengacu pada referensi dashboard Android.

---

## 📱 Panduan Tampilan & Fitur

1. **Header Gradient Night Sky**
   - Brand Header: `ASPARTECH` + badge `OWNER PRO`.
   - Tombol Aksi: Status API/Sinkronisasi Toko, Notifikasi Masuk, Logout.
   - Profile Owner: Avatar inisial, status online, live digital clock real-time (`WIB`).

2. **4 Quick Status Squircle Cards (Pengganti Hadir/Sakit/Izin/Cuti)**:
   - 🟢 **Pesanan Baru**
   - 🟠 **Perlu Dikirim**
   - 🔴 **Komplain / Retur**
   - 🔵 **Selesai Hari Ini**

3. **2 Highlight Cards (Pengganti Absen Masuk & Pulang)**:
   - 🟢 **Omset Hari Ini & Margin Bersih**
   - 🔴 **Target Komisi & Pencapaian Tim (%)**

4. **12 Squircle App Launcher Grid (Khusus Owner)**:
   - Pesanan Masuk, Target Komisi, Rekap Margin, Mutasi Kas, Gudang Ready, SPK Produksi, Toko Online, Scanner Gudang, Pelanggan, Laporan Laba, Performa Tim, Pengaturan.

5. **Tabel / Rincian Transaksi Ringan (API Ready)**:
   - Menampilkan invoice, toko marketplace (Shopee, TikTok, Tokopedia, POS), omset dilepas, HPP modal, margin bersih, dan komisi tim.
   - Klik kartu transaksi otomatis membuka **Modal Bottom-Sheet Detail Pesanan**.

6. **Floating Bottom Navigation Dock**:
   - 🏠 Beranda
   - 📦 Pesanan
   - ⚡ Tombol Tengah Floating (Scanner Cepat)
   - 💳 Keuangan
   - 👤 Profil

---

## 📁 Struktur File & Kode

Struktur project dibuat modular dan terpisah rapi agar mudah dipelihara oleh programmer:

```text
mobile-owner/
├── src/
│   ├── api/
│   │   ├── ordersApi.js          # API service untuk fetch pesanan & rincian order
│   │   ├── metricsApi.js         # API service untuk KPI omset, margin, target komisi
│   │   └── mockData.js           # Mock dataset realistis sesuai schema DB ERP
│   │
│   ├── components/
│   │   ├── layout/
│   │   │   ├── Header.jsx        # Header gradient, profile card, live clock
│   │   │   └── BottomNav.jsx     # Floating bottom navigation dock + center FAB
│   │   ├── dashboard/
│   │   │   ├── QuickStatusGrid.jsx  # 4 squircle card status pesanan
│   │   │   ├── HighlightBanners.jsx # 2 wide action banner keuangan
│   │   │   └── OwnerMenuGrid.jsx    # 12 squircle menu khusus owner
│   │   └── orders/
│   │       ├── OrderCard.jsx        # Kartu transaksi mobile dengan channel badge
│   │       └── OrderDetailModal.jsx # Bottom-sheet modal detail rincian pesanan
│   │
│   ├── pages/
│   │   ├── DashboardPage.jsx     # Halaman utama beranda mobile
│   │   ├── OrdersPage.jsx        # Halaman filter & daftar pesanan lengkap
│   │   ├── FinancePage.jsx       # Halaman ringkasan kas & laba rugi
│   │   └── ProfilePage.jsx       # Halaman profil owner & channel marketplace
│   │
│   ├── utils/
│   │   └── formatters.js         # Format rupiah, tanggal Indonesia, badge toko
│   │
│   ├── App.jsx                   # Orchestrator state tab & modal
│   ├── index.css                 # Design system CSS mobile & squircle tokens
│   └── main.jsx                  # Entry point React
│
├── package.json
└── vite.config.js
```

---

## 🚀 Cara Menjalankan

Masuk ke folder `mobile-owner`:

```bash
cd mobile-owner
npm install
npm run dev
```

Untuk build file bundle production:
```bash
npm run build
```

---

## 🔌 Integrasi API Laravel (Opsional / Siap Pakai)

Buat file `.env` di folder `mobile-owner`:
```env
VITE_API_BASE_URL=https://erp.aspartech.com/api/v2
VITE_USE_LIVE_API=false # Ubah true jika endpoint API backend Laravel sudah terpasang
```
Jika `VITE_USE_LIVE_API=false`, aplikasi otomatis menggunakan dataset internal yang sudah mencerminkan database asli ERP Marketplace.
