<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

$sn = $argv[1] ?? '585554122547168568';

echo "======================================================================\n";
echo "  ANALISA MENGAPA ORDER ID {$sn} TIDAK DITEMUKAN DI API\n";
echo "======================================================================\n\n";

$order = Order::where('order_marketplace_id', $sn)->first();

if (!$order) {
    echo "❌ Order '{$sn}' tidak ditemukan di database ERP.\n";
    exit(1);
}

echo "1. DETAIL STATUS TOKO ID #{$order->store_id} DI DATABASE ERP:\n";
$store = DB::table('stores')->where('id', $order->store_id)->first();

if ($store) {
    echo "   Nama Toko          : " . ($store->store_name ?? $store->name ?? 'N/A') . "\n";
    echo "   Channel            : " . ($store->channel_code ?? 'N/A') . "\n";
    echo "   Status Toko di DB  : " . strtoupper($store->status ?? 'N/A') . "\n";
    echo "   Access Token       : " . (empty($store->access_token) ? 'KOSONG / DISCONNECTED' : 'ADA') . "\n";
    echo "   Refresh Token      : " . (empty($store->refresh_token) ? 'KOSONG' : 'ADA') . "\n";
    echo "   shop_cipher        : " . ($store->shop_cipher ?? 'KOSONG') . "\n";

    if ($store->status === 'disconnected' || empty($store->access_token)) {
        echo "\n💡 KESIMPULAN MENGAPA API TIKTOK RESPON KOSONG:\n";
        echo "   Toko #{$store->id} ({$store->store_name}) berstatus DISCONNECTED / TERPUTUS dari TikTok Shop!\n";
        echo "   Karena koneksi toko ini sudah terputus/di-logout di ERP, sistem tidak memiliki akses izin (Access Token) resmi dari TikTok untuk mengambil detail item pesanan ini.\n";
    } else {
        echo "\n🔍 MENMENCOBA TIKTOK API UNTUK TOKO #{$store->id} KEMBALI...\n";
        try {
            $tStore = Store::find($store->id);
            $tiktokService = app(\App\Services\TiktokService::class);
            $token = $tStore->getValidAccessToken();
            $res = $tiktokService->getOrderDetail($token, $tStore->shop_cipher, [$sn]);
            echo "   API Response: " . json_encode($res) . "\n";
        } catch (\Throwable $e) {
            echo "   ❌ Error TikTok API Toko #{$store->id}: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "❌ Toko ID #{$order->store_id} SUDAH DIHAPUS PERMANEN dari tabel `stores` ERP.\n";
    echo "   Orderan ini adalah pesanan sisa (sampah) dari toko yang pernah Anda hapus.\n";
}

echo "\n======================================================================\n";
