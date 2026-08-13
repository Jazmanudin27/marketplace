<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Log;

class SyncOrderFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync-fees {--order_id= : Nomor order Shopee/TikTok tertentu (opsional)} {--order_sn= : Alias nomor order (opsional)} {--store_id= : ID toko tertentu (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menembak API Shopee & TikTok untuk menyinkronkan rincian 5 komponen biaya resmi dan dana dilepas (escrow) untuk semua toko';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("========================================================");
        $this->info("SINKRONISASI BIAYA & DANA DILEPAS (SHOPEE & TIKTOK SHOP)");
        $this->info("========================================================\n");

        $orderSn = $this->option('order_id') ?: $this->option('order_sn');
        $storeId = $this->option('store_id');

        $shopeeService = app(ShopeeService::class);
        $tiktokService = app(TiktokService::class);

        // 1. Sync Shopee Orders Escrow
        $shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'));
        if ($storeId) {
            $shopeeStores->where('id', $storeId);
        }

        foreach ($shopeeStores->get() as $s) {
            try {
                $accToken = $s->getValidAccessToken();
                $q = Order::where('store_id', $s->id)->whereNotNull('order_marketplace_id');
                if ($orderSn) {
                    $q->where('order_marketplace_id', $orderSn);
                }

                $shOrders = $q->get();
                if ($shOrders->count() > 0) {
                    $this->info("Menyinkronkan {$shOrders->count()} order Shopee di toko {$s->store_name}...");
                    foreach ($shOrders as $o) {
                        try {
                            $res = $shopeeService->getEscrowDetail($accToken, (int)$s->marketplace_store_id, $o->order_marketplace_id);
                            if (!empty($res['order_income'])) {
                                $o->financial_breakdown = $res['order_income'];
                                try {
                                    $detailRes = $shopeeService->getOrderDetail($accToken, (int)$s->marketplace_store_id, [$o->order_marketplace_id]);
                                    $shOrder = $detailRes['order_list'][0] ?? [];
                                    if (!empty($shOrder['update_time'])) {
                                        $o->completed_at = date('Y-m-d H:i:s', $shOrder['update_time']);
                                    }
                                } catch (\Exception $exDetail) {}
                                $o->saveQuietly();
                            }
                        } catch (\Exception $e) {}
                    }
                }
            } catch (\Exception $e) {}
        }

        // 2. Sync TikTok Orders Escrow
        $tiktokStores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']));
        if ($storeId) {
            $tiktokStores->where('id', $storeId);
        } elseif ($orderSn) {
            $dbMatch = Order::where('order_marketplace_id', $orderSn)->first();
            if ($dbMatch && $dbMatch->store_id) {
                $tiktokStores->where('id', $dbMatch->store_id);
            }
        }

        foreach ($tiktokStores->get() as $s) {
            try {
                $accToken = $s->getValidAccessToken();
                $shopCipher = $s->shop_cipher;
                if (empty($shopCipher)) continue;

                $q = Order::where('store_id', $s->id)->whereNotNull('order_marketplace_id');
                if ($orderSn) {
                    $q->where('order_marketplace_id', $orderSn);
                }

                $tOrders = $q->get();
                if ($tOrders->count() > 0) {
                    $this->info("Menyinkronkan {$tOrders->count()} order TikTok di toko {$s->store_name}...");
                    foreach ($tOrders->chunk(50) as $chunk) {
                        $ids = $chunk->pluck('order_marketplace_id')->toArray();
                        try {
                            $detailRes = $tiktokService->getOrderDetail($accToken, $shopCipher, $ids);
                            $tOrdersRes = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];
                            foreach ($tOrdersRes as $tOrder) {
                                $mId = $tOrder['id'] ?? $tOrder['order_id'] ?? null;
                                if (!$mId) continue;
                                $dbOrder = $tOrders->firstWhere('order_marketplace_id', $mId);
                                if (!$dbOrder) continue;

                                $paymentInfo = $tOrder['payment'] ?? $tOrder['payment_info'] ?? [];
                                $totalAmount = (float) ($paymentInfo['total_amount'] ?? $tOrder['total_amount'] ?? $dbOrder->total_amount);
                                $escrowAmount = (float) ($paymentInfo['settlement_amount'] ?? $paymentInfo['escrow_amount'] ?? 0);
                                
                                $platformCommission = (float) ($paymentInfo['platform_commission'] ?? $paymentInfo['commission_before_discount'] ?? 0);
                                $platformCommissionDiscount = (float) ($paymentInfo['platform_commission_discount'] ?? 0);
                                $netPlatformCommission = (float) ($paymentInfo['net_platform_commission'] ?? ($platformCommission > 0 ? max(0.0, $platformCommission - $platformCommissionDiscount) : 0));
                                
                                $preorderServiceFee = (float) ($paymentInfo['preorder_service_fee'] ?? 0);
                                $dynamicCommission = (float) ($paymentInfo['dynamic_commission'] ?? $paymentInfo['affiliate_commission'] ?? 0);
                                $growthXtraFee = (float) ($paymentInfo['growth_xtra_fee'] ?? 0);
                                $orderProcessingFee = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['transaction_fee'] ?? 0);

                                $totalTiktokFees = $netPlatformCommission + $preorderServiceFee + $dynamicCommission + $growthXtraFee + $orderProcessingFee;

                                $dbOrder->financial_breakdown = [
                                    'original_price' => $totalAmount,
                                    'net_platform_commission' => $netPlatformCommission,
                                    'preorder_service_fee' => $preorderServiceFee,
                                    'dynamic_commission' => $dynamicCommission,
                                    'growth_xtra_fee' => $growthXtraFee,
                                    'order_processing_fee' => $orderProcessingFee,
                                    'service_fee' => $totalTiktokFees,
                                    'escrow_amount' => $escrowAmount > 0 ? $escrowAmount : max(0.0, $totalAmount - $totalTiktokFees),
                                ];

                                $compTs = $tOrder['delivery_time'] ?? $tOrder['update_time'] ?? $tOrder['paid_time'] ?? null;
                                if ($compTs) {
                                    $compTsSec = (is_numeric($compTs) && strlen((string)$compTs) >= 13) ? (int)($compTs / 1000) : (int)$compTs;
                                    $dbOrder->completed_at = date('Y-m-d H:i:s', $compTsSec);
                                }

                                $dbOrder->saveQuietly();
                            }
                        } catch (\Exception $exTT) {}
                    }
                }
            } catch (\Exception $e) {}
        }

        // 3. Update Order Model attributes (fee columns & net_amount) for all orders
        $count = 0;
        $allQuery = Order::query();
        if ($orderSn) {
            $allQuery->where('order_marketplace_id', $orderSn);
        }
        if ($storeId) {
            $allQuery->where('store_id', $storeId);
        }

        $allQuery->chunk(100, function ($orders) use (&$count) {
            foreach ($orders as $order) {
                $details = $order->fee_breakdown_details;
                $order->fee_platform_amount = abs($details['platform_fee'] ?? 0);
                $order->fee_free_shipping_amount = abs($details['free_shipping'] ?? 0);
                $order->fee_service_amount = abs($details['service_fee'] ?? 0);
                $order->fee_promo_amount = abs($details['promo_fee'] ?? 0);
                $order->fee_other_amount = abs($details['other_fee'] ?? 0);

                $totalFee = abs($details['total_fee'] ?? 0);
                if ($totalFee > 0) {
                    $order->marketplace_fee = $totalFee;
                    $order->net_amount = max(0.0, (float) $order->total_amount - $totalFee);
                }
                $order->saveQuietly();
                $count++;
            }
        });

        $this->info("✨ Selesai! Berhasil menyinkronkan API & memperbarui {$count} pesanan (Shopee & TikTok Shop).");
    }
}
