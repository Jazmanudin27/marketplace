<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use Illuminate\Support\Facades\DB;

class RecalculateOrderHpp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:recalculate-hpp 
                            {--from=2026-09-01 : Tanggal mulai (YYYY-MM-DD)} 
                            {--to=2026-10-31 : Tanggal akhir (YYYY-MM-DD)} 
                            {--tenant= : Filter tenant ID tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memperbarui HPP dan hpp_subtotal order_items berdasarkan master_products terkini untuk rentang tanggal tertentu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $from = $this->option('from') ?: '2026-09-01';
        $to = $this->option('to') ?: '2026-10-31';
        $tenantId = $this->option('tenant');

        $this->info("=== UPDATE HPP HISTORI PENJUALAN ===");
        $this->info("Rentang Tanggal: {$from} 00:00:00 s/d {$to} 23:59:59");
        if ($tenantId) {
            $this->info("Tenant ID: {$tenantId}");
        }

        $query = Order::with(['items'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('order_date', ["{$from} 00:00:00", "{$to} 23:59:59"])
                  ->orWhereBetween('completed_at', ["{$from} 00:00:00", "{$to} 23:59:59"]);
            });

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->warn('Tidak ada pesanan yang ditemukan pada rentang tanggal tersebut.');
            return 0;
        }

        $this->info("Ditemukan {$orders->count()} pesanan untuk diproses ulang HPP-nya...");

        // Cache Master Products per tenant
        $masterProductsByTenant = [];
        $masterSkusByTenant = [];

        $tenants = $orders->pluck('tenant_id')->unique();
        foreach ($tenants as $tid) {
            $mps = MasterProduct::where('tenant_id', $tid)->get();
            $masterProductsByTenant[$tid] = $mps->keyBy('id');
            $skus = [];
            foreach ($mps as $m) {
                if (!empty($m->sku)) {
                    $k = strtolower(trim($m->sku));
                    if (!isset($skus[$k]) || ((float)$m->cost_price > 0 && (float)$skus[$k]->cost_price <= 0)) {
                        $skus[$k] = $m;
                    }
                }
            }
            $masterSkusByTenant[$tid] = $skus;
        }

        $ordersUpdated = 0;
        $itemsUpdated = 0;
        $zeroHppCount = 0;

        DB::beginTransaction();
        try {
            foreach ($orders as $order) {
                $status = strtoupper((string)($order->order_status ?? ''));
                $refundAmt = (float)$order->refund_amount;
                $omsetAmt = (float)$order->total_amount;

                $isFullReturn = in_array($status, ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'])
                    || ($omsetAmt > 0 && $refundAmt >= $omsetAmt);

                $tid = $order->tenant_id;
                $mpMap = $masterProductsByTenant[$tid] ?? collect();
                $skuMap = $masterSkusByTenant[$tid] ?? [];

                $orderHasUpdate = false;

                foreach ($order->items as $item) {
                    $qty = max(1, (int)($item->quantity ?? 1));

                    if ($isFullReturn) {
                        // Khusus retur / refund full -> HPP = 0
                        $item->cost_price = 0;
                        $item->hpp_subtotal = 0;
                        $item->save();
                        $itemsUpdated++;
                        $zeroHppCount++;
                        $orderHasUpdate = true;
                        continue;
                    }

                    // Cari master product:
                    // 1. Prioritaskan SKU persis jika ada dan memiliki cost_price > 0
                    $mp = null;
                    $cleanSku = !empty($item->sku) ? strtolower(trim($item->sku)) : null;
                    $cleanSellerSku = !empty($item->seller_sku) ? strtolower(trim($item->seller_sku)) : null;

                    if ($cleanSku && isset($skuMap[$cleanSku]) && (float)$skuMap[$cleanSku]->cost_price > 0) {
                        $mp = $skuMap[$cleanSku];
                    } elseif ($cleanSellerSku && isset($skuMap[$cleanSellerSku]) && (float)$skuMap[$cleanSellerSku]->cost_price > 0) {
                        $mp = $skuMap[$cleanSellerSku];
                    } elseif ($item->master_product_id && isset($mpMap[$item->master_product_id]) && (float)$mpMap[$item->master_product_id]->cost_price > 0) {
                        $mp = $mpMap[$item->master_product_id];
                    } elseif ($cleanSku && isset($skuMap[$cleanSku])) {
                        $mp = $skuMap[$cleanSku];
                    } elseif ($cleanSellerSku && isset($skuMap[$cleanSellerSku])) {
                        $mp = $skuMap[$cleanSellerSku];
                    } elseif ($item->master_product_id && isset($mpMap[$item->master_product_id])) {
                        $mp = $mpMap[$item->master_product_id];
                    } elseif ($item->marketplace_product_id) {
                        $mkt = MarketplaceProduct::find($item->marketplace_product_id);
                        if ($mkt && $mkt->master_product_id && isset($mpMap[$mkt->master_product_id])) {
                            $mp = $mpMap[$mkt->master_product_id];
                        }
                    }

                    if ($mp) {
                        $unitCost = (float)$mp->cost_price;
                        $item->master_product_id = $mp->id;
                        $item->cost_price = $unitCost;
                        $item->hpp_subtotal = $unitCost * $qty;
                        $item->save();

                        // Update mapping marketplace_products jika HPP valid
                        if ($item->marketplace_product_id && $unitCost > 0) {
                            MarketplaceProduct::where('id', $item->marketplace_product_id)
                                ->orWhere('marketplace_product_id', $item->marketplace_product_id)
                                ->update(['master_product_id' => $mp->id]);
                        }

                        $itemsUpdated++;
                        $orderHasUpdate = true;
                    }
                }

                if ($orderHasUpdate) {
                    $ordersUpdated++;
                }
            }

            DB::commit();

            $this->info("SUKSES:");
            $this->info("- Total Order Diperbarui : {$ordersUpdated}");
            $this->info("- Total Item Diperbarui  : {$itemsUpdated}");
            $this->info("- Item HPP 0 (Retur/Batal): {$zeroHppCount}");

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal memperbarui HPP: " . $e->getMessage());
            return 1;
        }
    }
}
