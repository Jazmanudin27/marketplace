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
            if (app()->environment('local') || str_contains($this->store->marketplace_store_id, 'DEMO')) {
                $this->seedDemoReturns();
            }
            return;
        }

        try {
            // Get returns from the last 7 days
            $timeFrom = now()->subDays(7)->timestamp;
            $timeTo = now()->timestamp;

            $accessToken = $this->store->getValidAccessToken();

            try {
                $returnListResponse = $shopeeService->getReturnList(
                    $accessToken,
                    (int) $this->store->marketplace_store_id,
                    0, // page_no
                    50, // page_size
                    $timeFrom,
                    $timeTo
                );
            } catch (\RuntimeException $e) {
                // If it is an invalid access token error, try to force-refresh and retry once
                if (str_contains($e->getMessage(), 'invalid_access_token') || str_contains($e->getMessage(), 'invalid_acceess_token')) {
                    Log::info("[Shopee] Access token invalid for store #{$this->storeId}. Attempting force refresh...");
                    
                    if (empty($this->store->refresh_token)) {
                        Log::warning("[Shopee] Refresh token is empty for store '{$this->store->store_name}' (ID #{$this->storeId}). Cannot auto-refresh. Please re-authenticate this store in Settings.");
                        throw $e;
                    }

                    $accessToken = $this->store->getValidAccessToken(true);
                    
                    // Retry
                    $returnListResponse = $shopeeService->getReturnList(
                        $accessToken,
                        (int) $this->store->marketplace_store_id,
                        0, // page_no
                        50, // page_size
                        $timeFrom,
                        $timeTo
                    );
                } else {
                    throw $e;
                }
            }

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
            
            Log::info("Berhasil sinkronisasi retur Shopee untuk toko: {$this->store->store_name}");
            
        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi retur Shopee untuk toko: {$this->store->store_name}", [
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

    private function seedDemoReturns()
    {
        $orders = Order::where('store_id', $this->storeId)
            ->where('tenant_id', $this->store->tenant_id)
            ->limit(2)
            ->get();

        foreach ($orders as $index => $order) {
            $returnSn = 'RET-' . $order->order_marketplace_id;
            $exists = ReturnOrder::where('return_sn', $returnSn)->exists();
            if ($exists) {
                continue;
            }

            $reasons = [
                'Barang rusak saat pengiriman',
                'Ukuran tidak sesuai deskripsi',
                'Pembeli berubah pikiran',
                'Salah kirim warna produk'
            ];
            
            $returnOrder = ReturnOrder::create([
                'tenant_id' => $this->store->tenant_id,
                'store_id' => $this->storeId,
                'order_id' => $order->id,
                'return_sn' => $returnSn,
                'reason' => $reasons[$index % count($reasons)],
                'status' => 'REQUESTED',
                'refund_amount' => $order->total_amount,
                'is_restocked' => false,
            ]);

            $order->update(['order_status' => Order::STATUS_RETURN]);

            foreach ($order->items as $item) {
                ReturnOrderItem::create([
                    'return_order_id' => $returnOrder->id,
                    'order_item_id' => $item->id,
                    'quantity' => $item->quantity,
                ]);
            }
        }
    }
}
