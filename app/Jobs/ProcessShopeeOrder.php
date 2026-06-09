<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ShopeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessShopeeOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(ShopeeService $shopeeService): void
    {
        Log::info('[Shopee] Starting ProcessShopeeOrder', [
            'order_id' => $this->order->id,
            'order_sn' => $this->order->order_marketplace_id,
        ]);

        try {
            $store = $this->order->store;

            // 1. Arrange Shipment (Drop-off)
            $shipResponse = $shopeeService->shipOrder(
                $store->access_token,
                $store->marketplace_shop_id,
                $this->order->order_marketplace_id
            );

            Log::info('[Shopee] Arrange shipment successful', ['response' => $shipResponse]);

            // 2. Fetch tracking number
            $trackingResponse = $shopeeService->getTrackingNumber(
                $store->access_token,
                $store->marketplace_shop_id,
                $this->order->order_marketplace_id
            );

            $trackingNo = $trackingResponse['tracking_number'] ?? null;

            // 3. Update Order Status locally
            $this->order->update([
                'order_status' => Order::STATUS_SHIPPED,
                'tracking_number' => $trackingNo ?? $this->order->tracking_number,
            ]);

            Log::info('[Shopee] Order processed and shipped', ['tracking_no' => $trackingNo]);

        } catch (\Exception $e) {
            Log::error('[Shopee] Failed to process order: ' . $e->getMessage());
            throw $e;
        }
    }
}
