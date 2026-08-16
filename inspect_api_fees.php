<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\Order;

$stores = Store::where('status', 'connected')->get();

foreach ($stores as $store) {
    $orders = Order::where('store_id', $store->id)
        ->whereNotIn('order_status', ['CANCELLED'])
        ->whereBetween('order_date', ['2026-08-01 00:00:00', '2026-08-16 23:59:59'])
        ->get();

    $totGross = $orders->sum('total_amount');
    $totFee = 0;
    $totNet = $orders->sum('net_amount');

    foreach ($orders as $o) {
        $totFee += $o->marketplace_fee;
    }

    echo "Toko #{$store->id} - {$store->name} (" . strtoupper($store->channel->code ?? '') . "):\n";
    echo "  Total Orders   : " . count($orders) . "\n";
    echo "  Total Gross    : Rp " . number_format($totGross, 0, ',', '.') . "\n";
    echo "  Total Admin Fee: Rp " . number_format($totFee, 0, ',', '.') . "\n";
    echo "  Total Net      : Rp " . number_format($totNet, 0, ',', '.') . "\n";
    echo "  Sample 1 Financial Breakdown: " . json_encode($orders->first()->financial_breakdown ?? []) . "\n\n";
}
