<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;

$search = $argv[1] ?? '585509092544906406';

echo "========================================================\n";
echo "INSPEKSI MEMORY DATABASE ERP UNTUK ID: {$search}\n";
echo "========================================================\n\n";

$orders = Order::where('order_marketplace_id', 'LIKE', "%{$search}%")
    ->orWhere('id', 'LIKE', "%{$search}%")
    ->orWhere('invoice_number', 'LIKE', "%{$search}%")
    ->get();

if ($orders->isEmpty()) {
    echo "❌ Order ID '{$search}' TIDAK DITEMUKAN sama sekali di database ERP lokal.\n";
    exit;
}

foreach ($orders as $o) {
    $storeName = $o->store ? $o->store->store_name : 'Unknown Store';
    $channelCode = $o->store && $o->store->channel ? $o->store->channel->code : 'Unknown Channel';
    echo "📌 FOUND IN DB:\n";
    echo "   • ERP Order ID        : {$o->id}\n";
    echo "   • Marketplace Order ID: {$o->order_marketplace_id}\n";
    echo "   • Store               : {$storeName} (ID: {$o->store_id})\n";
    echo "   • Channel             : {$channelCode}\n";
    echo "   • Order Status        : {$o->order_status}\n";
    echo "   • Total Amount (Kotor): Rp " . number_format($o->total_amount, 2) . "\n";
    echo "   • Marketplace Fee     : Rp " . number_format($o->marketplace_fee, 2) . "\n";
    echo "   • Net Amount (Bersih) : Rp " . number_format($o->net_amount, 2) . "\n";
    echo "   • Financial Breakdown : " . json_encode($o->financial_breakdown, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
}
