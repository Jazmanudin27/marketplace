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
            $shopCipher = $this->store->shop_cipher;

            if (empty($shopCipher)) {
                Log::warning("[TikTok] shop_cipher kosong untuk toko {$this->store->store_name}.");
                return;
            }

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
        $itemList = $tiktokOrder['line_items'] ?? $tiktokOrder['item_list'] ?? [];
        foreach ($itemList as $item) {
            $masterProduct = null;
            $skuId = $item['sku_id'] ?? null;
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;

            if ($skuId) {
                $mapping = \App\Models\MarketplaceProduct::where('marketplace_variant_id', $skuId)
                            ->orWhere('marketplace_product_id', $productId)
                            ->first();
                if ($mapping) {
                    $masterProduct = $mapping->masterProduct;
                }
            }

            if (!$masterProduct && $sellerSku) {
                $masterProduct = MasterProduct::where('tenant_id', $this->store->tenant_id)
                                              ->where('sku', $sellerSku)
                                              ->first();
            }

            // Snapshot HPP dari MasterProduct saat pesanan dibuat
            $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;
            $qty = $item['quantity'] ?? 1;
            
            // Standardisasi harga
            $price = $item['sku_sale_price'] ?? $item['price'] ?? $item['sku_original_price'] ?? 0;
            // Jika price berupa string (misal "150000.00"), cast ke float
            $price = (float) $price;

            OrderItem::updateOrCreate(
                [
                    'order_id'              => $order->id,
                    'marketplace_item_id'   => $productId,
                ],
                [
                    'master_product_id' => $masterProduct ? $masterProduct->id : null,
                    'product_name'      => $item['product_name'] ?? 'Unknown Item',
                    'sku'               => $sellerSku,
                    'quantity'          => $qty,
                    'price'             => $price,
                    'total_price'       => $price * $qty,
                    'cost_price'        => $costPrice,
                    'hpp_subtotal'      => $costPrice * $qty,
                ]
            );
        }


        // Process stock deduction or return
        $order->processStockDeduction();
    }
}
