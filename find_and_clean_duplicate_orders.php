<?php

/**
 * ============================================================
 * DETEKSI & PEMBERSIHAN PESANAN GANDA (DUPLICATE ORDERS)
 * ============================================================
 * Script ini mendeteksi order_marketplace_id yang muncul ganda
 * di database ERP untuk toko yang sama, serta membersihkan
 * duplikatnya secara aman (mempertahankan order yang paling lengkap/terbaru).
 *
 * Cara pakai:
 *   php find_and_clean_duplicate_orders.php --dry-run
 *   php find_and_clean_duplicate_orders.php --fix
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

$args  = array_slice($argv, 1);
$isFix = in_array('--fix', $args);

echo "\n";
echo "======================================================================\n";
echo "  DETEKSI PESANAN GANDA (DUPLICATE ORDERS) DI ERP\n";
echo "======================================================================\n";
echo "  Mode : " . ($isFix ? "LIVE FIX (Hapus Duplikat)" : "DRY-RUN (Preview saja)") . "\n";
echo "======================================================================\n\n";

// Cari order_marketplace_id + store_id yang muncul lebih dari 1x
$duplicates = DB::table('orders')
    ->select('store_id', 'order_marketplace_id', DB::raw('COUNT(*) as total_dup'))
    ->groupBy('store_id', 'order_marketplace_id')
    ->having('total_dup', '>', 1)
    ->get();

echo "Ditemukan " . $duplicates->count() . " nomor order marketplace yang memiliki duplikat ganda.\n\n";

if ($duplicates->isEmpty()) {
    echo "✨ Semua pesanan di database ERP Anda 100% UNIK dan bebas dari duplikat!\n\n";
    exit(0);
}

$totalDeleted = 0;

foreach ($duplicates as $idx => $dup) {
    $storeId = $dup->store_id;
    $mId     = $dup->order_marketplace_id;

    $orders = Order::where('store_id', $storeId)
        ->where('order_marketplace_id', $mId)
        ->withCount('items')
        ->orderByDesc('items_count') // Prioritaskan order yang punya rincian item
        ->orderByDesc('id')          // Lalu prioritaskan ID yang paling baru
        ->get();

    $keepOrder   = $orders->first();
    $deleteOrders = $orders->slice(1);

    $storeName = $keepOrder->store->store_name ?? "Store #{$storeId}";

    echo "  [" . ($idx + 1) . "] Toko: {$storeName} | ID Marketplace: {$mId} (Ganda: {$dup->total_dup}x)\n";
    echo "      👉 DIPERTAHANKAN : ID ERP #{$keepOrder->id} (Items: {$keepOrder->items_count}, Status: {$keepOrder->order_status})\n";

    foreach ($deleteOrders as $del) {
        echo "      ❌ DIHAPUS       : ID ERP #{$del->id} (Items: {$del->items_count}, Status: {$del->order_status})\n";

        if ($isFix) {
            // Hapus item order bawaannya dulu
            OrderItem::where('order_id', $del->id)->delete();
            // Hapus record order duplikat
            Order::where('id', $del->id)->delete();
            $totalDeleted++;
        }
    }
    echo "\n";
}

echo "======================================================================\n";
echo "  RINGKASAN " . ($isFix ? "PEMBERSIHAN" : "PREVIEW") . "\n";
echo "======================================================================\n";
echo "  Total Nomor Order Ganda : " . $duplicates->count() . "\n";
if ($isFix) {
    echo "  Total Row Duplikat Dihapus : {$totalDeleted}\n";
    echo "✨ Selesai! Semua pesanan ganda telah dibersihkan secara aman.\n";
} else {
    echo "  Jalankan perintah ini untuk menghapus duplikat secara aman:\n";
    echo "    php find_and_clean_duplicate_orders.php --fix\n";
}
echo "======================================================================\n\n";
