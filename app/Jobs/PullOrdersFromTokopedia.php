<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Customer;
use App\Models\MasterProduct;
use App\Services\TokopediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PullOrdersFromTokopedia implements ShouldQueue
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

    public function handle(TokopediaService $tokopediaService): void
    {
        if ($this->store->status !== 'connected') {
            Log::warning("[Tokopedia] Toko {$this->store->store_name} tidak terhubung.");
            return;
        }

        try {
            Log::info("[Tokopedia] Memulai penarikan pesanan untuk toko {$this->store->store_name}");

            // 1. Get Access Token
            $accessToken = $tokopediaService->getAccessToken($this->store->marketplace_store_id);

            // 2. Fetch Order List
            $response = $tokopediaService->getOrderList(
                $accessToken,
                $this->store->marketplace_store_id,
                $this->timeFrom,
                $this->timeTo
            );

            $orderIds = $response['order_ids'] ?? [];

            if (empty($orderIds)) {
                Log::info("[Tokopedia] Tidak ada pesanan baru untuk toko {$this->store->store_name}");
                return;
            }

            $totalSynced = 0;

            // 3. Loop order IDs and fetch detail for each
            foreach ($orderIds as $orderId) {
                try {
                    $orderDetail = $tokopediaService->getOrderDetail(
                        $accessToken,
                        $this->store->marketplace_store_id,
                        $orderId,
                        $this->store->tenant_id
                    );

                    $this->processOrder($orderDetail);
                    $totalSynced++;
                } catch (\Exception $e) {
                    Log::error("[Tokopedia] Gagal memproses pesanan Tokopedia ID $orderId: " . $e->getMessage());
                }
            }

            Log::info("[Tokopedia] Berhasil sinkronisasi {$totalSynced} pesanan untuk toko {$this->store->store_name}");

        } catch (\Exception $e) {
            Log::error("[Tokopedia] Gagal menarik pesanan untuk toko {$this->store->store_name}: " . $e->getMessage());
        }
    }

    protected function processOrder(array $tokopediaOrder)
    {
        DB::transaction(function () use ($tokopediaOrder) {
            // 1. Customer Handling
            $buyerPhone = $tokopediaOrder['buyer_phone'] ?: '000000000';
            $buyerName = $tokopediaOrder['buyer_name'] ?: 'Buyer Tokopedia';
            
            $customer = Customer::firstOrCreate(
                [
                    'tenant_id' => $this->store->tenant_id,
                    'phone'     => $buyerPhone,
                ],
                [
                    'name'    => $buyerName,
                    'email'   => null,
                    'address' => $tokopediaOrder['shipping_address'],
                ]
            );

            // 2. Order Create / Update
            $orderDate = date('Y-m-d H:i:s', $tokopediaOrder['create_time']);

            $order = Order::updateOrCreate(
                [
                    'tenant_id'            => $this->store->tenant_id,
                    'store_id'             => $this->store->id,
                    'order_marketplace_id' => $tokopediaOrder['order_id'],
                ],
                [
                    'customer_id'      => $customer->id,
                    'invoice_number'   => $tokopediaOrder['invoice_number'],
                    'order_status'     => $tokopediaOrder['order_status'],
                    'buyer_name'       => $buyerName,
                    'buyer_phone'      => $buyerPhone,
                    'shipping_address' => $tokopediaOrder['shipping_address'],
                    'total_amount'     => $tokopediaOrder['total_amount'],
                    'shipping_fee'     => $tokopediaOrder['shipping_fee'],
                    'discount_amount'  => $tokopediaOrder['discount_amount'],
                    'marketplace_fee'  => $tokopediaOrder['marketplace_fee'],
                    'net_amount'       => $tokopediaOrder['net_amount'],
                    'courier'          => $tokopediaOrder['courier'],
                    'tracking_number'  => $tokopediaOrder['tracking_number'],
                    'order_date'       => $orderDate,
                ]
            );

            // 3. Process Order Items
            $items = $tokopediaOrder['items'] ?? [];
            foreach ($items as $item) {
                $masterProduct = null;
                $marketplaceProductId = null;

                // Find marketplace product mapping first
                $mapping = \App\Models\MarketplaceProduct::where('store_id', $this->store->id)
                    ->where('marketplace_product_id', $item['product_id'])
                    ->first();

                if ($mapping) {
                    $masterProduct = $mapping->masterProduct;
                    $marketplaceProductId = $mapping->id;
                }

                // Fallback to SKU lookup in MasterProduct
                if (!$masterProduct && !empty($item['sku'])) {
                    $masterProduct = MasterProduct::where('tenant_id', $this->store->tenant_id)
                        ->where('sku', $item['sku'])
                        ->first();
                }

                $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;
                $qty = $item['quantity'];
                $price = $item['price'];

                OrderItem::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'sku'      => $item['sku'] ?: $item['product_id'], // fallback
                    ],
                    [
                        'marketplace_product_id' => $marketplaceProductId,
                        'master_product_id'      => $masterProduct ? $masterProduct->id : null,
                        'product_name'           => $item['product_name'],
                        'price'                  => $price,
                        'quantity'               => $qty,
                        'total_price'            => $price * $qty,
                        'cost_price'             => $costPrice,
                        'hpp_subtotal'           => $costPrice * $qty,
                    ]
                );
            }

            // Deduct stock if required
            $order->processStockDeduction();
        });
    }
}
