<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\ReturnOrderItem;
use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullReturnsFromTiktok implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $storeId;

    public function __construct(Store $store)
    {
        $this->storeId = $store->id;
    }

    public function handle(TiktokService $tiktokService): void
    {
        $store = Store::find($this->storeId);

        if (!$store) {
            Log::warning('[TikTok] PullReturnsFromTiktok: Store #' . $this->storeId . ' no longer exists. Discarding job.');
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
            $accessToken = $this->store->getValidAccessToken();
            $shopCipher = $this->store->shop_cipher;

            if (empty($shopCipher)) {
                Log::warning("[TikTok] shop_cipher kosong untuk toko {$this->store->store_name}.");
                return;
            }

            try {
                $response = $tiktokService->getReturnList($accessToken, $shopCipher);
            } catch (\RuntimeException $e) {
                // If token is invalid, attempt force refresh and retry once
                if (str_contains($e->getMessage(), 'invalid_access_token') || str_contains($e->getMessage(), 'invalid_token') || str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), 'token_invalid')) {
                    Log::info("[TikTok] Access token invalid in PullReturnsFromTiktok for store #{$this->storeId}. Attempting force refresh...");
                    
                    if (empty($this->store->refresh_token)) {
                        Log::warning("[TikTok] Refresh token is empty for store '{$this->store->store_name}' (ID #{$this->storeId}). Cannot auto-refresh. Please re-authenticate this store in Settings.");
                        throw $e;
                    }

                    $accessToken = $this->store->getValidAccessToken(true);
                    $response = $tiktokService->getReturnList($accessToken, $shopCipher);
                } else {
                    throw $e;
                }
            }

            $returns = $response['return_orders'] ?? [];

            foreach ($returns as $rItem) {
                $this->saveReturnOrder($rItem);
            }

            Log::info("Berhasil sinkronisasi retur TikTok untuk toko: {$this->store->store_name}");

        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi retur TikTok untuk toko: {$this->store->store_name}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function saveReturnOrder(array $tiktokReturn)
    {
        $orderId = $tiktokReturn['order_id'] ?? null;
        if (!$orderId) return;

        $order = Order::where('tenant_id', $this->store->tenant_id)
            ->where('order_marketplace_id', $orderId)
            ->first();

        if (!$order) {
            Log::warning("[TikTok] Otorisasi retur #{$tiktokReturn['return_id']} dilewati karena pesanan asli #{$orderId} belum ditarik ke ERP.");
            return;
        }

        $slaDeadline = null;
        if (isset($tiktokReturn['seller_next_action_response']) && is_array($tiktokReturn['seller_next_action_response'])) {
            $firstAction = $tiktokReturn['seller_next_action_response'][0] ?? null;
            if ($firstAction && isset($firstAction['deadline'])) {
                $slaDeadline = \Carbon\Carbon::createFromTimestamp($firstAction['deadline']);
            }
        }

        $returnOrder = ReturnOrder::updateOrCreate(
            [
                'tenant_id' => $this->store->tenant_id,
                'store_id' => $this->store->id,
                'order_id' => $order->id,
                'return_sn' => $tiktokReturn['return_id'],
            ],
            [
                'return_tracking_number' => $tiktokReturn['return_tracking_number'] ?? null,
                'shipping_provider' => $tiktokReturn['return_provider_name'] ?? null,
                'reason' => $tiktokReturn['return_reason_text'] ?? $tiktokReturn['return_reason'] ?? $tiktokReturn['reason'] ?? null,
                'status' => $tiktokReturn['return_status'] ?? $tiktokReturn['status'] ?? 'REQUESTED',
                'sla_deadline' => $slaDeadline,
                'refund_amount' => $tiktokReturn['refund_amount']['refund_total'] ?? $tiktokReturn['refund_amount'] ?? 0,
            ]
        );

        $order->update(['order_status' => Order::STATUS_RETURN]);

        if (isset($tiktokReturn['return_line_items']) && is_array($tiktokReturn['return_line_items'])) {
            foreach ($tiktokReturn['return_line_items'] as $item) {
                // Find order item by SKU or product ID
                $orderItem = null;
                if (isset($item['seller_sku'])) {
                    $orderItem = $order->items()
                        ->whereHas('marketplaceProduct', function($q) use ($item) {
                            $q->where('sku', $item['seller_sku']);
                        })->first();
                }
                
                if (!$orderItem) {
                    $orderItem = $order->items()->first(); // fallback
                }

                if ($orderItem) {
                    ReturnOrderItem::updateOrCreate(
                        [
                            'return_order_id' => $returnOrder->id,
                            'order_item_id' => $orderItem->id,
                        ],
                        [
                            'quantity' => $item['quantity'] ?? 1,
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
            $returnSn = 'RET-TT-' . $order->order_marketplace_id;
            $exists = ReturnOrder::where('return_sn', $returnSn)->exists();
            if ($exists) {
                continue;
            }

            $reasons = [
                'Pembeli mengajukan pembatalan / retur di TikTok',
                'Barang cacat produksi',
                'Warna tidak sesuai pesanan',
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
