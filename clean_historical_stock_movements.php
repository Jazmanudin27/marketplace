<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterProduct;
use App\Models\StockMovement;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

$isFix = in_array('--fix', $argv);

echo "======================================================================\n";
echo "  PEMBERSIHAN MUTASI STOK PESANAN LAMA & RESTORE STOK (ERP)\n";
echo "======================================================================\n";
echo "  Mode: " . ($isFix ? "LIVE FIX (Hapus Mutasi Ganda & Reset Stok Minus ke 0)" : "DRY-RUN (Deteksi Saja)") . "\n";
echo "======================================================================\n\n";

// 1. DETEKSI MUTASI STOK DARI PESANAN KIPAS/BACKFILL HARI INI
echo "--- 1. MEMERIKSA MUTASI STOK DARI PESANAN LAMA YANG BARU SYNC HARI INI ---\n";

// Cari pergerakan stok (StockMovement) tipe 'out' yang dibuat hari ini tetapi berasal dari order lama (order_date < hari ini)
$todaysMovements = DB::select("
    SELECT sm.id, sm.master_product_id, sm.quantity, sm.reference, sm.created_at, o.order_date, o.order_status
    FROM stock_movements sm
    INNER JOIN orders o ON sm.reference LIKE CONCAT('%', o.order_marketplace_id, '%')
    WHERE sm.type = 'out'
    AND DATE(sm.created_at) = CURDATE()
    AND DATE(o.order_date) < CURDATE()
");

echo "Ditemukan " . count($todaysMovements) . " mutasi stok keluar yang terbuat hari ini dari pesanan lampau.\n";

$restoredStockPerProduct = [];
$movementIdsToDelete = [];

foreach ($todaysMovements as $m) {
    $movementIdsToDelete[] = $m->id;
    $restoredStockPerProduct[$m->master_product_id] = ($restoredStockPerProduct[$m->master_product_id] ?? 0) + $m->quantity;
}

if ($isFix && !empty($movementIdsToDelete)) {
    // Hapus mutasi ganda/lampau
    $deleted = DB::table('stock_movements')->whereIn('id', $movementIdsToDelete)->delete();
    echo "  ✅ Berhasil menghapus {$deleted} baris mutasi stok pesanan lampau!\n";

    // Kembangkan/Kembalikan stok master product
    foreach ($restoredStockPerProduct as $mpId => $qtyToRestore) {
        $mp = MasterProduct::find($mpId);
        if ($mp) {
            $oldStock = $mp->stock;
            $newStock = max(0, $oldStock + $qtyToRestore);
            $mp->update(['stock' => $newStock]);
            echo "  [RESTORE] Product #{$mp->id} ({$mp->sku}) | Stok: {$oldStock} + {$qtyToRestore} -> {$newStock}\n";
        }
    }
}

// 2. RESET SISANYA JIKA MASIH ADA STOK MINUS (< 0)
echo "\n--- 2. MEMERIKSA SISANYA PRODUK BERSTOK MINUS (< 0) ---\n";
$negProducts = MasterProduct::where('stock', '<', 0)->get();
echo "Ditemukan " . $negProducts->count() . " produk yang stoknya masih minus.\n";

if ($isFix && $negProducts->count() > 0) {
    $updated = DB::table('master_products')->where('stock', '<', 0)->update(['stock' => 0]);
    echo "  ✅ Berhasil mereset {$updated} produk ber-stok minus menjadi 0!\n";
}

echo "\n======================================================================\n";
echo "  SELESAI!\n";
if ($isFix) {
    echo "  - Mutasi pesanan lama berhasil dibersihkan & stok produk kembali normal!\n";
} else {
    echo "  Gunakan '--fix' untuk menghapus mutasi lama & mereset stok minus:\n";
    echo "  php clean_historical_stock_movements.php --fix\n";
}
echo "======================================================================\n";
