<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stores = DB::table('stores')
    ->join('channels', 'stores.channel_id', '=', 'channels.id')
    ->select('stores.id', 'stores.store_name', 'stores.marketplace_store_id', 'stores.status', 'channels.name as channel')
    ->get();

echo "\n=== DAFTAR TOKO ===\n";
foreach ($stores as $s) {
    echo sprintf("ID:%-3s | %-35s | shop_id:%-15s | status:%-12s | %s\n",
        $s->id, $s->store_name, $s->marketplace_store_id, $s->status, $s->channel);
}
echo "\nTotal: " . count($stores) . " toko\n";
