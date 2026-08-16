<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=======================================================\n";
echo "🛠️ PERBAIKAN TANGGAL MUTASI & STOK MINUS (ENGINE KILAT SQL)\n";
echo "=======================================================\n\n";

echo "1. Memperbaiki Tanggal Mutasi Barang Keluar/Masuk Presisi Tanggal Order...\n";

$affected = DB::affectingStatement("
    UPDATE stock_movements sm
    INNER JOIN orders o ON (
        sm.reference = CONCAT('Pesanan Masuk: ', o.order_marketplace_id)
        OR sm.reference = CONCAT('Pembatalan Pesanan: ', o.order_marketplace_id)
    )
    SET sm.created_at = o.order_date, sm.updated_at = o.order_date
    WHERE o.order_date IS NOT NULL AND DATE(sm.created_at) != DATE(o.order_date)
");

echo "✅ SELESAI KILAT! Berhasil mencocokkan {$affected} mutasi barang dengan Tanggal Order!\n\n";

echo "2. Mereset Master Product & Marketplace Product yang Stok Minus (< 0)...\n";

$affectedMp = DB::table('master_products')
    ->where('stock', '<', 0)
    ->update(['stock' => 0]);

$affectedMkp = DB::table('marketplace_products')
    ->where('stock', '<', 0)
    ->update(['stock' => 0, 'sync_stock' => true]);

echo "✅ Berhasil mereset {$affectedMp} produk master & {$affectedMkp} produk toko dari stok minus menjadi 0.\n\n";

echo "=======================================================\n";
echo "✨ SELESAI KILAT HANYA DALAM HITUNGAN DETIK!\n";
echo "=======================================================\n";
