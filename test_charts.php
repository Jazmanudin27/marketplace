<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Services\ShopeeService;

$store = Store::where('store_name', 'like', '%1442414542%')->first();
if (!$store) {
    echo "Toko tidak ditemukan di database!\n";
    exit;
}

echo "Toko Ditemukan: {$store->store_name} (Shop ID: {$store->marketplace_store_id})\n\n";

$shopeeService = app(ShopeeService::class);
$accessToken = $store->getValidAccessToken();

// Coba beberapa category ID yang relevan
$categoryIds = [
    101757 => 'Kaos (Atasan Anak Laki)',
    101760 => 'Atasan Lainnya (Anak Laki-Laki) ← KATEGORI PRODUK',
    101776 => 'Atasan Lainnya (Anak Perempuan)',
    101769 => 'Celana (Bawahan Anak Laki)',
];

foreach ($categoryIds as $catId => $catName) {
    echo "=== Kategori: $catName (ID: $catId) ===\n";
    try {
        $charts = $shopeeService->getSizeChartList($accessToken, (int)$store->marketplace_store_id, $catId);
        echo json_encode($charts, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "  -> ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// ======== CEK ATRIBUT KATEGORI 101760 ========
echo "\n=== ATRIBUT KATEGORI 101760 (cari size_chart) ===\n";
try {
    $attrs = $shopeeService->getCategoryAttributes($accessToken, (int)$store->marketplace_store_id, 101760);
    foreach ($attrs as $attr) {
        $name = $attr['attribute_info']['display_attribute_name'] ?? $attr['attribute_info']['attribute_name'] ?? 'N/A';
        $id   = $attr['attribute_id'] ?? '?';
        $type = $attr['attribute_info']['input_type'] ?? '?';
        $mandatory = !empty($attr['mandatory']) || !empty($attr['is_mandatory']) ? 'WAJIB' : 'opsional';
        echo "  [ID: $id] $name | type: $type | $mandatory\n";
    }
} catch (\Exception $e) {
    echo "ERROR atribut: " . $e->getMessage() . "\n";
}
