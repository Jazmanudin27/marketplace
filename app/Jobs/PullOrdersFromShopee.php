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
    public function handle(ShopeeService $shopeeService): void
    {
        $store = Store::find($this->storeId);

        if (! $store) {
            Log::warning('[Shopee] PullOrdersFromShopee: Store #' . $this->storeId . ' no longer exists. Discarding job.');
            return;
        }

        $this->store = $store;

        if ($this->store->status === 'disconnected' || (empty($this->store->access_token) && empty($this->store->refresh_token))) {
            if (app()->environment('local') || str_contains($this->store->marketplace_store_id, 'DEMO')) {
                $this->seedDemoReturns();
            }
            return;
        }

        try {
            $cursor     = '';
            $hasMore    = true;
            $allOrderSn = [];

            // 1. Fetch Order List (Search by create_time & update_time to capture status changes)
            $timeFields = ['create_time', 'update_time'];
            foreach ($timeFields as $field) {
                $cursor  = '';
                $hasMore = true;
                while ($hasMore) {
                    $response = $this->getValidAccessTokenWithRetry(function($token) use ($shopeeService, $cursor, $field) {
                        return $shopeeService->getOrderList(
                            $token,
                            (int) $this->store->marketplace_store_id,
                            $this->timeFrom,
                            $this->timeTo,
                            $field,
                            $cursor
                        );
                    });

                    if (empty($response['order_list'])) {
                        break;
                    }

                    foreach ($response['order_list'] as $order) {
                        $allOrderSn[] = $order['order_sn'];
                    }

                    $hasMore = $response['more'] ?? false;
                    $cursor  = $response['next_cursor'] ?? '';
                }
            }

            $allOrderSn = array_unique($allOrderSn);

            if (empty($allOrderSn)) {
                Log::info('[Shopee] No orders found in this period.');
                return;
            }

            // OPTIMISASI PINTAR: Hanya skip order jika sudah COMPLETED/CANCELLED DAN SUDAH PUNYA ITEM & ESCROW BREAKDOWN!
            // Jika pesanan belum punya item (0 item) atau belum punya rincian admin escrow, TETAP DI-FETCH!
            $skipOrderSns = Order::whereIn('order_marketplace_id', $allOrderSn)
                ->whereIn('order_status', ['COMPLETED', 'CANCELLED', 'SELESAI', 'FINISHED', 'BATAL'])
                ->has('items')
                ->whereNotNull('financial_breakdown')
                ->pluck('order_marketplace_id')
                ->toArray();

            $neededOrderSns = array_diff($allOrderSn, $skipOrderSns);

            if (empty($neededOrderSns)) {
                Log::info('[Shopee] All orders in this period are already up-to-date with complete items and escrow breakdown.');
                return;
            }

            // 2. Fetch Order Details (Max 50 per request)
            $chunks = array_chunk(array_values($neededOrderSns), 50);
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
    private static array $mpCache = [];
    private static array $masterCache = [];

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

        // Ambil data Escrow / Income resmi Shopee jika order sudah COMPLETED
        $financialBreakdown = null;
        if ($shopeeOrder['order_status'] === 'COMPLETED') {
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
                    $financialBreakdown = $escrowResponse['order_income'];
                    $shopeeOrder['escrow_amount'] = $financialBreakdown['escrow_amount'] ?? $shopeeOrder['escrow_amount'] ?? 0;
                    $shopeeOrder['seller_discount_amount'] = $financialBreakdown['seller_discount'] ?? $shopeeOrder['seller_discount_amount'] ?? 0;
                    $actualShipping = $financialBreakdown['actual_shipping_fee'] ?? 0;
                    $shopeeOrder['actual_shipping_fee'] = $actualShipping;
                }
            } catch (\Exception $e) {
                Log::warning('[Shopee] Failed to fetch escrow detail for ' . $shopeeOrder['order_sn'] . ': ' . $e->getMessage());
            }
        }

        $voucherCode = $shopeeOrder['voucher_info']['voucher_code'] ?? $shopeeOrder['voucher_code'] ?? null;
        $shopeeUtmKeyword = $shopeeOrder['utm_keyword'] ?? $shopeeOrder['utm_source'] ?? null;

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

        if ($escrowAmount > 0) {
            $netAmount = $escrowAmount;
            $sellerFee = (float) ($financialBreakdown['seller_coin_cash_back'] ?? 0)
                       + (float) ($financialBreakdown['commission_fee'] ?? 0)
                       + (float) ($financialBreakdown['service_fee'] ?? 0)
                       + (float) ($financialBreakdown['seller_transaction_fee'] ?? 0)
                       + (float) ($financialBreakdown['seller_order_processing_fee'] ?? 0)
                       + (float) ($financialBreakdown['ams_commission_fee'] ?? 0);
            $marketplaceFee = $sellerFee > 0 ? $sellerFee : max(0.0, $totalAmount - $escrowAmount);
        } else {
            $shopeeEstimatedRatio = 0.095;
            $marketplaceFee = round($totalAmount * $shopeeEstimatedRatio);
            $netAmount = max(0.0, $totalAmount - $sellerDiscount - $marketplaceFee);
        }

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'order_marketplace_id' => trim($shopeeOrder['order_sn']),
            ],
            [
                'store_id' => $this->store->id,
                'customer_id' => $customer->id,
                'order_status' => $shopeeOrder['order_status'],
                'buyer_name' => $shopeeOrder['buyer_username'] ?? 'Buyer',
                'buyer_phone' => $shopeeOrder['recipient_address']['phone'] ?? null,
                'shipping_address' => $shopeeOrder['recipient_address']['full_address'] ?? null,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $sellerDiscount,
                'net_amount' => $netAmount,
                'marketplace_fee' => $marketplaceFee,
                'courier' => $shopeeOrder['shipping_carrier'] ?? null,
                'tracking_number' => current($shopeeOrder['package_list'] ?? [])['tracking_number'] ?? current($shopeeOrder['package_list'] ?? [])['package_number'] ?? null,
                'order_date' => date('Y-m-d H:i:s', $shopeeOrder['create_time'] ?? time()),
                'completed_at' => in_array($shopeeOrder['order_status'], ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']) ? date('Y-m-d H:i:s', $shopeeOrder['update_time'] ?? ($shopeeOrder['create_time'] ?? time())) : null,
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

        // Hapus item ganda/kosong lama hanya jika item dari API tersedia
        if (!empty($shopeeOrder['item_list'])) {
            OrderItem::where('order_id', $order->id)->delete();

            $insertRows = [];
            foreach ($shopeeOrder['item_list'] as $item) {
                $modelId = $item['model_id'] ?? null;
                $query = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                    ->where('marketplace_product_id', (string) $item['item_id']);
                if ($modelId) {
                    $query->where('marketplace_variant_id', (string) $modelId);
                }
                $marketplaceProduct = $query->first();

                if (!$marketplaceProduct && $modelId) {
                    $marketplaceProduct = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                        ->where('marketplace_product_id', (string) $item['item_id'])
                        ->first();
                }

                $price = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                $qty = $item['model_quantity_purchased'] ?? 1;

                $masterProduct = $marketplaceProduct ? $marketplaceProduct->masterProduct : null;
                $itemSku = $item['model_sku'] ?: ($item['item_sku'] ?? null);

                if (!$masterProduct && $itemSku) {
                    $masterProduct = \App\Models\MasterProduct::where('tenant_id', $this->store->tenant_id)
                        ->where('sku', trim($itemSku))
                        ->first();
                }

                $masterProductId = $masterProduct ? $masterProduct->id : null;
                $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;

                $insertRows[] = [
                    'order_id'               => $order->id,
                    'sku'                    => $itemSku,
                    'marketplace_product_id' => $marketplaceProduct ? $marketplaceProduct->id : null,
                    'master_product_id'      => $masterProductId,
                    'product_name'           => mb_substr($item['item_name'] . (!empty($item['model_name']) ? ' - ' . $item['model_name'] : ''), 0, 250),
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

    protected function resolveShipBeforeDate(array $shopeeOrder): ?string
    {
        $timestamp = $shopeeOrder['ship_by_date']
            ?? $shopeeOrder['ship_before_date']
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

    private function getValidAccessTokenWithRetry(callable $apiCall)
    {
        try {
            return $apiCall($this->store->getValidAccessToken());
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'invalid_access_token') || str_contains($e->getMessage(), 'invalid_acceess_token')) {
                Log::info("[Shopee] Access token invalid in PullOrdersFromShopee for store #{$this->storeId}. Attempting force refresh...");
                
                if (empty($this->store->refresh_token)) {
                    Log::warning("[Shopee] Refresh token is empty for store '{$this->store->store_name}' (ID #{$this->storeId}). Cannot auto-refresh. Please re-authenticate this store in Settings.");
                    throw $e;
                }

                $accessToken = $this->store->getValidAccessToken(true);
                return $apiCall($accessToken);
            }
            throw $e;
        }
    }

    private function seedDemoReturns(): void
    {
        // Demo mode fallback
    }
}
