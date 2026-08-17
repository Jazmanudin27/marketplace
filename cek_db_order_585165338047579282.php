<?php
/**
 * SCRIPT CEK VERIFIKASI KOLOM DATABASE ERP REAL
 * Dijalankan via terminal: php cek_db_order_585165338047579282.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$orderSn = '585165338047579282';
$order = Order::where('order_marketplace_id', $orderSn)->first();

if (!$order) {
    echo "⚠️ Order ID '{$orderSn}' belum tersimpan di DB lokal. Menjalankan penarikan API & simpan ke DB...\n";
    // Sync order ke DB
    \Illuminate\Support\Facades\Artisan::call('tiktok:sync-escrow', ['--order_id' => $orderSn]);
    $order = Order::where('order_marketplace_id', $orderSn)->first();
}

if (!$order) {
    echo "❌ Order tidak ditemukan.\n";
    exit;
}

echo "======================================================================\n";
echo " ✅ BUKTI ISI KOLOM 'financial_breakdown' DI TABEL 'orders' DATABASE ERP\n";
echo "======================================================================\n";
echo "Order Marketplace ID : {$order->order_marketplace_id}\n";
echo "Toko                 : " . ($order->store->store_name ?? '-') . "\n";
echo "Status Pesanan DB    : {$order->order_status}\n";
echo "Omset Kotor DB       : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
echo "Net Escrow DB        : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
echo "----------------------------------------------------------------------\n";
echo "ISI LENGKAP JSON DI KOLOM DATABASE 'financial_breakdown':\n\n";

print_r($order->financial_breakdown);

echo "\n======================================================================\n";
