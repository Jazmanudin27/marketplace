<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shopee = app(\App\Services\ShopeeService::class);

$stores = \App\Models\Store::with('channel')
    ->whereHas('channel', fn($q) => $q->where('code', 'shopee'))
    ->whereNotNull('access_token')
    ->get();

foreach ($stores as $store) {
    echo "=== Store ID:{$store->id} — {$store->store_name} ===" . PHP_EOL;
    echo "Expires: " . ($store->token_expires_at ?? 'NULL') . PHP_EOL;
    echo "Is expired: " . ($store->isTokenExpired() ? 'YES' : 'NO') . PHP_EOL;

    $shopId = (int) $store->marketplace_store_id;
    $refreshToken = $store->getAttributes()['refresh_token'];

    if (!$refreshToken) {
        echo "No refresh token — skip" . PHP_EOL;
        continue;
    }

    try {
        echo "Refreshing token for shop_id={$shopId}..." . PHP_EOL;
        $data = $shopee->refreshAccessToken($refreshToken, $shopId);
        echo "New token obtained: " . substr($data['access_token'], 0, 20) . "..." . PHP_EOL;
        echo "Expires in: " . ($data['expire_in'] ?? '?') . " seconds" . PHP_EOL;

        // Save to DB
        $store->access_token = $data['access_token'];
        $store->refresh_token = $data['refresh_token'] ?? $refreshToken;
        $store->token_expires_at = now()->addSeconds($data['expire_in'] ?? 14400);
        $store->save();

        echo "Token saved! New expiry: " . $store->token_expires_at . PHP_EOL;
    } catch (\Throwable $e) {
        echo "REFRESH FAILED: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
}
echo "Done." . PHP_EOL;
