@extends('layouts.app')
@section('title', 'Pusat Bantuan & Tutorial')
@section('page-title', 'Bantuan & Tutorial')

@push('styles')
    <style>
        .faq-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Glassmorphism Header */
        .faq-hero {
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid var(--border-light);
            border-radius: var(--radius);
            padding: 2.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .faq-hero::before {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: var(--primary);
            filter: blur(80px);
            opacity: 0.2;
            top: -50px;
            right: -50px;
        }

        .faq-hero h2 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #fff, var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .faq-hero p {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto 1.5rem;
        }

        /* Search Bar */
        .search-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-light);
            border-radius: 99px;
            padding: 0.9rem 1.5rem 0.9rem 3rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
            background: rgba(108, 99, 255, 0.05);
        }

        .search-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Category Navigation */
        .faq-categories {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none;
        }

        .faq-categories::-webkit-scrollbar {
            display: none;
        }

        .category-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.75rem 1.25rem;
            border-radius: 99px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-btn:hover {
            border-color: var(--primary-dark);
            color: var(--text-primary);
        }

        .category-btn.active {
            background: rgba(108, 99, 255, 0.15);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Tutorial Content Cards */
        .tutorial-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .tutorial-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: none;
            /* Controlled by JavaScript */
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tutorial-card.active-category {
            display: block;
        }

        .tutorial-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .tutorial-icon {
            width: 48px;
            height: 48px;
            background: rgba(108, 99, 255, 0.15);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .tutorial-title h3 {
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
        }

        .tutorial-title p {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin: 0.2rem 0 0;
        }

        /* visual workflow list */
        .workflow-steps {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin: 1.5rem 0;
            position: relative;
        }

        .workflow-step {
            display: flex;
            gap: 1rem;
            position: relative;
        }

        .step-number {
            width: 28px;
            height: 28px;
            background: var(--bg-card2);
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
            z-index: 2;
        }

        .workflow-step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 13px;
            top: 28px;
            bottom: -20px;
            width: 2px;
            background: var(--border);
            z-index: 1;
        }

        .step-content {
            padding-top: 0.2rem;
        }

        .step-content h4 {
            font-weight: 700;
            font-size: 0.95rem;
            margin: 0 0 0.25rem 0;
        }

        .step-content p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.4;
        }

        /* Accordions for FAQs */
        .faq-accordion {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .accordion-item {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--bg-card2);
            transition: border-color 0.2s;
        }

        .accordion-item:hover {
            border-color: rgba(108, 99, 255, 0.4);
        }

        .accordion-trigger {
            width: 100%;
            background: none;
            border: none;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            text-align: left;
            outline: none;
            transition: background 0.2s;
        }

        .accordion-trigger:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            color: var(--text-secondary);
            font-size: 0.88rem;
            line-height: 1.5;
            background: rgba(0, 0, 0, 0.1);
            padding: 0 1.5rem;
        }

        .accordion-item.active .accordion-content {
            padding: 1rem 1.5rem 1.5rem 1.5rem;
        }

        .accordion-trigger i {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
            color: var(--text-muted);
        }

        .accordion-item.active .accordion-trigger i {
            transform: rotate(180deg);
            color: var(--primary);
        }

        /* Highlights & badges */
        .flow-badge {
            background: rgba(108, 99, 255, 0.15);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="faq-wrapper">
        <!-- Hero Header -->
        <div class="faq-hero">
            <h2>Pusat Bantuan & Panduan Sistem</h2>
            <p>Temukan panduan langkah demi langkah, pemecahan masalah (FAQ), serta alur kerja dari semua fitur manajemen
                ERP Marketplace Anda.</p>

            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="faq-search" class="search-input"
                    placeholder="Cari tutorial atau kata kunci (misal: 'mapping', 'shopee', 'retur')...">
            </div>
        </div>

        <!-- Category Filter Tabs -->
        <div class="faq-categories">
            <button class="category-btn active" data-category="all">
                <i class="fas fa-th-large"></i> Semua Panduan
            </button>
            <button class="category-btn" data-category="integrasi">
                <i class="fas fa-plug"></i> Integrasi Toko
            </button>
            <button class="category-btn" data-category="produk">
                <i class="fas fa-box-open"></i> Produk & Mapping
            </button>
            <button class="category-btn" data-category="transaksi">
                <i class="fas fa-shopping-cart"></i> Pesanan & Fulfillment
            </button>
            <button class="category-btn" data-category="stok">
                <i class="fas fa-boxes"></i> Stok & Opname
            </button>
            <button class="category-btn" data-category="keuangan">
                <i class="fas fa-wallet"></i> Keuangan & Laba Rugi
            </button>
        </div>

        <!-- Tutorial Content Grid -->
        <div class="tutorial-grid">

            <!-- ==================== INTEGRASI ==================== -->
            <div class="tutorial-card active-category" data-category="integrasi">
                <div class="tutorial-header">
                    <div class="tutorial-icon"><i class="fas fa-plug"></i></div>
                    <div class="tutorial-title">
                        <h3>Integrasi Toko & Otorisasi Marketplace</h3>
                        <p>Cara menghubungkan toko Shopee dan TikTok Shop ke dalam sistem ERP</p>
                    </div>
                </div>

                <div class="workflow-steps">
                    <div class="workflow-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Akses Menu Integrasi</h4>
                            <p>Masuk ke menu <strong>Integrasi -> Kelola Toko</strong> lalu klik tombol <strong>Tambah
                                    Toko</strong>.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Pilih Channel Marketplace</h4>
                            <p>Pilih channel toko yang ingin dihubungkan (Shopee atau TikTok Shop). Sistem akan mengarahkan
                                (redirect) ke portal login resmi marketplace tersebut.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Otorisasi Akun</h4>
                            <p>Login ke akun toko Anda di portal marketplace tersebut, lalu setujui otorisasi akses data
                                untuk ERP Marketplace.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Otomatis Sinkronisasi</h4>
                            <p>Setelah sukses, Anda akan dialihkan kembali ke ERP. Status toko akan berubah menjadi
                                <strong>Aktif</strong> dan sistem siap menarik data produk serta pesanan secara otomatis.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="faq-accordion mt-4">
                    <div class="accordion-item">
                        <button class="accordion-trigger">Bagaimana jika masa aktif otorisasi token toko habis? <i
                                class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Token otorisasi Shopee/TikTok memiliki masa aktif berkala (biasanya 1 tahun untuk Shopee, 30
                            hari untuk TikTok). Jika status toko berubah menjadi "Expired" atau gagal sync, silakan klik
                            tombol <strong>Re-otorisasi / Hubungkan Ulang</strong> pada halaman Kelola Toko tanpa perlu
                            menghapus toko lama agar data transaksi tidak terduplikasi.
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-trigger">Apakah integrasi Tokopedia juga didukung? <i
                                class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Ya. Karena adanya merger layanan, otorisasi Tokopedia sekarang terintegrasi langsung dengan
                            TikTok Shop OAuth. Menghubungkan akun TikTok Shop secara otomatis juga akan mensinkronisasikan
                            inventori dan produk Tokopedia Anda.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== PRODUK & MAPPING ==================== -->
            <div class="tutorial-card active-category" data-category="produk">
                <div class="tutorial-header">
                    <div class="tutorial-icon"><i class="fas fa-box-open"></i></div>
                    <div class="tutorial-title">
                        <h3>Master Produk & Pemetaan (Product Mapping)</h3>
                        <p>Konsep inventori terpusat untuk menghubungkan variasi SKU antar marketplace</p>
                    </div>
                </div>

                <div class="workflow-steps">
                    <div class="workflow-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Tarik Produk dari Marketplace <span class="flow-badge">Alur Mulai</span></h4>
                            <p>Setelah toko terhubung, masuk ke <strong>Marketplace Produk</strong>, lalu klik <strong>Tarik
                                    Produk Terbaru</strong> agar semua item di Shopee/TikTok tersinkron ke daftar produk
                                mentah.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Tautkan ke Master Product (Mapping)</h4>
                            <p>Untuk mengelola stok terpusat, produk marketplace harus ditautkan ke satu <strong>Master
                                    Product</strong> yang sama. Klik <strong>Tautkan...</strong> lalu pilih SKU master
                                gudang Anda. Atau gunakan tombol <strong>Jadikan Master</strong> untuk mendaftarkan produk
                                baru di gudang lokal Anda secara instan.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Aktifkan Fitur Sync Stok & Atur Safety Stock</h4>
                            <p>Aktifkan switch <strong>Sinkronisasi Stok otomatis</strong> pada pengaturan produk
                                marketplace. Anda juga bisa mengatur <strong>Safety Stock (Stok Pengaman)</strong> agar
                                menyisakan cadangan fisik di gudang sehingga menghindari pembeli memesan produk yang stok
                                aslinya sudah menipis.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-accordion mt-4">
                    <div class="accordion-item">
                        <button class="accordion-trigger">Mengapa satu Master Product perlu dipetakan (mapping) ke banyak
                            Produk Marketplace? <i class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Misalnya Anda menjual "Kaos Polos Hitam" di Shopee dengan SKU `shopee-kaos-black`, dan di TikTok
                            dengan SKU `tiktok-kaos-hitam`. Dengan memetakan kedua SKU ini ke satu Master Product "Kaos
                            Polos Hitam", maka jika terjadi penjualan di Shopee, stok master berkurang, dan sistem akan
                            langsung memperbarui sisa stok kaos hitam di TikTok secara real-time.
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-trigger">Apa gunanya Safety Stock (Stok Pengaman)? <i
                                class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Jika Anda mengisi safety stock senilai 2 unit, dan stok Master Anda tinggal 2 unit, sistem akan
                            mengirimkan stok senilai 0 unit ke Shopee/TikTok agar produk tersebut tidak bisa dibeli online.
                            Hal ini mencegah terjadinya *overselling* (terjual melampaui stok) di saat fisik barang
                            sebenarnya sudah habis/rusak.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== PESANAN & FULFILLMENT ==================== -->
            <div class="tutorial-card active-category" data-category="transaksi">
                <div class="tutorial-header">
                    <div class="tutorial-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="tutorial-title">
                        <h3>Manajemen Pesanan & Fulfillment (Scan Kemas)</h3>
                        <p>Alur memproses pesanan masuk hingga penarikan resi dan update status pengiriman</p>
                    </div>
                </div>

                <div class="workflow-steps">
                    <div class="workflow-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Tarik Pesanan Terbaru <span class="flow-badge">Alur Pesanan</span></h4>
                            <p>Masuk ke halaman <strong>Pesanan Masuk</strong>, lalu klik tombol <strong>Tarik Pesanan
                                    Terbaru</strong> untuk memicu job antrean sinkronisasi order dari API Shopee & TikTok.
                            </p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Proses Otorisasi Pengiriman & Cetak Resi</h4>
                            <p>Klik link detail invoice pesanan, lalu klik <strong>Kirim / Proses Pengiriman</strong> untuk
                                mendaftarkan booking kurir (pick-up / drop-off). Setelah disetujui marketplace, cetak label
                                pengiriman secara massal melalui tombol <strong>Cetak Massal</strong>.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Scan Kemas Pesanan (Fulfillment Scan)</h4>
                            <p>Masuk ke menu <strong>Kemas Pesanan (Scan)</strong>. Arahkan barcode scanner Anda ke label
                                resi cetak. Sistem akan memuat detail pesanan beserta daftar item barang yang harus
                                dimasukkan ke dalam paket.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Validasi Barcode Barang & Update Stok</h4>
                            <p>Scan barcode dari masing-masing produk fisik yang dimasukkan ke dalam paket untuk memastikan
                                barang yang dikemas sudah sesuai (mencegah salah kirim). Setelah semua item valid, status
                                pesanan akan otomatis berubah menjadi <strong>Siap Dikirim</strong> di marketplace dan
                                memotong stok gudang lokal.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-accordion mt-4">
                    <div class="accordion-item">
                        <button class="accordion-trigger">Bagaimana alur retur barang jika pembeli membatalkan pesanan? <i
                                class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Masuk ke menu <strong>Pesanan Retur</strong>, klik <strong>Tarik Retur Terbaru</strong> untuk
                            sinkron data. Setelah paket retur sampai di gudang fisik Anda, klik tombol <strong>Kembalikan ke
                                Stok (Restock)</strong> agar sistem mengembalikan jumlah stok produk tersebut ke database
                            inventori gudang Anda secara otomatis.
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-trigger">Apakah kami bisa mengemas pesanan tanpa menggunakan scanner
                            barcode? <i class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Bisa. Pada halaman Kemas Pesanan (Scan), Anda dapat memasukkan ID Invoice atau nomor resi secara
                            manual pada kolom input pencarian jika scanner barcode Anda tidak aktif.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== STOK & OPNAME ==================== -->
            <div class="tutorial-card active-category" data-category="stok">
                <div class="tutorial-header">
                    <div class="tutorial-icon"><i class="fas fa-boxes"></i></div>
                    <div class="tutorial-title">
                        <h3>Inventori, Barang Masuk, & Opname Stok</h3>
                        <p>Mengelola perputaran barang fisik di dalam gudang lokal Anda</p>
                    </div>
                </div>

                <div class="workflow-steps">
                    <div class="workflow-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Penerimaan Stok (Barang Masuk) <span class="flow-badge">Stok Masuk</span></h4>
                            <p>Jika ada pasokan barang baru dari Supplier, masuk ke <strong>Barang Masuk</strong>, buat
                                invoice tanda terima baru, pilih Supplier, dan tambahkan list produk beserta kuantitas yang
                                masuk. Stok Master Product Anda akan otomatis bertambah.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Pantau Kartu Stok (Stock Ledger)</h4>
                            <p>Gunakan menu <strong>Stok Gudang -> Kartu Stok</strong> untuk melihat mutasi keluar-masuk
                                barang secara detail (misal: penambahan manual, pengurangan akibat checkout Shopee, atau
                                pengembalian retur).</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Opname Stok (Stock Take) berkala</h4>
                            <p>Jika ada selisih stok fisik vs stok di sistem, lakukan penyesuaian melalui menu
                                <strong>Opname Stok -> Buat Opname</strong>. Sistem akan mencatat selisih tersebut dan
                                mengupdate nominal stok di database agar kembali sesuai dengan fisik di gudang Anda.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="faq-accordion mt-4">
                    <div class="accordion-item">
                        <button class="accordion-trigger">Mengapa stok di sistem berbeda dengan stok fisik di gudang? <i
                                class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Perbedaan stok dapat terjadi akibat adanya barang yang rusak, salah kirim, atau pencurian fisik.
                            Lakukan **Opname Stok** minimal sebulan sekali untuk mengaudit database inventori Anda.
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-trigger">Apakah saat kita melakukan barang masuk, stok di marketplace
                            langsung berubah? <i class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Ya. Jika produk marketplace tersebut telah <strong>ter-mapping</strong> ke Master Product yang
                            bersangkutan dan status <strong>Sync Stok aktif</strong>, maka setiap penambahan stok gudang
                            (dari Barang Masuk / Opname) akan memicu update stok otomatis ke Shopee/TikTok.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== KEUANGAN & LABA RUGI ==================== -->
            <div class="tutorial-card active-category" data-category="keuangan">
                <div class="tutorial-header">
                    <div class="tutorial-icon"><i class="fas fa-wallet"></i></div>
                    <div class="tutorial-title">
                        <h3>Laporan Profitabilitas & Rekonsiliasi Dana</h3>
                        <p>Pemantauan laba bersih usaha dan pencocokan uang pencairan dari marketplace</p>
                    </div>
                </div>

                <div class="workflow-steps">
                    <div class="workflow-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Pencatatan Harga Pokok Penjualan (HPP / COGS)</h4>
                            <p>Pastikan Anda sudah mengisi harga modal di setiap Master Product. HPP ini akan menjadi dasar
                                perhitungan laba bersih ketika pesanan selesai.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Laporan Profit per Pesanan</h4>
                            <p>Setiap transaksi dari marketplace yang selesai akan ditarik nilai penjualan kotornya. Masuk
                                ke <strong>Laporan Profit / Pesanan</strong> untuk melihat laba bersih setelah dikurangi HPP
                                dan biaya potongan admin marketplace.</p>
                        </div>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Rekonsiliasi Keuangan (Pencairan Dana)</h4>
                            <p>Gunakan menu <strong>Rekonsiliasi Keuangan</strong> untuk membandingkan nominal total order
                                yang dirilis oleh Shopee/TikTok dengan uang riil yang ditransfer masuk ke rekening bank
                                Anda, guna mendeteksi potongan gelap atau biaya admin terselubung.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-accordion mt-4">
                    <div class="accordion-item">
                        <button class="accordion-trigger">Bagaimana cara mencatat pengeluaran operasional lain? <i
                                class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Gunakan menu **Pengeluaran & Biaya** untuk mencatat ongkos operasional (seperti gaji karyawan,
                            biaya iklan ads, atau lakban packing). Pencatatan ini akan langsung memotong perhitungan laba
                            bersih pada **Laporan Laba Rugi** akhir bulan Anda.
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-trigger">Dari mana sistem mendeteksi biaya komisi administrasi
                            marketplace? <i class="fas fa-chevron-down"></i></button>
                        <div class="accordion-content">
                            Sistem menarik rincian biaya komisi, biaya layanan, dan potongan ongkir langsung dari data
                            settlement API pesanan Shopee & TikTok secara periodik saat proses sync order dijalankan.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab Filtering Logic
            const categoryBtns = document.querySelectorAll('.category-btn');
            const tutorialCards = document.querySelectorAll('.tutorial-card');

            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Toggle Active Tab class
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const selectedCategory = this.getAttribute('data-category');

                    // Filter Cards
                    tutorialCards.forEach(card => {
                        if (selectedCategory === 'all' || card.getAttribute(
                                'data-category') === selectedCategory) {
                            card.classList.add('active-category');
                        } else {
                            card.classList.remove('active-category');
                        }
                    });
                });
            });

            // Search Filter Logic
            const searchInput = document.getElementById('faq-search');

            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();

                tutorialCards.forEach(card => {
                    const textContent = card.textContent.toLowerCase();
                    const accordionTriggers = card.querySelectorAll('.accordion-trigger');
                    let matchesSearch = textContent.includes(query);

                    // Check inside accordion triggers/contents as well
                    let showCard = false;
                    if (matchesSearch || query === '') {
                        showCard = true;
                    }

                    // If filter is active and we match, show card, else hide
                    const selectedCategory = document.querySelector('.category-btn.active')
                        .getAttribute('data-category');
                    const cardCategory = card.getAttribute('data-category');

                    const matchesCategory = (selectedCategory === 'all' || cardCategory ===
                        selectedCategory);

                    if (showCard && matchesCategory) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }

                    // Auto-expand accordions that match search query
                    const accordions = card.querySelectorAll('.accordion-item');
                    accordions.forEach(acc => {
                        const accText = acc.textContent.toLowerCase();
                        if (query !== '' && accText.includes(query)) {
                            acc.classList.add('active');
                            const content = acc.querySelector('.accordion-content');
                            content.style.maxHeight = content.scrollHeight + "px";
                        } else if (query === '') {
                            acc.classList.remove('active');
                            acc.querySelector('.accordion-content').style.maxHeight = null;
                        }
                    });
                });
            });

            // Accordion Toggle Logic
            const accordionTriggers = document.querySelectorAll('.accordion-trigger');

            accordionTriggers.forEach(trigger => {
                trigger.addEventListener('click', function() {
                    const item = this.parentElement;
                    const content = this.nextElementSibling;
                    const isActive = item.classList.contains('active');

                    // Toggle class
                    if (isActive) {
                        item.classList.remove('active');
                        content.style.maxHeight = null;
                    } else {
                        item.classList.add('active');
                        content.style.maxHeight = content.scrollHeight + "px";
                    }
                });
            });
        });
    </script>
@endsection
