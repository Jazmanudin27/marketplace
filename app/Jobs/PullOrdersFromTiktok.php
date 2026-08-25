<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullOrdersFromTiktok implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $storeId;
    public ?int $timeFrom;
    public ?int $timeTo;
    public bool $skipStockDeduction;
    public ?string $orderId;

    public ?Store $store = null;

    /**
     * Create a new job instance.
     */
    public function __construct(Store|int $store, ?int $timeFrom = null, ?int $timeTo = null, bool $skipStockDeduction = false, ?string $orderId = null)
    {
        if ($store instanceof Store) {
            $this->store = $store;
            $this->storeId = $store->id;
        } else {
            $this->storeId = (int) $store;
            $loadedStore = Store::find($store);
            if ($loadedStore) {
                $this->store = $loadedStore;
            }
        }
        $this->timeFrom = $timeFrom ?? now()->subDays(15)->timestamp;
        $this->timeTo = $timeTo ?? now()->timestamp;
        $this->skipStockDeduction = $skipStockDeduction;
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(TiktokService $tiktokService): void
    {
        if (!$this->store) {
            $this->store = Store::find($this->storeId);
        }

        if (! $this->store) {
            Log::warning('[TikTok] PullOrdersFromTiktok: Store #' . $this->storeId . ' no longer exists. Discarding job.');
            return;
        }

        if ($this->store->status === 'disconnected' || (empty($this->store->access_token) && empty($this->store->refresh_token))) {
            Log::warning("[TikTok] Toko {$this->store->store_name} tidak terhubung.");
            return;
        }

        try {
            $accessToken = $this->store->getValidAccessToken();
            $shopCipher = $this->store->shop_cipher;

            if (empty($shopCipher)) {
                Log::warning("[TikTok] shop_cipher kosong untuk toko {$this->store->store_name}.");
                return;
            }

            // 🎯 JIKA ORDER_ID SPESIFIK DIISI (KILAT WEBHOOK)
            if ($this->orderId) {
                Log::info("[TikTok] Webhook Trigger: Menyinkronkan order detail tunggal: {$this->orderId}");
                $detailResponse = $tiktokService->getOrderDetail(
                    $accessToken,
                    $shopCipher,
                    [$this->orderId]
                );

                $orderList = $detailResponse['orders'] ?? $detailResponse['order_list'] ?? [];
                foreach ($orderList as $tiktokOrder) {
                    $this->processOrder($tiktokOrder);
                }
                return;
            }

            $cursor = '';
            $orderIds = [];
            $pageCount = 0;
            $previousCursor = null;

            do {
                $response = $tiktokService->getOrderList(
                    $accessToken,
                    $shopCipher,
                    $this->timeFrom,
                    $this->timeTo,
                    $cursor
                );

                $orders = $response['orders'] ?? $response['order_list'] ?? [];

                foreach ($orders as $o) {
                    $id = $o['id'] ?? $o['order_id'] ?? null;
                    if ($id) {
                        $orderIds[] = $id;
                    }
                }

                $previousCursor = $cursor;
                $cursor = $response['next_cursor'] ?? '';
                $hasMore = $response['more'] ?? false;
                
                if ($cursor === $previousCursor || ++$pageCount > 10) {
                    break;
                }
                
            } while ($hasMore && $cursor);

            if (empty($orderIds)) {
                Log::info("[TikTok] Tidak ada pesanan baru untuk toko {$this->store->store_name}");
                return;
            }

            $chunks = array_chunk(array_values($orderIds), 50);

            foreach ($chunks as $chunk) {
                $detailResponse = $tiktokService->getOrderDetail(
                    $accessToken,
                    $shopCipher,
                    $chunk
                );

                $orderList = $detailResponse['orders'] ?? $detailResponse['order_list'] ?? [];

                foreach ($orderList as $tiktokOrder) {
                    $this->processOrder($tiktokOrder);
                }
            }

        } catch (\Exception $e) {
            Log::error("[TikTok] Gagal menarik pesanan untuk toko {$this->store->store_name}: " . $e->getMessage());
        }
    }

    protected function processOrder(array $tiktokOrder)
    {
        if (!$this->store) {
            $this->store = Store::find($this->storeId);
        }

        // Standarisasi Status TikTok
        $statusRaw = $tiktokOrder['status'] ?? $tiktokOrder['order_status'] ?? 'UNPAID';
        $statusMap = [
            'UNPAID' => 'UNPAID',
            '100' => 'UNPAID',
            'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
            '111' => 'READY_TO_SHIP',
            'AWAITING_COLLECTION' => 'READY_TO_SHIP',
            '112' => 'READY_TO_SHIP',
            'IN_TRANSIT' => 'SHIPPED',
            '121' => 'SHIPPED',
            'DELIVERED' => 'DELIVERED',
            '122' => 'DELIVERED',
            'COMPLETED' => 'COMPLETED',
            '130' => 'COMPLETED',
            'CANCELLED' => 'CANCELLED',
            '140' => 'CANCELLED',
        ];
        $erpStatus = $statusMap[strtoupper((string)$statusRaw)] ?? strtoupper((string)$statusRaw);

        // 🚀 BUYER NAME ACCURACY: Prioritaskan nama penerima resmi TikTok API v202309
        $recName = $tiktokOrder['recipient_address']['name'] 
            ?? (trim(($tiktokOrder['recipient_address']['first_name'] ?? '') . ' ' . ($tiktokOrder['recipient_address']['last_name'] ?? '')));

        if (empty($recName) || $recName === ' ') {
            $recName = $tiktokOrder['buyer_email'] ?? 'TikTok Buyer';
        }

        $buyerName = is_array($recName) ? ($recName['name'] ?? 'TikTok Buyer') : $recName;
        $buyerPhone = $tiktokOrder['recipient_address']['phone'] ?? $tiktokOrder['recipient_address']['phone_number'] ?? '0000000000';
        $buyerAddress = $tiktokOrder['recipient_address']['full_address'] 
            ?? $tiktokOrder['recipient_address']['address_detail'] 
            ?? ($tiktokOrder['recipient_address']['address_line1'] ?? '');

        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'phone' => $buyerPhone ?: '0000000000',
            ],
            [
                'name' => $buyerName,
                'address' => $buyerAddress,
            ]
        );

        $createTsSec = (function() use ($tiktokOrder) {
            $ts = $tiktokOrder['create_time'] ?? $tiktokOrder['create_time_ge'] ?? time();
            return (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
        })();
        $orderDateTime = \Carbon\Carbon::createFromTimestamp($createTsSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');

        $liveSessionId = null;
        if (!empty($tiktokOrder['live_session_id'])) {
            $liveSessionId = $tiktokOrder['live_session_id'];
        }

        // ✅ Definisikan $orderIdStr di sini agar bisa dipakai di fallback & statement block di bawah
        $orderIdStr = (string)($tiktokOrder['id'] ?? $tiktokOrder['order_id'] ?? '');

        $itemList = $tiktokOrder['line_items']
            ?? $tiktokOrder['item_list']
            ?? $tiktokOrder['order_line_list']
            ?? $tiktokOrder['sku_list']
            ?? $tiktokOrder['items']
            ?? $tiktokOrder['product_list']
            ?? $tiktokOrder['sku_info_list']
            ?? $tiktokOrder['item_info_list']
            ?? $tiktokOrder['order_items']
            ?? [];

        if (empty($itemList) && !empty($tiktokOrder['packages'])) {
            foreach ($tiktokOrder['packages'] as $pkg) {
                if (!empty($pkg['items'])) {
                    $itemList = array_merge($itemList, $pkg['items']);
                } elseif (!empty($pkg['line_items'])) {
                    $itemList = array_merge($itemList, $pkg['line_items']);
                } elseif (!empty($pkg['item_list'])) {
                    $itemList = array_merge($itemList, $pkg['item_list']);
                } elseif (!empty($pkg['order_line_list'])) {
                    $itemList = array_merge($itemList, $pkg['order_line_list']);
                } elseif (!empty($pkg['sku_list'])) {
                    $itemList = array_merge($itemList, $pkg['sku_list']);
                }
            }
        }

        // 🚀 FALLBACK OTOMATIS: Jika itemList masih kosong, panggil Detail Order API spesifik untuk order ini
        // BUG FIX: $orderIdStr sekarang sudah didefinisikan di atas sehingga kondisi ini bisa berjalan
        if (empty($itemList) && !empty($orderIdStr)) {
            try {
                $tiktokServiceFallback = app(\App\Services\TiktokService::class);
                $accessTokenFallback = $this->store->getValidAccessToken();
                $detailRes = $tiktokServiceFallback->getOrderDetail($accessTokenFallback, $this->store->shop_cipher, [$orderIdStr]);
                $detailOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];
                if (!empty($detailOrders[0])) {
                    $singleDetail = $detailOrders[0];
                    $itemList = $singleDetail['line_items']
                        ?? $singleDetail['item_list']
                        ?? $singleDetail['order_line_list']
                        ?? $singleDetail['sku_list']
                        ?? $singleDetail['items']
                        ?? [];
                    // Cek packages dari fallback juga
                    if (empty($itemList) && !empty($singleDetail['packages'])) {
                        foreach ($singleDetail['packages'] as $pkg) {
                            if (!empty($pkg['items'])) {
                                $itemList = array_merge($itemList, $pkg['items']);
                            } elseif (!empty($pkg['line_items'])) {
                                $itemList = array_merge($itemList, $pkg['line_items']);
                            } elseif (!empty($pkg['item_list'])) {
                                $itemList = array_merge($itemList, $pkg['item_list']);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("[TikTok] Fallback getOrderDetail gagal untuk order {$orderIdStr}: " . $e->getMessage());
            }
        }

        // 🚀 METODE HITUNG AKURAT TOTAL NILAI BARANG
        $productSubtotal = 0.0;
        if (!empty($itemList)) {
            foreach ($itemList as $it) {
                $origP = (float) ($it['original_price'] ?? $it['price'] ?? 0);
                $sD = (float) ($it['seller_discount'] ?? 0);
                $iPrice = max(0.0, $origP - $sD);
                $iQty = (int) ($it['quantity'] ?? 1);
                $productSubtotal += ($iPrice * $iQty);
            }
        }

        $paymentInfo = $tiktokOrder['payment_info'] ?? $tiktokOrder['payment'] ?? [];
        $discountAmount = (float) ($paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? 0);
        $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? $tiktokOrder['total_amount'] ?? 0);

        if (isset($paymentInfo['subtotal_after_seller_discounts']) && (float)$paymentInfo['subtotal_after_seller_discounts'] > 0) {
            $subtotalAfterSeller = (float)$paymentInfo['subtotal_after_seller_discounts'];
        } elseif ($productSubtotal > 0 && $discountAmount > 0 && $productSubtotal > $discountAmount) {
            $subtotalAfterSeller = $productSubtotal - $discountAmount;
        } elseif ($productSubtotal > 0) {
            $subtotalAfterSeller = $productSubtotal;
        } else {
            $subtotalAfterSeller = (float) ($paymentInfo['sub_total'] ?? $paymentInfo['subtotal'] ?? $paymentInfo['total_amount'] ?? 0);
        }

        $totalAmount = $subtotalAfterSeller > 0 ? $subtotalAfterSeller : (float) ($paymentInfo['total_amount'] ?? $tiktokOrder['total_amount'] ?? 0);
        $shippingFee = (float) ($paymentInfo['shipping_fee'] ?? $paymentInfo['actual_shipping_fee'] ?? 0);

        $financialBreakdown = null;
        // $orderIdStr sudah didefinisikan di atas (sebelum fallback block)
        
        if ($erpStatus !== 'CANCELLED') {
            try {
                $tiktokService = app(\App\Services\TiktokService::class);
                $accessToken = $this->store->getValidAccessToken();
                $stmtRes = $tiktokService->getOrderStatementTransactions($accessToken, $this->store->shop_cipher, $orderIdStr);
                if (!empty($stmtRes['statement_transactions'])) {
                    $financialBreakdown = $stmtRes;
                }
            } catch (\Throwable $e) {
                // Ignore if statement not available yet
            }
        }

        $platformCommission = (float) ($paymentInfo['platform_commission'] ?? 0);
        $platformCommissionDiscount = (float) ($paymentInfo['platform_commission_discount'] ?? 0);
        $netPlatformCommission = max(0.0, $platformCommission - $platformCommissionDiscount);

        $preorderServiceFee = (float) ($paymentInfo['preorder_service_fee'] ?? 0);
        $dynamicCommission = (float) ($paymentInfo['dynamic_commission'] ?? 0);
        $growthXtraFee = (float) ($paymentInfo['growth_xtra_fee'] ?? 0);
        $orderProcessingFee = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['seller_transaction_fee'] ?? 0);
        $escrowAmount = (float) ($paymentInfo['escrow_amount'] ?? $paymentInfo['settlement_amount'] ?? 0);

        $totalTiktokFees = $netPlatformCommission + $preorderServiceFee + $dynamicCommission + $growthXtraFee + $orderProcessingFee;
        
        if ($escrowAmount > 0) {
            $netAmount = $escrowAmount;
            $marketplaceFee = max(0.0, $totalAmount - $netAmount);
        } elseif ($totalTiktokFees > 0) {
            $marketplaceFee = $totalTiktokFees;
            $netAmount = max(0.0, $totalAmount - $totalTiktokFees);
        } else {
            $marketplaceFee = round($totalAmount * 0.085);
            $netAmount = max(0.0, $totalAmount - $marketplaceFee);
        }

        $stmtList = $stmtRes['statement_transactions'] ?? [];
        $st0 = (!empty($stmtList[0]) && is_array($stmtList[0])) ? $stmtList[0] : [];

        $financialBreakdown = array_merge($paymentInfo, $st0, [
            'original_price' => $totalAmount,
            'buyer_paid_total' => $buyerPaidTotal,
            'subtotal_after_seller_discounts' => $subtotalAfterSeller,
            'seller_discount' => $discountAmount,
            'actual_shipping_fee' => $shippingFee,
            'shopee_shipping_rebate' => (float) ($paymentInfo['shipping_fee_subsidy'] ?? $paymentInfo['platform_shipping_discount'] ?? 0),
            'voucher_from_seller' => $discountAmount,
            'voucher_from_shopee' => (float) ($paymentInfo['platform_discount'] ?? 0),
            'platform_discount' => (float) ($paymentInfo['platform_discount'] ?? 0),
            'withholding_tax' => (float) ($paymentInfo['withholding_tax'] ?? $paymentInfo['tax_amount'] ?? 0),
            'seller_return_refund' => (float) ($paymentInfo['refund_amount'] ?? $paymentInfo['return_amount'] ?? $st0['customer_refund_amount'] ?? 0),
            'refund_amount' => (float) ($paymentInfo['refund_amount'] ?? $paymentInfo['return_amount'] ?? $st0['customer_refund_amount'] ?? 0),
            'total_adjustment_amount' => (float) ($paymentInfo['total_adjustment_amount'] ?? $paymentInfo['adjustment_amount'] ?? 0),
            'shipping_seller_protection_fee_amount' => (float) ($paymentInfo['shipping_seller_protection_fee_amount'] ?? $paymentInfo['protection_fee'] ?? 0),
            'platform_commission' => $platformCommission,
            'platform_commission_discount' => $platformCommissionDiscount,
            'net_platform_commission' => $netPlatformCommission,
            'preorder_service_fee' => $preorderServiceFee,
            'dynamic_commission' => $dynamicCommission,
            'growth_xtra_fee' => $growthXtraFee,
            'order_processing_fee' => $orderProcessingFee,
            'service_fee' => $totalTiktokFees > 0 ? $totalTiktokFees : $marketplaceFee,
            'escrow_amount' => $escrowAmount > 0 ? $escrowAmount : $netAmount,
            'settlement_amount' => $escrowAmount > 0 ? $escrowAmount : $netAmount,
        ], $stmtRes ?? []);

        $courier = $tiktokOrder['shipping_provider'] ?? $tiktokOrder['shipping_provider_name'] ?? null;
        $trackingNumber = $tiktokOrder['tracking_number'] ?? $tiktokOrder['tracking_no'] ?? null;
        $createTime = $tiktokOrder['create_time'] ?? $tiktokOrder['create_time_ge'] ?? time();
        if (is_numeric($createTime) && strlen((string)$createTime) >= 13) {
            $createTime = (int)($createTime / 1000);
        }

        $tiktokCreatorName = $tiktokOrder['affiliate']['creator_name'] ?? $tiktokOrder['creator_name'] ?? null;
        $tiktokCreatorId = $tiktokOrder['affiliate']['creator_id'] ?? $tiktokOrder['creator_id'] ?? null;
        $affiliateCommission = $tiktokOrder['affiliate']['commission_amount'] ?? $tiktokOrder['commission_amount'] ?? 0;

        $cancelReason = $tiktokOrder['cancel_reason'] ?? $tiktokOrder['cancel_user_reason'] ?? null;
        $cancelledBy = $tiktokOrder['cancel_by'] ?? null;

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'order_marketplace_id' => $orderIdStr,
            ],
            [
                'store_id' => $this->store->id,
                'customer_id' => $customer->id,
                'order_status' => $erpStatus,
                'order_date' => $orderDateTime,
                'buyer_name' => $buyerName,
                'buyer_phone' => $buyerPhone,
                'shipping_address' => $buyerAddress,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'marketplace_fee' => $marketplaceFee,
                'courier' => $courier,
                'tracking_number' => $trackingNumber,
                'completed_at' => in_array($erpStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']) ? \Carbon\Carbon::createFromTimestamp((function() use ($tiktokOrder, $createTime) {
                    $ts = $tiktokOrder['finish_time'] ?? $tiktokOrder['delivered_time'] ?? $tiktokOrder['complete_time'] ?? $tiktokOrder['delivery_time'] ?? $tiktokOrder['update_time'] ?? $tiktokOrder['paid_time'] ?? $createTime;
                    return (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
                })(), 'Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'ship_before_date' => $this->resolveShipBeforeDate($tiktokOrder),
                'paid_at' => !empty($tiktokOrder['paid_time']) ? \Carbon\Carbon::createFromTimestamp((is_numeric($tiktokOrder['paid_time']) && strlen((string)$tiktokOrder['paid_time']) >= 13) ? (int)($tiktokOrder['paid_time'] / 1000) : (int)$tiktokOrder['paid_time'], 'Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'payment_method' => $tiktokOrder['payment_method_name'] ?? $tiktokOrder['payment_method_code'] ?? (!empty($tiktokOrder['is_cod']) ? 'Cash on Delivery' : null),
                'buyer_email' => $tiktokOrder['buyer_email'] ?? null,
                'buyer_message' => $tiktokOrder['buyer_message'] ?? null,
                'seller_note' => $tiktokOrder['seller_note'] ?? null,
                'package_id' => $tiktokOrder['packages'][0]['id'] ?? $tiktokOrder['package_id'] ?? null,
                'financial_breakdown' => $financialBreakdown,
                'tiktok_creator_name' => $tiktokCreatorName,
                'tiktok_creator_id' => $tiktokCreatorId,
                'affiliate_commission' => $affiliateCommission,
                'tiktok_live_session_id' => $liveSessionId,
                'cancel_reason' => $cancelReason,
                'cancelled_by' => $cancelledBy,
            ]
        );

        if (!empty($itemList)) {
            OrderItem::where('order_id', $order->id)->delete();

            $insertRows = [];
            foreach ($itemList as $item) {
                $productId = (string)($item['product_id'] ?? '');
                $skuId     = (string)($item['sku_id'] ?? '');
                $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;
                $skuName   = $item['sku_name'] ?? $item['variation_name'] ?? null;
                $origPrice = (float)($item['original_price'] ?? $item['price'] ?? 0);
                $sDisc     = (float)($item['seller_discount'] ?? 0);
                $pDisc     = (float)($item['platform_discount'] ?? 0);

                // Mapping ke Marketplace Product / Master Product jika ada
                $marketplaceProduct = null;
                if ($productId) {
                    $query = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                        ->where('marketplace_product_id', $productId);
                    if ($skuId) {
                        $query->where('marketplace_variant_id', $skuId);
                    }
                    $marketplaceProduct = $query->first();

                    if (!$marketplaceProduct && $skuId) {
                        $marketplaceProduct = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                            ->where('marketplace_product_id', $productId)
                            ->first();
                    }
                }

                $masterProduct = $marketplaceProduct ? $marketplaceProduct->masterProduct : null;
                if (!$masterProduct && $sellerSku) {
                    $skuClean = trim($sellerSku);
                    $masterProduct = \App\Models\MasterProduct::where('tenant_id', $this->store->tenant_id)
                        ->where('sku', $skuClean)
                        ->first();
                }

                if (empty($sellerSku) && $masterProduct) {
                    $sellerSku = $masterProduct->sku;
                }

                $marketplaceProductId = $marketplaceProduct ? $marketplaceProduct->id : null;
                $masterProductId = $masterProduct ? $masterProduct->id : null;

                $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;
                $qty = (int) ($item['quantity'] ?? 1);
                
                $unitPrice = max(0.0, $origPrice - $sDisc);

                $pName = $item['product_name'] ?? $item['item_name'] ?? 'Produk TikTok';
                $vName = $item['sku_name'] ?? $item['variant_name'] ?? '';

                $insertRows[] = [
                    'order_id'               => $order->id,
                    'sku'                    => $sellerSku ?: $skuId,
                    'seller_sku'             => $sellerSku,
                    'sku_id'                 => $skuId,
                    'sku_name'               => $skuName ?: $vName,
                    'marketplace_product_id' => $marketplaceProductId,
                    'master_product_id'      => $masterProductId,
                    'product_name'           => mb_substr($pName . ($vName ? ' - ' . $vName : ''), 0, 250),
                    'price'                  => $unitPrice,
                    'original_price'         => $origPrice,
                    'seller_discount'        => $sDisc,
                    'platform_discount'      => $pDisc,
                    'quantity'               => $qty,
                    'total_price'            => $unitPrice * $qty,
                    'cost_price'             => $costPrice,
                    'hpp_subtotal'           => $costPrice * $qty,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }

            if (!empty($insertRows)) {
                \Illuminate\Support\Facades\DB::table('order_items')->insert($insertRows);
            }
        }

        if (!$this->skipStockDeduction) {
            $order->processStockDeduction();
        }
    }

    protected function resolveShipBeforeDate(array $tiktokOrder): ?string
    {
        // 🚀 BATAS KIRIM TIKTOK API v202309 SLA TIMESTAMPS
        $timestamp = $tiktokOrder['shipping_due_time']
            ?? $tiktokOrder['rts_sla_time']
            ?? $tiktokOrder['tts_sla_time']
            ?? $tiktokOrder['cancel_order_sla_time']
            ?? $tiktokOrder['ship_by_date']
            ?? $tiktokOrder['ship_before_date']
            ?? null;

        if (!$timestamp || !is_numeric($timestamp)) {
            return null;
        }

        $timestamp = (int) $timestamp;
        if (strlen((string)$timestamp) >= 13) {
            $timestamp = (int)($timestamp / 1000);
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
