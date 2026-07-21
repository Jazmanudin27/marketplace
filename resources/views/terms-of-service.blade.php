<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ketentuan Layanan ERP Marketplace - Perjanjian penggunaan sistem ERP kami">
    <title>Ketentuan Layanan | ERP Marketplace</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
                    <li><a href="#persetujuan" class="toc-link">1. Persetujuan Ketentuan</a></li>
                    <li><a href="#akun-keamanan" class="toc-link">2. Akun & Keamanan</a></li>
                    <li><a href="#penggunaan-layanan" class="toc-link">3. Penggunaan yang Diperbolehkan</a></li>
                    <li><a href="#koneksi-marketplace" class="toc-link">4. Integrasi & API Pihak Ketiga</a></li>
                    <li><a href="#tanggung-jawab" class="toc-link">5. Batasan Tanggung Jawab</a></li>
                    <li><a href="#kekayaan-intelektual" class="toc-link">6. Kekayaan Intelektual</a></li>
                    <li><a href="#penangguhan" class="toc-link">7. Penangguhan Akun</a></li>
                    <li><a href="#perubahan-ketentuan" class="toc-link">8. Perubahan Layanan</a></li>
                </ul>
            </div>
        </aside>

        <!-- Content Panel -->
        <section class="content-panel">
            <h1 class="page-title">Ketentuan Layanan Penggunaan</h1>
            <div class="last-updated">
                <i class="far fa-calendar-alt"></i> Terakhir diperbarui: 21 Juli 2026
            </div>

            <p>
                Selamat datang di ERP Marketplace. Ketentuan Layanan Penggunaan ini ("Perjanjian") mengatur akses dan penggunaan Anda atas platform perangkat lunak manajemen operasional multi-channel terpusat kami.
            </p>

            <div class="important-box">
                <p>
                    <i class="fas fa-circle-info me-1"></i> Mohon baca Ketentuan Layanan ini dengan saksama. Dengan mengakses atau menggunakan platform ERP Marketplace, Anda menyatakan bahwa Anda setuju untuk terikat oleh seluruh syarat dan ketentuan yang tercantum di sini.
                </p>
            </div>

            <!-- Section 1 -->
            <h2 class="section-title" id="persetujuan">
                <i class="fas fa-handshake"></i> 1. Persetujuan Ketentuan
            </h2>
            <p>
                Perjanjian ini berlaku antara perusahaan Anda ("Penyewa" atau "Tenant") dan Aspartech sebagai penyedia sistem. Layanan ini disediakan semata-mata untuk mempermudah manajemen bisnis retail, pergudangan, manufaktur produksi kustom (SPK/BOM), dan sinkronisasi logistik e-commerce Anda.
            </p>

            <!-- Section 2 -->
            <h2 class="section-title" id="akun-keamanan">
                <i class="fas fa-key"></i> 2. Registrasi Akun & Keamanan Kredensial
            </h2>
            <p>
                Untuk mulai menggunakan layanan ERP Marketplace, Anda diwajibkan untuk memiliki akun terdaftar yang disiapkan oleh administrator sistem:
            </p>
            <ul>
                <li>Anda bertanggung jawab penuh atas kerahasiaan username, password, dan pembagian hak akses (role) staf Anda.</li>
                <li>Setiap aktivitas yang dilakukan melalui akun Anda dianggap sebagai tindakan sah dari perwakilan bisnis Anda.</li>
                <li>Anda setuju untuk segera memberitahukan tim support Aspartech jika mendeteksi adanya penggunaan akun tanpa izin atau celah keamanan lainnya.</li>
            </ul>

            <!-- Section 3 -->
            <h2 class="section-title" id="penggunaan-layanan">
                <i class="fas fa-check-double"></i> 3. Penggunaan yang Diperbolehkan (Acceptable Use)
            </h2>
            <p>
                Anda setuju untuk menggunakan layanan ini hanya untuk tujuan bisnis yang sah dan mematuhi semua peraturan hukum yang berlaku. Anda dilarang untuk:
            </p>
            <ul>
                <li>Melakukan reverse engineering, merusak, atau mencoba menembus arsitektur keamanan multi-tenant platform kami.</li>
                <li>Menggunakan skrip otomatis ilegal guna memanipulasi atau mengambil data dari sistem di luar API resmi yang kami sediakan.</li>
                <li>Mengunggah file berbahaya, virus, trojan, atau malware ke dalam server cloud ERP.</li>
            </ul>

            <!-- Section 4 -->
            <h2 class="section-title" id="koneksi-marketplace">
                <i class="fas fa-circle-nodes"></i> 4. Integrasi & API Platform Pihak Ketiga
            </h2>
            <p>
                ERP Marketplace menyediakan koneksi opsional untuk menghubungkan operasional Anda dengan toko e-commerce pihak ketiga (seperti Shopee, TikTok Shop, Lazada, dll.).
            </p>
            <p>
                Koneksi ini sepenuhnya bergantung pada ketersediaan dan kebijakan API dari pihak ketiga tersebut. Kami tidak bertanggung jawab atas keterlambatan sinkronisasi, kesalahan pembaruan stok, atau pembatalan pesanan otomatis yang disebabkan oleh gangguan sistem, pembatasan akses, atau perubahan kebijakan mendadak dari platform marketplace bersangkutan.
            </p>

            <!-- Section 5 -->
            <h2 class="section-title" id="tanggung-jawab">
                <i class="fas fa-triangle-exclamation"></i> 5. Batasan Tanggung Jawab (Limitation of Liability)
            </h2>
            <p>
                Kami berupaya keras menyediakan sistem dengan uptime dan performa terbaik. Namun, platform ini disediakan "SEBAGAIMANA ADANYA" tanpa jaminan mutlak tanpa cacat.
            </p>
            <p>
                Dalam kondisi hukum apa pun, Aspartech tidak bertanggung jawab atas kerugian finansial, kehilangan keuntungan bisnis, kesalahan input stok bahan baku oleh pengguna, atau kerugian tidak langsung lainnya yang disebabkan oleh penggunaan atau ketidakmampuan menggunakan layanan kami.
            </p>

            <!-- Section 6 -->
            <h2 class="section-title" id="kekayaan-intelektual">
                <i class="fas fa-copyright"></i> 6. Hak Kekayaan Intelektual
            </h2>
            <p>
                Seluruh kode sumber, desain antarmuka, aset visual, logo, dan algoritma yang membentuk ERP Marketplace adalah milik sah Aspartech dan dilindungi oleh undang-undang hak cipta. Data produk, formula resep, data penjualan, dan informasi pelanggan yang diunggah oleh Anda sepenuhnya tetap menjadi hak milik bisnis Anda (Tenant).
            </p>

            <!-- Section 7 -->
            <h2 class="section-title" id="penangguhan">
                <i class="fas fa-ban"></i> 7. Penangguhan & Penghentian Akun
            </h2>
            <p>
                Kami berhak menangguhkan atau menghentikan akses Anda ke platform secara sementara atau permanen jika:
            </p>
            <ul>
                <li>Terjadi pelanggaran berat terhadap Ketentuan Layanan Penggunaan ini.</li>
                <li>Penyewa gagal memenuhi kewajiban pembayaran lisensi berlangganan bulanan/tahunan setelah melewati masa tenggang yang disepakati.</li>
                <li>Penggunaan akun Anda terdeteksi melakukan aktivitas mencurigakan yang membahayakan stabilitas server tenant lain.</li>
            </ul>

            <!-- Section 8 -->
            <h2 class="section-title" id="perubahan-ketentuan">
                <i class="fas fa-arrows-rotate"></i> 8. Perubahan Ketentuan & Layanan
            </h2>
            <p>
                Kami dapat memperbarui Ketentuan Layanan ini dari waktu ke waktu untuk menyesuaikan dengan fitur baru atau regulasi hukum yang berlaku. Kami akan memberikan notifikasi perubahan melalui dashboard utama ERP sebelum ketentuan baru tersebut diberlakukan.
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
