<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\StockMovement;

echo "=======================================================\n";
echo "SINKRONISASI & PERBAIKAN POTONG STOK PESANAN TIKTOK\n";
echo "=======================================================\n\n";

$pendingOrders = Order::where('is_stock_deducted', false)
    ->where('order_status', '!=', Order::STATUS_CANCELLED)
    ->whereHas('store.channel', function ($q) {
        $q->whereIn('code', ['tiktok', 'tokopedia']);
    })
    ->get();

echo "Ditemukan " . $pendingOrders->count() . " pesanan TikTok yang belum memotong stok.\n\n";

$success = 0;
$failed = 0;

foreach ($pendingOrders as $order) {
    echo "Memproses Pesanan TikTok #{$order->id} (No. Ref: {$order->order_marketplace_id})...\n";
    try {
        $order->processStockDeduction();
        $order->refresh();

        if ($order->is_stock_deducted) {
            $success++;
            echo "  ✅ BERHASIL: Stok terpotong & masuk ke Kartu Stok!\n";
        } else {
            $failed++;
            echo "  ⚠️ TERTAHAN: Sebagian item belum terhubung ke MasterProduct.\n";
        }
    } catch (\Exception $e) {
        $failed++;
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=======================================================\n";
echo "HASIL: {$success} Berhasil, {$failed} Gagal/Tertahan.\n";
echo "=======================================================\n";
