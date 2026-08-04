<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\Channel;
use App\Models\Store;
use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;

echo "==========================================" . PHP_EOL;
echo "TEST: TikTok Order Stock Deduction & Stock Movement Fix" . PHP_EOL;
echo "==========================================" . PHP_EOL;

// 1. Ambil atau buat data dummy untuk pengujian
$tenant = Tenant::firstOrCreate(['name' => 'Test Tenant'], ['code' => 'TEST_TENANT']);
$channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
$store = Store::firstOrCreate(
    ['tenant_id' => $tenant->id, 'marketplace_store_id' => 'TT_STORE_TEST_99'],
    ['channel_id' => $channel->id, 'store_name' => 'TikTok Test Shop', 'status' => 'connected']
);

$testSku = 'TEST-TT-SKU-' . rand(1000, 9999);
$masterProduct = MasterProduct::create([
    'tenant_id' => $tenant->id,
    'sku' => $testSku,
    'name' => 'Produk TikTok Test ' . $testSku,
    'stock' => 100,
    'cost_price' => 50000,
    'selling_price' => 75000,
]);

echo "1. Master Product dibuat: {$masterProduct->sku} (Stok Awal: {$masterProduct->stock})" . PHP_EOL;

$mpProduct = MarketplaceProduct::create([
    'store_id' => $store->id,
    'master_product_id' => $masterProduct->id,
    'marketplace_product_id' => 'MP_TT_PROD_' . rand(1000, 9999),
    'marketplace_variant_id' => 'MP_TT_VAR_' . rand(1000, 9999),
    'marketplace_sku' => $testSku,
    'name' => 'Marketplace Product TikTok Test',
    'price' => 75000,
    'stock' => 100,
]);

echo "2. Marketplace Product dibuat: MP ID {$mpProduct->id}" . PHP_EOL;

// 2. Buat Order Dummy TikTok
$orderMarketplaceId = 'TT_ORDER_' . rand(100000, 999999);
$order = Order::create([
    'tenant_id' => $tenant->id,
    'store_id' => $store->id,
    'order_marketplace_id' => $orderMarketplaceId,
    'order_status' => Order::STATUS_READY_TO_SHIP,
    'buyer_name' => 'Pembeli TikTok Test',
    'total_amount' => 75000,
    'is_stock_deducted' => false,
]);

echo "3. Order dibuat: Order #{$order->id} ({$order->order_marketplace_id})" . PHP_EOL;

// 3. Buat Order Item
$orderItem = OrderItem::create([
    'order_id' => $order->id,
    'sku' => $testSku,
    'marketplace_product_id' => $mpProduct->id,
    'master_product_id' => null, // Simulasi awal belum ter-link
    'product_name' => 'Baju TikTok Test',
    'price' => 75000,
    'quantity' => 2,
    'total_price' => 150000,
]);

echo "4. OrderItem dibuat: Item ID {$orderItem->id} (master_product_id: NULL, Qty: 2)" . PHP_EOL;

// 4. Panggil processStockDeduction()
echo "5. Memanggil processStockDeduction() pada Order #{$order->id}..." . PHP_EOL;
$order->processStockDeduction();
$order->refresh();

$masterProduct->refresh();
$orderItem->refresh();

echo "==========================================" . PHP_EOL;
echo "RESULT VERIFICATION:" . PHP_EOL;
echo "==========================================" . PHP_EOL;
echo "Order is_stock_deducted: " . ($order->is_stock_deducted ? "YES ✅" : "NO ❌") . PHP_EOL;
echo "OrderItem master_product_id: " . ($orderItem->master_product_id ?? 'NULL') . " (Expected: {$masterProduct->id})" . PHP_EOL;
echo "Master Product Stock After: {$masterProduct->stock} (Expected: 98)" . PHP_EOL;

$sm = StockMovement::where('master_product_id', $masterProduct->id)
    ->where('reference', 'Pesanan Masuk: ' . $orderMarketplaceId)
    ->first();

if ($sm) {
    echo "Stock Movement (Kartu Stok): CREATED ✅ | Type: {$sm->type} | Qty: {$sm->quantity} | Ref: {$sm->reference}" . PHP_EOL;
} else {
    echo "Stock Movement (Kartu Stok): NOT CREATED ❌" . PHP_EOL;
}

// Cleanup
$sm?->delete();
$orderItem->delete();
$order->delete();
$mpProduct->delete();
$masterProduct->delete();

echo "==========================================" . PHP_EOL;
echo "TEST COMPLETED!" . PHP_EOL;
