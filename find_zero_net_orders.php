<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$orders = Order::whereNotIn('order_status', ['CANCELLED'])
    ->where(function($q) {
        $q->where('net_amount', '<=', 0)
          ->orWhereColumn('marketplace_fee', '>=', 'total_amount');
    })
    ->get();

echo "=======================================================\n";
echo "🔍 MEMERIKSA ORDER DENGAN NET AMOUNT = 0 / BIAYA LAIN PENUH:\n";
echo "=======================================================\n";
echo "Menemukan " . count($orders) . " order berselisih (Net = 0):\n\n";

foreach ($orders->take(10) as $o) {
    $fb = $o->financial_breakdown ?? [];
    $details = $o->fee_breakdown_details;
    echo "Order SN      : {$o->order_marketplace_id}\n";
    echo "  • Store     : " . ($o->store->name ?? '-') . " (" . ($o->store->channel->code ?? '-') . ")\n";
    echo "  • Status    : {$o->order_status}\n";
    echo "  • Total Amt : Rp " . number_format($o->total_amount, 0, ',', '.') . "\n";
    echo "  • Fee Mkt   : Rp " . number_format($o->marketplace_fee, 0, ',', '.') . "\n";
    echo "  • Net Amt   : Rp " . number_format($o->net_amount, 0, ',', '.') . "\n";
    echo "  • Fee Details: " . json_encode($details) . "\n";
    echo "  • RAW Breakdown: " . json_encode($fb) . "\n";
    echo "-------------------------------------------------------\n";
}
