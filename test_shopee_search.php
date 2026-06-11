<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shopeeService = app(\App\Services\ShopeeService::class);try {
    $store = \App\Models\Store::where('store_name', 'Shopee Toko 227617490')->first();
    if (!$store) {
        echo "Store Shopee Toko 227617490 NOT found!\n";
        exit;
    }
    
    echo "Fetching categories on production store: " . $store->store_name . "\n";
    $categories = $shopeeService->getCategoryTree($store->access_token, (int)$store->marketplace_store_id);
    echo "Total categories count: " . count($categories) . "\n";
    
    $matches = [];
    foreach ($categories as $cat) {
        $name = $cat['display_category_name'] ?? '';
        if (stripos($name, 'laptop') !== false) {
            $matches[] = $cat;
        }
    }
    
    echo "Found " . count($matches) . " matching categories on production:\n";
    foreach ($matches as $m) {
        echo "ID: " . $m['category_id'] . ", Name: " . $m['display_category_name'] . ", Parent: " . $m['parent_category_id'] . ", Has Children: " . ($m['has_children'] ? 'YES' : 'NO') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
