<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

$isFix = in_array('--fix', $argv);

echo "======================================================================\n";
echo "  PEMBERSIHAN & VERIFIKASI SELESAI: ORDER TANPA ITEM (ERP MARKETPLACE)\n";
echo "======================================================================\n\n";

// 1. CEK SELURUH PESANAN KOSONG DI DATABASE
$emptyOrders = Order::doesntHave('items')->get();

echo "Total Pesanan Tanpa Item di Database ERP: " . $emptyOrders->count() . " pesanan.\n";

if ($emptyOrders->isEmpty()) {
    echo "🎉 LUAR BIASA! Seluruh pesanan di ERP Anda sudah 100% MEMILIKI ITEM LENGKAP!\n";
    exit(0);
}

// Kelompokkan berdasarkan ketersediaan Toko Aktif
$connectedStoreIds = Store::where('status', '!=', 'disconnected')->pluck('id')->toArray();

$orphanedOrders = $emptyOrders->filter(fn($o) => !in_array($o->store_id, $connectedStoreIds));
$activeEmptyOrders = $emptyOrders->filter(fn($o) => in_array($o->store_id, $connectedStoreIds));

echo "  - Pesanan Kosong milik Toko Terhubung Aktif : " . $activeEmptyOrders->count() . " pesanan\n";
echo "  - Pesanan Kosong dari Toko Terputus/Dihapus : " . $orphanedOrders->count() . " pesanan (Sampah/Orphaned)\n\n";

if ($orphanedOrders->count() > 0) {
    echo "--- PEMBERSIHAN PESANAN SAMPAH DARI TOKO TERPUTUS/DIHAPUS ---\n";
    foreach ($orphanedOrders as $o) {
        echo "  [ORPHANED ORDER] ID #{$o->id} | SN: {$o->order_marketplace_id} | Store ID: #{$o->store_id}\n";
    }

    if ($isFix) {
        $orphanedIds = $orphanedOrders->pluck('id')->toArray();
        $deleted = DB::table('orders')->whereIn('id', $orphanedIds)->delete();
        echo "\n✅ Berhasil membersihkan {$deleted} pesanan sampah dari toko yang sudah tidak aktif/dihapus!\n";
    } else {
        echo "\n💡 Jalankan dengan --fix untuk membersihkan {$orphanedOrders->count()} pesanan sampah ini:\n";
        echo "   php fix_duplicate_and_empty_items.php --fix\n";
    }
}

echo "\n======================================================================\n";
