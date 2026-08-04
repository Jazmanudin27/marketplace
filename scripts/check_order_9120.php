<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\StockMovement;
use App\Models\MasterProduct;

$order = Order::with(['items.masterProduct', 'items.marketplaceProduct'])->where('order_marketplace_id', '585373116624635860')->orWhere('id', 9120)->first();

if (!$order) {
    echo "Order #9120 / 585373116624635860 NOT FOUND in DB!\n";
    exit;
}

echo "ORDER DETAILS:\n";
echo "ID                    : {$order->id}\n";
echo "Order Marketplace ID  : {$order->order_marketplace_id}\n";
echo "Order Status          : {$order->order_status}\n";
echo "Is Stock Deducted     : " . ($order->is_stock_deducted ? 'YES' : 'NO') . "\n";
echo "Order Date            : {$order->order_date}\n";
echo "Store ID              : {$order->store_id}\n";
echo "Tenant ID             : {$order->tenant_id}\n\n";

echo "ORDER ITEMS:\n";
foreach ($order->items as $it) {
    $mp = $it->masterProduct;
    echo "Item ID          : {$it->id}\n";
    echo "Product Name     : {$it->product_name}\n";
    echo "Item SKU         : '{$it->sku}'\n";
    echo "Master Product ID: " . ($it->master_product_id ?? 'NULL') . "\n";
    if ($mp) {
        echo "Master Product SKU : {$mp->sku}\n";
        echo "Master Product Name: {$mp->name}\n";
        echo "Master Current Stock: {$mp->stock}\n";

        // Check StockMovements for this master product
        $movements = StockMovement::where('master_product_id', $mp->id)->get();
        echo "Stock Movements count for {$mp->sku}: " . $movements->count() . "\n";
        foreach ($movements as $sm) {
            echo "  - SM ID #{$sm->id} | Type: {$sm->type} | Qty: {$sm->quantity} | Ref: {$sm->reference} | Date: {$sm->created_at}\n";
        }
    } else {
        echo "Master Product: NONE\n";
    }
    echo "----------------------------------------\n";
}
