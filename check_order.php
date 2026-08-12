<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orderSn = $argv[1] ?? null;

if (!$orderSn) {
    echo "========================================\n";
    echo "PENGGUNAAN:\n";
    echo "php check_order.php <NOMOR_ORDER_MARKETPLACE>\n";
    echo "Contoh: php check_order.php 2607315PUR3M2W\n";
    echo "========================================\n";
    exit;
}

$order = Order::where('order_marketplace_id', (string)$orderSn)
    ->orWhere('invoice_number', (string)$orderSn)
    ->first();

if (!$order) {
    echo "❌ Order dengan nomor '{$orderSn}' tidak ditemukan di database ERP.\n";
    exit;
}

echo "========================================\n";
echo "ID Order           : {$order->id}\n";
echo "No. Order MP       : {$order->order_marketplace_id}\n";
echo "Channel / Toko     : " . ($order->store->store_name ?? 'N/A') . " (" . ($order->store->channel->name ?? 'Marketplace') . ")\n";
echo "Status Order       : {$order->order_status}\n";
echo "Total Amount (Omset): Rp " . number_format($order->total_amount, 2, '.', ',') . "\n";
echo "Marketplace Fee    : Rp " . number_format($order->marketplace_fee, 2, '.', ',') . "\n";
echo "Net Amount (Cair)  : Rp " . number_format($order->net_amount, 2, '.', ',') . "\n";
echo "========================================\n";
echo "Rincian Biaya 5 Komponen ERP (JSON):\n";
print_r($order->fee_breakdown_details);
echo "========================================\n";
if (!empty($order->financial_breakdown)) {
    echo "Financial Breakdown Raw Data (API):\n";
    print_r($order->financial_breakdown);
    echo "========================================\n";
}
