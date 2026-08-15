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

    /**
     * Create a new job instance.
     */
    public function __construct(Store|int $store, ?int $timeFrom = null, ?int $timeTo = null, bool $skipStockDeduction = false)
    {
        $this->storeId = $store instanceof Store ? $store->id : $store;
        $this->timeFrom = $timeFrom ?? now()->subDays(15)->timestamp;
        $this->timeTo = $timeTo ?? now()->timestamp;
        $this->skipStockDeduction = $skipStockDeduction;
    }

    public Store $store;

    /**
     * Execute the job.
     */
    public function handle(TiktokService $tiktokService): void
    {
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
                $response = $tiktokService->getOrderList(
                    $accessToken,
                    $shopCipher,
                    $this->timeFrom,
                    $this->timeTo,
                    $cursor
                );

                $orders = $response['orders'] ?? [];

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

            // OPTIMISASI PINTAR TIKTOK: Hanya skip jika order sudah COMPLETED/CANCELLED DAN SUDAH MEMILIKI ITEM & ESCROW BREAKDOWN
            $skipOrderIds = Order::whereIn('order_marketplace_id', $orderIds)
                ->whereIn('order_status', ['COMPLETED', 'CANCELLED', 'SELESAI', 'FINISHED', 'BATAL'])
                ->has('items')
                ->whereNotNull('financial_breakdown')
                ->pluck('order_marketplace_id')
                ->toArray();

            $neededOrderIds = array_diff($orderIds, $skipOrderIds);

            if (empty($neededOrderIds)) {
                Log::info("[TikTok] Tidak ada pesanan TikTok yang perlu diproses untuk toko {$this->store->store_name}.");
                return;
            }

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

        // Buyer Information
        $buyerInfo = $tiktokOrder['buyer_email'] ?? $tiktokOrder['recipient_address']['name'] ?? 'TikTok Buyer';
        $buyerName = is_array($buyerInfo) ? ($buyerInfo['name'] ?? 'TikTok Buyer') : $buyerInfo;
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

        $orderDateTime = date('Y-m-d H:i:s', (function() use ($tiktokOrder) {
            $ts = $tiktokOrder['create_time'] ?? $tiktokOrder['create_time_ge'] ?? time();
            return (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
        })());

        $liveSessionId = null;
        if (!empty($tiktokOrder['live_session_id'])) {
            $liveSessionId = $tiktokOrder['live_session_id'];
        }

        $paymentInfo = $tiktokOrder['payment_info'] ?? $tiktokOrder['payment'] ?? [];
        $totalAmount = (float) ($paymentInfo['original_shipping_fee'] ?? 0) 
            + (float) ($paymentInfo['subtotal'] ?? $paymentInfo['product_total'] ?? 0)
            + (float) ($paymentInfo['total_amount'] ?? $tiktokOrder['total_amount'] ?? 0);
        
        if ($totalAmount <= 0) {
            $totalAmount = (float) ($paymentInfo['total_amount'] ?? $tiktokOrder['total_amount'] ?? 0);
        }

        $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $tiktokOrder['total_amount'] ?? 0);
        $subtotalAfterSeller = (float) ($paymentInfo['subtotal_after_seller_discounts'] ?? $paymentInfo['subtotal'] ?? $totalAmount);
        $shippingFee = (float) ($paymentInfo['shipping_fee'] ?? $paymentInfo['actual_shipping_fee'] ?? 0);
        $discountAmount = (float) ($paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? 0);

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

        $cancelReason = $tiktokOrder['cancel_reason'] ?? $tiktokOrder['cancel_user_reason'] ?? null;
        $cancelledBy = $tiktokOrder['cancel_by'] ?? null;

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'order_marketplace_id' => (string)($tiktokOrder['id'] ?? $tiktokOrder['order_id']),
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

        if (!empty($itemList)) {
            OrderItem::where('order_id', $order->id)->delete();

            $insertRows = [];
            foreach ($itemList as $item) {
                $productId = (string)($item['product_id'] ?? '');
                $skuId     = (string)($item['sku_id'] ?? '');
                $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;

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

                $marketplaceProductId = $marketplaceProduct ? $marketplaceProduct->id : null;
                $masterProductId = $masterProduct ? $masterProduct->id : null;

                $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['sku_display_price'] ?? $item['sku_original_price'] ?? $item['price'] ?? 0);

                $pName = $item['product_name'] ?? $item['item_name'] ?? 'Produk TikTok';
                $vName = $item['sku_name'] ?? $item['variant_name'] ?? '';

                $insertRows[] = [
                    'order_id'               => $order->id,
                    'sku'                    => $sellerSku,
                    'marketplace_product_id' => $marketplaceProductId,
                    'master_product_id'      => $masterProductId,
                    'product_name'           => mb_substr($pName . ($vName ? ' - ' . $vName : ''), 0, 250),
                    'price'                  => $price,
                    'quantity'               => $qty,
                    'total_price'            => $price * $qty,
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
        $timestamp = $tiktokOrder['ship_by_date']
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
