<?php

use App\Models\Store;
use App\Jobs\PullProductsFromTiktok;

$store = Store::whereHas('channel', function($q) {
    $q->where('code', 'tiktok');
})->first();

if (!$store) {
    echo "Toko TikTok tidak ditemukan.\n";
    exit;
}

echo "Mencoba sync produk untuk toko: " . $store->store_name . " (ID: " . $store->id . ")\n";

try {
    $job = new PullProductsFromTiktok($store);
    $job->handle(app(\App\Services\TiktokService::class));
    echo "Job selesai dieksekusi tanpa throw error!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
