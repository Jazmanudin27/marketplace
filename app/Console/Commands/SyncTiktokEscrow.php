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

        // Jika order_id spesifik diberikan, cari toko pemilik order secara presisi lebih dulu
        if ($orderIdOption) {
            $dbMatch = Order::where('order_marketplace_id', $orderIdOption)->first();
            if ($dbMatch && $dbMatch->store_id) {
                $query->where('id', $dbMatch->store_id);
            }
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

            if ($orderIdOption && $orders->isEmpty()) {
                // Jika order_id spesifik dicari tetapi belum ada di DB lokal toko ini, tembak langsung TikTok API
                try {
                    $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderIdOption]);
                    $tiktokOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

                    if (!empty($tiktokOrders)) {
                        $this->info("✅ Order ID '{$orderIdOption}' DITEMUKAN di Toko TikTok: {$store->store_name} (ID: {$store->id})!");
                        // Gunakan PullOrdersFromTiktok processOrder via Job / Helper
                        $job = new PullOrdersFromTiktok($store, time() - 86400, time());
                        $reflection = new \ReflectionClass($job);
                        $method = $reflection->getMethod('processOrder');
                        $method->setAccessible(true);
                        $method->invoke($job, $tiktokOrders[0]);

                        $orders = Order::where('store_id', $store->id)
                            ->where('order_marketplace_id', $orderIdOption)
                            ->get();
                    }
                } catch (\Exception $e) {
                    // Lanjut ke toko berikutnya jika bukan pemilik order
                }
            }

            if ($orders->isEmpty()) {
                if ($orderIdOption) {
                    continue; // Jika sedang mencari order_id spesifik, lanjut cari ke toko lain
                }
                // Tarik pesanan 7 hari terakhir jika tidak ada filter order_id
                $timeTo = time();
                $timeFrom = strtotime('-7 days', $timeTo);
                PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);
                $this->info("Job penarikan pesanan TikTok telah dikirim ke antrean.");
                continue;
            }

            $this->info("Menemukan {$orders->count()} pesanan TikTok untuk disinkronkan...");

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
                        
                        $productSubtotal = (float) ($paymentInfo['original_total_product_price'] 
                            ?? $paymentInfo['sub_total'] 
                            ?? $paymentInfo['subtotal_after_seller_discounts'] 
                            ?? 0);

                        if ($productSubtotal <= 0 && !empty($tOrder['line_items'])) {
                            foreach ($tOrder['line_items'] as $lItem) {
                                $itemPrice = (float) ($lItem['original_price'] ?? $lItem['sale_price'] ?? 0);
                                $itemQty = (int) ($lItem['quantity'] ?? 1);
                                $productSubtotal += ($itemPrice * $itemQty);
                            }
                        }

                        $totalAmount = $productSubtotal > 0 ? $productSubtotal : (float) ($paymentInfo['total_amount'] ?? $tOrder['total_amount'] ?? $dbOrder->total_amount);
                        $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? $totalAmount);
                        $escrowAmount = (float) ($paymentInfo['settlement_amount'] ?? $paymentInfo['escrow_amount'] ?? 0);
                        
                        $platformCommission = (float) ($paymentInfo['platform_commission'] ?? $paymentInfo['commission_before_discount'] ?? 0);
                        $platformCommissionDiscount = (float) ($paymentInfo['platform_commission_discount'] ?? 0);
                        $netPlatformCommission = (float) ($paymentInfo['net_platform_commission'] ?? ($platformCommission > 0 ? max(0.0, $platformCommission - $platformCommissionDiscount) : 0));
                        
                        $preorderServiceFee = (float) ($paymentInfo['preorder_service_fee'] ?? 0);
                        $dynamicCommission = (float) ($paymentInfo['dynamic_commission'] ?? $paymentInfo['affiliate_commission'] ?? 0);
                        $growthXtraFee = (float) ($paymentInfo['growth_xtra_fee'] ?? 0);
                        $orderProcessingFee = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['transaction_fee'] ?? 0);

                        // Tembak API Finance TikTok jika settlement belum ada di data order
                        if ($escrowAmount <= 0) {
                            try {
                                $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
                                $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? [];
                                foreach ($stmtList as $st) {
                                    $amount = (float) ($st['amount'] ?? $st['settlement_amount'] ?? 0);
                                    $type = strtoupper((string)($st['type'] ?? $st['fee_type'] ?? ''));

                                    if (str_contains($type, 'SETTLEMENT') || str_contains($type, 'ESCROW') || str_contains($type, 'REVENUE')) {
                                        if ($amount > 0) $escrowAmount = $amount;
                                    } elseif (str_contains($type, 'COMMISSION') || str_contains($type, 'PLATFORM')) {
                                        $netPlatformCommission = abs($amount);
                                    } elseif (str_contains($type, 'PREORDER')) {
                                        $preorderServiceFee = abs($amount);
                                    } elseif (str_contains($type, 'GROWTH') || str_contains($type, 'XTRA')) {
                                        $growthXtraFee = abs($amount);
                                    } elseif (str_contains($type, 'PROCESSING') || str_contains($type, 'TRANSACTION')) {
                                        $orderProcessingFee = abs($amount);
                                    } elseif (str_contains($type, 'AFFILIATE') || str_contains($type, 'DYNAMIC')) {
                                        $dynamicCommission = abs($amount);
                                    }
                                }
                            } catch (\Exception $exStmt) {}
                        }

                        $totalTiktokFees = $netPlatformCommission + $preorderServiceFee + $dynamicCommission + $growthXtraFee + $orderProcessingFee;

                        if ($totalTiktokFees <= 0 && $escrowAmount > 0 && $totalAmount > $escrowAmount) {
                            $totalTiktokFees = max(0.0, $totalAmount - $escrowAmount);
                        }

                        if ($totalTiktokFees <= 0 && $totalAmount > 0) {
                            $totalTiktokFees = round($totalAmount * 0.085);
                        }

                        if ($escrowAmount <= 0) {
                            $escrowAmount = max(0.0, $totalAmount - $totalTiktokFees);
                        }

                        $dbOrder->total_amount = $totalAmount;
                        $dbOrder->marketplace_fee = $totalTiktokFees;
                        $dbOrder->net_amount = $escrowAmount;

                        $dbOrder->financial_breakdown = [
                            'original_price' => $totalAmount,
                            'buyer_paid_total' => $buyerPaidTotal,
                            'net_platform_commission' => $netPlatformCommission,
                            'preorder_service_fee' => $preorderServiceFee,
                            'dynamic_commission' => $dynamicCommission,
                            'growth_xtra_fee' => $growthXtraFee,
                            'order_processing_fee' => $orderProcessingFee,
                            'service_fee' => $totalTiktokFees,
                            'escrow_amount' => $escrowAmount > 0 ? $escrowAmount : $netAmount,
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
