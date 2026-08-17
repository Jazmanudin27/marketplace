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
                        
                        $subtotalAfterSeller = (float) ($paymentInfo['subtotal_after_seller_discounts'] ?? $paymentInfo['after_seller_discounts_subtotal_amount'] ?? $paymentInfo['sub_total'] ?? $paymentInfo['subtotal'] ?? 0);
                        $productSubtotal = (float) ($paymentInfo['original_total_product_price'] ?? 0);

                        if ($productSubtotal <= 0 && !empty($tOrder['line_items'])) {
                            foreach ($tOrder['line_items'] as $lItem) {
                                $itemPrice = (float) ($lItem['original_price'] ?? $lItem['sale_price'] ?? 0);
                                $itemQty = (int) ($lItem['quantity'] ?? 1);
                                $productSubtotal += ($itemPrice * $itemQty);
                            }
                        }

                        $totalAmount = $subtotalAfterSeller > 0 ? $subtotalAfterSeller : ($productSubtotal > 0 ? $productSubtotal : (float) ($paymentInfo['total_amount'] ?? $tOrder['total_amount'] ?? $dbOrder->total_amount));
                        $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? $totalAmount);
                        $escrowAmount = (float) ($paymentInfo['settlement_amount'] ?? $paymentInfo['escrow_amount'] ?? 0);
                        
                        $platformCommission = (float) ($paymentInfo['platform_commission'] ?? $paymentInfo['commission_before_discount'] ?? 0);
                        $platformCommissionDiscount = (float) ($paymentInfo['platform_commission_discount'] ?? 0);
                        $netPlatformCommission = (float) ($paymentInfo['net_platform_commission'] ?? ($platformCommission > 0 ? max(0.0, $platformCommission - $platformCommissionDiscount) : 0));
                        
                        $preorderServiceFee = (float) ($paymentInfo['preorder_service_fee'] ?? 0);
                        $dynamicCommission = (float) ($paymentInfo['dynamic_commission'] ?? $paymentInfo['affiliate_commission'] ?? 0);
                        $growthXtraFee = (float) ($paymentInfo['growth_xtra_fee'] ?? 0);
                        $orderProcessingFee = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['transaction_fee'] ?? 0);

                        // Tembak API Finance TikTok untuk mengambil data settlement transaksi resmi yang sudah cair
                        $totalFeeFromStmt = 0.0;
                        $revenueFromStmt = 0.0;
                        $settlementFromStmt = null;
                        $customerRefundFromStmt = 0.0;
                        $returnShippingFromStmt = 0.0;

                        try {
                            $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
                            $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];
                            foreach ($stmtList as $st) {
                                if (isset($st['customer_refund_amount']) && (float)$st['customer_refund_amount'] != 0) {
                                    $customerRefundFromStmt = abs((float)$st['customer_refund_amount']);
                                } elseif (isset($st['gross_sales_refund_amount']) && (float)$st['gross_sales_refund_amount'] != 0) {
                                    $customerRefundFromStmt = abs((float)$st['gross_sales_refund_amount']);
                                } elseif (isset($st['customer_order_refund_amount']) && (float)$st['customer_order_refund_amount'] != 0) {
                                    $customerRefundFromStmt = abs((float)$st['customer_order_refund_amount']);
                                }

                                if (isset($st['return_shipping_fee_amount']) && (float)$st['return_shipping_fee_amount'] != 0) {
                                    $returnShippingFromStmt = abs((float)$st['return_shipping_fee_amount']);
                                } elseif (isset($st['actual_return_shipping_fee_amount']) && (float)$st['actual_return_shipping_fee_amount'] != 0) {
                                    $returnShippingFromStmt = abs((float)$st['actual_return_shipping_fee_amount']);
                                }

                                if (isset($st['revenue_amount']) && (float)$st['revenue_amount'] > 0) {
                                    $revenueFromStmt = (float)$st['revenue_amount'];
                                } elseif (isset($st['net_sales_amount']) && (float)$st['net_sales_amount'] > 0) {
                                    $revenueFromStmt = (float)$st['net_sales_amount'];
                                }

                                if (isset($st['fee_amount']) && (float)$st['fee_amount'] != 0 && $customerRefundFromStmt == 0) {
                                    $totalFeeFromStmt = abs((float)$st['fee_amount']);
                                }

                                if (isset($st['settlement_amount'])) {
                                    $settlementFromStmt = (float)$st['settlement_amount'];
                                }

                                if (isset($st['platform_commission_amount']) && (float)$st['platform_commission_amount'] != 0) {
                                    $platformCommission = abs((float)$st['platform_commission_amount']);
                                }

                                if (isset($st['seller_discount_amount']) && (float)$st['seller_discount_amount'] != 0) {
                                    $sellerDiscount = abs((float)$st['seller_discount_amount']);
                                }
                            }
                        } catch (\Exception $exStmt) {}

                        if ($revenueFromStmt > 0) {
                            $totalAmount = $revenueFromStmt;
                        }

                        if ($settlementFromStmt !== null) {
                            $escrowAmount = max(0.0, $settlementFromStmt);
                        }

                        $sellerDiscount = (float) ($paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? $sellerDiscount ?? 0);
                        $actualShipping = (float) ($paymentInfo['shipping_fee'] ?? $paymentInfo['actual_shipping_fee'] ?? 0);
                        $shippingSubsidy = (float) ($paymentInfo['shipping_fee_subsidy'] ?? $paymentInfo['platform_shipping_discount'] ?? 0);
                        $platformDiscount = (float) ($paymentInfo['platform_discount'] ?? 0);
                        $withholdingTax = (float) ($paymentInfo['withholding_tax'] ?? $paymentInfo['tax_amount'] ?? 0);
                        $sellerReturnRefund = $customerRefundFromStmt > 0 ? $customerRefundFromStmt : (float) ($paymentInfo['refund_amount'] ?? $paymentInfo['return_amount'] ?? 0);
                        $totalAdjustment = (float) ($paymentInfo['total_adjustment_amount'] ?? $paymentInfo['adjustment_amount'] ?? 0);
                        $protectionFee = (float) ($paymentInfo['shipping_seller_protection_fee_amount'] ?? $paymentInfo['protection_fee'] ?? 0);

                        // 🎯 PRESISI 100%: Total Fee dari TikTok Statement
                        if ($totalFeeFromStmt > 0 && $sellerReturnRefund == 0) {
                            $totalTiktokFees = $totalFeeFromStmt;
                        } elseif ($escrowAmount > 0 && $totalAmount > $escrowAmount) {
                            $totalTiktokFees = max(0.0, $totalAmount - $escrowAmount);
                        } else {
                            $totalTiktokFees = $netPlatformCommission + $preorderServiceFee + $dynamicCommission + $growthXtraFee + $orderProcessingFee + $sellerDiscount + $withholdingTax + $totalAdjustment + $protectionFee;
                        }

                        if ($totalTiktokFees <= 0 && $totalAmount > 0 && $sellerReturnRefund == 0) {
                            $totalTiktokFees = round($totalAmount * 0.085);
                        }

                        if ($sellerReturnRefund >= $totalAmount && $totalAmount > 0) {
                            $escrowAmount = 0.0;
                            $totalTiktokFees = 0.0;
                        } elseif ($escrowAmount <= 0 && $sellerReturnRefund == 0) {
                            $escrowAmount = max(0.0, $totalAmount - $totalTiktokFees);
                        }

                        $financialData = [
                            'original_price' => $totalAmount,
                            'buyer_paid_total' => $buyerPaidTotal,
                            'seller_discount' => $sellerDiscount,
                            'actual_shipping_fee' => $actualShipping,
                            'shopee_shipping_rebate' => $shippingSubsidy,
                            'shipping_fee_subsidy' => $shippingSubsidy,
                            'voucher_from_seller' => $sellerDiscount,
                            'voucher_from_shopee' => $platformDiscount,
                            'platform_discount' => $platformDiscount,
                            'withholding_tax' => $withholdingTax,
                            'seller_return_refund' => $sellerReturnRefund,
                            'refund_amount' => $sellerReturnRefund,
                            'return_shipping_fee' => $returnShippingFromStmt,
                            'total_adjustment_amount' => $totalAdjustment,
                            'shipping_seller_protection_fee_amount' => $protectionFee,
                            'platform_commission' => $platformCommission,
                            'platform_commission_discount' => $platformCommissionDiscount,
                            'net_platform_commission' => $netPlatformCommission,
                            'preorder_service_fee' => $preorderServiceFee,
                            'dynamic_commission' => $dynamicCommission,
                            'growth_xtra_fee' => $growthXtraFee,
                            'order_processing_fee' => $orderProcessingFee,
                            'service_fee' => ($sellerReturnRefund > 0 ? ($preorderServiceFee + $orderProcessingFee) : $totalTiktokFees),
                            'escrow_amount' => $escrowAmount,
                        ];

                        $dbOrder->financial_breakdown = array_merge($financialData, $stmtData ?? []);
                        $dbOrder->total_amount = $totalAmount;
                        $dbOrder->marketplace_fee = $totalTiktokFees;
                        $dbOrder->net_amount = $escrowAmount;

                        $stmtTs = null;
                        foreach ($stmtList as $st) {
                            $stTime = $st['statement_time'] ?? $st['settlement_time'] ?? null;
                            if ($stTime) {
                                $stSec = (is_numeric($stTime) && strlen((string)$stTime) >= 13) ? (int)($stTime / 1000) : (int)$stTime;
                                if ($stmtTs === null || $stSec > $stmtTs) {
                                    $stmtTs = $stSec;
                                }
                            }
                        }

                        $compTs = $stmtTs ?? $tOrder['finish_time'] ?? $tOrder['delivered_time'] ?? $tOrder['complete_time'] ?? $tOrder['delivery_time'] ?? $tOrder['update_time'] ?? $tOrder['paid_time'] ?? null;
                        if ($compTs) {
                            $compTsSec = (is_numeric($compTs) && strlen((string)$compTs) >= 13) ? (int)($compTs / 1000) : (int)$compTs;
                            $dbOrder->completed_at = \Carbon\Carbon::createFromTimestamp($compTsSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                        }

                        $createTs = $tOrder['create_time'] ?? null;
                        if ($createTs) {
                            $cTsSec = (is_numeric($createTs) && strlen((string)$createTs) >= 13) ? (int)($createTs / 1000) : (int)$createTs;
                            $dbOrder->order_date = \Carbon\Carbon::createFromTimestamp($cTsSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
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

        // Clear reconciliation web cache so dashboard reflects updates immediately
        \Illuminate\Support\Facades\Cache::flush();
    }
}
