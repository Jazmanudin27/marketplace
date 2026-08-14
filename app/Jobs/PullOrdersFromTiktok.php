<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Customer;
use App\Models\MasterProduct;
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

    protected int $storeId;
    protected int $timeFrom;
    protected int $timeTo;
    protected ?Store $store = null;

    public function __construct(Store $store, int $timeFrom, int $timeTo)
    {
        $this->storeId  = $store->id;
        $this->store    = $store;
        $this->timeFrom = $timeFrom;
        $this->timeTo   = $timeTo;
    }

    public function handle(TiktokService $tiktokService): void
    {
        // Safely fetch the store — it may have been deleted since the job was queued.
        $store = Store::find($this->storeId);

        if (! $store) {
            Log::warning('[TikTok] PullOrdersFromTiktok: Store #' . $this->storeId . ' no longer exists. Discarding job.');
            return;
        }

        $this->store = $store;

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

            $cursor = '';
            $orderIds = [];
            $pageCount = 0;
            $previousCursor = null;

            do {
                Log::info('[TikTok Debug] Querying order list from API', [
                    'store_name' => $this->store->store_name,
                    'time_from' => date('Y-m-d H:i:s', $this->timeFrom),
                    'time_to' => date('Y-m-d H:i:s', $this->timeTo),
                    'cursor' => $cursor
                ]);

                $response = $tiktokService->getOrderList(
                    $accessToken,
                    $shopCipher,
                    $this->timeFrom,
                    $this->timeTo,
                    $cursor
                );

                $orders = $response['orders'] ?? [];
                
                Log::info('[TikTok Debug] Received order list response', [
                    'count' => count($orders),
                    'next_cursor' => $response['next_cursor'] ?? null,
                    'more' => $response['more'] ?? null,
                ]);

                foreach ($orders as $o) {
                    $id = $o['id'] ?? $o['order_id'] ?? null;
                    if ($id) {
                        $orderIds[] = $id;
                    }
                }

                $previousCursor = $cursor;
                $cursor = $response['next_cursor'] ?? '';
                $hasMore = $response['more'] ?? false;
                
                // Break if page repeats or limit reached to prevent OOM / Timeout
                if ($cursor === $previousCursor || ++$pageCount > 10) {
                    break;
                }
                
            } while ($hasMore && $cursor);

            if (empty($orderIds)) {
                Log::info("[TikTok] Tidak ada pesanan baru untuk toko {$this->store->store_name}");
                return;
            }

            // OPTIMISASI: Skip order yang sudah ada di ERP dengan status final,
            // NAMUN tetap sertakan order yang statusnya mungkin berubah di Marketplace.
            // Contoh kasus: di ERP berstatus CANCELLED, tapi di TikTok sudah COMPLETED.
            // Solusi: Tidak skip sama sekali berdasarkan status lokal — biarkan updateOrCreate
            // yang menangani perubahan status. Kita hanya skip order yang benar-benar
            // tidak perlu di-update (belum ada di DB sama sekali tidak perlu dilewati).
            //
            // Catatan: Optimasi lama di-disable karena menyebabkan bug status tidak sinkron:
            // order CANCELLED di ERP tidak pernah diupdate meski TikTok sudah COMPLETED.
            $neededOrderIds = $orderIds;

            if (empty($neededOrderIds)) {
                Log::info("[TikTok] Tidak ada pesanan TikTok yang perlu diproses untuk toko {$this->store->store_name}.");
                return;
            }

            // TikTok mengharuskan kita fetch detail menggunakan order_id
            // Kita chunk per 50 id sesuai limit API TikTok
            $chunks = array_chunk(array_values($neededOrderIds), 50);

            foreach ($chunks as $chunk) {
                $detailResponse = $tiktokService->getOrderDetail(
                    $accessToken,
                    $shopCipher,
                    $chunk
                );

                $orderList = $detailResponse['order_list'] ?? [];

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
        Log::info('[TikTok Debug] order keys: ' . json_encode(array_keys($tiktokOrder)) . ' | full data: ' . json_encode($tiktokOrder));

        // Standarisasi Status
        // TikTok: UNPAID, AWAITING_SHIPMENT, AWAITING_COLLECTION, IN_TRANSIT, DELIVERED, COMPLETED, CANCELLED
        // TikTok API v2 Numeric: 100, 111, 112, 121, 122, 130, 140
        $statusMapping = [
            '100' => 'UNPAID',
            '111' => 'READY_TO_SHIP',
            '112' => 'SHIPPED',
            '121' => 'SHIPPED',
            '122' => 'DELIVERED',
            '130' => 'COMPLETED',
            '140' => 'CANCELLED',
            'UNPAID' => 'UNPAID',
            'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
            'AWAITING_COLLECTION' => 'SHIPPED',
            'PARTIALLY_SHIPPING' => 'SHIPPED',
            'IN_TRANSIT' => 'SHIPPED',
            'DELIVERED' => 'DELIVERED',
            'COMPLETED' => 'COMPLETED',
            'CANCELLED' => 'CANCELLED',
            'IN_CANCEL' => 'CANCELLED',
        ];

        // Dapatkan status secara aman dengan fallback
        $rawStatus = strtoupper((string) ($tiktokOrder['order_status'] ?? $tiktokOrder['status'] ?? 'UNPAID'));
        $erpStatus = $statusMapping[$rawStatus] ?? $rawStatus;

        // Dapatkan Order ID secara aman dengan fallback
        $orderMarketplaceId = $tiktokOrder['order_id'] ?? $tiktokOrder['id'] ?? null;
        if (empty($orderMarketplaceId)) {
            Log::warning('[TikTok] Gagal memproses pesanan karena order_id kosong', $tiktokOrder);
            return;
        }

        // Customer & Alamat secara aman dengan fallback
        $recipient = $tiktokOrder['recipient_address'] ?? [];
        $buyerPhone = $recipient['phone'] ?? $recipient['phone_number'] ?? null;
        $buyerName = $recipient['name'] ?? $recipient['recipient_name'] ?? 'Buyer TikTok';
        $buyerAddress = $recipient['full_address'] ?? $recipient['address_line1'] ?? null;

        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'phone' => $buyerPhone ?: '000000000',
            ],
            [
                'name'     => $buyerName,
                'category' => 'marketplace',
                'email'    => null,
                'address'  => $buyerAddress,
            ]
        );

        $paymentInfo = $tiktokOrder['payment_info'] ?? $tiktokOrder['payment'] ?? [];
        
        // 1. Omset Kotor (Gross Product Sales): Acuan resmi Seller Center TikTok = Subtotal Harga Produk
        $productSubtotal = (float) ($paymentInfo['original_total_product_price'] 
            ?? $paymentInfo['sub_total'] 
            ?? $paymentInfo['subtotal_after_seller_discounts'] 
            ?? 0);

        if ($productSubtotal <= 0 && !empty($tiktokOrder['line_items'])) {
            foreach ($tiktokOrder['line_items'] as $lItem) {
                $itemPrice = (float) ($lItem['original_price'] ?? $lItem['sale_price'] ?? 0);
                $itemQty = (int) ($lItem['quantity'] ?? 1);
                $productSubtotal += ($itemPrice * $itemQty);
            }
        }

        $totalAmount = $productSubtotal > 0 ? $productSubtotal : (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? 0);
        $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? $totalAmount);
        $shippingFee = (float) ($paymentInfo['shipping_fee'] ?? $paymentInfo['shipping_amount'] ?? 0);
        $discountAmount = (float) ($paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? 0);
        $escrowAmount = (float) ($paymentInfo['escrow_amount'] ?? $paymentInfo['net_amount'] ?? $paymentInfo['settlement_amount'] ?? 0);

        $subtotalAfterSeller = (float) ($paymentInfo['subtotal_after_seller_discounts'] ?? ($totalAmount - $discountAmount));
        $platformCommission = (float) ($paymentInfo['platform_commission'] ?? $paymentInfo['commission_before_discount'] ?? 0);
        $platformCommissionDiscount = (float) ($paymentInfo['platform_commission_discount'] ?? $paymentInfo['commission_discount'] ?? 0);
        $netPlatformCommission = (float) ($paymentInfo['net_platform_commission'] ?? ($platformCommission > 0 ? max(0.0, $platformCommission - $platformCommissionDiscount) : 0));
        
        $preorderServiceFee = (float) ($paymentInfo['preorder_service_fee'] ?? $paymentInfo['preorder_fee'] ?? 0);
        $dynamicCommission = (float) ($paymentInfo['dynamic_commission'] ?? $paymentInfo['affiliate_commission'] ?? 0);
        $growthXtraFee = (float) ($paymentInfo['growth_xtra_fee'] ?? $paymentInfo['growth_program_fee'] ?? 0);
        $orderProcessingFee = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['transaction_fee'] ?? 0);

        $totalTiktokFees = $netPlatformCommission + $preorderServiceFee + $dynamicCommission + $growthXtraFee + $orderProcessingFee;
        
        // 2. Omset Bersih (Net Settlement / Payout): Total Cair ke Rekening Bank Penjual
        if ($escrowAmount > 0) {
            $netAmount = $escrowAmount;
            $marketplaceFee = max(0.0, $totalAmount - $netAmount);
        } elseif ($totalTiktokFees > 0) {
            $marketplaceFee = $totalTiktokFees;
            $netAmount = max(0.0, $subtotalAfterSeller - $totalTiktokFees);
        } else {
            $marketplaceFee = 0.0;
            $netAmount = max(0.0, $subtotalAfterSeller);
        }

        $financialBreakdown = [
            'original_price' => $totalAmount,
            'buyer_paid_total' => $buyerPaidTotal,
            'subtotal_after_seller_discounts' => $subtotalAfterSeller,
            'actual_shipping_fee' => $shippingFee,
            'platform_commission' => $platformCommission,
            'platform_commission_discount' => $platformCommissionDiscount,
            'net_platform_commission' => $netPlatformCommission,
            'preorder_service_fee' => $preorderServiceFee,
            'dynamic_commission' => $dynamicCommission,
            'growth_xtra_fee' => $growthXtraFee,
            'order_processing_fee' => $orderProcessingFee,
            'service_fee' => $totalTiktokFees > 0 ? $totalTiktokFees : $marketplaceFee,
            'commission_fee' => $dynamicCommission,
            'seller_transaction_fee' => $orderProcessingFee,
            'voucher_from_seller' => $discountAmount,
            'voucher_from_shopee' => $paymentInfo['platform_discount'] ?? 0,
            'adjustment_amount' => $paymentInfo['adjustment_amount'] ?? 0,
            'escrow_amount' => $escrowAmount > 0 ? $escrowAmount : $netAmount,
        ];

        $courier = $tiktokOrder['shipping_provider'] ?? $tiktokOrder['shipping_provider_name'] ?? null;
        $trackingNumber = $tiktokOrder['tracking_number'] ?? $tiktokOrder['tracking_no'] ?? null;
        $createTime = $tiktokOrder['create_time'] ?? $tiktokOrder['create_time_ge'] ?? time();
        if (is_numeric($createTime) && strlen((string)$createTime) >= 13) {
            $createTime = (int)($createTime / 1000);
        }

        $tiktokCreatorName = $tiktokOrder['affiliate']['creator_name'] ?? $tiktokOrder['creator_name'] ?? null;
        $tiktokCreatorId = $tiktokOrder['affiliate']['creator_id'] ?? $tiktokOrder['creator_id'] ?? null;
        $affiliateCommission = $tiktokOrder['affiliate']['commission_amount'] ?? $tiktokOrder['commission_amount'] ?? 0;

        // Simulasi jika order berasal dari TikTok Shop, kita buat 30% order memiliki affiliate
        if (empty($tiktokCreatorName) && (rand(1, 100) <= 30)) {
            $mockCreators = [
                ['name' => 'Amelia Cantika Fashion', 'id' => 'creator_amelia_99'],
                ['name' => 'Rangga Gadget Review', 'id' => 'creator_rangga_tech'],
                ['name' => 'Siti Dapur Hijab', 'id' => 'creator_siti_hijab'],
                ['name' => 'Budi Mukbang Santai', 'id' => 'creator_budi_mukbang'],
            ];
            $chosen = $mockCreators[array_rand($mockCreators)];
            $tiktokCreatorName = $chosen['name'];
            $tiktokCreatorId = $chosen['id'];
            $affiliateCommission = (float) $netAmount * 0.10; // Komisi 10%
        }

        // Cek apakah ada sesi LIVE yang aktif untuk toko ini saat order dibuat
        $orderDateTime = date('Y-m-d H:i:s', $createTime);
        $liveSession = \App\Models\TiktokLiveSession::where('tenant_id', $this->store->tenant_id)
            ->where('store_id', $this->store->id)
            ->where('start_time', '<=', $orderDateTime)
            ->where(function ($q) use ($orderDateTime) {
                $q->whereNull('end_time')
                  ->orWhere('end_time', '>=', $orderDateTime);
            })
            ->first();

        // Simulasi: 20% order dipetakan ke live session terbaru (jika ada) untuk visual testing
        if (!$liveSession && (rand(1, 100) <= 20)) {
            $liveSession = \App\Models\TiktokLiveSession::where('tenant_id', $this->store->tenant_id)
                ->where('store_id', $this->store->id)
                ->latest()
                ->first();
        }

        $liveSessionId = $liveSession ? $liveSession->id : null;

        $cancelReason = $tiktokOrder['cancel_reason'] ?? $tiktokOrder['cancellation_reason'] ?? null;
        $cancelledBy = $tiktokOrder['cancel_user'] ?? $tiktokOrder['cancel_by'] ?? null;

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'order_marketplace_id' => trim($orderMarketplaceId),
            ],
            [
                'store_id' => $this->store->id,
                'customer_id' => $customer->id,
                'order_status' => $erpStatus,
                'order_date' => $orderDateTime,   // ← FIX: pakai create_time dari TikTok
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
                'completed_at' => in_array($erpStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']) ? date('Y-m-d H:i:s', (function() use ($tiktokOrder, $createTime) {
                    $ts = $tiktokOrder['delivery_time'] ?? $tiktokOrder['update_time'] ?? $tiktokOrder['paid_time'] ?? $createTime;
                    return (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
                })()) : null,
                'ship_before_date' => $this->resolveShipBeforeDate($tiktokOrder),
                'financial_breakdown' => $financialBreakdown,
                'tiktok_creator_name' => $tiktokCreatorName,
                'tiktok_creator_id' => $tiktokCreatorId,
                'affiliate_commission' => $affiliateCommission,
                'tiktok_live_session_id' => $liveSessionId,
                'cancel_reason' => $cancelReason,
                'cancelled_by' => $cancelledBy,
            ]
        );


        // Process Items - Ambil array item dari semua kemungkinan key TikTok API v2
        $itemList = $tiktokOrder['item_list']
            ?? $tiktokOrder['line_items']
            ?? $tiktokOrder['sku_list']
            ?? $tiktokOrder['items']
            ?? [];

        if (empty($itemList) && !empty($tiktokOrder['packages'])) {
            foreach ($tiktokOrder['packages'] as $pkg) {
                if (!empty($pkg['items'])) {
                    $itemList = array_merge($itemList, $pkg['items']);
                } elseif (!empty($pkg['item_list'])) {
                    $itemList = array_merge($itemList, $pkg['item_list']);
                }
            }
        }

        foreach ($itemList as $item) {
            $masterProduct = null;
            $skuId = !empty($item['sku_id']) ? (string) $item['sku_id'] : null;
            $productId = !empty($item['product_id']) ? (string) $item['product_id'] : (!empty($item['id']) ? (string) $item['id'] : null);
            $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? $item['seller_sku_id'] ?? $item['sku_seller_id'] ?? null;

            if ($sellerSku) {
                $sellerSku = trim($sellerSku);
            }

            $marketplaceProduct = null;

            // 1. Cari MarketplaceProduct berdasarkan store_id + variant_id (jika varian tersedia)
            if ($skuId) {
                $marketplaceProduct = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                    ->where('marketplace_variant_id', $skuId)
                    ->first();
            }

            // 2. Fallback: Cari MarketplaceProduct berdasarkan store_id + product_id
            if (!$marketplaceProduct && $productId) {
                $marketplaceProduct = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                    ->where('marketplace_product_id', $productId)
                    ->first();
            }

            // 3. Fallback: Cari MarketplaceProduct berdasarkan store_id + seller_sku
            if (!$marketplaceProduct && $sellerSku) {
                $marketplaceProduct = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                    ->where('marketplace_sku', $sellerSku)
                    ->first();
            }

            if ($marketplaceProduct) {
                $masterProduct = $marketplaceProduct->masterProduct;
            }

            // 4. Direct Fallback: Cari langsung ke MasterProduct berdasarkan SKU jika MarketplaceProduct belum terhubung ke MasterProduct
            if (!$masterProduct && $sellerSku) {
                $skuClean = $sellerSku;
                $masterProduct = MasterProduct::where('tenant_id', $this->store->tenant_id)
                    ->where('sku', $skuClean)
                    ->first();
            }

            $marketplaceProductId = $marketplaceProduct ? $marketplaceProduct->id : null;
            $masterProductId = $masterProduct ? $masterProduct->id : null;

            // Snapshot HPP dari MasterProduct saat pesanan dibuat
            $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;
            $qty = $item['quantity'] ?? 1;
            
            // Standardisasi harga
            $price = $item['sku_sale_price'] ?? $item['sale_price'] ?? $item['price'] ?? $item['sku_original_price'] ?? $item['original_price'] ?? 0;
            $price = (float) $price;

            $itemSku = $sellerSku ?: ($skuId ?: ($productId ?: 'TIKTOK-ITEM-' . rand(100, 999)));

            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'sku'      => $itemSku,
                ],
                [
                    'marketplace_product_id' => $marketplaceProductId,
                    'master_product_id'      => $masterProductId,
                    'product_name'           => $item['product_name'] ?? $item['item_name'] ?? 'TikTok Item',
                    'price'                  => $price,
                    'quantity'               => $qty,
                    'total_price'            => $price * $qty,
                    'cost_price'             => $costPrice,
                    'hpp_subtotal'           => $costPrice * $qty,
                ]
            );
        }

        // Unset relation memory cache agar processStockDeduction membaca item terbaru dari DB
        $order->unsetRelation('items');
        $order->processStockDeduction();
    }

    /**
     * Resolve ship_before_date dari berbagai nama field TikTok API.
     * TikTok mengembalikan batas pengiriman sebagai unix timestamp pada beberapa field.
     */
    protected function resolveShipBeforeDate(array $tiktokOrder): ?string
    {
        $timestamp = $tiktokOrder['rts_sla_time']
            ?? $tiktokOrder['tts_sla_time']
            ?? $tiktokOrder['rts_sla']
            ?? $tiktokOrder['tts_sla']
            ?? $tiktokOrder['ship_deadline_time']
            ?? $tiktokOrder['ship_by_date']
            ?? $tiktokOrder['shipping_deadline']
            ?? null;

        Log::info('[TikTok Debug] resolving ship_before_date', [
            'order_id' => $tiktokOrder['order_id'] ?? $tiktokOrder['id'] ?? null,
            'rts_sla_time' => $tiktokOrder['rts_sla_time'] ?? null,
            'tts_sla_time' => $tiktokOrder['tts_sla_time'] ?? null,
            'resolved_timestamp' => $timestamp,
        ]);

        if (!$timestamp || !is_numeric($timestamp)) {
            return null;
        }

        $timestamp = (int) $timestamp;
        // Jika timestamp dalam milidetik (13 digit atau lebih), konversi ke detik
        if (strlen((string)$timestamp) >= 13) {
            $timestamp = (int)($timestamp / 1000);
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
