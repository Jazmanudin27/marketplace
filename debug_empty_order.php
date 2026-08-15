<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;

echo "======================================================================\n";
echo "  INSPEKSI DETIL 35 ORDER KOSONG (DATABASE ANALYSIS)\n";
echo "======================================================================\n\n";

$emptyOrders = Order::doesntHave('items')
    ->with('store.channel')
    ->orderBy('id', 'desc')
    ->get();

echo "Ditemukan TOTAL " . $emptyOrders->count() . " pesanan kosong di database ERP.\n\n";

echo str_pad("ID ERP", 8) . " | " . str_pad("ORDER MARKETPLACE ID", 25) . " | " . str_pad("STORE ID", 10) . " | " . str_pad("CHANNEL", 12) . " | " . "STATUS\n";
echo str_repeat("-", 80) . "\n";

foreach ($emptyOrders as $o) {
    $cCode = $o->store->channel->code ?? 'KOSONG / OFFLINE';
    $sName = $o->store->name ?? 'NO_STORE';
    echo str_pad("#" . $o->id, 8) . " | " . str_pad($o->order_marketplace_id ?: 'NO_SN', 25) . " | " . str_pad("#" . $o->store_id . " ({$sName})", 10) . " | " . str_pad($cCode, 12) . " | " . $o->order_status . "\n";
}

echo "\n======================================================================\n";
