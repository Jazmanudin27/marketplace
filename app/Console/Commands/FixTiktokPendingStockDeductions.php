<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class FixTiktokPendingStockDeductions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:fix-stock-deduction 
                            {--order_id= : ID pesanan tertentu} 
                            {--from_date= : Tanggal mulai pesanan (Format YYYY-MM-DD, contoh: 2026-08-01)} 
                            {--all_time : Memproses semua transaksi tanpa batasan tanggal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memproses potongan stok dan mutasi kartu stok untuk pesanan TikTok yang tertahan (Bulan Agustus 2026)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan dan perbaikan potongan stok pesanan TikTok...');

        // Langkah 1: Sinkronisasi dan tautkan otomatis MarketplaceProduct ke MasterProduct berdasarkan SKU (Exact, Lower, & Normalized) dan Nama
        $unlinkedMp = \App\Models\MarketplaceProduct::whereNull('master_product_id')->get();

        $linkedMpCount = 0;
        foreach ($unlinkedMp as $mp) {
            $store = $mp->store;
            if (!$store) continue;
            
            $master = null;
            $tenantId = $store ? $store->tenant_id : null;

            if (!empty($mp->marketplace_sku)) {
                $skuClean = trim($mp->marketplace_sku);
                $skuNorm = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($skuClean));

                $queryMaster = \App\Models\MasterProduct::query();
                if ($tenantId) {
                    $queryMaster->where('tenant_id', $tenantId);
                }

                $master = $queryMaster->where(function ($q) use ($skuClean) {
                        $q->where('sku', $skuClean)
                          ->orWhereRaw('LOWER(sku) = LOWER(?)', [strtolower($skuClean)]);
                    })->first();

                if (!$master && !empty($skuNorm)) {
                    $allMasters = $tenantId ? \App\Models\MasterProduct::where('tenant_id', $tenantId)->get() : \App\Models\MasterProduct::all();
                    $master = $allMasters->first(function ($m) use ($skuNorm) {
                        return preg_replace('/[^a-zA-Z0-9]/', '', strtolower($m->sku)) === $skuNorm;
                    });
                }

                // Coba Prefix / Similarity SKU Matching (misal: BB-BR-MERAH-LPJ-X vs BB-BR-MERAH-LPJ-XL)
                if (!$master) {
                    $allMasters = $tenantId ? \App\Models\MasterProduct::where('tenant_id', $tenantId)->get() : \App\Models\MasterProduct::all();
                    $master = $allMasters->first(function ($m) use ($skuClean) {
                        $mSku = strtolower(trim($m->sku));
                        $iSku = strtolower(trim($skuClean));
                        if (empty($mSku) || empty($iSku)) return false;

                        if (str_starts_with($iSku, $mSku) || str_starts_with($mSku, $iSku)) {
                            return true;
                        }

                        similar_text($iSku, $mSku, $percent);
                        return $percent >= 85;
                    });
                }

                // Global Fallback tanpa batasan tenant
                if (!$master) {
                    $master = \App\Models\MasterProduct::where('sku', $skuClean)
                        ->orWhereRaw('LOWER(sku) = LOWER(?)', [strtolower($skuClean)])
                        ->first();
                }
            }

            if (!$master && !empty($mp->name)) {
                $nameClean = trim($mp->name);
                $queryName = \App\Models\MasterProduct::query();
                if ($tenantId) {
                    $queryName->where('tenant_id', $tenantId);
                }
                $master = $queryName->where(function ($q) use ($nameClean) {
                    $q->where('name', $nameClean)
                      ->orWhereRaw('LOWER(name) = LOWER(?)', [strtolower($nameClean)]);
                })->first();
            }

            if ($master) {
                $mp->update([
                    'master_product_id' => $master->id,
                    'sync_stock'        => true,
                ]);
                $linkedMpCount++;
            }
        }

        if ($linkedMpCount > 0) {
            $this->info("Berhasil menghubungkan {$linkedMpCount} produk toko TikTok ke MasterProduct secara otomatis.");
        }

        $orderIdOption = $this->option('order_id');
        $fromDateOption = $this->option('from_date');
        $allTimeOption = $this->option('all_time');

        // Default tanggal mulai: Awal bulan Agustus 2026 (atau tanggal 1 bulan berjalan)
        $defaultFromDate = date('Y-m-01'); // e.g. 2026-08-01
        $fromDate = $fromDateOption ?: $defaultFromDate;

        $query = Order::where('order_status', '!=', Order::STATUS_CANCELLED)
            ->whereHas('store.channel', function ($q) {
                $q->whereIn('code', ['tiktok', 'tokopedia']);
            });

        if ($orderIdOption) {
            $query->where(function ($q) use ($orderIdOption) {
                $q->where('id', $orderIdOption)
                  ->orWhere('order_marketplace_id', (string) $orderIdOption);
            });
            $this->info("Filter: Memproses Pesanan Spesifik TikTok #{$orderIdOption}");
        } else {
            $query->where('is_stock_deducted', false);
            if (!$allTimeOption) {
                $query->whereDate('order_date', '>=', $fromDate);
                $this->info("Filter Tanggal: Hanya memproses pesanan TikTok tanggal {$fromDate} onwards (Agustus 2026).");
            } else {
                $this->info("Filter Tanggal: Memproses seluruh periode transaksi pesanan TikTok (--all_time).");
            }
        }

        $pendingOrders = $query->get();

        if ($pendingOrders->isEmpty()) {
            $this->info("Pesanan TikTok #{$orderIdOption} atau pesanan tertahan tidak ditemukan.");
            return 0;
        }

        $this->info("Ditemukan {$pendingOrders->count()} pesanan TikTok yang belum terpotong stoknya. Memproses...");

        $successCount = 0;
        $failedCount = 0;

        foreach ($pendingOrders as $order) {
            try {
                $order->processStockDeduction();
                $order->refresh();

                if ($order->is_stock_deducted) {
                    $successCount++;
                    $this->info("✅ Pesanan #{$order->id} ({$order->order_marketplace_id}) berhasil dipotong stoknya & dicatat ke Kartu Stok.");
                } else {
                    $failedCount++;
                    $unmappedItems = $order->items->whereNull('master_product_id');
                    $details = [];
                    foreach ($unmappedItems as $un) {
                        $details[] = "[SKU: '" . ($un->sku ?: 'KOSONG') . "', Nama: '" . $un->product_name . "']";
                    }
                    $this->warn("⚠️ Pesanan #{$order->id} ({$order->order_marketplace_id}) item unmapped: " . implode(', ', $details));
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("❌ Gagal memproses pesanan #{$order->id}: " . $e->getMessage());
                Log::error("[TikTok Fix] Error processing order #{$order->id}: " . $e->getMessage());
            }
        }

        $this->info("Proses selesai: {$successCount} sukses dipotong, {$failedCount} perlu pemeriksaan rincian item.");
        return 0;
    }
}
