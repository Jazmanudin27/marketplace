<?php

namespace App\Jobs;

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

    protected $store;
    protected $timeFrom;
    protected $timeTo;

    public function __construct(Store $store, int $timeFrom, int $timeTo)
    {
        $this->store = $store;
        $this->timeFrom = $timeFrom;
        $this->timeTo = $timeTo;
    }

    public function handle(ShopeeService $shopeeService): void
    {
        Log::info('[Shopee] Starting PullOrdersFromShopee', [
            'store_id' => $this->store->id,
            'time_from' => $this->timeFrom,
            'time_to' => $this->timeTo,
        ]);

        try {
            $cursor = '';
            $hasMore = true;
            $allOrderSn = [];

            // 1. Fetch Order List
            while ($hasMore) {
                $response = $shopeeService->getOrderList(
                    $this->store->getValidAccessToken(),
                    (int) $this->store->marketplace_store_id,
                    $this->timeFrom,
                    $this->timeTo,
                    'create_time',
                    $cursor
                );

                if (empty($response['order_list'])) {
                    break;
                }

                foreach ($response['order_list'] as $order) {
                    $allOrderSn[] = $order['order_sn'];
                }

                $hasMore = $response['more'] ?? false;
                $cursor = $response['next_cursor'] ?? '';
            }

            if (empty($allOrderSn)) {
                Log::info('[Shopee] No orders found in this period.');
                return;
            }

            // 2. Fetch Order Details (Max 50 per request)
            $chunks = array_chunk($allOrderSn, 50);
            foreach ($chunks as $chunk) {
                $detailsResponse = $shopeeService->getOrderDetail(
                    $this->store->getValidAccessToken(),
                    (int) $this->store->marketplace_store_id,
                    $chunk
                );

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

    private function saveOrder(array $shopeeOrder)
    {
        $username = $shopeeOrder['buyer_username'] ?? 'Buyer';
        $customer = \App\Models\Customer::firstOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'marketplace_username' => $username,
            ],
            [
                'name' => $username,
                'phone' => $shopeeOrder['recipient_address']['phone'] ?? null,
                'address' => $shopeeOrder['recipient_address']['full_address'] ?? null,
            ]
        );

        // Fetch Escrow Detail if order is COMPLETED
        $financialBreakdown = null;
        if ($shopeeOrder['order_status'] === 'COMPLETED') {
            try {
                $shopeeService = app(\App\Services\ShopeeService::class);
                $escrowResponse = $shopeeService->getEscrowDetail(
                    $this->store->getValidAccessToken(),
                    (int) $this->store->marketplace_store_id,
                    $shopeeOrder['order_sn']
                );
                
                if (!empty($escrowResponse['order_income'])) {
                    $financialBreakdown = $escrowResponse['order_income'];
                    // We can refine net_amount, shipping_fee, marketplace_fee based on escrow
                    $shopeeOrder['escrow_amount'] = $financialBreakdown['escrow_amount'] ?? $shopeeOrder['escrow_amount'] ?? 0;
                    $shopeeOrder['seller_discount_amount'] = $financialBreakdown['seller_discount'] ?? $shopeeOrder['seller_discount_amount'] ?? 0;
                    $actualShipping = $financialBreakdown['actual_shipping_fee'] ?? 0;
                    $shopeeOrder['actual_shipping_fee'] = $actualShipping;
                }
            } catch (\Exception $e) {
                Log::warning('[Shopee] Failed to fetch escrow detail for ' . $shopeeOrder['order_sn'] . ': ' . $e->getMessage());
            }
        }

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'store_id' => $this->store->id,
                'order_marketplace_id' => $shopeeOrder['order_sn'],
            ],
            [
                'customer_id' => $customer->id,
                'order_status' => $shopeeOrder['order_status'],
                'buyer_name' => $shopeeOrder['buyer_username'] ?? 'Buyer', // fallback if username not provided
                'buyer_phone' => $shopeeOrder['recipient_address']['phone'] ?? null,
                'shipping_address' => $shopeeOrder['recipient_address']['full_address'] ?? null,
                'total_amount' => $shopeeOrder['total_amount'] ?? 0,
                'shipping_fee' => $shopeeOrder['actual_shipping_fee'] ?? $shopeeOrder['estimated_shipping_fee'] ?? 0,
                'discount_amount' => $shopeeOrder['seller_discount_amount'] ?? 0,
                'net_amount' => $shopeeOrder['escrow_amount'] ?? 0,
                'marketplace_fee' => ($shopeeOrder['total_amount'] ?? 0) - ($shopeeOrder['escrow_amount'] ?? 0) - ($shopeeOrder['actual_shipping_fee'] ?? $shopeeOrder['estimated_shipping_fee'] ?? 0), // Rough estimation or exact
                'courier' => $shopeeOrder['shipping_carrier'] ?? null,
                'tracking_number' => current($shopeeOrder['package_list'] ?? [])['tracking_number'] ?? current($shopeeOrder['package_list'] ?? [])['package_number'] ?? null,
                'order_date' => date('Y-m-d H:i:s', $shopeeOrder['create_time'] ?? time()),
                'financial_breakdown' => $financialBreakdown,
            ]
        );

        // Save Items
        if (!empty($shopeeOrder['item_list'])) {
            foreach ($shopeeOrder['item_list'] as $item) {
                $marketplaceProduct = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                    ->where('marketplace_product_id', (string) $item['item_id'])
                    ->first();

                $price = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                $qty = $item['model_quantity_purchased'] ?? 1;

                // Snapshot HPP dari MasterProduct saat pesanan dibuat
                $masterProduct = $marketplaceProduct ? $marketplaceProduct->masterProduct : null;
                $masterProductId = $masterProduct ? $masterProduct->id : null;
                $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;

                OrderItem::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'sku' => $item['model_sku'] ?: ($item['item_sku'] ?? null),
                    ],
                    [
                        'marketplace_product_id' => $marketplaceProduct ? $marketplaceProduct->id : null,
                        'master_product_id'      => $masterProductId,
                        'product_name'           => $item['item_name'] . (!empty($item['model_name']) ? ' - ' . $item['model_name'] : ''),
                        'price'                  => $price,
                        'quantity'               => $qty,
                        'total_price'            => $price * $qty,
                        'cost_price'             => $costPrice,
                        'hpp_subtotal'           => $costPrice * $qty,
                    ]
                );
            }
        }


        // Process stock deduction or return
        $order->processStockDeduction();
    }
}
