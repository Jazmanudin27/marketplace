<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

echo "========================================================================\n";
echo "PEMBERSIHAN & PENGHAPUSAN MASSAL PESANAN GANDA (DUPLICATE ORDERS) IN ERP\n";
echo "========================================================================\n\n";

// Cari order_marketplace_id yang muncul lebih dari 1 kali
$duplicates = Order::select('tenant_id', DB::raw('TRIM(order_marketplace_id) as mp_id'), DB::raw('COUNT(*) as total_count'))
    ->whereNotNull('order_marketplace_id')
    ->where('order_marketplace_id', '!=', '')
    ->groupBy('tenant_id', DB::raw('TRIM(order_marketplace_id)'))
    ->having('total_count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "✅ TIDAK DITEMUKAN PESANAN GANDA (DUPLICATE) DI DATABASE ERP!\n";
    echo "Database Anda 100% bersih dari pesanan ganda.\n";
    exit;
}

echo "⚠️ Menemukan " . $duplicates->count() . " grup nomor pesanan yang memiliki data ganda (duplicate).\n\n";

$deletedCount = 0;
$mergedCount  = 0;

foreach ($duplicates as $dup) {
    $mpId = $dup->mp_id;
    $tenantId = $dup->tenant_id;

    $orders = Order::where('tenant_id', $tenantId)
        ->where(DB::raw('TRIM(order_marketplace_id)'), $mpId)
        ->orderByDesc('updated_at')
        ->orderByDesc('id')
        ->get();

    if ($orders->count() <= 1) {
        continue;
    }

    // Pilih 1 Utama (Utamakan yang COMPLETED/CANCELLED atau yang punya item)
    $primaryOrder = $orders->first(function ($o) {
        return in_array($o->order_status, ['COMPLETED', 'CANCELLED', 'SELESAI', 'FINISHED', 'BATAL']);
    });

    if (!$primaryOrder) {
        $primaryOrder = $orders->first();
    }

    echo "📌 Memproses ID Order Marketplace: {$mpId} (Total: {$orders->count()} baris ganda)\n";
    echo "   -> Menyimpan Order Utama ID: {$primaryOrder->id} (Status: {$primaryOrder->order_status})\n";

    foreach ($orders as $order) {
        if ($order->id == $primaryOrder->id) {
            continue;
        }

        // Pindahkan items dari order duplikat ke order utama jika order utama belum punya item
        if ($primaryOrder->items->isEmpty() && $order->items->isNotEmpty()) {
            OrderItem::where('order_id', $order->id)->update(['order_id' => $primaryOrder->id]);
        } else {
            // Hapus item pada order duplikat
            OrderItem::where('order_id', $order->id)->delete();
        }

        // Hapus record duplikat
        $order->delete();
        $deletedCount++;
    }

    $mergedCount++;
}

echo "\n========================================================================\n";
echo "✨ PEMBERSIHAN SELESAI!\n";
echo "• Total grup pesanan ganda yang digabung  : {$mergedCount}\n";
echo "• Total baris pesanan duplikat yang dihapus: {$deletedCount}\n";
echo "========================================================================\n";
