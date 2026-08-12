<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Order;
use App\Jobs\PullOrdersFromTiktok;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Log;

class SyncTiktokEscrow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:sync-escrow {--store_id= : ID Toko TikTok tertentu (opsional)} {--order_id= : Nomor order TikTok tertentu untuk dites}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menembak API TikTok Shop secara langsung dan mengupdate rincian biaya rincian 5 komponen (financial breakdown) resmi di ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("========================================================");
        $this->info("SINKRONISASI BIAYA & SETTLEMENT ESCROW TIKTOK SHOP");
        $this->info("========================================================\n");

        $tiktokService = app(TiktokService::class);
        $storeIdOption = $this->option('store_id');
        $orderIdOption = $this->option('order_id');

        $query = Store::whereHas('channel', function ($q) {
            $q->whereIn('code', ['tiktok', 'tokopedia']);
        });

        if ($storeIdOption) {
            $query->where('id', $storeIdOption);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error('Tidak ada toko TikTok/Tokopedia yang terhubung.');
            return;
        }

        foreach ($stores as $store) {
            $this->info("Memproses Toko: {$store->store_name} (ID: {$store->id})");

            try {
                $accessToken = $store->getValidAccessToken();
                $shopCipher = $store->shop_cipher;

                if (empty($shopCipher)) {
                    $this->warn("Toko {$store->store_name} belum memiliki shop_cipher.");
                    continue;
                }
            } catch (\Exception $e) {
                $this->error("Gagal mendapatkan access token untuk toko {$store->store_name}: " . $e->getMessage());
                continue;
            }

            $ordersQuery = Order::where('store_id', $store->id)
                ->whereNotNull('order_marketplace_id');

            if ($orderIdOption) {
                $ordersQuery->where('order_marketplace_id', $orderIdOption);
            }

            $orders = $ordersQuery->get();
            $this->info("Menemukan {$orders->count()} pesanan TikTok untuk disinkronkan...");

            if ($orders->isEmpty()) {
                // Tarik pesanan 7 hari terakhir
                $timeTo = time();
                $timeFrom = strtotime('-7 days', $timeTo);
                PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);
                $this->info("Job penarikan pesanan TikTok telah dikirim ke antrean.");
                continue;
            }

            $chunked = $orders->chunk(50);
            foreach ($chunked as $chunk) {
                $orderIds = $chunk->pluck('order_marketplace_id')->toArray();
                try {
                    $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, $orderIds);
                    $tiktokOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

                    foreach ($tiktokOrders as $tOrder) {
                        $mId = $tOrder['id'] ?? $tOrder['order_id'] ?? null;
                        if (!$mId) continue;

                        $dbOrder = $orders->firstWhere('order_marketplace_id', $mId);
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

                        $dbOrder->save();
                        $this->info("✅ Berhasil Sync Order TikTok: {$mId} | Fees: Rp " . number_format($totalTiktokFees, 0, ',', '.'));
                    }
                } catch (\Exception $e) {
                    $this->error("Gagal sync detail TikTok: " . $e->getMessage());
                }
            }
        }

        $this->info("\n✨ Selesai! Seluruh Rincian Biaya TikTok Shop berhasil disinkronkan ke database ERP.");
    }
}
