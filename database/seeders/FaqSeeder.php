<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for clean seed
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        FaqCategory::truncate();
        FaqItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            [
                'slug' => 'integrasi',
                'name' => 'Integrasi Toko & Otorisasi Marketplace',
                'subtitle' => 'Cara menghubungkan toko Shopee dan TikTok Shop ke dalam sistem ERP',
                'icon' => 'fas fa-plug',
                'color' => '#6C63FF',
                'color_rgb' => '108, 99, 255',
                'read_time' => '3 mnt',
                'workflow_title' => 'Alur Kerja Otorisasi',
                'sort_order' => 0,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Buka Menu Kelola Toko',
                        'content' => 'Klik menu <strong>Kelola Toko</strong> pada panel navigasi INTEGRASI di sisi kiri layar untuk membuka halaman dashboard toko.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana jika masa aktif otorisasi token toko habis?',
                        'content' => 'Token API Shopee biasanya berlaku selama 1 tahun, sedangkan TikTok Shop bertahan selama 30 hari. Jika status toko berubah menjadi "Expired", Anda hanya perlu mengklik tombol <strong>Hubungkan Ulang (Re-authorize)</strong> pada halaman Kelola Toko. Jangan hapus data toko lama agar data transaksi sejarah tidak terduplikasi.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Tambah Toko & Pilih Channel',
                        'content' => 'Klik tombol <strong>Tambah Toko / Sambungkan</strong>, lalu pilih channel toko online yang ingin diintegrasikan (Shopee atau TikTok Shop).',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah integrasi Tokopedia juga didukung?',
                        'content' => 'Ya. Melalui kerja sama sistematis, otorisasi Tokopedia saat ini terintegrasi langsung dengan TikTok Shop OAuth. Menghubungkan akun TikTok Shop Anda otomatis mensinkronisasikan inventori serta pesanan Tokopedia Anda.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Otorisasi Keamanan API',
                        'content' => 'Sistem akan mengarahkan (redirect) Anda ke portal login resmi marketplace. Masukkan kredensial toko Anda dan berikan izin otorisasi data kepada ERP.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Sinkronisasi Data Otomatis <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Setelah disetujui, Anda dialihkan kembali ke ERP. Status toko Anda akan berubah menjadi <strong style="color:var(--success);">Aktif</strong> dan ERP akan otomatis mengimpor data awal.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'slug' => 'produk',
                'name' => 'Master Produk & Pemetaan (Product Mapping)',
                'subtitle' => 'Konsep inventori terpusat untuk menghubungkan variasi SKU antar marketplace',
                'icon' => 'fas fa-box-open',
                'color' => '#8B5CF6',
                'color_rgb' => '139, 92, 246',
                'read_time' => '4 mnt',
                'workflow_title' => 'Alur Kerja Sinkronisasi SKU',
                'sort_order' => 1,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Tarik Data Produk Online',
                        'content' => 'Masuk ke menu <strong>Marketplace Produk</strong>, lalu klik tombol <strong>Tarik Produk Terbaru</strong> untuk memicu pengambilan data SKU mentah dari API Shopee & TikTok.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Mengapa satu Master Product perlu dipetakan ke banyak SKU online?',
                        'content' => 'Misalnya Anda menjual "Kaos Polos Hitam" di Shopee dengan SKU `shopee-kaos-black` dan di TikTok dengan SKU `tiktok-kaos-hitam`. Dengan memetakan kedua SKU ini ke satu Master SKU "Kaos Polos Hitam" di ERP, maka ketika kaos terjual di Shopee, stok master berkurang dan sistem langsung mengupdate sisa stok kaos di TikTok Shop agar stok sinkron di semua toko.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Buat atau Tentukan Master Product',
                        'content' => 'Buat Master Product lokal di gudang Anda yang bertindak sebagai database persediaan pusat fisik.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana cara kerja Safety Stock (Stok Pengaman)?',
                        'content' => 'Safety Stock berfungsi sebagai cadangan fisik di gudang. Jika Anda menyetel Safety Stock sebanyak 2 unit, dan stok Master Anda sisa 2 unit, sistem akan mengirimkan data stok bernilai 0 ke marketplace online agar produk tidak bisa dibeli lagi, menghindari risiko overselling jika ternyata ada fisik barang yang cacat/rusak di gudang.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Petakan (Mapping) SKU Marketplace',
                        'content' => 'Tautkan item produk marketplace ke SKU Master Product yang bersangkutan. Anda dapat memetakan banyak produk marketplace yang berbeda nama ke satu SKU Master gudang yang sama.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Aktifkan Sync Stok Otomatis <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Nyalakan switch <strong>Sinkronisasi Otomatis</strong> agar setiap perubahan stok di Master Product langsung dikirim ke seluruh marketplace terkait dalam hitungan detik.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'slug' => 'transaksi',
                'name' => 'Manajemen Pesanan & Fulfillment (Scan Kemas)',
                'subtitle' => 'Alur memproses pesanan masuk hingga penarikan resi dan update status pengiriman',
                'icon' => 'fas fa-shopping-cart',
                'color' => '#10B981',
                'color_rgb' => '16, 185, 129',
                'read_time' => '4 mnt',
                'workflow_title' => 'Alur Kerja Fulfillment Gudang',
                'sort_order' => 2,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Tarik Pesanan Baru',
                        'content' => 'Pergi ke menu <strong>Pesanan Masuk</strong>, lalu klik tombol <strong>Tarik Pesanan Terbaru</strong> untuk menyinkronkan daftar orderan terbaru dari Shopee dan TikTok Shop.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana memproses barang retur (pengembalian)?',
                        'content' => 'Masuk ke menu <strong>Pesanan Retur</strong>, klik <strong>Tarik Retur Terbaru</strong> untuk mengambil data dari marketplace. Setelah paket retur sampai secara fisik di gudang Anda, klik tombol <strong>Kembalikan ke Stok (Restock)</strong> agar sistem mengembalikan jumlah stok produk tersebut ke database gudang pusat.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Booking Kurir & Cetak Resi',
                        'content' => 'Klik tombol <strong>Proses Pengiriman</strong> pada invoice pesanan untuk meminta nomor resi / AWB resmi dari kurir. Setelah disetujui, cetak label pengiriman thermal secara massal.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah kami bisa mengemas pesanan tanpa menggunakan scanner barcode?',
                        'content' => 'Bisa. Pada halaman Kemas Pesanan (Scan), Anda dapat mengetikkan ID Invoice atau nomor resi secara manual pada kolom input pencarian jika scanner barcode Anda tidak aktif.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Scan Barcode Resi (Fulfillment Scan)',
                        'content' => 'Buka halaman <strong>Kemas Pesanan (Scan)</strong>, lalu scan barcode resi pengiriman yang tertera pada paket menggunakan barcode scanner Anda.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Mengapa resi gagal ditarik (Error AWB)?',
                        'content' => 'Hal ini biasanya terjadi jika server kurir logistik dari pihak marketplace sedang mengalami gangguan (overload). Tunggu beberapa menit kemudian klik tombol <strong>Tarik Resi Ulang</strong>.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Verifikasi Barang & Potong Stok <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Scan barcode produk fisik satu per satu untuk memvalidasi isinya. Setelah isi paket terkonfirmasi valid, sistem otomatis memperbarui status pesanan menjadi "Siap Dikirim" ke marketplace dan memotong stok gudang lokal.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'slug' => 'stok',
                'name' => 'Inventori, Barang Masuk, & Opname Stok',
                'subtitle' => 'Mengelola pasokan barang fisik di gudang serta penyesuaian selisih stok database vs fisik',
                'icon' => 'fas fa-boxes',
                'color' => '#F59E0B',
                'color_rgb' => '245, 158, 11',
                'read_time' => '3 mnt',
                'workflow_title' => 'Alur Kerja Kelola Persediaan',
                'sort_order' => 3,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Penerimaan Barang (Barang Masuk)',
                        'content' => 'Ketika menerima pasokan dari Supplier, buat nota <strong>Barang Masuk</strong> baru, tentukan supplier, lalu masukkan daftar produk dan kuantitasnya. Stok Master Anda akan bertambah otomatis.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Mengapa stok di sistem bisa berbeda dengan stok fisik di gudang?',
                        'content' => 'Selisih stok sering disebabkan oleh barang rusak/cacat yang tidak tercatat, salah packing saat pengiriman, barang retur tidak di-input ulang, atau kehilangan fisik. Disarankan melakukan audit **Opname Stok** minimal sebulan sekali.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Pemantauan Kartu Stok (Stock Ledger)',
                        'content' => 'Gunakan menu <strong>Kartu Stok</strong> untuk mengaudit riwayat pergerakan keluar-masuk barang secara rinci (misal: pengurangan order online, penambahan barang masuk, retur).',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah saat kita melakukan barang masuk, stok di marketplace langsung berubah?',
                        'content' => 'Ya. Jika produk tersebut telah ter-mapping ke Master Product dan status Sinkronisasi Stok Aktif, penambahan stok lokal (dari Barang Masuk / Opname) akan memicu update otomatis ke seluruh toko marketplace Anda.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Opname Stok (Penyesuaian Fisik) <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Jika terjadi selisih stok sistem vs fisik gudang, buat dokumen penyesuaian di menu <strong>Opname Stok</strong>. Masukkan kuantitas riil fisik, sistem akan mencatat selisih rugi/lebihnya.',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'slug' => 'keuangan',
                'name' => 'Laporan Profitabilitas & Rekonsiliasi Dana',
                'subtitle' => 'Pemantauan laba bersih usaha dan pencocokan uang pencairan dari marketplace',
                'icon' => 'fas fa-wallet',
                'color' => '#EF4444',
                'color_rgb' => '239, 68, 68',
                'read_time' => '4 mnt',
                'workflow_title' => 'Alur Kerja Pencatatan Keuangan',
                'sort_order' => 4,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Input Nilai HPP (COGS) Produk',
                        'content' => 'Pastikan Anda mengisi harga modal dasar (HPP) di setiap Master Product. HPP ini penting untuk menghitung margin keuntungan dari setiap transaksi penjualan secara akurat.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Dari mana sistem mendeteksi biaya komisi administrasi marketplace?',
                        'content' => 'Sistem ERP Marketplace menarik rincian biaya komisi, biaya layanan promosi (seperti gratis ongkir ekstra), serta potongan voucher belanja langsung dari log settlement API pesanan Shopee & TikTok secara berkala ketika sinkronisasi pesanan dijalankan.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Pantau Laporan Profit per Order',
                        'content' => 'Setiap pesanan selesai akan dihitung profitnya secara otomatis di menu <strong>Laporan Profit / Pesanan</strong> dengan mengurangkan Omset Kotor dengan HPP dan komisi admin.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Mengapa pencairan dana bank saya berbeda dengan omset kotor pesanan?',
                        'content' => 'Perbedaan ini disebabkan oleh biaya potongan admin, biaya pengiriman jika terjadi perbedaan berat paket (charge selisih ongkir), dan diskon promosi yang ditanggung penjual. Gunakan menu <strong>Rekonsiliasi Keuangan</strong> untuk menganalisis jika ada selisih potongan ilegal.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Lakukan Rekonsiliasi Dana Bank',
                        'content' => 'Gunakan menu <strong>Rekonsiliasi Keuangan</strong> untuk membandingkan uang rilis marketplace dengan uang yang masuk ke rekening bank Anda secara otomatis.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Pencatatan Biaya Pengeluaran Operasional <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Catat pengeluaran rutin non-produk (ongkos kirim selisih, lakban, gaji staff, iklan, dll) pada menu <strong>Pengeluaran & Biaya</strong> untuk menghasilkan laporan laba rugi bulanan bersih.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'slug' => 'chat',
                'name' => 'Inbox Chat Multi-Toko (Customer Service)',
                'subtitle' => 'Membalas chat dari Shopee & TikTok Shop secara langsung dalam satu dasbor ERP terpusat',
                'icon' => 'fas fa-comments',
                'color' => '#06B6D4',
                'color_rgb' => '6, 182, 212',
                'read_time' => '3 mnt',
                'workflow_title' => 'Alur Kerja Inbox Chat',
                'sort_order' => 5,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Menerima Chat Pelanggan',
                        'content' => 'Setiap pesan baru dari pembeli di Shopee atau TikTok Shop otomatis disinkronkan ke menu <strong>Inbox Chat</strong> dalam hitungan detik tanpa perlu membuka web seller-center terpisah.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah membalas chat lewat ERP mempengaruhi performa persentase chat toko saya?',
                        'content' => 'Ya. Karena ERP menggunakan koneksi API resmi dari Shopee dan TikTok Shop, semua pesan yang dikirim dari sistem ini dihitung sebagai respon chat yang valid dan akan membantu menjaga performa persentase chat toko Anda tetap tinggi.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Gunakan Fitur Auto-Template (Quick Reply)',
                        'content' => 'Pilih pesan template cepat yang sudah dikonfigurasi sebelumnya (seperti info ukuran, stok ready, dll) untuk membalas pembeli secara cepat dan konsisten.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Berapa lama delay sinkronisasi chat?',
                        'content' => 'Sinkronisasi pesan berjalan secara real-time melalui webhook API. Rata-rata delay pesan masuk berkisar antara 1 hingga 4 detik tergantung pada beban server API dari marketplace tersebut.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Kirim Rekomendasi Produk Langsung <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Klik tombol rekomendasi produk di dalam panel chat, pilih item dari master produk ERP, dan kirimkan link card produk tersebut langsung ke ruang obrolan pelanggan.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah inbox chat mendukung pengiriman media (gambar/video)?',
                        'content' => 'Pengiriman gambar sepenuhnya didukung langsung dari dasbor ERP. Namun, untuk pengiriman dan pemutaran file video, saat ini belum diakomodasi oleh API marketplace, sehingga Anda harus membukanya melalui web seller-center resmi.',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'slug' => 'pos',
                'name' => 'POS Offline (Point of Sale / Kasir Fisik)',
                'subtitle' => 'Melayani penjualan langsung secara fisik di toko/butik offline Anda dan memotong stok gudang secara real-time',
                'icon' => 'fas fa-store-slash',
                'color' => '#EC4899',
                'color_rgb' => '236, 72, 153',
                'read_time' => '4 mnt',
                'workflow_title' => 'Alur Transaksi Kasir POS',
                'sort_order' => 6,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Buka Dasbor Penjualan Offline',
                        'content' => 'Buka halaman <strong>Penjualan Offline</strong> untuk memuat modul kasir (POS). Modul ini dioptimalkan agar ringan saat digunakan bertransaksi.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah penjualan kasir offline akan langsung memotong stok di Shopee/TikTok?',
                        'content' => 'Ya. Transaksi POS offline langsung memotong kuantitas Master Product di gudang lokal Anda. Sistem kemudian langsung mendorong perubahan sisa stok terbaru ke seluruh etalase marketplace yang terhubung dalam waktu < 5 detik untuk menghindari tabrakan stok dengan pembeli online.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Scan Barcode Barang Belanjaan',
                        'content' => 'Gunakan barcode scanner untuk memindai barcode produk fisik pembeli. Produk otomatis masuk ke keranjang belanja beserta harga modal & diskon yang aktif.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah POS offline kasir bisa dijalankan tanpa koneksi internet?',
                        'content' => 'Tidak. Karena sistem ERP terintegrasi multi-channel berbasis cloud, kasir membutuhkan koneksi internet aktif agar tidak terjadi penjualan barang online secara bersamaan yang bisa memicu overselling.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Pilih Metode Pembayaran & Checkout',
                        'content' => 'Pilih metode pembayaran (Tunai, Kartu Debit, atau Qris Dinamis). Masukkan nominal uang tunai yang diterima untuk menghitung kembalian.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana kasir merekap omset per shift harian?',
                        'content' => 'Kasir dapat membuka laporan rekap penjualan di menu Laporan Penjualan Offline, lalu memfilternya berdasarkan tanggal hari ini dan nama karyawan yang bertugas untuk melihat total omset tunai vs non-tunai.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Cetak Struk Belanja & Update Stok <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Tekan tombol <strong>Bayar & Cetak</strong> untuk mencetak nota melalui printer thermal USB/Bluetooth. Stok master gudang langsung terpotong saat itu juga dan merilis update ke toko online.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'slug' => 'akses',
                'name' => 'Karyawan & Hak Akses (Multi-User)',
                'subtitle' => 'Mengelola peran kustom karyawan dan membatasi izin akses menu demi keamanan database ERP',
                'icon' => 'fas fa-user-shield',
                'color' => '#8492A6',
                'color_rgb' => '132, 146, 166',
                'read_time' => '3 mnt',
                'workflow_title' => 'Alur Kerja Manajemen Karyawan',
                'sort_order' => 7,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Buat Peran / Role Kustom',
                        'content' => 'Masuk ke menu <strong>Hak Akses</strong>, lalu buat nama role baru sesuai kebutuhan operasional (misal: "Kasir Toko", "Staff Gudang Packing", "Akuntan Keuangan").',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah staff gudang bisa mengakses laporan keuntungan jika tidak diizinkan?',
                        'content' => 'Tidak. Jika izin menu keuangan tidak dicentang untuk role staff gudang, seluruh menu Laporan Laba Rugi, Rekonsiliasi, dan Kartu Stok nominal keuangan akan secara otomatis disembunyikan sepenuhnya dari dasbor mereka ketika login.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Pemberian Izin Menu (Permissions)',
                        'content' => 'Centang checkbox menu dan tombol aksi yang boleh diakses oleh role tersebut. Semisal, sembunyikan menu Keuangan dari staff gudang.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana jika akun karyawan terkunci atau lupa password?',
                        'content' => 'Sebagai Tenant Owner or Admin Utama, Anda dapat mereset kata sandi karyawan secara manual melalui menu <strong>Master -> Pengguna -> Edit Pengguna -> Ganti Password</strong>.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Tambahkan Data Karyawan & Tautkan Role <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Pergi ke menu <strong>Karyawan -> Tambah Karyawan</strong>, isi identitas email/username dan kaitkan dengan Role yang telah Anda buat sebelumnya. Karyawan siap login dengan hak akses terbatas.',
                        'sort_order' => 3,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apa perbedaan Tenant Owner dengan Role Admin?',
                        'content' => 'Tenant Owner memiliki wewenang penuh atas kepemilikan langganan ERP, pembayaran paket, dan penghapusan database keseluruhan, sedangkan Role Admin hanya bertugas mengelola operasional sistem harian tanpa memiliki akses ke setelan billing/langganan.',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'slug' => 'laporan',
                'name' => 'Laporan Gudang & Analitik Inventori',
                'subtitle' => 'Memantau perputaran stok barang, laporan opname, serta estimasi kapan stok akan habis',
                'icon' => 'fas fa-chart-pie',
                'color' => '#3B82F6',
                'color_rgb' => '59, 130, 246',
                'read_time' => '3 mnt',
                'workflow_title' => 'Alur Kerja Laporan & Analitik',
                'sort_order' => 8,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Akses Menu Analitik Inventori',
                        'content' => 'Buka halaman <strong>Analitik Inventori</strong> di bawah menu LAPORAN untuk memuat dasbor grafik laju stok Anda.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana sistem menghitung prediksi sisa hari stok (stock-out)?',
                        'content' => 'Sistem ERP Marketplace memantau laju penjualan rata-rata produk (*Sales Velocity*) selama 7, 30, dan 90 hari terakhir. Nilai rata-rata tersebut digunakan untuk membagi jumlah stok master aktif saat ini guna menghasilkan estimasi jumlah hari yang tersisa sebelum barang habis.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Evaluasi Estimasi Habis Stok (Stock-out Forecast)',
                        'content' => 'Sistem otomatis menghitung kecepatan penjualan rata-rata produk harian untuk memprediksi sisa hari sebelum stok fisik Anda habis total.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah laporan gudang dapat difilter berdasarkan channel toko tertentu?',
                        'content' => 'Ya. Di dalam modul laporan rekap persediaan, Anda dapat memilih filter "Channel Toko" (seperti Shopee atau TikTok Shop) untuk melihat kontribusi penjualan masing-masing toko terhadap pengurangan stok master gudang pusat Anda.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Ekspor Rekap Persediaan <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Masuk ke menu <strong>Rekap Persediaan</strong> atau <strong>Stok Barang</strong>, pilih filter rentang tanggal, lalu klik tombol <strong>Ekspor PDF / Excel</strong> untuk mencetak data mutasi gudang.',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'slug' => 'voucher',
                'name' => 'Promosi & Voucher Marketplace',
                'subtitle' => 'Mengelola kampanye diskon, coret harga, serta rekonsiliasi subsidi voucher belanja',
                'icon' => 'fas fa-ticket-alt',
                'color' => '#F59E0B',
                'color_rgb' => '245, 158, 11',
                'read_time' => '3 mnt',
                'workflow_title' => 'Alur Kerja Voucher & Promosi',
                'sort_order' => 9,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Buka Menu Voucher & Promosi',
                        'content' => 'Pilih menu <strong>Voucher / Promosi</strong> di panel MASTER atau TRANSAKSI untuk membuka dashboard promosi gabungan.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana sistem mencatat voucher subsidi diskon dari marketplace?',
                        'content' => 'Potongan belanja dari voucher subsidi (misal diskon potongan TikTok Shop) tidak akan mengurangi omset bersih toko Anda. Nilai diskon tersebut akan dicatat oleh ERP sebagai piutang platform dan ditambahkan kembali sebagai penerimaan ketika rekonsiliasi dana selesai ditarik ke bank.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Tarik Data Campaign Aktif',
                        'content' => 'Klik <strong>Tarik Promosi Terbaru</strong> untuk mengimpor promo coret harga atau kode voucher diskon yang sedang berjalan di Shopee dan TikTok Shop.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Apakah kita bisa membuat promosi coret harga langsung dari dasbor ERP?',
                        'content' => 'Untuk menjaga kestabilan promosi dan kepatuhan terhadap API, pembuatan diskon coret harga disarankan tetap dikonfigurasi melalui seller center masing-masing marketplace. Sistem ERP akan secara otomatis menarik data promosi tersebut untuk sinkronisasi pesanan.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Mapping Subsidi Diskon <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Sistem secara otomatis membaca rincian order untuk memisahkan nominal diskon yang dipotong: apakah ditanggung penuh oleh seller atau disubsidi oleh pihak platform marketplace.',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'slug' => 'pengaturan',
                'name' => 'Pengaturan Tenant, Profil, & Logistik Ekspedisi',
                'subtitle' => 'Konfigurasi profil usaha, alamat gudang fisik utama, ekspedisi kurir, dan printer thermal',
                'icon' => 'fas fa-cog',
                'color' => '#10B981',
                'color_rgb' => '16, 185, 129',
                'read_time' => '3 mnt',
                'workflow_title' => 'Alur Konfigurasi Awal',
                'sort_order' => 10,
                'items' => [
                    [
                        'type' => 'workflow',
                        'title' => 'Buka Menu Pengaturan Sistem',
                        'content' => 'Klik menu <strong>Pengaturan</strong> di dashboard untuk membuka setelan profil tenant (alamat perusahaan, info kontak, logo, dll).',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Bagaimana cara mengubah profil alamat gudang fisik utama?',
                        'content' => 'Masuk ke menu <strong>Pengaturan -> Alamat Gudang</strong>, klik Edit, isi titik koordinat serta alamat lengkap. Alamat gudang ini digunakan sebagai acuan titik pick-up kurir logistik dan perhitungan tarif ongkir pada POS Kasir Offline.',
                        'sort_order' => 1,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Aktifkan Ekspedisi & Kurir (Shipments)',
                        'content' => 'Buka sub-menu <strong>Logistik / Ekspedisi</strong> untuk mencentang jenis ekspedisi yang didukung oleh gudang Anda (J&T, JNE, SiCepat, Shopee Express, GoSend, dll).',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Mengapa ekspedisi pengiriman tertentu tidak muncul saat proses booking kurir?',
                        'content' => 'Pastikan ekspedisi tersebut telah diaktifkan di setelan pengiriman pada toko seller center marketplace resmi Anda (misal seller center Shopee). Setelah itu, lakukan refresh logistik di ERP untuk memuat ulang daftar opsi kurir yang valid.',
                        'sort_order' => 2,
                    ],
                    [
                        'type' => 'workflow',
                        'title' => 'Konfigurasi Printer Thermal & Webhook <span class="flow-badge">Alur Selesai</span>',
                        'content' => 'Setel format kertas struk belanja / label resi (seperti format kertas A6 thermal) dan pasang webhook URL notifikasi logistik untuk update resi otomatis.',
                        'sort_order' => 3,
                    ],
                ],
            ],
        ];

        foreach ($data as $catData) {
            $items = $catData['items'] ?? [];
            unset($catData['items']);

            $category = FaqCategory::create($catData);

            foreach ($items as $item) {
                $item['faq_category_id'] = $category->id;
                FaqItem::create($item);
            }
        }
    }
}

