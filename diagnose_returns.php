<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Models\ReturnOrder;

echo "=== 1. TOKO DI DATABASE ===\n";
$stores = Store::with('channel')->get();
foreach ($stores as $s) {
    $hasAccessToken = !empty($s->access_token) ? "ADA" : "KOSONG";
    $hasRefreshToken = !empty($s->refresh_token) ? "ADA" : "KOSONG";
    echo "ID: {$s->id} | Nama: {$s->store_name} | Channel: {$s->channel->code} | Status: {$s->status} | Access Token: {$hasAccessToken} | Refresh Token: {$hasRefreshToken}\n";
}

echo "\n=== 2. JUMLAH DATA RETUR ===\n";
echo "Total baris di tabel return_orders: " . ReturnOrder::count() . "\n";
$latestReturns = ReturnOrder::orderBy('created_at', 'desc')->limit(10)->get();
if ($latestReturns->isEmpty()) {
    echo "Tabel return_orders kosong.\n";
} else {
    foreach ($latestReturns as $r) {
        echo "ID: {$r->id} | SN Retur: {$r->return_sn} | Status: {$r->status} | ID Order Asli: {$r->order_id} | Tanggal Input: {$r->created_at}\n";
    }
}

echo "\n=== 3. ORDER STATUS RETURN ===\n";
echo "Total orders dengan order_status = 'RETURN': " . Order::where('order_status', 'RETURN')->count() . "\n";
$returnOrders = Order::where('order_status', 'RETURN')->limit(5)->get();
foreach ($returnOrders as $o) {
    echo "Order ID: {$o->id} | Marketplace ID: {$o->order_marketplace_id} | Buyer: {$o->buyer_name}\n";
}
