<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderTrackingService
{
    public function __construct(
        protected ShopeeService $shopeeService,
        protected TiktokService $tiktokService,
        protected LazadaService $lazadaService
    ) {}

    /**
     * Mengambil nomor resi dari marketplace API jika belum ada atau kosong.
     * Jika pesanan belum diatur pengirimannya di marketplace (sehingga nomor resi belum di-generate),
     * service ini akan secara otomatis memproses pengaturan pengiriman (ship_order) ke API marketplace
     * lalu menarik nomor resi yang telah diterbitkan dan menyimpannya ke database pesanan.
     *
     * @param Order $order
     * @return string|null Nomor resi yang didapatkan, atau null jika gagal/belum diterbitkan
     */
    public function fetchTrackingNumber(Order $order): ?string
    {
        // Jika sudah ada resi yang valid di DB, kembalikan langsung
        $existing = trim((string) ($order->tracking_number ?? ''));
        if ($existing !== '' && $existing !== '-') {
            return $existing;
        }

        $store = $order->store;
        if (!$store || !$store->channel) {
            return null;
        }

        $channelCode = strtolower($store->channel->code ?? '');
        $trackingNo = null;

        try {
            if ($channelCode === 'shopee') {
                $accessToken = $store->getValidAccessToken();
                $shopId = (int) $store->marketplace_store_id;
                $orderSn = $order->order_marketplace_id;

                // 1. Coba ambil nomor resi langsung dari Shopee jika sudah diterbitkan
                try {
                    $response = $this->shopeeService->getTrackingNumber(
                        $accessToken,
                        $shopId,
                        $orderSn
                    );
                    $trackingNo = $response['tracking_number'] ?? $response['package_list'][0]['tracking_number'] ?? null;
                } catch (\Throwable $e) {
                    Log::info("[OrderTrackingService] Shopee getTrackingNumber cek awal: " . $e->getMessage());
                }

                // 2. Jika nomor resi belum ada, lakukan atur pengiriman (ship_order) otomatis agar Shopee menerbitkan resi AWB
                if (empty($trackingNo)) {
                    $handoverMethod = $store->shipping_handover_method ?? 'DROP_OFF';
                    try {
                        $this->shopeeService->shipOrder(
                            $accessToken,
                            $shopId,
                            $orderSn,
                            $handoverMethod
                        );
                    } catch (\Throwable $e) {
                        $eMsg = strtolower($e->getMessage());
                        if (
                            str_contains($eMsg, 'already_shipped') ||
                            str_contains($eMsg, 'already been shipped') ||
                            str_contains($eMsg, 'already shipped') ||
                            str_contains($eMsg, 'shipping_method_already_set')
                        ) {
                            Log::info("[OrderTrackingService] Pesanan Shopee {$orderSn} sudah pernah di-ship di Shopee.");
                        } else {
                            Log::warning("[OrderTrackingService] Shopee auto shipOrder: " . $e->getMessage());
                        }
                    }

                    // Ambil nomor resi setelah ship_order (coba hingga 3 kali dengan jeda jika kurir sedang mengalokasikan AWB)
                    for ($attempt = 0; $attempt < 3; $attempt++) {
                        if (!empty($trackingNo)) break;
                        if ($attempt > 0) {
                            sleep(1);
                        }
                        try {
                            $response = $this->shopeeService->getTrackingNumber(
                                $accessToken,
                                $shopId,
                                $orderSn
                            );
                            $trackingNo = $response['tracking_number'] ?? $response['package_list'][0]['tracking_number'] ?? null;
                        } catch (\Throwable $e) {
                            Log::warning("[OrderTrackingService] Shopee getTrackingNumber pasca shipOrder attempt {$attempt}: " . $e->getMessage());
                        }
                    }
                }

                // 3. Fallback: ambil dari getOrderDetail Shopee jika masih kosong
                if (empty($trackingNo)) {
                    try {
                        $shopeeOrder = $this->shopeeService->getOrderDetail(
                            $accessToken,
                            $shopId,
                            [$orderSn]
                        );
                        $ordersList = $shopeeOrder['order_list'] ?? [];
                        if (!empty($ordersList[0])) {
                            $firstShopee = $ordersList[0];
                            $trackingNo = $firstShopee['package_list'][0]['tracking_number'] 
                                ?? $firstShopee['tracking_number'] 
                                ?? $firstShopee['tracking_no'] 
                                ?? null;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("[OrderTrackingService] Shopee getOrderDetail fallback: " . $e->getMessage());
                    }
                }

            } elseif (in_array($channelCode, ['tiktok', 'tokopedia'])) {
                $accessToken = $store->getValidAccessToken();
                $shopCipher = $store->shop_cipher;

                // Self-healing jika shop_cipher belum tersimpan di DB
                if (empty($shopCipher)) {
                    try {
                        $shopsData = $this->tiktokService->getShopInfo($accessToken);
                        $shopsList = $shopsData['shops'] ?? (is_array($shopsData) ? $shopsData : []);
                        foreach ($shopsList as $s) {
                            $c = $s['cipher'] ?? $s['shop_cipher'] ?? null;
                            if ($c) {
                                $shopCipher = $c;
                                $store->shop_cipher = $c;
                                $store->save();
                                break;
                            }
                        }
                    } catch (\Throwable $e) {}
                }

                if (empty($shopCipher)) {
                    $shopCipher = $store->marketplace_store_id;
                }

                // 1. Cek detail order TikTok untuk tracking_number yang ada
                try {
                    $detailData = $this->tiktokService->getOrderDetail(
                        $accessToken,
                        $shopCipher,
                        [$order->order_marketplace_id]
                    );

                    $tOrders = $detailData['order_list'] ?? $detailData['orders'] ?? [];
                    if (!empty($tOrders[0])) {
                        $tOrder = $tOrders[0];
                        $trackingNo = $tOrder['tracking_number'] ?? $tOrder['tracking_no'] ?? $tOrder['express_tracking_number'] ?? null;
                        if (empty($trackingNo) && !empty($tOrder['packages'])) {
                            foreach ($tOrder['packages'] as $pkg) {
                                $t = $pkg['tracking_number'] ?? $pkg['tracking_no'] ?? $pkg['express_tracking_number'] ?? null;
                                if (!empty($t)) {
                                    $trackingNo = $t;
                                    break;
                                }
                            }
                        }
                        if (empty($order->package_id) && !empty($tOrder['packages'][0]['id'])) {
                            $order->package_id = (string) $tOrder['packages'][0]['id'];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("[OrderTrackingService] TikTok getOrderDetail cek awal: " . $e->getMessage());
                }

                // 2. Jika resi belum ada, lakukan shipOrder otomatis ke TikTok
                if (empty($trackingNo)) {
                    $handoverMethod = $store->shipping_handover_method ?? 'DROP_OFF';
                    try {
                        $shipRes = $this->tiktokService->shipOrder(
                            $accessToken,
                            $shopCipher,
                            $order->order_marketplace_id,
                            $handoverMethod,
                            $order->package_id
                        );
                        if (!empty($shipRes['package_id']) && empty($order->package_id)) {
                            $order->package_id = (string) $shipRes['package_id'];
                        }
                    } catch (\Throwable $e) {
                        Log::info("[OrderTrackingService] TikTok shipOrder attempt: " . $e->getMessage());
                    }

                    // Ambil detail lagi setelah ship
                    try {
                        $detailData = $this->tiktokService->getOrderDetail(
                            $accessToken,
                            $shopCipher,
                            [$order->order_marketplace_id]
                        );
                        $tOrders = $detailData['order_list'] ?? $detailData['orders'] ?? [];
                        if (!empty($tOrders[0])) {
                            $tOrder = $tOrders[0];
                            $trackingNo = $tOrder['tracking_number'] ?? $tOrder['tracking_no'] ?? $tOrder['express_tracking_number'] ?? null;
                            if (empty($trackingNo) && !empty($tOrder['packages'])) {
                                foreach ($tOrder['packages'] as $pkg) {
                                    $t = $pkg['tracking_number'] ?? $pkg['tracking_no'] ?? $pkg['express_tracking_number'] ?? null;
                                    if (!empty($t)) {
                                        $trackingNo = $t;
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $e) {}
                }

                // 3. Fallback: getShippingDocument
                if (empty($trackingNo)) {
                    try {
                        $docRes = $this->tiktokService->getShippingDocument(
                            $accessToken,
                            $shopCipher,
                            $order->order_marketplace_id,
                            $order->package_id
                        );
                        $trackingNo = $docRes['tracking_number'] ?? $docRes['tracking_no'] ?? $docRes['express_tracking_number'] ?? null;
                    } catch (\Throwable $e) {}
                }

            } elseif ($channelCode === 'lazada') {
                $accessToken = $store->getValidAccessToken();
                $shopId = $store->marketplace_store_id;

                try {
                    $response = $this->lazadaService->getTrackingNumber(
                        $accessToken,
                        $shopId,
                        $order->order_marketplace_id
                    );
                    $trackingNo = $response['tracking_number'] ?? null;
                } catch (\Throwable $e) {}

                if (empty($trackingNo)) {
                    try {
                        $this->lazadaService->shipOrder(
                            $accessToken,
                            $shopId,
                            $order->order_marketplace_id,
                            'dropoff'
                        );
                        $response = $this->lazadaService->getTrackingNumber(
                            $accessToken,
                            $shopId,
                            $order->order_marketplace_id
                        );
                        $trackingNo = $response['tracking_number'] ?? null;
                    } catch (\Throwable $e) {}
                }
            }

            $trackingNo = trim((string) $trackingNo);
            if ($trackingNo !== '' && $trackingNo !== '-') {
                $order->tracking_number = $trackingNo;
                if (!in_array($order->order_status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])) {
                    $order->order_status = Order::STATUS_SHIPPED;
                }
                $order->save();
                return $trackingNo;
            }
        } catch (\Throwable $e) {
            Log::error("[OrderTrackingService] Gagal menarik resi untuk pesanan {$order->id}: " . $e->getMessage());
        }

        return null;
    }
}
