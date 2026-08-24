<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Services\ShopeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullOrdersFromShopee implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $storeId;
    public ?int $timeFrom;
    public ?int $timeTo;
    public bool $skipStockDeduction;
    public ?string $orderSn;

    /**
     * Create a new job instance.
     */
    public function __construct(Store|int $store, ?int $timeFrom = null, ?int $timeTo = null, bool $skipStockDeduction = false, ?string $orderSn = null)
    {
        $this->storeId = $store instanceof Store ? $store->id : $store;
        $this->timeFrom = $timeFrom ?? now()->subDays(15)->timestamp;
        $this->timeTo = $timeTo ?? now()->timestamp;
        $this->skipStockDeduction = $skipStockDeduction;
        $this->orderSn = $orderSn;
    }

    public Store $store;

    /**
     * Get a valid access token, refreshing if necessary and updating $this->store.
     */
    private function getValidAccessTokenWithRetry(callable $apiCallback)
    {
        try {
            $token = $this->store->getValidAccessToken();
            return $apiCallback($token);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'invalid_access_token') || str_contains($e->getMessage(), 'error_auth')) {
                Log::warning('[Shopee] Access token expired during API call. Refreshing...');
                $shopeeService = app(ShopeeService::class);
                $tokens = $shopeeService->refreshAccessToken(
                    $this->store->refresh_token,
                    (int) $this->store->marketplace_store_id
                );
                $this->store->update([
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'token_expires_at' => now()->addSeconds($tokens['expire_in'] ?? 14400),
                ]);
                return $apiCallback($tokens['access_token']);
            }
            throw $e;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(ShopeeService $shopeeService): void
    {
        $store = Store::find($this->storeId);

        if (! $store) {
            Log::warning('[Shopee] PullOrdersFromShopee: Store #' . $this->storeId . ' no longer exists. Discarding job.');
            return;
        }

        $this->store = $store;

        if ($this->store->status === 'disconnected' || (empty($this->store->access_token) && empty($this->store->refresh_token))) {
            Log::warning("[Shopee] Toko {$this->store->store_name} tidak terhubung.");
            return;
        }

        try {
            // 🎯 JIKA ORDER_SN SPESIFIK DIISI (KILAT WEBHOOK)
            if ($this->orderSn) {
                Log::info("[Shopee] Webhook Trigger: Menyinkronkan order detail tunggal: {$this->orderSn}");
                $detailsResponse = $this->getValidAccessTokenWithRetry(function($token) use ($shopeeService) {
                    return $shopeeService->getOrderDetail(
                        $token,
                        (int) $this->store->marketplace_store_id,
                        [$this->orderSn]
                    );
                });

                if (!empty($detailsResponse['order_list'])) {
                    foreach ($detailsResponse['order_list'] as $shopeeOrder) {
                        $this->saveOrder($shopeeOrder);
                    }
                }
                return;
            }

            $allOrderSn = [];
            $statusesToScan = [null, 'PROCESSED', 'SHIPPED', 'COMPLETED']; // Scan semua status utama Shopee agar tidak ada order kilat terlewat

            foreach ($statusesToScan as $scanStatus) {
                $cursor = '';
                $pageCount = 0;

                do {
                    $response = $this->getValidAccessTokenWithRetry(function($token) use ($shopeeService, $cursor, $scanStatus) {
                        return $shopeeService->getOrderList(
                            $token,
                            (int) $this->store->marketplace_store_id,
                            $this->timeFrom,
                            $this->timeTo,
                            'create_time',
                            $cursor,
                            50,
                            $scanStatus
                        );
                    });

                    $orderList = $response['order_list'] ?? [];
                    foreach ($orderList as $o) {
                        if (!empty($o['order_sn'])) {
                            $allOrderSn[] = $o['order_sn'];
                        }
                    }

                    $cursor = $response['next_cursor'] ?? '';
                    $hasMore = $response['more'] ?? false;
                    $pageCount++;

                    if ($pageCount > 10) {
                        break;
                    }

                } while ($hasMore && !empty($cursor));
            }

            $allOrderSn = array_unique($allOrderSn);

            if (empty($allOrderSn)) {
                Log::info('[Shopee] No orders found in range for store ' . $this->store->store_name);
                return;
            }

            // 🚀 SINKRONISASI STATUS 100% AKURAT: Ambil detail seluruh order tanpa melewatinya agar status di ERP & API 100% SAMA
            $chunks = array_chunk(array_values($allOrderSn), 50);

            foreach ($chunks as $chunk) {
                $detailsResponse = $this->getValidAccessTokenWithRetry(function($token) use ($shopeeService, $chunk) {
                    return $shopeeService->getOrderDetail(
                        $token,
                        (int) $this->store->marketplace_store_id,
                        $chunk
                    );
                });

                if (empty($detailsResponse['order_list'])) {
                    continue;
                }

                foreach ($detailsResponse['order_list'] as $shopeeOrder) {
                    $this->saveOrder($shopeeOrder);
                }
            }

            Log::info('[Shopee] Successfully pulled ' . count($allOrderSn) . ' orders.');

        } catch (\Exception $e) {
            Log::error('[Shopee] Failed to pull orders: ' . $e->getMessage());
            throw $e;
        }
    }

    private static array $customerCache = [];

    private function saveOrder(array $shopeeOrder)
    {
        $buyerPhone = $shopeeOrder['recipient_address']['phone'] ?? null;
        $buyerName = $shopeeOrder['buyer_username'] ?? 'Buyer';

        $cacheKey = $this->store->tenant_id . '_' . ($buyerPhone ?: $buyerName);
        if (isset(self::$customerCache[$cacheKey])) {
            $customer = self::$customerCache[$cacheKey];
        } else {
            $customer = Customer::firstOrCreate(
                [
                    'tenant_id' => $this->store->tenant_id,
                    'phone' => $buyerPhone ?: '0000000000',
                ],
                [
                    'name' => $buyerName,
                    'address' => $shopeeOrder['recipient_address']['full_address'] ?? null,
                ]
            );
            self::$customerCache[$cacheKey] = $customer;
        }

        // STANDARISASI STATUS RESMI SHOPEE
        $statusRaw = strtoupper((string)($shopeeOrder['order_status'] ?? 'UNPAID'));
        $shopeeStatusMap = [
            'UNPAID' => 'UNPAID',
            'READY_TO_SHIP' => 'READY_TO_SHIP',
            'PROCESSED' => 'READY_TO_SHIP',
            'RETRY_SHIP' => 'READY_TO_SHIP',
            'TO_RETRY_LOGISTICS' => 'READY_TO_SHIP',
            'SHIPPED' => 'SHIPPED',
            'TO_CONFIRM_RECEIVE' => 'SHIPPED',
            'DELIVERED' => 'DELIVERED',
            'COMPLETED' => 'COMPLETED',
            'CANCELLED' => 'CANCELLED',
            'IN_CANCEL' => 'CANCELLED',
        ];
        $erpStatus = $shopeeStatusMap[$statusRaw] ?? $statusRaw;

        // 🚀 BIAYA ADMIN PRESISI: Ambil data Escrow / Income resmi Shopee untuk SEMUA pesanan yang bukan CANCELLED!
        $financialBreakdown = null;
        if ($erpStatus !== 'CANCELLED') {
            try {
                $shopeeService = app(\App\Services\ShopeeService::class);
                $escrowResponse = $this->getValidAccessTokenWithRetry(function($token) use ($shopeeService, $shopeeOrder) {
                    return $shopeeService->getEscrowDetail(
                        $token,
                        (int) $this->store->marketplace_store_id,
                        $shopeeOrder['order_sn']
                    );
                });
                
                if (!empty($escrowResponse['order_income'])) {
                    $income = $escrowResponse['order_income'];
                    $financialBreakdown = array_merge([
                        'seller_discount' => (float) ($income['seller_discount'] ?? $shopeeOrder['seller_discount_amount'] ?? 0),
                        'actual_shipping_fee' => (float) ($income['actual_shipping_fee'] ?? 0),
                        'shopee_shipping_rebate' => (float) ($income['shopee_shipping_rebate'] ?? 0),
                        'voucher_from_seller' => (float) ($income['voucher_from_seller'] ?? 0),
                        'voucher_from_shopee' => (float) ($income['voucher_from_shopee'] ?? 0),
                        'withholding_tax' => (float) ($income['withholding_tax'] ?? $income['escrow_tax'] ?? 0),
                        'seller_return_refund' => (float) ($income['seller_return_refund'] ?? 0),
                        'total_adjustment_amount' => (float) ($income['total_adjustment_amount'] ?? 0),
                    ], $income);

                    $shopeeOrder['escrow_amount'] = $income['escrow_amount'] ?? $shopeeOrder['escrow_amount'] ?? 0;
                    $shopeeOrder['seller_discount_amount'] = $income['seller_discount'] ?? $shopeeOrder['seller_discount_amount'] ?? 0;
                    if (($income['actual_shipping_fee'] ?? 0) > 0) {
                        $shopeeOrder['actual_shipping_fee'] = $income['actual_shipping_fee'];
                    }
                }
            } catch (\Throwable $e) {
                // Ignore if escrow detail is not generated yet by Shopee API for very new UNPAID orders
            }
        }

        $voucherCode = null;
        $shopeeUtmKeyword = null;
        if (!empty($shopeeOrder['voucher_code'])) {
            $voucherCode = $shopeeOrder['voucher_code'];
        }

        $createTime = $shopeeOrder['create_time'] ?? time();
        $orderDateTime = date('Y-m-d H:i:s', $createTime);
        $liveSession = \App\Models\ShopeeLiveSession::where('tenant_id', $this->store->tenant_id)
            ->where('store_id', $this->store->id)
            ->where('start_time', '<=', $orderDateTime)
            ->where(function ($q) use ($orderDateTime) {
                $q->whereNull('end_time')
                  ->orWhere('end_time', '>=', $orderDateTime);
            })
            ->first();

        $liveSessionId = $liveSession ? $liveSession->id : null;

        $dropshipperName = isset($shopeeOrder['dropshipper']) ? trim($shopeeOrder['dropshipper']) : null;
        $dropshipperPhone = isset($shopeeOrder['dropshipper_phone']) ? trim($shopeeOrder['dropshipper_phone']) : null;
        $isDropship = !empty($dropshipperName);

        $cancelReason = $shopeeOrder['cancel_reason'] ?? $shopeeOrder['buyer_cancel_reason'] ?? null;
        $cancelledBy = $shopeeOrder['cancel_by'] ?? null;

        $productSubtotal = 0.0;
        if (!empty($shopeeOrder['item_list'])) {
            foreach ($shopeeOrder['item_list'] as $item) {
                $itemPrice = (float) ($item['model_discounted_price'] ?? $item['model_original_price'] ?? 0);
                $itemQty = (int) ($item['model_quantity_purchased'] ?? 1);
                $productSubtotal += ($itemPrice * $itemQty);
            }
        }

        $totalAmount = $productSubtotal > 0 
            ? $productSubtotal 
            : (float) ($financialBreakdown['cost_of_goods_sold'] ?? $financialBreakdown['order_selling_price'] ?? $shopeeOrder['total_amount'] ?? 0);

        $shippingFee = (float) ($shopeeOrder['actual_shipping_fee'] ?? $shopeeOrder['estimated_shipping_fee'] ?? 0);
        $sellerDiscount = (float) ($shopeeOrder['seller_discount_amount'] ?? $financialBreakdown['seller_discount'] ?? 0);
        $escrowAmount = (float) ($shopeeOrder['escrow_amount'] ?? $financialBreakdown['escrow_amount'] ?? 0);

        // HITUNG BIAYA ADMIN RESMI SHOPEE SECARA PRESISI
        if ($escrowAmount > 0 || !empty($financialBreakdown)) {
            $sellerFee = (float) ($financialBreakdown['seller_coin_cash_back'] ?? 0)
                       + (float) ($financialBreakdown['commission_fee'] ?? 0)
                       + (float) ($financialBreakdown['service_fee'] ?? 0)
                       + (float) ($financialBreakdown['seller_transaction_fee'] ?? 0)
                       + (float) ($financialBreakdown['seller_order_processing_fee'] ?? 0)
                       + (float) ($financialBreakdown['ams_commission_fee'] ?? 0);

            if ($sellerFee > 0) {
                $marketplaceFee = $sellerFee;
                $netAmount = $escrowAmount > 0 ? $escrowAmount : max(0.0, $totalAmount - $sellerFee);
            } else {
                $netAmount = $escrowAmount > 0 ? $escrowAmount : $totalAmount;
                $marketplaceFee = max(0.0, $totalAmount - $netAmount);
            }
        } else {
            $shopeeEstimatedRatio = 0.095;
            $marketplaceFee = round($totalAmount * $shopeeEstimatedRatio);
            $netAmount = max(0.0, $totalAmount - $marketplaceFee);
        }

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'order_marketplace_id' => trim($shopeeOrder['order_sn']),
            ],
            [
                'store_id' => $this->store->id,
                'customer_id' => $customer->id,
                'order_status' => $erpStatus,
                'buyer_name' => $shopeeOrder['buyer_username'] ?? 'Buyer',
                'buyer_phone' => $shopeeOrder['recipient_address']['phone'] ?? null,
                'shipping_address' => $shopeeOrder['recipient_address']['full_address'] ?? null,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $sellerDiscount,
                'net_amount' => $netAmount,
                'marketplace_fee' => $marketplaceFee,
                'courier' => $shopeeOrder['shipping_carrier'] ?? null,
                'tracking_number' => (function() use ($shopeeOrder) {
                    $tracking = (!empty($shopeeOrder['package_list']) && !empty(current($shopeeOrder['package_list'])['tracking_number'])) ? current($shopeeOrder['package_list'])['tracking_number'] : null;
                    if ($tracking && (str_starts_with($tracking, 'PSG') || str_starts_with($tracking, 'psg'))) {
                        return null;
                    }
                    return $tracking;
                })(),
                'order_date' => date('Y-m-d H:i:s', $shopeeOrder['create_time'] ?? time()),
                'completed_at' => in_array($erpStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']) ? date('Y-m-d H:i:s', $shopeeOrder['update_time'] ?? ($shopeeOrder['create_time'] ?? time())) : null,
                'ship_before_date' => $this->resolveShipBeforeDate($shopeeOrder),
                'financial_breakdown' => $financialBreakdown,
                'voucher_code' => $voucherCode,
                'shopee_utm_keyword' => $shopeeUtmKeyword,
                'shopee_live_session_id' => $liveSessionId,
                'is_dropship' => $isDropship,
                'dropshipper_name' => $dropshipperName,
                'dropshipper_phone' => $dropshipperPhone,
                'cancel_reason' => $cancelReason,
                'cancelled_by' => $cancelledBy,
            ]
        );

        if (!empty($shopeeOrder['item_list'])) {
            OrderItem::where('order_id', $order->id)->delete();

            $insertRows = [];
            foreach ($shopeeOrder['item_list'] as $item) {
                $modelId = $item['model_id'] ?? null;

                $mp = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                    ->where('marketplace_product_id', (string) $item['item_id'])
                    ->when($modelId, fn($q) => $q->where('marketplace_variant_id', (string) $modelId))
                    ->first();

                $price = (float) ($item['model_discounted_price'] ?? $item['model_original_price'] ?? 0);
                $qty = (int) ($item['model_quantity_purchased'] ?? 1);
                $itemSku = $item['model_sku'] ?: ($item['item_sku'] ?? null);

                $masterProduct = $mp ? $mp->masterProduct : null;
                if (!$masterProduct && $itemSku) {
                    $masterProduct = \App\Models\MasterProduct::where('tenant_id', $this->store->tenant_id)
                        ->where('sku', trim($itemSku))
                        ->first();
                }

                $insertRows[] = [
                    'order_id' => $order->id,
                    'marketplace_product_id' => $mp ? $mp->id : null,
                    'master_product_id' => $masterProduct ? $masterProduct->id : null,
                    'product_name' => mb_substr($item['item_name'] . ($item['model_name'] ? " ({$item['model_name']})" : ''), 0, 250),
                    'sku' => $itemSku,
                    'price' => $price,
                    'total_price' => $price * $qty,
                    'cost_price' => $masterProduct ? (float) $masterProduct->cost_price : 0,
                    'hpp_subtotal' => ($masterProduct ? (float) $masterProduct->cost_price : 0) * $qty,
                    'quantity' => $qty,
                    'created_at' => now(),
                    'updated_at' => now(),
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

    private function resolveShipBeforeDate(array $shopeeOrder): ?string
    {
        $timestamp = $shopeeOrder['ship_by_date'] ?? null;
        if (! $timestamp) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
