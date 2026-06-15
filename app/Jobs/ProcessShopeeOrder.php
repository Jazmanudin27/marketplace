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
            $accessToken = $store->getValidAccessToken();
            $handoverMethod = $store->shipping_handover_method ?? 'DROP_OFF';

            // 1. Arrange Shipment (Drop-off/Pickup)
            try {
                $shipResponse = $shopeeService->shipOrder(
                    $accessToken,
                    (int) $store->marketplace_store_id,
                    $this->order->order_marketplace_id,
                    $handoverMethod
                );
                Log::info('[Shopee] Arrange shipment successful', ['response' => $shipResponse]);
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'package_already_shipped') || str_contains(strtolower($e->getMessage()), 'already been shipped')) {
                    Log::info('[Shopee] Package already shipped on Shopee. Proceeding to fetch tracking.');
                } else {
                    throw $e;
                }
            }

            // 2. Fetch tracking number
            $trackingResponse = $shopeeService->getTrackingNumber(
                $accessToken,
                (int) $store->marketplace_store_id,
                $this->order->order_marketplace_id
            );

            $trackingNo = !empty($trackingResponse['tracking_number']) ? $trackingResponse['tracking_number'] : null;

            if (!$trackingNo) {
                // Fallback: coba ambil dari order detail (package_number) jika tracking_number kosong (terutama di Sandbox)
                $detailData = $shopeeService->getOrderDetail(
                    $accessToken,
                    (int) $store->marketplace_store_id,
                    [$this->order->order_marketplace_id]
                );
                $shopeeOrder = $detailData['order_list'][0] ?? [];
                $trackingNo = current($shopeeOrder['package_list'] ?? [])['tracking_number'] 
                            ?? current($shopeeOrder['package_list'] ?? [])['package_number'] 
                            ?? null;
            }

            // 3. Deduct local stock automatically (hanya jika belum diproses sebelumnya)
            $this->order->processStockDeduction();

            // 4. Update Order Status locally
            $this->order->update([
                'order_status' => Order::STATUS_SHIPPED,
                'tracking_number' => $trackingNo ?? $this->order->tracking_number,
            ]);

            Log::info('[Shopee] Order processed, shipped, and stock deducted', ['tracking_no' => $trackingNo]);

        } catch (\Exception $e) {
            Log::error('[Shopee] Failed to process order: ' . $e->getMessage());
            throw $e;
        }
    }
}
