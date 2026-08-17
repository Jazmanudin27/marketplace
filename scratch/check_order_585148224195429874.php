<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Models\ReturnRefund; // check if ReturnRefund model exists or check returns table
use App\Services\TiktokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$orderId = '585148224195429874';

echo "========================================================\n";
echo "1. CHECK DATABASE TABLE STRUCTURE FOR ORDERS & RETURNS\n";
echo "========================================================\n";
$orderColumns = Schema::getColumnListing('orders');
echo "Orders Table Columns:\n" . implode(', ', $orderColumns) . "\n\n";

$tables = DB::select('SHOW TABLES');
$tableNames = array_map(function($t) {
    return array_values((array)$t)[0];
}, $tables);
echo "Tables in DB matching refund/return/statement/settlement:\n";
foreach ($tableNames as $t) {
    if (preg_match('/return|refund|settle|statement|escrow|finance|tiktok/i', $t)) {
        echo " - $t\n";
    }
}

echo "\n========================================================\n";
echo "2. DATA PADA DATABASE ERP UNTUK ORDER {$orderId}:\n";
echo "========================================================\n";
$dbOrder = Order::with(['items', 'store', 'channel'])->where('order_marketplace_id', (string)$orderId)->first();
if ($dbOrder) {
    echo "ID (PK): {$dbOrder->id}\n";
    echo "Order MP ID: {$dbOrder->order_marketplace_id}\n";
    echo "Store: " . ($dbOrder->store->store_name ?? $dbOrder->store->name ?? 'N/A') . "\n";
    echo "Status Order ERP: {$dbOrder->status}\n";
    echo "Total Amount (Bruto): {$dbOrder->total_amount}\n";
    echo "Shipping Fee: {$dbOrder->shipping_fee}\n";
    echo "Discount Amount: {$dbOrder->discount_amount}\n";
    echo "Marketplace Fee: {$dbOrder->marketplace_fee}\n";
    echo "Net Amount (Omset Bersih ERP): {$dbOrder->net_amount}\n";
    echo "Tgl Lepas / Settlement Date: " . ($dbOrder->tgl_lepas ?? 'N/A') . "\n";
    echo "Created At: {$dbOrder->created_at}\n";
    
    echo "\nFinancial Breakdown column:\n";
    var_dump($dbOrder->financial_breakdown);

    echo "\nAll attributes of Order:\n";
    print_r($dbOrder->getAttributes());

    echo "\nOrder Items:\n";
    foreach ($dbOrder->items as $item) {
        echo "  - {$item->product_name} | Qty: {$item->quantity} | Unit Price: {$item->price} | Total: {$item->total_price}\n";
    }
} else {
    echo "❌ Order ID '{$orderId}' tidak ditemukan di tabel orders database ERP!\n";
}

echo "\n========================================================\n";
echo "3. CHECK FOR RETURNS / REFUNDS RECORD IN DB:\n";
echo "========================================================\n";

foreach ($tableNames as $t) {
    if (preg_match('/return|refund|settle|statement|escrow/i', $t)) {
        $cols = Schema::getColumnListing($t);
        $query = DB::table($t);
        // check if order_id or order_marketplace_id or marketplace_order_id exists
        $matchingCol = null;
        foreach ($cols as $col) {
            if (in_array($col, ['order_marketplace_id', 'order_id', 'marketplace_order_id', 'order_sn'])) {
                $matchingCol = $col;
                break;
            }
        }
        if ($matchingCol) {
            $records = DB::table($t)->where($matchingCol, $orderId)->get();
            echo "Table '{$t}' (querying col '{$matchingCol}'): count = " . count($records) . "\n";
            if (count($records) > 0) {
                print_r($records->toArray());
            }
        }
    }
}

echo "\n========================================================\n";
echo "4. QUERY API TIKTOK SHOP FOR ORDER DETAIL & STATEMENT/SETTLEMENT/RETURN:\n";
echo "========================================================\n";

$tiktokService = app(TiktokService::class);
$stores = Store::whereHas('channel', function ($q) {
    $q->whereIn('code', ['tiktok', 'tokopedia']);
})->get();

$foundStore = null;
$foundOrderApi = null;

foreach ($stores as $store) {
    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        if (empty($shopCipher)) continue;

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderId]);
        $ordersList = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

        if (!empty($ordersList)) {
            $foundStore = $store;
            $foundOrderApi = $ordersList[0];
            echo "TOKO MATCH IN TIKTOK API: " . ($store->store_name ?? $store->name) . " (ID: {$store->id})\n";
            break;
        }
    } catch (\Exception $e) {
        echo "Error on store " . ($store->store_name ?? $store->name) . ": " . $e->getMessage() . "\n";
    }
}

if ($foundOrderApi) {
    echo "\nRaw Order Detail from TikTok API:\n";
    echo json_encode($foundOrderApi, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Order tidak ditemukan via TikTok Order Detail API.\n";
}

