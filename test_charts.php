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

// ======== CEK support_size_chart KATEGORI 101760 ========
echo "\n=== CEK support_size_chart KATEGORI 101760 ===\n";
try {
    $res = $shopeeService->checkSupportSizeChart($accessToken, (int)$store->marketplace_store_id, 101760);
    echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "ERROR support_size_chart: " . $e->getMessage() . "\n";
}
