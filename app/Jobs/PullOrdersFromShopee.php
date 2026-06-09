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
                    $this->store->access_token,
                    $this->store->marketplace_shop_id,
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
                    $this->store->access_token,
                    $this->store->marketplace_shop_id,
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
        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'store_id' => $this->store->id,
                'order_marketplace_id' => $shopeeOrder['order_sn'],
            ],
            [
                'order_status' => $shopeeOrder['order_status'],
                'buyer_name' => $shopeeOrder['buyer_username'] ?? 'Buyer', // fallback if username not provided
                'buyer_phone' => $shopeeOrder['recipient_address']['phone'] ?? null,
                'shipping_address' => $shopeeOrder['recipient_address']['full_address'] ?? null,
                'total_amount' => $shopeeOrder['total_amount'] ?? 0,
                'shipping_fee' => $shopeeOrder['actual_shipping_fee'] ?? $shopeeOrder['estimated_shipping_fee'] ?? 0,
                'courier' => $shopeeOrder['shipping_carrier'] ?? null,
                'tracking_number' => current($shopeeOrder['package_list'] ?? [])['tracking_number'] ?? null,
                'order_date' => date('Y-m-d H:i:s', $shopeeOrder['create_time'] ?? time()),
            ]
        );

        // Save Items
        if (!empty($shopeeOrder['item_list'])) {
            foreach ($shopeeOrder['item_list'] as $item) {
                OrderItem::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'marketplace_item_id' => $item['item_id'],
                        'marketplace_model_id' => $item['model_id'] ?? 0,
                    ],
                    [
                        'product_name' => $item['item_name'],
                        'variation_name' => $item['model_name'] ?? null,
                        'quantity' => $item['model_quantity_purchased'] ?? 1,
                        'price' => $item['model_discounted_price'] > 0 ? $item['model_discounted_price'] : $item['model_original_price'],
                        'subtotal' => ($item['model_discounted_price'] > 0 ? $item['model_discounted_price'] : $item['model_original_price']) * ($item['model_quantity_purchased'] ?? 1),
                    ]
                );
            }
        }
    }
}
