<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use Illuminate\Support\Facades\DB;

$isFix = in_array('--fix', $argv);
$isSyncMarketplace = in_array('--sync-marketplace', $argv);

echo "======================================================================\n";
echo "  DIAGNOSIS & PERBAIKAN STOK MINUS (ERP MARKETPLACE)\n";
echo "======================================================================\n";
echo "  Mode: " . ($isFix ? "LIVE FIX (Reset Stok Minus ke 0 / Sync Marketplace)" : "DRY-RUN (Deteksi Saja)") . "\n";
echo "======================================================================\n\n";

// 1. DETEKSI PRODUK DENGAN STOK MINUS
$negativeProducts = MasterProduct::where('stock', '<', 0)->get();

echo "Ditemukan " . $negativeProducts->count() . " produk master yang memiliki STOK MINUS (< 0).\n\n";

if ($negativeProducts->count() > 0) {
    echo str_pad("ID", 6) . " | " . str_pad("SKU", 20) . " | " . str_pad("NAMA PRODUK", 45) . " | " . "STOK MINUS\n";
    echo str_repeat("-", 85) . "\n";

    foreach ($negativeProducts->take(30) as $p) {
        echo str_pad($p->id, 6) . " | " . str_pad(substr($p->sku, 0, 20), 20) . " | " . str_pad(substr($p->name, 0, 45), 45) . " | " . $p->stock . "\n";
    }

    if ($negativeProducts->count() > 30) {
        echo "... dan " . ($negativeProducts->count() - 30) . " produk minus lainnya.\n";
    }
}

// 2. PERBAIKAN
if ($isFix) {
    echo "\n--- MELAKUKAN PERBAIKAN STOK MINUS ---\n";

    if ($isSyncMarketplace) {
        echo "1. Sinkronisasi Stok Real dari Marketplace Product ke Master Product...\n";
        $syncedCount = 0;
        foreach ($negativeProducts as $p) {
            $mp = MarketplaceProduct::where('master_product_id', $p->id)
                ->where('stock', '>', 0)
                ->orderBy('stock', 'desc')
                ->first();

            if ($mp && $mp->stock > 0) {
                $oldStock = $p->stock;
                $p->update(['stock' => $mp->stock]);
                $syncedCount++;
                echo "  [SYNC] SKU: {$p->sku} | Stok Lama: {$oldStock} -> Stok Baru dari Marketplace: {$mp->stock}\n";
            } else {
                $p->update(['stock' => 0]);
            }
        }
        echo "✅ Berhasil sinkronisasi {$syncedCount} produk dari marketplace, sisa stok minus di-reset ke 0!\n";
    } else {
        $updated = DB::table('master_products')->where('stock', '<', 0)->update(['stock' => 0]);
        echo "✅ Berhasil mereset {$updated} produk ber-stok minus menjadi 0!\n";
    }
} else {
    echo "\n======================================================================\n";
    echo "💡 MENGAPA BISA TERJADI STOK MINUS?\n";
    echo "1. Stok Awal Belum Diisi (Stok Awal = 0), lalu ada orderan masuk berturut-turut.\n";
    echo "2. Sebelumnya ada 523 item double yang memotong stok 2x (sekarang item double sudah dibersihkan).\n";
    echo "3. Pesanan masuk sebelum stok fisik diinput ke ERP.\n";
    echo "\n👉 CARA MEMPERBAIKI STOK MINUS:\n";
    echo "Option A (Reset Semua Stok Minus ke 0):\n";
    echo "  php fix_negative_stocks.php --fix\n\n";
    echo "Option B (Sinkronkan Stok dari Data Produk Marketplace):\n";
    echo "  php fix_negative_stocks.php --fix --sync-marketplace\n";
    echo "======================================================================\n";
}
