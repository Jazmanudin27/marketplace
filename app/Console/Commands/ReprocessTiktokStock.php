<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use Illuminate\Support\Facades\Log;

class ReprocessTiktokStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:reprocess-tiktok-stock {--order_id= : Reprocess specific order marketplace ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memproses ulang pemotongan stok dan pencatatan mutasi stok gudang untuk pesanan TikTok yang belum memotong stok';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificOrderId = $this->option('order_id');

        $query = Order::with(['items', 'store'])
            ->whereHas('store.channel', function ($q) {
                $q->where('code', 'tiktok');
            });

        if ($specificOrderId) {
            $query->where('order_marketplace_id', $specificOrderId);
        } else {
            $query->where('is_stock_deducted', false)
                  ->where('order_status', '!=', Order::STATUS_CANCELLED);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada pesanan TikTok yang perlu diproses ulang.');
            return 0;
        }

        $this->info("Menemukan {$orders->count()} pesanan TikTok untuk diproses ulang stoknya...");

        $successCount = 0;
        $failedCount = 0;

        foreach ($orders as $order) {
            $this->line("Memproses Order #{$order->id} (Marketplace ID: {$order->order_marketplace_id})...");

            foreach ($order->items as $item) {
                // Jika master_product_id belum terisi, coba hubungkan kembali
                if (!$item->master_product_id) {
                    $masterProduct = null;
                    $marketplaceProduct = null;

                    if ($item->marketplace_product_id) {
                        $marketplaceProduct = MarketplaceProduct::find($item->marketplace_product_id);
                    }

                    if (!$marketplaceProduct && !empty($item->sku)) {
                        $marketplaceProduct = MarketplaceProduct::where('store_id', $order->store_id)
                            ->where(function ($q) use ($item) {
                                $q->where('marketplace_variant_id', $item->sku)
                                  ->orWhere('marketplace_sku', $item->sku);
                            })->first();
                    }

                    if ($marketplaceProduct && $marketplaceProduct->master_product_id) {
                        $masterProduct = $marketplaceProduct->masterProduct;
                    }

                    // Fallback direct match SKU ke MasterProduct
                    if (!$masterProduct && !empty($item->sku)) {
                        $skuClean = trim($item->sku);
                        $masterProduct = MasterProduct::where('tenant_id', $order->tenant_id)
                            ->where(function ($q) use ($skuClean) {
                                $q->where('sku', $skuClean)
                                  ->orWhereRaw('LOWER(sku) = LOWER(?)', [$skuClean]);
                            })->first();
                    }

                    if ($masterProduct) {
                        $item->update([
                            'marketplace_product_id' => $marketplaceProduct ? $marketplaceProduct->id : $item->marketplace_product_id,
                            'master_product_id' => $masterProduct->id,
                            'cost_price' => $masterProduct->cost_price,
                            'hpp_subtotal' => (float) $masterProduct->cost_price * $item->quantity,
                        ]);
                        $this->info("  - Item #{$item->id} ({$item->sku}) berhasil di-map ke MasterProduct #{$masterProduct->id} ({$masterProduct->sku})");
                    } else {
                        $this->warn("  - Item #{$item->id} ({$item->sku}) BELUM bisa ter-map ke MasterProduct (periksa SKU / pemetaan produk).");
                    }
                }
            }

            // Jalankan pemotongan stok & pencatatan mutasi stok gudang
            // PENTING: Reset is_stock_deducted dulu agar processStockDeduction() tidak skip
            $order->update(['is_stock_deducted' => false]);

            // Hapus movement duplikat yang mungkin sudah ada dari proses sebelumnya
            // agar alreadyDeducted check tidak memblok pencatatan baru
            \App\Models\StockMovement::where('reference', 'Pesanan Masuk: ' . $order->order_marketplace_id)
                ->whereIn('master_product_id', $order->items->pluck('master_product_id')->filter()->toArray())
                ->delete();

            $order->unsetRelation('items');
            $order->processStockDeduction();

            // Refresh order status setelah processStockDeduction
            $order->refresh();

            // Cek apakah stock movement benar-benar tercatat
            $movementsCreated = \App\Models\StockMovement::where('reference', 'Pesanan Masuk: ' . $order->order_marketplace_id)->count();

            if ($order->is_stock_deducted && $movementsCreated > 0) {
                $this->info("  -> Sukses: {$movementsCreated} mutasi stok tercatat untuk Order #{$order->order_marketplace_id}!");
                $successCount++;
            } elseif ($order->is_stock_deducted && $movementsCreated === 0) {
                $this->warn("  -> Perhatian: is_stock_deducted=true tapi TIDAK ADA mutasi stok tercatat. Kemungkinan produk belum ter-map.");
                $failedCount++;
            } else {
                $this->error("  -> Gagal: Stok Order #{$order->order_marketplace_id} belum sepenuhnya terpotong (ada item belum ter-map ke MasterProduct).");
                $failedCount++;
            }
        }

        $this->info("Proses Selesai. Sukses: {$successCount}, Belum Lengkap: {$failedCount}");
        return 0;
    }
}
