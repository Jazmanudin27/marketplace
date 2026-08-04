<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\StockMovement;
use App\Models\MasterProduct;

echo "=== ALL ORDERS WITH ID LIKE 9120 OR MARKETPLACE ID 585373116624635860 ===\n";
$orders = Order::with('items.masterProduct')->where('order_marketplace_id', 'like', '%585373116624635860%')->orWhere('id', 9120)->get();

foreach ($orders as $ord) {
    echo "Order ID: {$ord->id} | Marketplace ID: {$ord->order_marketplace_id} | Status: {$ord->order_status} | Deducted: " . ($ord->is_stock_deducted ? 'YES' : 'NO') . "\n";
    foreach ($ord->items as $it) {
        echo "   Item ID: {$it->id} | SKU in item: '{$it->sku}' | Name: {$it->product_name}\n";
        echo "   MasterProduct ID: " . ($it->master_product_id ?? 'NULL') . "\n";
        if ($it->masterProduct) {
            echo "   Mapped Master SKU: '{$it->masterProduct->sku}' | Name: {$it->masterProduct->name}\n";
        }
    }
}

echo "\n=== STOCK MOVEMENTS WITH REFERENCE CONTAINING 585373116624635860 ===\n";
$movements = StockMovement::with('masterProduct')->where('reference', 'like', '%585373116624635860%')->get();
if ($movements->isEmpty()) {
    echo "NO STOCK MOVEMENTS FOUND FOR 585373116624635860!\n";
} else {
    foreach ($movements as $m) {
        echo "SM ID: {$m->id} | Master ID: {$m->master_product_id} | SKU: " . ($m->masterProduct ? $m->masterProduct->sku : 'N/A') . " | Qty: {$m->quantity} | Ref: {$m->reference} | Created: {$m->created_at}\n";
    }
}

echo "\n=== MASTER PRODUCTS IN DB CONTAINING 'BB-' ===\n";
$masters = MasterProduct::where('sku', 'like', '%BB-%')->get();
foreach ($masters as $mp) {
    echo "Master ID: {$mp->id} | SKU: '{$mp->sku}' | Name: {$mp->name} | Stock: {$mp->stock}\n";
    $sms = StockMovement::where('master_product_id', $mp->id)->get();
    echo "   -> Movements Count: " . $sms->count() . "\n";
    foreach ($sms as $s) {
        echo "      SM #{$s->id} | Qty: {$s->quantity} | Ref: {$s->reference} | Date: {$s->created_at}\n";
    }
}
