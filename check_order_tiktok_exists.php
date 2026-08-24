<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$ids = [
    '585669992609514647',
    '585669817137792557',
    '585669508897866863'
];

foreach ($ids as $id) {
    $order = Order::where('order_marketplace_id', $id)->first();
    if ($order) {
        echo "Order $id exists: status={$order->order_status}, created_at={$order->order_date}\n";
    } else {
        echo "Order $id DOES NOT EXIST in ERP database!\n";
    }
}
