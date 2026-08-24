<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$counts = Order::where('store_id', 24)
    ->selectRaw('order_status, count(*) as total')
    ->groupBy('order_status')
    ->get();

echo "TikTok Store (ID 24) Order Counts in ERP Database:\n";
foreach ($counts as $row) {
    echo "• {$row->order_status}: {$row->total}\n";
}
