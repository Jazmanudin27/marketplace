<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tiktokOrders = \App\Models\Order::whereHas('store.channel', function($q) {
    $q->where('code', 'tiktok');
})->get();

echo "Total TikTok Orders: " . $tiktokOrders->count() . PHP_EOL;

foreach ($tiktokOrders as $o) {
    echo "Order #{$o->id} ({$o->order_marketplace_id}) | Status: {$o->order_status} | Stock Deducted: " . ($o->is_stock_deducted ? 'YES' : 'NO') . PHP_EOL;
    foreach ($o->items as $it) {
        $masterSku = $it->masterProduct ? $it->masterProduct->sku : 'NONE';
        $mpSku = $it->marketplaceProduct ? $it->marketplaceProduct->marketplace_sku : 'NONE';
        echo "  - Item ID {$it->id}: {$it->product_name} | Item SKU: {$it->sku} | MasterProduct ID: " . ($it->master_product_id ?? 'NULL') . " (SKU: {$masterSku}) | MP Product ID: " . ($it->marketplace_product_id ?? 'NULL') . " (MP SKU: {$mpSku})\n";
    }
}
