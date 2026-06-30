<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\ReturnOrderItem;
use App\Models\Store;
use App\Services\ShopeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullReturnsFromShopee implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $storeId;

    public function __construct(Store $store)
    {
        $this->storeId = $store->id;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopeeService $shopeeService): void
    {
        // Safely fetch the store — it may have been deleted since the job was queued.
        $store = Store::find($this->storeId);

        if (! $store) {
            Log::warning('[Shopee] PullReturnsFromShopee: Store #' . $this->storeId . ' no longer exists. Discarding job.');
            return;
        }

        $this->store = $store;

        if ($this->store->status === 'disconnected' || (empty($this->store->access_token) && empty($this->store->refresh_token))) {
            return;
        }

        try {
            // Get returns from the last 7 days
            $timeFrom = now()->subDays(7)->timestamp;
            $timeTo = now()->timestamp;

            $accessToken = $this->store->getValidAccessToken();

            $returnListResponse = $shopeeService->getReturnList(
                $accessToken,
                (int) $this->store->marketplace_store_id,
                0, // page_no
                50, // page_size
                $timeFrom,
                $timeTo
            );

            $returnList = $returnListResponse['return'] ?? [];

            foreach ($returnList as $returnItem) {
                $returnSn = $returnItem['return_sn'];
                
                // Get more details
                $returnDetailResponse = $shopeeService->getReturnDetail(
                    $accessToken,
                    (int) $this->store->marketplace_store_id,
                    $returnSn
                );
                
                $detail = $returnDetailResponse;
                
                if (empty($detail)) {
                    continue;
                }

                $this->saveReturnOrder($detail);
            }
            
            Log::info("Berhasil sinkronisasi retur Shopee untuk toko: {$this->store->name}");
            
        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi retur Shopee untuk toko: {$this->store->name}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function saveReturnOrder(array $shopeeReturn)
    {
        // Temukan pesanan asli
        $orderSn = $shopeeReturn['order_sn'] ?? null;
        if (!$orderSn) return;
        
        $order = Order::where('tenant_id', $this->store->tenant_id)
            ->where('order_marketplace_id', $orderSn)
            ->first();
            
        if (!$order) {
            // Jika pesanan belum ditarik, tidak bisa simpan retur. 
            // Opsional: Tarik pesanan ini dulu. Tapi untuk sekarang skip.
            return;
        }

        // Simpan Return Order
        $returnOrder = ReturnOrder::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'store_id' => $this->store->id,
                'order_id' => $order->id,
                'return_sn' => $shopeeReturn['return_sn'],
            ],
            [
                'reason' => $shopeeReturn['reason'] ?? null,
                'status' => $shopeeReturn['status'] ?? 'REQUESTED',
                'refund_amount' => $shopeeReturn['refund_amount'] ?? 0,
            ]
        );

        // Ubah status pesanan asli ke RETURN
        $order->update(['order_status' => Order::STATUS_RETURN]);

        // Simpan Return Items jika ada
        if (isset($shopeeReturn['item']) && is_array($shopeeReturn['item'])) {
            foreach ($shopeeReturn['item'] as $rItem) {
                // Temukan order item asli
                $orderItem = $order->items()
                    ->whereHas('marketplaceProduct', function($q) use ($rItem) {
                        if (isset($rItem['model_id']) && $rItem['model_id'] > 0) {
                            $q->where('marketplace_variant_id', $rItem['model_id']);
                        } else {
                            $q->where('marketplace_product_id', $rItem['item_id']);
                        }
                    })->first();

                if ($orderItem) {
                    ReturnOrderItem::updateOrCreate(
                        [
                            'return_order_id' => $returnOrder->id,
                            'order_item_id' => $orderItem->id,
                        ],
                        [
                            'quantity' => $rItem['amount'] ?? 1,
                        ]
                    );
                }
            }
        }
    }
}
