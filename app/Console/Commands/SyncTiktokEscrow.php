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
    protected $signature = 'tiktok:sync-escrow {--store_id= : ID Toko TikTok tertentu (opsional)} {--order_id= : Nomor order TikTok tertentu untuk dites} {--date_from= : Tanggal awal order (contoh: 2026-08-01)} {--date_to= : Tanggal akhir order (contoh: 2026-08-18)} {--all : Paksa sinkronisasi semua order termasuk yang sudah match atau tanggal lama}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menembak API TikTok Shop dan mengupdate rincian biaya resmi untuk pesanan yang belum sinkron atau berbeda (mismatch) antara ERP dan API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("========================================================");
        $this->info("SINKRONISASI BIAYA & SETTLEMENT ESCROW TIKTOK SHOP");
        $this->info("========================================================\n");

        $tiktokService  = app(TiktokService::class);
        $storeIdOption  = $this->option('store_id');
        $orderIdOption  = $this->option('order_id');
        $dateFromOption = $this->option('date_from');
        $dateToOption   = $this->option('date_to');
        $forceAll       = $this->option('all');

        $query = Store::whereHas('channel', function ($q) {
            $q->whereIn('code', ['tiktok', 'tokopedia']);
        });

        // Jika order_id spesifik diberikan, cari toko pemilik order secara presisi lebih dulu
        if ($orderIdOption) {
            $orderIdClean = trim($orderIdOption);
            $dbMatch = Order::where('order_marketplace_id', $orderIdClean)
                ->orWhere('id', $orderIdClean)
                ->first();
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
                ->whereNotNull('order_marketplace_id')
                ->whereNotIn('order_status', ['CANCELLED', 'BATAL', 'CANCELED']);

            if ($orderIdOption) {
                $orderIdClean = trim($orderIdOption);
                $ordersQuery->where(function($q) use ($orderIdClean) {
                    $q->where('order_marketplace_id', $orderIdClean)
                      ->orWhere('id', $orderIdClean);
                });
            } else {
                if ($dateFromOption) {
                    $ordersQuery->whereDate('order_date', '>=', $dateFromOption);
                }
                if ($dateToOption) {
                    $ordersQuery->whereDate('order_date', '<=', $dateToOption);
                }
                // Jika tidak difilter tanggal spesifik dan bukan --all, batasi ke 30 hari terakhir agar tidak memproses data lampau
                if (!$dateFromOption && !$dateToOption && !$forceAll) {
                    $ordersQuery->whereDate('order_date', '>=', now()->subDays(30)->toDateString());
                }
            }

            $allOrders = $ordersQuery->get();

            if ($orderIdOption && $allOrders->isEmpty()) {
                // Jika order_id spesifik dicari tetapi belum ada di DB lokal toko ini, tembak langsung TikTok API
                try {
                    $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [trim($orderIdOption)]);
                    $tiktokOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

                    if (!empty($tiktokOrders)) {
                        $this->info("✅ Order ID '{$orderIdOption}' DITEMUKAN di Toko TikTok: {$store->store_name} (ID: {$store->id})!");
                        $job = new PullOrdersFromTiktok($store, time() - 86400, time());
                        $reflection = new \ReflectionClass($job);
                        $method = $reflection->getMethod('processOrder');
                        $method->setAccessible(true);
                        $method->invoke($job, $tiktokOrders[0]);

                        $allOrders = Order::where('store_id', $store->id)
                            ->where(function($q) use ($orderIdOption) {
                                $q->where('order_marketplace_id', trim($orderIdOption))
                                  ->orWhere('id', trim($orderIdOption));
                            })
                            ->get();
                    }
                } catch (\Exception $e) {
                    // Lanjut ke toko berikutnya jika bukan pemilik order
                }
            }

            if ($allOrders->isEmpty()) {
                if ($orderIdOption) {
                    continue;
                }
                $this->info("Tidak ada pesanan aktif pada periode yang dipilih.");
                continue;
            }

            // 🎯 FILTER HANYA PESANAN YANG BENAR-BENAR MISMATCH ATAU BELUM MEMILIKI SETTLEMENT RESMI
            if (!$orderIdOption && !$forceAll) {
                $orders = $allOrders->filter(function($ord) {
                    $status = strtoupper($ord->order_status);
                    
                    // 1. Jika status pesanan belum selesai/final (misal masih dikirim/siap dikirim), 
                    //    jangan tarik escrow/settlement dulu karena belum ada dana cair resmi di API.
                    $finalStatuses = ['COMPLETED', 'SELESAI', 'FINISHED', 'DELIVERED', 'RETURNED', 'REFUNDED', 'RETURN', 'REFUND', 'RETURN_APPROVED', 'RETURN_COMPLETED', 'PARTIAL_RETURN'];
                    if (!in_array($status, $finalStatuses)) {
                        return false; 
                    }

                    // 2. Jika financial_breakdown masih kosong atau tidak valid, wajib disinkronkan agar terisi
                    $fb = $ord->financial_breakdown;
                    if (empty($fb)) return true;

                    if (is_string($fb)) {
                        $fb = json_decode($fb, true);
                    }
                    if (!is_array($fb) || empty($fb)) return true;

                    // 3. Jika rincian statement_transactions belum ada/kosong untuk pesanan selesai,
                    //    wajib disinkronkan agar data transaksi riil dari API Finance ditarik.
                    $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? $fb['transactions'] ?? [];
                    if (empty($stmtList)) {
                        return true;
                    }

                    // 4. Jika ada statement, cek kecocokan data nominal ERP dengan API
                    $st0 = (is_array($stmtList) && !empty($stmtList[0]) && is_array($stmtList[0])) ? $stmtList[0] : [];
                    $sellerDisc = (float)($fb['seller_discount'] ?? $fb['voucher_from_seller'] ?? $fb['discount_amount'] ?? 0);
                    if (isset($fb['subtotal_after_seller_discounts']) && (float)$fb['subtotal_after_seller_discounts'] > 0) {
                        $apiOmset = (float)$fb['subtotal_after_seller_discounts'];
                    } elseif (isset($st0['net_sales_amount']) && (float)$st0['net_sales_amount'] > 0) {
                        $apiOmset = (float)$st0['net_sales_amount'];
                    } elseif (isset($st0['revenue_amount']) && (float)$st0['revenue_amount'] > 0) {
                        $apiOmset = (float)$st0['revenue_amount'];
                    } elseif (isset($fb['original_total_product_price']) && (float)$fb['original_total_product_price'] > 0) {
                        $orig = (float)$fb['original_total_product_price'];
                        $apiOmset = ($sellerDisc > 0 && $orig > $sellerDisc) ? max(0.0, $orig - $sellerDisc) : $orig;
                    } else {
                        $apiOmset = (float)$ord->total_amount;
                    }

                    $apiNet = (float)($fb['escrow_amount'] ?? $st0['settlement_amount'] ?? $fb['settlement_amount'] ?? $fb['seller_settlement_amount'] ?? $ord->net_amount);
                    
                    if (!empty($st0['fee_amount']) && (float)$st0['fee_amount'] != 0) {
                        $apiFee = abs((float)$st0['fee_amount']);
                    } elseif ($apiOmset > 0 && $apiNet > 0 && $apiOmset > $apiNet) {
                        $apiFee = max(0.0, $apiOmset - $apiNet);
                    } else {
                        $apiFee = abs((float)($ord->fee_breakdown_details['total_fee'] ?? $ord->marketplace_fee));
                    }

                    $diffOmset = abs((float)$ord->total_amount - $apiOmset);
                    $diffNet   = abs((float)$ord->net_amount - $apiNet);
                    $diffFee   = abs((float)$ord->marketplace_fee - $apiFee);

                    return ($ord->recon_status !== 'RECONCILED' || $diffOmset > 100 || $diffNet > 100 || $diffFee > 100);
                });

                $skippedCount = $allOrders->count() - $orders->count();
                $this->info("Total Pesanan Aktif: {$allOrders->count()} | Ditemukan Mismatch: {$orders->count()} ({$skippedCount} pesanan sudah match dilewati)");
            } else {
                $orders = $allOrders;
                $this->info("Menemukan {$orders->count()} pesanan TikTok untuk disinkronkan...");
            }

            if ($orders->isEmpty()) {
                $this->info("✨ Semua pesanan di toko ini sudah MATCH 100%!");
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

                        $dbOrder = $orders->first(function($o) use ($mId) {
                            return (string)$o->order_marketplace_id === (string)$mId || (string)$o->id === (string)$mId;
                        });
                        if (!$dbOrder) continue;

                        $paymentInfo = $tOrder['payment'] ?? $tOrder['payment_info'] ?? [];
                        $sellerDiscount = (float) ($paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? 0);
                        $productSubtotal = (float) ($paymentInfo['original_total_product_price'] ?? 0);

                        if ($productSubtotal <= 0 && !empty($tOrder['line_items'])) {
                            foreach ($tOrder['line_items'] as $lItem) {
                                $itemPrice = max(0.0, ((float)($lItem['original_price'] ?? $lItem['price'] ?? 0)) - ((float)($lItem['seller_discount'] ?? 0)));
                                $itemQty = (int) ($lItem['quantity'] ?? 1);
                                $productSubtotal += ($itemPrice * $itemQty);
                            }
                        }

                        // Subtotal setelah diskon penjual (Net Sales / Omset Jual Murni)
                        if (isset($paymentInfo['subtotal_after_seller_discounts']) && (float)$paymentInfo['subtotal_after_seller_discounts'] > 0) {
                            $subtotalAfterSeller = (float)$paymentInfo['subtotal_after_seller_discounts'];
                        } elseif ($productSubtotal > 0 && $sellerDiscount > 0 && $productSubtotal > $sellerDiscount) {
                            $subtotalAfterSeller = $productSubtotal - $sellerDiscount;
                        } elseif ($productSubtotal > 0) {
                            $subtotalAfterSeller = $productSubtotal;
                        } else {
                            $subtotalAfterSeller = (float) ($paymentInfo['sub_total'] ?? $paymentInfo['subtotal'] ?? $paymentInfo['total_amount'] ?? 0);
                        }

                        $totalAmount = $subtotalAfterSeller > 0 ? $subtotalAfterSeller : (float) ($paymentInfo['total_amount'] ?? $tOrder['total_amount'] ?? $dbOrder->total_amount);
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
                        $settlementSum = 0.0;
                        $feeSum = 0.0;
                        $hasSettlement = false;

                        try {
                            $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
                            $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];
                            foreach ($stmtList as $st) {
                                if (isset($st['customer_refund_amount']) && (float)$st['customer_refund_amount'] != 0) {
                                    $customerRefundFromStmt += abs((float)$st['customer_refund_amount']);
                                } elseif (isset($st['gross_sales_refund_amount']) && (float)$st['gross_sales_refund_amount'] != 0) {
                                    $customerRefundFromStmt += abs((float)$st['gross_sales_refund_amount']);
                                } elseif (isset($st['customer_order_refund_amount']) && (float)$st['customer_order_refund_amount'] != 0) {
                                    $customerRefundFromStmt += abs((float)$st['customer_order_refund_amount']);
                                }

                                if (isset($st['return_shipping_fee_amount']) && (float)$st['return_shipping_fee_amount'] != 0) {
                                    $returnShippingFromStmt += abs((float)$st['return_shipping_fee_amount']);
                                } elseif (isset($st['actual_return_shipping_fee_amount']) && (float)$st['actual_return_shipping_fee_amount'] != 0) {
                                    $returnShippingFromStmt += abs((float)$st['actual_return_shipping_fee_amount']);
                                }

                                if (isset($st['revenue_amount']) && (float)$st['revenue_amount'] > 0) {
                                    $revenueFromStmt = max($revenueFromStmt, (float)$st['revenue_amount']);
                                } elseif (isset($st['net_sales_amount']) && (float)$st['net_sales_amount'] > 0) {
                                    $revenueFromStmt = max($revenueFromStmt, (float)$st['net_sales_amount']);
                                }

                                if (isset($st['fee_amount'])) {
                                    $feeSum += (float)$st['fee_amount'];
                                }

                                if (isset($st['settlement_amount'])) {
                                    $settlementSum += (float)$st['settlement_amount'];
                                    $hasSettlement = true;
                                }
                            }

                            if ($hasSettlement) {
                                $settlementFromStmt = $settlementSum;
                            }
                            $totalFeeFromStmt = abs($feeSum);
                        } catch (\Exception $exStmt) {}

                        if ($revenueFromStmt > 0) {
                            $totalAmount = $revenueFromStmt;
                        }

                        if ($settlementFromStmt !== null) {
                            $escrowAmount = $settlementFromStmt;
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
                        if ($totalFeeFromStmt > 0) {
                            $totalTiktokFees = $totalFeeFromStmt;
                        } elseif ($escrowAmount > 0 && $totalAmount > $escrowAmount) {
                            $totalTiktokFees = max(0.0, $totalAmount - $escrowAmount);
                        } else {
                            $totalTiktokFees = $netPlatformCommission + $preorderServiceFee + $dynamicCommission + $growthXtraFee + $orderProcessingFee + $protectionFee;
                        }

                        if ($totalTiktokFees <= 0 && $totalAmount > 0 && $sellerReturnRefund == 0) {
                            $totalTiktokFees = round($totalAmount * 0.085);
                        }

                        // Jika nominal settlement didapatkan langsung dari transaksi API, jangan paksa menjadi 0!
                        if ($settlementFromStmt !== null) {
                            $escrowAmount = $settlementFromStmt;
                            if ($totalFeeFromStmt > 0) {
                                $totalTiktokFees = $totalFeeFromStmt;
                            }
                        } else {
                            if ($sellerReturnRefund >= $totalAmount && $totalAmount > 0) {
                                $escrowAmount = 0.0;
                                $totalTiktokFees = 0.0;
                            } elseif ($escrowAmount <= 0 && $sellerReturnRefund == 0) {
                                $escrowAmount = max(0.0, $totalAmount - $totalTiktokFees);
                            }
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

                        $finalFb = array_merge($financialData, $stmtData ?? []);
                        $dbOrder->financial_breakdown = $finalFb;
                        $dbOrder->total_amount = $totalAmount;
                        $dbOrder->marketplace_fee = $totalTiktokFees;
                        $dbOrder->net_amount = $escrowAmount;

                        $dtSave = $dbOrder->fee_breakdown_details;
                        $dbOrder->fee_platform_amount = abs($dtSave['platform_fee'] ?? 0);
                        $dbOrder->fee_free_shipping_amount = abs($dtSave['free_shipping'] ?? 0);
                        $dbOrder->fee_service_amount = abs($dtSave['service_fee'] ?? 0);
                        $dbOrder->fee_promo_amount = abs($dtSave['promo_fee'] ?? 0);
                        $dbOrder->fee_other_amount = abs($dtSave['other_fee'] ?? 0);

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

                        $dbOrder->total_amount = $totalAmount;
                        $dbOrder->marketplace_fee = $totalTiktokFees;
                        $dbOrder->net_amount = $escrowAmount;
                        $dbOrder->recon_status = 'RECONCILED';
                        $dbOrder->financial_breakdown = $finalFb;
                        $dbOrder->save();

                        // 📦 PERBAIKAN OTOMATIS: Jika order belum punya item di DB, buatkan itemnya dari API
                        if ($dbOrder->items()->count() === 0) {
                            $itemListSync = $tOrder['line_items']
                                ?? $tOrder['item_list']
                                ?? $tOrder['order_line_list']
                                ?? $tOrder['sku_list']
                                ?? $tOrder['items']
                                ?? [];

                            if (!empty($itemListSync)) {
                                foreach ($itemListSync as $itSync) {
                                    $productId = (string)($itSync['product_id'] ?? '');
                                    $skuId     = (string)($itSync['sku_id'] ?? '');
                                    $sellerSku = $itSync['seller_sku'] ?? $itSync['sku'] ?? null;
                                    $skuName   = $itSync['sku_name'] ?? $itSync['variation_name'] ?? null;
                                    $origPrice = (float)($itSync['original_price'] ?? $itSync['price'] ?? 0);
                                    $sDisc     = (float)($itSync['seller_discount'] ?? 0);
                                    $pDisc     = (float)($itSync['platform_discount'] ?? 0);
                                    $qty       = (int)($itSync['quantity'] ?? 1);
                                    $unitPrice = max(0.0, $origPrice - $sDisc);
                                    $pName     = $itSync['product_name'] ?? $itSync['item_name'] ?? 'Produk TikTok';
                                    $vName     = $itSync['sku_name'] ?? $itSync['variant_name'] ?? '';

                                    \App\Models\OrderItem::create([
                                        'order_id'               => $dbOrder->id,
                                        'sku'                    => $sellerSku ?: $skuId,
                                        'seller_sku'             => $sellerSku,
                                        'sku_id'                 => $skuId,
                                        'sku_name'               => $skuName ?: $vName,
                                        'product_name'           => mb_substr($pName . ($vName ? ' - ' . $vName : ''), 0, 250),
                                        'price'                  => $unitPrice,
                                        'original_price'         => $origPrice,
                                        'seller_discount'        => $sDisc,
                                        'platform_discount'      => $pDisc,
                                        'quantity'               => $qty,
                                        'total_price'            => $unitPrice * $qty,
                                    ]);
                                }
                                $this->info("   └─ 📦 Berhasil menambahkan " . count($itemListSync) . " item produk untuk order {$mId}");
                            }
                        } else {
                            // Update harga single item agar konsisten dengan total_amount
                            $existItems = $dbOrder->items;
                            if ($existItems->count() === 1 && $totalAmount > 0) {
                                $singleItem = $existItems->first();
                                $iQty = $singleItem->quantity ?: 1;
                                \DB::table('order_items')->where('id', $singleItem->id)->update([
                                    'price'       => round($totalAmount / $iQty, 2),
                                    'total_price' => $totalAmount,
                                    'updated_at'  => now(),
                                ]);
                            }
                        }

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
