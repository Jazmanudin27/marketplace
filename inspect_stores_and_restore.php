<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use Illuminate\Support\Facades\DB;

echo "======================================================================\n";
echo "  INSPEKSI STORE ID & STATUS TOKO ERP (INCLUDING SOFT DELETED)\n";
echo "======================================================================\n\n";

$targetStoreIds = [31, 34, 35, 39, 43, 44, 46];

echo str_pad("STORE ID", 10) . " | " . str_pad("NAME / STORE NAME", 25) . " | " . str_pad("CHANNEL", 10) . " | " . str_pad("STATUS", 12) . " | DELETED_AT\n";
echo str_repeat("-", 80) . "\n";

foreach ($targetStoreIds as $sId) {
    $storeRaw = DB::table('stores')->where('id', $sId)->first();
    
    if ($storeRaw) {
        $sName = $storeRaw->store_name ?? $storeRaw->name ?? 'N/A';
        $cCode = $storeRaw->channel_code ?? $storeRaw->channel ?? 'N/A';
        $status = $storeRaw->status ?? 'N/A';
        $deletedAt = $storeRaw->deleted_at ?? 'NULL (AKTIF)';

        echo str_pad("#" . $sId, 10) . " | " . str_pad($sName, 25) . " | " . str_pad($cCode, 10) . " | " . str_pad($status, 12) . " | " . $deletedAt . "\n";
    } else {
        echo str_pad("#" . $sId, 10) . " | " . str_pad("TIDAK DITEMUKAN DI DB", 25) . " | " . str_pad("N/A", 10) . " | " . str_pad("N/A", 12) . " | -\n";
    }
}

echo "\n--- SELURUH TOKO DI TABEL STORES (" . DB::table('stores')->count() . " TOKO) ---\n";
$allStoresRaw = DB::table('stores')->get();

foreach ($allStoresRaw as $st) {
    $ch = DB::table('channels')->where('id', $st->channel_id)->first();
    echo "ID #{$st->id} | Tenant #{$st->tenant_id} | Name: " . ($st->store_name ?? $st->name) . " | Channel ID: {$st->channel_id} (" . ($ch->code ?? 'N/A') . ") | Status: {$st->status}\n";
}

echo "\n======================================================================\n";
