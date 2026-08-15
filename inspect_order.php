<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

$sn = $argv[1] ?? '585554122547168568';
$isFix = in_array('--fix', $argv);

echo "======================================================================\n";
echo "  PENGISIAN PRESISI ITEM ORDER: {$sn}\n";
echo "======================================================================\n\n";

$order = Order::where('order_marketplace_id', $sn)->first();

if (!$order) {
    echo "❌ Order '{$sn}' tidak ditemukan di database ERP.\n";
    exit(1);
}

$allStores = Store::where('status', '!=', 'disconnected')->get();
$tiktokService = app(\App\Services\TiktokService::class);

$foundStore = null;
$foundTiktokOrder = null;

foreach ($allStores as $sStore) {
    if (!in_array(strtolower($sStore->channel->code ?? ''), ['tiktok', 'tokopedia'])) continue;

    try {
        $token = $sStore->getValidAccessToken();
        $cipher = $sStore->shop_cipher;

        if (empty($token) || empty($cipher)) continue;

        $res = $tiktokService->getOrderDetail($token, $cipher, [$sn]);
        $ordersList = $res['orders'] ?? $res['order_list'] ?? [];

        foreach ($ordersList as $oItem) {
            $oId = (string) ($oItem['id'] ?? $oItem['order_id'] ?? '');
            if ($oId === (string) $sn) {
                $foundStore = $sStore;
                $foundTiktokOrder = $oItem;
                break 2;
            }
        }
    } catch (\Throwable $e) {
        echo "  [ERROR] Toko #{$sStore->id}: " . $e->getMessage() . "\n";
    }
}

if ($foundStore && $foundTiktokOrder) {
    echo "🎯 ORDER DITEMUKAN DI TIKTOK API:\n";
    echo "   Toko Asli Resmikan : ID #{$foundStore->id} - {$foundStore->name}\n";
    echo "   Status Order API   : " . ($foundTiktokOrder['status'] ?? 'N/A') . "\n";

    $itemList = $foundTiktokOrder['line_items'] 
        ?? $foundTiktokOrder['item_list'] 
        ?? $foundTiktokOrder['sku_list'] 
        ?? $foundTiktokOrder['items'] 
        ?? [];

    echo "   Jumlah Item API    : " . count($itemList) . " item\n";

    foreach ($itemList as $idx => $it) {
        $pName = $it['product_name'] ?? $it['item_name'] ?? 'Produk TikTok';
        $vName = $it['sku_name'] ?? $it['variant_name'] ?? '';
        $itemSku = $it['seller_sku'] ?? $it['sku'] ?? null;
        $price = (float) ($it['original_price'] ?? $it['sale_price'] ?? $it['sku_display_price'] ?? $it['price'] ?? 0);
        $qty = (int) ($it['quantity'] ?? 1);

        echo "      [" . ($idx + 1) . "] {$pName}" . ($vName ? " ({$vName})" : "") . " | SKU: {$itemSku} | Qty: {$qty} | Price: Rp " . number_format($price, 0, ',', '.') . "\n";
    }

    if ($isFix && !empty($itemList)) {
        echo "\n🚀 MELAKUKAN UPDATE BESAR KE DATABASE ERP...\n";

        // Update Store ID jika berbeda
        if ($order->store_id !== $foundStore->id) {
            DB::table('orders')->where('id', $order->id)->update(['store_id' => $foundStore->id]);
            echo "   ✅ Store ID diperbarui dari #{$order->store_id} -> #{$foundStore->id}\n";
        }

        // Clean & Insert Items
        DB::table('order_items')->where('order_id', $order->id)->delete();

        $insertRows = [];
        foreach ($itemList as $item) {
            $productId = (string)($item['product_id'] ?? '');
            $skuId     = (string)($item['sku_id'] ?? '');
            $itemSku   = $item['seller_sku'] ?? $item['sku'] ?? null;

            $mp = \App\Models\MarketplaceProduct::where('store_id', $foundStore->id)
                ->where('marketplace_product_id', $productId)
                ->when($skuId, fn($q) => $q->where('marketplace_variant_id', $skuId))
                ->first();

            $price = (float) ($item['original_price'] ?? $item['sale_price'] ?? $item['sku_display_price'] ?? $item['price'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 1);

            $masterProduct = $mp ? $mp->masterProduct : null;
            if (!$masterProduct && $itemSku) {
                $masterProduct = \App\Models\MasterProduct::where('tenant_id', $foundStore->tenant_id)
                    ->where('sku', trim($itemSku))
                    ->first();
            }

            $pName = $item['product_name'] ?? $item['item_name'] ?? 'Produk TikTok';
            $vName = $item['sku_name'] ?? $item['variant_name'] ?? '';

            $insertRows[] = [
                'order_id' => $order->id,
                'marketplace_product_id' => $mp ? $mp->id : null,
                'master_product_id' => $masterProduct ? $masterProduct->id : null,
                'product_name' => mb_substr($pName . ($vName ? " ({$vName})" : ''), 0, 250),
                'sku' => $itemSku,
                'price' => $price,
                'total_price' => $price * $qty,
                'cost_price' => $masterProduct ? (float) $masterProduct->cost_price : 0,
                'quantity' => $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('order_items')->insert($insertRows);
        echo "   ✅ BERHASIL 100%! Memasukkan " . count($insertRows) . " item barang ke database ERP!\n";
    }
} else {
    echo "⚠️ Order ID '{$sn}' tidak ditemukan di API TikTok toko manapun.\n";
}

echo "\n======================================================================\n";
