<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Customer;
use App\Models\MasterProduct;
use App\Services\LazadaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullOrdersFromLazada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $storeId;
    protected int $timeFrom;
    protected int $timeTo;

    public function __construct(Store $store, int $timeFrom, int $timeTo)
    {
        $this->storeId  = $store->id;
        $this->timeFrom = $timeFrom;
        $this->timeTo   = $timeTo;
    }

    public function handle(LazadaService $lazadaService): void
    {
        $store = Store::find($this->storeId);

        if (!$store) {
            Log::warning('[Lazada] PullOrdersFromLazada: Store #' . $this->storeId . ' no longer exists.');
            return;
        }

        if ($store->status === 'disconnected' || (empty($store->access_token) && empty($store->refresh_token))) {
            Log::warning("[Lazada] Toko {$store->store_name} tidak terhubung.");
            return;
        }

        try {
            $accessToken = $store->getValidAccessToken();
            $shopId = $store->marketplace_store_id;

            $response = $lazadaService->getOrderList(
                $accessToken,
                $shopId,
                $this->timeFrom,
                $this->timeTo
            );

            $orderIds = $response['order_ids'] ?? [];

            if (empty($orderIds)) {
                Log::info("[Lazada] Tidak ada pesanan baru untuk toko {$store->store_name}");
                return;
            }

            foreach ($orderIds as $orderId) {
                try {
                    $lazadaOrder = $lazadaService->getOrderDetail(
                        $accessToken,
                        $shopId,
                        $orderId,
                        $store->tenant_id
                    );

                    $this->processOrder($store, $lazadaOrder);

                } catch (\Exception $e) {
                    Log::error("[Lazada] Gagal memproses detail pesanan {$orderId}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error("[Lazada] Gagal menarik pesanan untuk toko {$store->store_name}: " . $e->getMessage());
        }
    }

    protected function processOrder(Store $store, array $orderData)
    {
        $orderMarketplaceId = $orderData['order_id'];
        $buyerPhone = $orderData['buyer_phone'];
        $buyerName = $orderData['buyer_name'];
        $buyerAddress = $orderData['shipping_address'];

        // Create or find customer
        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $store->tenant_id,
                'phone' => $buyerPhone ?: '000000000',
            ],
            [
                'name' => $buyerName,
                'email' => null,
                'address' => $buyerAddress,
            ]
        );

        $totalAmount = $orderData['total_amount'];
        $shippingFee = $orderData['shipping_fee'];
        $discountAmount = $orderData['discount_amount'];
        $netAmount = $orderData['net_amount'];
        $marketplaceFee = $orderData['marketplace_fee'];

        $financialBreakdown = [
            'original_price' => $totalAmount - $shippingFee,
            'actual_shipping_fee' => $shippingFee,
            'service_fee' => $marketplaceFee,
            'commission_fee' => 0,
            'voucher_from_lazada' => $discountAmount,
            'adjustment_amount' => 0,
        ];

        // 14 days standard shipping limit
        $shipBefore = date('Y-m-d H:i:s', $orderData['create_time'] + (86400 * 3));

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $store->tenant_id,
                'store_id' => $store->id,
                'order_marketplace_id' => $orderMarketplaceId,
            ],
            [
                'customer_id' => $customer->id,
                'order_status' => $orderData['order_status'],
                'buyer_name' => $buyerName,
                'buyer_phone' => $buyerPhone,
                'shipping_address' => $buyerAddress,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'marketplace_fee' => $marketplaceFee,
                'courier' => $orderData['courier'],
                'tracking_number' => $orderData['tracking_number'],
                'order_date' => date('Y-m-d H:i:s', $orderData['create_time']),
                'ship_before_date' => $shipBefore,
                'financial_breakdown' => $financialBreakdown,
            ]
        );

        // Process Items
        $itemsList = $orderData['items'] ?? [];
        foreach ($itemsList as $item) {
            $masterProduct = null;
            $productId = $item['product_id'] ?? null;
            $sellerSku = $item['sku'] ?? null;

            $marketplaceProductId = null;
            if ($productId) {
                $mapping = \App\Models\MarketplaceProduct::where('marketplace_product_id', $productId)
                            ->first();
                if ($mapping) {
                    $masterProduct = $mapping->masterProduct;
                    $marketplaceProductId = $mapping->id;
                }
            }

            if (!$masterProduct && $sellerSku) {
                $masterProduct = MasterProduct::where('tenant_id', $store->tenant_id)
                                              ->where('sku', $sellerSku)
                                              ->first();
            }

            $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;
            $qty = $item['quantity'] ?? 1;
            $price = (float) ($item['price'] ?? 0);

            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'sku'      => $sellerSku ?: $productId,
                ],
                [
                    'marketplace_product_id' => $marketplaceProductId,
                    'master_product_id'      => $masterProduct ? $masterProduct->id : null,
                    'product_name'           => $item['product_name'] ?? 'Lazada Item',
                    'price'                  => $price,
                    'quantity'               => $qty,
                    'total_price'            => $price * $qty,
                    'cost_price'             => $costPrice,
                    'hpp_subtotal'           => $costPrice * $qty,
                ]
            );
        }

        // Process stock deduction or return
        $order->processStockDeduction();
    }
}
