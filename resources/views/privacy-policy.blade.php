<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kebijakan Privasi ERP Marketplace - Komitmen kami dalam melindungi data Anda">
    <title>Kebijakan Privasi | ERP Marketplace</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-100: #f1f5f9;
            --bg-light: #f8fafc;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            --font-outfit: 'Outfit', sans-serif;
            --font-inter: 'Inter', sans-serif;
        }

        * {
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-light);
            font-family: var(--font-inter);
            color: var(--slate-800);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        header.navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 14px 24px;
        }

        .nav-container {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-logo {
            font-family: var(--font-outfit);
            font-weight: 800;
            font-size: 20px;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-logo i {
            color: var(--primary);
        }

        .back-btn {
            background: var(--slate-100);
            color: var(--slate-800);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .back-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Main Layout */
        .container {
            max-width: 1100px;
            margin: 40px auto 80px;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }

        @media (max-width: 900px) {
            .container {
                grid-template-columns: 1fr;
                margin-top: 24px;
            }

            .sidebar {
                display: none;
            }
        }

        /* Sidebar Table of Contents */
        .sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .toc-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .toc-title {
            font-family: var(--font-outfit);
            font-weight: 700;
            font-size: 16px;
            color: var(--dark);
            margin-top: 0;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--slate-100);
        }

        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .toc-link {
            color: var(--slate-600);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: block;
        }

        .toc-link:hover {
            color: var(--primary);
            padding-left: 4px;
        }

        /* Content Panel */
        .content-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--card-shadow);
        }

        @media (max-width: 600px) {
            .content-panel {
                padding: 24px;
            }
        }

        .page-title {
            font-family: var(--font-outfit);
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            margin-top: 0;
            margin-bottom: 12px;
        }

        .last-updated {
            font-size: 13px;
            color: var(--slate-600);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .last-updated i {
            color: var(--primary);
        }

        /* Typography & Styling */
        h2.section-title {
            font-family: var(--font-outfit);
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-top: 36px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--slate-100);
        }

        h2.section-title i {
            color: var(--primary);
            font-size: 18px;
        }

        p {
            margin-top: 0;
            margin-bottom: 16px;
            color: var(--slate-800);
            text-align: justify;
        }

        ul {
            padding-left: 20px;
            margin-bottom: 20px;
        }

        li {
            margin-bottom: 8px;
        }

        .important-box {
            background-color: var(--primary-light);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 16px 20px;
            margin: 24px 0;
        }

        .important-box p {
            margin: 0;
            color: #3730a3;
            font-weight: 500;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            padding: 30px 20px;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: var(--slate-600);
        }
    </style>
</head>

<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <a href="/" class="brand-logo">
                <i class="fas fa-shield-halved"></i> ERP Marketplace
            </a>
            <a href="{{ route('login') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Login
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="toc-card">
                <h3 class="toc-title">Daftar Isi</h3>
                <ul class="toc-list">
                    <li><a href="#pengantar" class="toc-link">1. Pengantar</a></li>
                    <li><a href="#data-dikumpulkan" class="toc-link">2. Data yang Dikumpulkan</a></li>
                    <li><a href="#tujuan-penggunaan" class="toc-link">3. Tujuan Penggunaan</a></li>
                    <li><a href="#integrasi-marketplace" class="toc-link">4. Integrasi Marketplace</a></li>
                    <li><a href="#keamanan-data" class="toc-link">5. Keamanan & Isolasi Data</a></li>
                    <li><a href="#hak-pengguna" class="toc-link">6. Hak Pengguna</a></li>
                    <li><a href="#kontak" class="toc-link">7. Kontak Kami</a></li>
                </ul>
            </div>
        </aside>

        <!-- Privacy Content Panel -->
        <section class="content-panel">
            <h1 class="page-title">Kebijakan Perlindungan Data & Privasi</h1>
            <div class="last-updated">
                <i class="far fa-calendar-alt"></i> Terakhir diperbarui: 21 Juli 2026
            </div>

            <p>
                Selamat datang di ERP Marketplace. Kami memahami bahwa keamanan dan kerahasiaan data operasional bisnis
                Anda adalah hal yang sangat vital. Kebijakan ini menjelaskan bagaimana kami mengumpulkan, menggunakan,
                menyimpan, dan melindungi informasi Anda saat menggunakan platform kami.
            </p>

            <div class="important-box">
                <p>
                    <i class="fas fa-circle-info me-1"></i> Dengan menggunakan ERP Marketplace, Anda menyetujui praktik
                    perlindungan data yang dijelaskan dalam kebijakan ini. Kami tidak pernah menjual data operasional
                    atau informasi bisnis Anda kepada pihak ketiga mana pun.
                </p>
            </div>

            <!-- Section 1 -->
            <h2 class="section-title" id="pengantar">
                <i class="fas fa-info-circle"></i> 1. Pengantar
            </h2>
            <p>
                ERP Marketplace adalah platform SaaS (Software-as-a-Service) multi-tenant terpusat yang dirancang khusus
                untuk mengelola produk, pesanan, inventaris stok, pembukuan keuangan, dan sinkronisasi otomatis ke
                berbagai saluran penjualan e-commerce pihak ketiga (seperti Shopee, TikTok Shop, Lazada, dll.).
            </p>

            <!-- Section 2 -->
            <h2 class="section-title" id="data-dikumpulkan">
                <i class="fas fa-database"></i> 2. Data yang Kami Kumpulkan
            </h2>
            <p>
                Untuk memfasilitasi kebutuhan bisnis Anda, platform kami mengumpulkan beberapa kategori data berikut:
            </p>
            <ul>
                <li><strong>Informasi Akun Pengguna:</strong> Nama lengkap, alamat email, nomor telepon, username,
                    password terenkripsi, serta peran/hak akses khusus Anda di sistem.</li>
                <li><strong>Data Perusahaan & Toko (Tenant):</strong> Nama perusahaan, informasi badan usaha, alamat
                    gudang/toko, logo, dan rincian kontak operasional.</li>
                <li><strong>Data Produk & Inventaris:</strong> SKU (Stock Keeping Unit), deskripsi produk, detail
                    formula resep/BOM (Bill of Materials), harga modal, harga jual, dan jumlah stok barang.</li>
                <li><strong>Data Transaksi & Pelanggan:</strong> Rincian pesanan masuk, logistik pengiriman, nomor resi,
                    rincian biaya transaksi, serta informasi pembeli (nama, nomor telepon, alamat kirim).</li>
            </ul>

            <!-- Section 3 -->
            <h2 class="section-title" id="tujuan-penggunaan">
                <i class="fas fa-gears"></i> 3. Tujuan Penggunaan Data
            </h2>
            <p>
                Kami memproses data bisnis Anda untuk tujuan utama operasional ERP, termasuk:
            </p>
            <ul>
                <li>Mengelola dan memproses pesanan masuk baik dari marketplace maupun penjualan langsung offline (POS).
                </li>
                <li>Menghitung estimasi Harga Pokok Penjualan (HPP) berdasarkan bahan baku (BOM) dan operasional
                    produksi penjahit (SPK).</li>
                <li>Menyelaraskan (sinkronisasi) harga dan kuantitas stok produk secara real-time ke saluran marketplace
                    yang terhubung guna mencegah terjadinya overselling.</li>
                <li>Menyediakan analisis performa penjualan, margin keuntungan, laporan mutasi barang, serta laporan
                    laba rugi bisnis Anda.</li>
            </ul>

            <!-- Section 4 -->
            <h2 class="section-title" id="integrasi-marketplace">
                <i class="fas fa-circle-nodes"></i> 4. Integrasi Marketplace Pihak Ketiga
            </h2>
            <p>
                Platform kami menggunakan koneksi API resmi yang aman (OAuth) untuk berinteraksi dengan platform pihak
                ketiga seperti Shopee, TikTok Shop, Lazada, dll.
            </p>
            <p>
                Saat Anda mengotorisasi toko marketplace Anda dengan ERP kami, kami hanya mengambil dan mengirimkan
                informasi yang diperlukan untuk pemrosesan pesanan, pembaruan stok, dan pemetaan katalog produk. Kami
                tunduk sepenuhnya pada syarat dan kebijakan developer masing-masing platform e-commerce tersebut.
            </p>

            <!-- Section 5 -->
            <h2 class="section-title" id="keamanan-data">
                <i class="fas fa-shield-halved"></i> 5. Keamanan & Isolasi Data (Multi-Tenancy)
            </h2>
            <p>
                Kami menerapkan standar pengamanan ketat untuk menjamin kerahasiaan informasi Anda:
            </p>
            <ul>
                <li><strong>Isolasi Tenant:</strong> Arsitektur platform kami menjamin data antar perusahaan (tenant)
                    terpisah sepenuhnya. Tenant lain tidak akan pernah dapat mengakses, melihat, atau memodifikasi data
                    produk, formula resep, pembukuan, atau pesanan Anda.</li>
                <li><strong>Enkripsi Data:</strong> Data sensitif seperti kredensial API dan password disimpan
                    menggunakan algoritma enkripsi satu arah (hash) dan enkripsi kunci publik-privat yang aman.</li>
                <li><strong>Akses Berbasis Peran:</strong> Anda memiliki kendali penuh melalui menu manajemen hak akses
                    untuk menentukan staf/karyawan mana saja yang dapat mengakses fitur tertentu (seperti data keuangan,
                    admin produksi SPK, admin gudang, dll.).</li>
            </ul>

            <!-- Section 6 -->
            <h2 class="section-title" id="hak-pengguna">
                <i class="fas fa-user-check"></i> 6. Hak Pengguna atas Data
            </h2>
            <p>
                Sebagai pemilik data sah, Anda memiliki kendali penuh atas informasi bisnis Anda:
            </p>
            <ul>
                <li>Melihat, memperbarui, atau mengekspor seluruh katalog produk dan pesanan bisnis Anda dalam format
                    Excel/CSV.</li>
                <li>Memutus hubungan integrasi API toko marketplace Anda kapan saja dari menu Pengaturan Toko.</li>
                <li>Meminta penghapusan permanen akun pengguna atau seluruh data tenant Anda jika tidak lagi menggunakan
                    layanan kami.</li>
            </ul>

            <!-- Section 7 -->
            <h2 class="section-title" id="kontak">
                <i class="fas fa-envelope"></i> 7. Kontak dan Bantuan
            </h2>
            <p>
                Jika Anda memiliki pertanyaan, saran, atau kekhawatiran terkait pengelolaan privasi data di platform
                kami, silakan menghubungi administrator IT Aspartech melalui:
            </p>
            <p style="margin-left: 10px;">
                <i class="fas fa-headset me-2 text-primary"></i> <strong>Pusat Layanan Pelanggan:</strong>
                support@aspartech.com<br>
                <i class="fab fa-whatsapp me-2 text-success"></i> <strong>WhatsApp Staf IT:</strong> +62 851-XXXX-XXXX
            </p>
        </section>
    </main>

    <!-- Footer Area -->
    <footer class="footer">
        <div class="container-footer">
            &copy; 2026 ERP Marketplace by Aspartech. Hak Cipta Dilindungi Undang-Undang.
        </div>
    </footer>

</body>

</html>
