<?php

/**
 * ============================================================
 * DIAGNOSTIC TOOL FOR MYSQL LOCKS & TRIGGERS
 * ============================================================
 * Script ini memeriksa secara langsung ke MySQL:
 * 1. Triggers di tabel order_items / orders / master_products.
 * 2. Transaksi aktif (INNODB_TRX) yang menggantung / belum commit.
 * 3. Lock wait & status kunci tabel / baris.
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "======================================================================\n";
echo "  DIAGNOSA KUNCI MYSQL & TRANSAKSI GANTUNG\n";
echo "======================================================================\n\n";

// 1. Cek Apakah Ada Trigger di Database MySQL
echo "1. MEMERIKSA TRIGGER DI TABEL DATABASE...\n";
try {
    $triggers = DB::select("SHOW TRIGGERS");
    if (empty($triggers)) {
        echo "  [OK] Tidak ada Trigger database di MySQL.\n";
    } else {
        echo "  [PERHATIAN] Ditemukan " . count($triggers) . " Trigger di MySQL:\n";
        foreach ($triggers as $trg) {
            echo "   - Trigger: {$trg->Trigger} | Tabel: {$trg->Table} | Event: {$trg->Event} {$trg->Timing}\n";
        }
    }
} catch (\Exception $e) {
    echo "  Error show triggers: " . $e->getMessage() . "\n";
}
echo "\n";

// 2. Cek Transaksi Aktif yang Menggantung (Active INNODB Transactions)
echo "2. MEMERIKSA TRANSAKSI MYSQL YANG SEDANG AKTIF / MENGGANTUNG...\n";
try {
    $trxList = DB::select("SELECT trx_id, trx_state, trx_started, trx_requested_lock_id, trx_wait_started, trx_mysql_thread_id, trx_query FROM information_schema.innodb_trx");
    if (empty($trxList)) {
        echo "  [OK] Tidak ada transaksi MySQL yang sedang menggantung.\n";
    } else {
        echo "  [DITEMUKAN] Ada " . count($trxList) . " Transaksi MySQL Aktif:\n";
        foreach ($trxList as $trx) {
            echo "   • Thread ID: {$trx->trx_mysql_thread_id} | State: {$trx->trx_state} | Started: {$trx->trx_started}\n";
            echo "     Query: " . ($trx->trx_query ?: '(Idle in Transaction / Belum Commit)') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "  Error check innodb_trx: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Cek Status Processlist MySQL
echo "3. MEMERIKSA PROCESSLIST MYSQL ACTIVE THREADS...\n";
try {
    $processes = DB::select("SHOW FULL PROCESSLIST");
    $activeProc = array_filter($processes, fn($p) => $p->Command !== 'Sleep');
    echo "  Total Process: " . count($processes) . " (Aktif non-sleep: " . count($activeProc) . ")\n";
    foreach ($activeProc as $p) {
        if ($p->Id == DB::connection()->getPdo()->lastInsertId()) continue;
        echo "   • ID: {$p->Id} | User: {$p->User} | Command: {$p->Command} | Time: {$p->Time}s | State: {$p->State}\n";
        if (!empty($p->Info)) {
            echo "     SQL: " . substr($p->Info, 0, 120) . "...\n";
        }
    }
} catch (\Exception $e) {
    echo "  Error processlist: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Uji Coba Insert Langsung Tanpa Foreign Key Constraint (Direct Query Test)
echo "4. UJI COBA QUERY EXECUTION SPEED ON order_items...\n";
$start = microtime(true);
try {
    $testOrderId = DB::table('orders')->max('id');
    if ($testOrderId) {
        // Query test
        $res = DB::select("SELECT COUNT(*) as cnt FROM order_items WHERE order_id = ?", [$testOrderId]);
        $duration = round((microtime(true) - $start) * 1000, 2);
        echo "  [OK] Query SELECT order_items execution time: {$duration} ms\n";
    }
} catch (\Exception $e) {
    echo "  Error test: " . $e->getMessage() . "\n";
}

echo "\n======================================================================\n\n";
