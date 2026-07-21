<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Petunjuk Penghapusan Data ERP Marketplace - Panduan menghapus akun dan data integrasi Anda">
    <title>Petunjuk Penghapusan Data | ERP Marketplace</title>

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

        .step-list {
            list-style: none;
            padding-left: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }

        .step-item {
            display: flex;
            gap: 14px;
        }

        .step-num {
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .step-text {
            font-size: 14px;
            color: var(--slate-800);
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
                    <li><a href="#komitmen" class="toc-link">1. Komitmen Kontrol Data</a></li>
                    <li><a href="#cara-hapus" class="toc-link">2. Cara Pengajuan Hapus Data</a></li>
                    <li><a href="#langkah-langkah" class="toc-link">3. Langkah Proses Deletion</a></li>
                    <li><a href="#data-dihapus" class="toc-link">4. Data yang Dihapus Permanen</a></li>
                    <li><a href="#data-disimpan" class="toc-link">5. Data yang Tetap Disimpan</a></li>
                    <li><a href="#pemutusan-marketplace" class="toc-link">6. Pemutusan Integrasi Mandiri</a></li>
                    <li><a href="#bantuan" class="toc-link">7. Kontak & Bantuan</a></li>
                </ul>
            </div>
        </aside>

        <!-- Content Panel -->
        <section class="content-panel">
            <h1 class="page-title">Petunjuk Penghapusan Data (Data Deletion Instructions)</h1>
            <div class="last-updated">
                <i class="far fa-calendar-alt"></i> Terakhir diperbarui: 21 Juli 2026
            </div>

            <p>
                Di ERP Marketplace, kami berkomitmen penuh untuk menghormati privasi data Anda serta mendukung penuh hak Anda untuk mengendalikan, mencabut, atau menghapus informasi bisnis yang telah Anda daftarkan di dalam sistem kami.
            </p>

            <div class="important-box">
                <p>
                    <i class="fas fa-circle-info me-1"></i> Sesuai dengan peraturan privasi data global (GDPR, UU Pelindungan Data Pribadi) serta kebijakan developer Google, Facebook, dan platform Marketplace (Shopee, TikTok, Lazada), panduan ini menjelaskan langkah demi langkah untuk menghapus data Anda secara permanen dari server kami.
                </p>
            </div>

            <!-- Section 1 -->
            <h2 class="section-title" id="komitmen">
                <i class="fas fa-user-shield"></i> 1. Komitmen Kontrol Data Anda
            </h2>
            <p>
                Kami percaya Anda memiliki hak kepemilikan penuh atas data bisnis Anda. Anda dapat mengajukan permohonan untuk menghapus akun pribadi, profil tenant/perusahaan, dan memutuskan koneksi otorisasi toko marketplace kapan pun Anda inginkan.
            </p>

            <!-- Section 2 -->
            <h2 class="section-title" id="cara-hapus">
                <i class="fas fa-paper-plane"></i> 2. Cara Mengajukan Permohonan Penghapusan Data
            </h2>
            <p>
                Untuk mengajukan penghapusan akun secara permanen, Anda dapat mengirimkan permohonan tertulis melalui email resmi yang terdaftar di dalam sistem:
            </p>
            <ul class="step-list">
                <li class="step-item">
                    <div class="step-num">1</div>
                    <div class="step-text">Kirim email ke alamat <strong>support@aspartech.com</strong> dengan subjek: <strong>"Permohonan Penghapusan Data Akun ERP - [Nama Tenant/Perusahaan Anda]"</strong>.</div>
                </li>
                <li class="step-item">
                    <div class="step-num">2</div>
                    <div class="step-text">Sebutkan nama lengkap Anda, alamat email terdaftar, nama tenant (perusahaan/toko) Anda, dan alasan penghapusan data.</div>
                </li>
                <li class="step-item">
                    <div class="step-num">3</div>
                    <div class="step-text">Staf IT kami akan melakukan verifikasi identitas pemilik tenant untuk memastikan keamanan permohonan sebelum proses penghapusan dilakukan.</div>
                </li>
            </ul>

            <!-- Section 3 -->
            <h2 class="section-title" id="langkah-langkah">
                <i class="fas fa-hourglass-half"></i> 3. Langkah Proses Deletion
            </h2>
            <p>
                Setelah permohonan diverifikasi, proses penghapusan data akan mengikuti alur berikut:
            </p>
            <ul>
                <li><strong>Penangguhan Akun (Maksimal 7 Hari):</strong> Akun dan tenant Anda akan dinonaktifkan sementara. Dalam masa tenggang ini, Anda masih dapat membatalkan permohonan jika berubah pikiran.</li>
                <li><strong>Penghapusan Data Permanen (Maksimal 30 Hari):</strong> Semua data yang disimpan di database utama dan backup server kami akan dihapus secara permanen menggunakan prosedur pembersihan data yang aman.</li>
            </ul>

            <!-- Section 4 -->
            <h2 class="section-title" id="data-dihapus">
                <i class="fas fa-trash-can"></i> 4. Data yang Dihapus Secara Permanen
            </h2>
            <p>
                Data berikut akan dibersihkan secara permanen dari server kami dan tidak dapat dipulihkan kembali:
            </p>
            <ul>
                <li>Profil detail akun pengguna (nama, email terenkripsi, nomor HP, peran hak akses).</li>
                <li>Katalog master produk, SKU, harga, dan file resep formulasi (BOM).</li>
                <li>Riwayat log transaksi, pesanan masuk e-commerce, riwayat cetak SPK, kartu stok, dan laporan absensi karyawan.</li>
                <li>Seluruh token otorisasi API marketplace (Shopee, TikTok, Lazada) sehingga sistem kami kehilangan akses sepenuhnya ke toko marketplace Anda.</li>
            </ul>

            <!-- Section 5 -->
            <h2 class="section-title" id="data-disimpan">
                <i class="fas fa-file-shield"></i> 5. Data yang Tetap Disimpan (Retensi Terbatas)
            </h2>
            <p>
                Kami hanya akan menyimpan data tertentu jika diwajibkan oleh undang-undang atau kepatuhan peraturan yang berlaku:
            </p>
            <ul>
                <li>Catatan transaksi keuangan atau bukti pembayaran lisensi berlangganan SaaS untuk keperluan perpajakan, audit internal, atau kewajiban hukum yang berlaku di Indonesia (disimpan maksimal 5 tahun).</li>
                <li>Log aktivitas keamanan server yang tidak berisi informasi data pribadi (disimpan maksimal 90 hari untuk tujuan audit keamanan).</li>
            </ul>

            <!-- Section 6 -->
            <h2 class="section-title" id="pemutusan-marketplace">
                <i class="fas fa-unlink"></i> 6. Pemutusan Integrasi Marketplace Secara Mandiri
            </h2>
            <p>
                Jika Anda tidak ingin menghapus seluruh akun ERP, tetapi ingin menghentikan sistem kami dari mengakses toko e-commerce Anda, Anda dapat melakukannya secara mandiri:
            </p>
            <ul class="step-list">
                <li class="step-item">
                    <div class="step-num">1</div>
                    <div class="step-text">Masuk ke dashboard ERP Marketplace Anda, buka menu <strong>Toko / Channel</strong>.</div>
                </li>
                <li class="step-item">
                    <div class="step-num">2</div>
                    <div class="step-text">Pilih toko yang ingin Anda putuskan koneksinya, lalu klik tombol <strong>Hapus / Putuskan Otorisasi</strong>.</div>
                </li>
                <li class="step-item">
                    <div class="step-num">3</div>
                    <div class="step-text">Alternatif lainnya, masuk ke panel seller e-commerce Anda (seperti Shopee Partner App atau TikTok App Store) dan cabut otorisasi aplikasi ERP Marketplace. Token otorisasi akan otomatis tidak valid seketika itu juga.</div>
                </li>
            </ul>

            <!-- Section 7 -->
            <h2 class="section-title" id="bantuan">
                <i class="fas fa-headset"></i> 7. Hubungi Pusat Bantuan
            </h2>
            <p>
                Jika Anda mengalami kesulitan atau memiliki pertanyaan lebih lanjut mengenai penghapusan data ini, silakan menghubungi:
            </p>
            <p style="margin-left: 10px;">
                <i class="fas fa-envelope text-primary me-2"></i> <strong>Support Email:</strong> support@aspartech.com
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
