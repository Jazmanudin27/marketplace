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

    protected $store;
    protected $timeFrom;
    protected $timeTo;

    public function __construct(Store $store, int $timeFrom, int $timeTo)
    {
        $this->store = $store;
        $this->timeFrom = $timeFrom;
        $this->timeTo = $timeTo;
    }

    public function handle(TiktokService $tiktokService): void
    {
        if ($this->store->status !== 'connected' || empty($this->store->access_token)) {
            Log::warning("[TikTok] Toko {$this->store->store_name} tidak terhubung.");
            return;
        }

        try {
            $accessToken = $this->store->access_token;
            // Di TikTok, marketplace_store_id seringkali disimpan sebagai open_id atau cipher
            // Asumsikan marketplace_store_id adalah cipher atau id toko
            $shopCipher = $this->store->marketplace_store_id;

            $cursor = '';
            $orderIds = [];

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
                    $orderIds[] = $o['order_id'];
                }

                $cursor = $response['next_cursor'] ?? '';
                $hasMore = $response['more'] ?? false;
                
            } while ($hasMore && $cursor);

            if (empty($orderIds)) {
                Log::info("[TikTok] Tidak ada pesanan baru untuk toko {$this->store->store_name}");
                return;
            }

            // TikTok mengharuskan kita fetch detail menggunakan order_id
            // Kita chunk per 50 id sesuai limit API TikTok
            $chunks = array_chunk($orderIds, 50);

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
        // Standarisasi Status
        // TikTok: UNPAID, AWAITING_SHIPMENT, AWAITING_COLLECTION, IN_TRANSIT, DELIVERED, COMPLETED, CANCELLED
        $statusMapping = [
            'UNPAID' => 'UNPAID',
            'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
            'AWAITING_COLLECTION' => 'READY_TO_SHIP',
            'PARTIALLY_SHIPPING' => 'SHIPPED',
            'IN_TRANSIT' => 'SHIPPED',
            'DELIVERED' => 'DELIVERED',
            'COMPLETED' => 'COMPLETED',
            'CANCELLED' => 'CANCELLED',
        ];

        $erpStatus = $statusMapping[$tiktokOrder['order_status']] ?? $tiktokOrder['order_status'];

        // Customer
        $buyerPhone = $tiktokOrder['recipient_address']['phone_number'] ?? null;
        $buyerName = $tiktokOrder['recipient_address']['name'] ?? 'Buyer TikTok';

        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'phone' => $buyerPhone ?: '000000000',
            ],
            [
                'name' => $buyerName,
                'email' => null,
                'address' => $tiktokOrder['recipient_address']['full_address'] ?? null,
            ]
        );

        $paymentInfo = $tiktokOrder['payment_info'] ?? [];

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'store_id' => $this->store->id,
                'order_marketplace_id' => $tiktokOrder['order_id'],
            ],
            [
                'customer_id' => $customer->id,
                'order_status' => $erpStatus,
                'buyer_name' => $buyerName,
                'buyer_phone' => $buyerPhone,
                'shipping_address' => $tiktokOrder['recipient_address']['full_address'] ?? null,
                'total_amount' => $paymentInfo['total_amount'] ?? 0,
                'shipping_fee' => $paymentInfo['shipping_fee'] ?? 0,
                'discount_amount' => $paymentInfo['seller_discount'] ?? 0,
                'net_amount' => $paymentInfo['sub_total'] ?? 0, // Simplified for now
                'marketplace_fee' => $paymentInfo['platform_discount'] ?? 0,
                'courier' => $tiktokOrder['shipping_provider'] ?? null,
                'tracking_number' => $tiktokOrder['tracking_number'] ?? null,
                'order_date' => date('Y-m-d H:i:s', $tiktokOrder['create_time'] ?? time()),
            ]
        );

        // Process Items
        $itemList = $tiktokOrder['item_list'] ?? [];
        foreach ($itemList as $item) {
            $masterProduct = null;
            if (isset($item['sku_id'])) {
                $mapping = \App\Models\MarketplaceProduct::where('marketplace_variant_id', $item['sku_id'])
                            ->orWhere('marketplace_product_id', $item['product_id'])
                            ->first();
                if ($mapping) {
                    $masterProduct = $mapping->masterProduct;
                }
            }

            if (!$masterProduct && isset($item['seller_sku'])) {
                $masterProduct = MasterProduct::where('tenant_id', $this->store->tenant_id)
                                              ->where('sku', $item['seller_sku'])
                                              ->first();
            }

            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'marketplace_item_id' => $item['product_id'],
                ],
                [
                    'master_product_id' => $masterProduct ? $masterProduct->id : null,
                    'product_name' => $item['product_name'] ?? 'Unknown Item',
                    'sku' => $item['seller_sku'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['sku_original_price'] ?? $item['sku_sale_price'] ?? 0,
                    'total_price' => ($item['sku_sale_price'] ?? 0) * ($item['quantity'] ?? 1),
                ]
            );
        }
    }
}
