<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$orderId = '585148224195429874';

echo "========================================================\n";
echo "1. DATA ORDER DI DATABASE ERP:\n";
echo "========================================================\n";
$dbOrder = Order::with(['items', 'store', 'channel'])->where('order_marketplace_id', (string)$orderId)->first();
if ($dbOrder) {
    echo "ID (PK): {$dbOrder->id}\n";
    echo "Order MP ID: {$dbOrder->order_marketplace_id}\n";
    echo "Store Name: " . ($dbOrder->store->name ?? $dbOrder->store->store_name ?? 'N/A') . "\n";
    echo "Channel: " . ($dbOrder->channel->code ?? 'N/A') . "\n";
    echo "Status Order ERP: {$dbOrder->status}\n";
    echo "Total Amount (Bruto ERP): {$dbOrder->total_amount}\n";
    echo "Shipping Fee: {$dbOrder->shipping_fee}\n";
    echo "Discount Amount: {$dbOrder->discount_amount}\n";
    echo "Marketplace Fee: {$dbOrder->marketplace_fee}\n";
    echo "Net Amount (Omset Bersih ERP): {$dbOrder->net_amount}\n";
    echo "Income: " . ($dbOrder->income ?? 'N/A') . "\n";
    echo "Tgl Lepas: " . ($dbOrder->tgl_lepas ?? 'N/A') . "\n";
    echo "Order Date: {$dbOrder->order_date}\n";
    
    echo "\nFinancial Breakdown:\n";
    print_r($dbOrder->financial_breakdown);

    echo "\nItems:\n";
    foreach ($dbOrder->items as $item) {
        echo "  - {$item->product_name} | Qty: {$item->quantity} | Price: {$item->price} | Total: {$item->total_price}\n";
    }
} else {
    echo "❌ Order ID '{$orderId}' tidak ditemukan di tabel orders!\n";
}

echo "\n========================================================\n";
echo "2. QUERY ALL TABLES IN DB FOR THIS ORDER ID:\n";
echo "========================================================\n";

$tables = DB::select('SHOW TABLES');
$tableNames = array_map(function($t) {
    return array_values((array)$t)[0];
}, $tables);

foreach ($tableNames as $t) {
    $cols = Schema::getColumnListing($t);
    $matchingCol = null;
    foreach ($cols as $col) {
        if (in_array($col, ['order_marketplace_id', 'order_id', 'marketplace_order_id', 'order_sn', 'order_no'])) {
            $matchingCol = $col;
            break;
        }
    }
    if ($matchingCol) {
        $records = DB::table($t)->where($matchingCol, $orderId)->orWhere($matchingCol, $dbOrder->id ?? 0)->get();
        if (count($records) > 0) {
            echo "Table '{$t}' (col '{$matchingCol}'): " . count($records) . " records found\n";
            foreach ($records as $r) {
                print_r((array)$r);
            }
        }
    }
}
