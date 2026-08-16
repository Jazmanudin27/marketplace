<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\StockMovement;
use App\Models\MasterProduct;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=======================================================\n";
echo "🛠️ PERBAIKAN TANGGAL MUTASI & STOK MINUS MASTER PRODUCT\n";
echo "=======================================================\n\n";

// 1. PERBAIKAN TANGGAL MUTASI: Samakan created_at StockMovement dengan order_date di Order
echo "1. Memperbaiki Tanggal Mutasi Barang Keluar agar Sama dengan Tanggal Order...\n";

$movements = StockMovement::where('reference', 'LIKE', 'Pesanan Masuk:%')
    ->orWhere('reference', 'LIKE', 'Pembatalan Pesanan:%')
    ->get();

$fixedDatesCount = 0;
foreach ($movements as $sm) {
    $cleanSn = trim(str_replace(['Pesanan Masuk: ', 'Pembatalan Pesanan: '], '', $sm->reference));
    if ($cleanSn) {
        $order = Order::where('order_marketplace_id', $cleanSn)->first();
        if ($order && $order->order_date) {
            $expectedDate = Carbon::parse($order->order_date)->format('Y-m-d H:i:s');
            $currentDate = Carbon::parse($sm->created_at)->format('Y-m-d H:i:s');
            
            if ($expectedDate !== $currentDate) {
                DB::table('stock_movements')
                    ->where('id', $sm->id)
                    ->update([
                        'created_at' => $expectedDate,
                        'updated_at' => $expectedDate,
                    ]);
                $fixedDatesCount++;
            }
        }
    }
}

echo "✅ Berhasil memperbaiki tanggal pada {$fixedDatesCount} mutasi barang keluar/masuk agar presisi dengan Tanggal Order!\n\n";

// 2. PERBAIKAN STOK MINUS: Reset stok minus (< 0) ke 0 atau samakan dengan saldo awal
echo "2. Memperbaiki Master Product yang Mengalami Stok Minus (< 0)...\n";

$negativeProducts = MasterProduct::where('stock', '<', 0)->get();
echo "Menemukan " . $negativeProducts->count() . " produk master dengan stok minus.\n";

$fixedStockCount = 0;
foreach ($negativeProducts as $mp) {
    echo "  • Fixing SKU: {$mp->sku} | Old Stock: {$mp->stock} -> New Stock: 0\n";
    $mp->update(['stock' => 0]);
    
    // Sync stok ke marketplace_products
    DB::table('marketplace_products')
        ->where('master_product_id', $mp->id)
        ->update([
            'stock' => 0,
            'sync_stock' => true,
            'updated_at' => now(),
        ]);
        
    $fixedStockCount++;
}

echo "\n=======================================================\n";
echo "✨ SELESAI! Berhasil memperbaiki tanggal mutasi & mereset stok minus!\n";
echo "=======================================================\n";
