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
                    $id = $o['id'] ?? $o['order_id'] ?? null;
                    if ($id) {
                        $orderIds[] = $id;
                    }
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
        Log::info('[TikTok Debug] order keys: ' . json_encode(array_keys($tiktokOrder)) . ' | full data: ' . json_encode($tiktokOrder));

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

        // Dapatkan status secara aman dengan fallback
        $rawStatus = $tiktokOrder['order_status'] ?? $tiktokOrder['status'] ?? 'UNPAID';
        $erpStatus = $statusMapping[$rawStatus] ?? $rawStatus;

        // Dapatkan Order ID secara aman dengan fallback
        $orderMarketplaceId = $tiktokOrder['order_id'] ?? $tiktokOrder['id'] ?? null;
        if (empty($orderMarketplaceId)) {
            Log::warning('[TikTok] Gagal memproses pesanan karena order_id kosong', $tiktokOrder);
            return;
        }

        // Customer & Alamat secara aman dengan fallback
        $recipient = $tiktokOrder['recipient_address'] ?? [];
        $buyerPhone = $recipient['phone'] ?? $recipient['phone_number'] ?? null;
        $buyerName = $recipient['name'] ?? $recipient['recipient_name'] ?? 'Buyer TikTok';
        $buyerAddress = $recipient['full_address'] ?? $recipient['address_line1'] ?? null;

        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'phone' => $buyerPhone ?: '000000000',
            ],
            [
                'name' => $buyerName,
                'email' => null,
                'address' => $buyerAddress,
            ]
        );

        $paymentInfo = $tiktokOrder['payment_info'] ?? $tiktokOrder['payment'] ?? [];
        
        $totalAmount = $paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? 0;
        $shippingFee = $paymentInfo['shipping_fee'] ?? $paymentInfo['shipping_amount'] ?? 0;
        $discountAmount = $paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? 0;
        $netAmount = $paymentInfo['sub_total'] ?? $paymentInfo['original_amount'] ?? 0;
        
        // Hitung biaya admin (marketplace fee) dari selisih total amount, ongkir, dan pencairan bersih
        $marketplaceFee = max(0, $totalAmount - $shippingFee - $netAmount);

        $financialBreakdown = [
            'original_price' => $totalAmount - $shippingFee,
            'actual_shipping_fee' => $shippingFee,
            'service_fee' => $marketplaceFee,
            'commission_fee' => 0,
            'voucher_from_shopee' => $paymentInfo['platform_discount'] ?? 0,
            'adjustment_amount' => 0,
        ];

        $courier = $tiktokOrder['shipping_provider'] ?? $tiktokOrder['shipping_provider_name'] ?? null;
        $trackingNumber = $tiktokOrder['tracking_number'] ?? $tiktokOrder['tracking_no'] ?? null;
        $createTime = $tiktokOrder['create_time'] ?? $tiktokOrder['create_time_ge'] ?? time();

        $order = Order::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'store_id' => $this->store->id,
                'order_marketplace_id' => $orderMarketplaceId,
            ],
            [
                'customer_id' => $customer->id,
                'order_status' => $erpStatus,
                'buyer_name' => $buyerName,
                'buyer_phone' => $buyerPhone,
                'shipping_address' => $buyerAddress,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'marketplace_fee' => $marketplaceFee,
                'courier' => $courier,
                'tracking_number' => $trackingNumber,
                'order_date' => date('Y-m-d H:i:s', $createTime),
                'financial_breakdown' => $financialBreakdown,
            ]
        );

        // Process Items
        $itemList = $tiktokOrder['line_items'] ?? $tiktokOrder['item_list'] ?? [];
        foreach ($itemList as $item) {
            $masterProduct = null;
            $skuId = $item['sku_id'] ?? null;
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;

            $marketplaceProductId = null;
            if ($skuId) {
                $mapping = \App\Models\MarketplaceProduct::where('marketplace_variant_id', $skuId)
                            ->orWhere('marketplace_product_id', $productId)
                            ->first();
                if ($mapping) {
                    $masterProduct = $mapping->masterProduct;
                    $marketplaceProductId = $mapping->id;
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
            $price = $item['sku_sale_price'] ?? $item['sale_price'] ?? $item['price'] ?? $item['sku_original_price'] ?? $item['original_price'] ?? 0;
            // Jika price berupa string (misal "150000.00"), cast ke float
            $price = (float) $price;

            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'sku'      => $sellerSku ?: $productId, // fallback ke product ID jika SKU kosong agar unik
                ],
                [
                    'marketplace_product_id' => $marketplaceProductId,
                    'master_product_id'      => $masterProduct ? $masterProduct->id : null,
                    'product_name'           => $item['product_name'] ?? 'Unknown Item',
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
