<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;

$dateFrom = $argv[1] ?? '2026-07-01';
$dateTo   = $argv[2] ?? '2026-07-31';
$storeId  = $argv[3] ?? null;

echo "======================================================================\n";
echo "📊 DIAGNOSTIK PEMBANDING DAFAR ORDER LAPORAN DILEPAS ERP (PERIODE: {$dateFrom} s/d {$dateTo})\n";
echo "======================================================================\n\n";

$stores = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))->get();

foreach ($stores as $store) {
    if ($storeId && $store->id != $storeId) continue;

    echo "🏬 Toko: {$store->store_name} (ID #{$store->id})\n";
    echo "----------------------------------------------------------------------\n";

    $orders = Order::where('store_id', $store->id)
        ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
        ->whereNotNull('completed_at')
        ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
        ->orderBy('completed_at', 'asc')
        ->get();

    echo "   Total Order Completed & Dilepas di ERP : " . $orders->count() . " order\n\n";

    if ($orders->count() > 0) {
        echo "   Daftar Order ID di ERP (5 Pertama & 5 Terakhir):\n";
        foreach ($orders->take(5) as $idx => $o) {
            echo "     " . ($idx + 1) . ". ID: '{$o->order_marketplace_id} | Order Date: " . ($o->order_date ? $o->order_date->format('d/m/Y H:i') : '-') . " | Released Date: " . ($o->completed_at ? $o->completed_at->format('d/m/Y H:i') : '-') . " | Net: Rp " . number_format($o->net_amount, 0, ',', '.') . "\n";
        }
        if ($orders->count() > 10) {
            echo "     ...\n";
            foreach ($orders->slice(-5) as $idx => $o) {
                echo "     " . ($orders->count() - 4 + $idx) . ". ID: '{$o->order_marketplace_id} | Order Date: " . ($o->order_date ? $o->order_date->format('d/m/Y H:i') : '-') . " | Released Date: " . ($o->completed_at ? $o->completed_at->format('d/m/Y H:i') : '-') . " | Net: Rp " . number_format($o->net_amount, 0, ',', '.') . "\n";
            }
        }
        echo "\n";
    }
}

echo "======================================================================\n";
