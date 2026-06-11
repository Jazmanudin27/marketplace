<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\TiktokService::class);
$store = \App\Models\Store::whereHas('channel', function($q) { $q->where('code', 'tiktok'); })->first();

try {
    $catId = '700554'; // Keripik & Camilan Isi
    $attributes = $service->getCategoryAttributes($store->access_token, $store->shop_cipher, $catId);
    echo "Mandatory attributes:\n";
    foreach ($attributes as $attr) {
        if (!empty($attr['is_requried'])) {
            echo "ID: " . $attr['id'] . ", Name: " . $attr['name'] . ", is_requried: " . ($attr['is_requried'] ? 'TRUE' : 'FALSE') . "\n";
            if (!empty($attr['values'])) {
                echo "  Values: ";
                foreach (array_slice($attr['values'], 0, 10) as $val) {
                    echo "[" . $val['id'] . ": " . $val['name'] . "] ";
                }
                echo "\n";
            }
        }
        if ($attr['id'] == '101084') {
            echo "\nFound target 101084 attributes details:\n" . json_encode($attr, JSON_PRETTY_PRINT) . "\n\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
