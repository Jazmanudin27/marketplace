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
     * Jika berhasil, nomor resi otomatis disimpan ke database pesanan.
     *
     * @param Order $order
     * @return string|null Nomor resi yang didapatkan, atau null jika gagal/belum diterbitkan
     */
    public function fetchTrackingNumber(Order $order): ?string
    {
        // Jika sudah ada resi yang valid, kembalikan langsung
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
                $response = $this->shopeeService->getTrackingNumber(
                    $accessToken,
                    (int) $store->marketplace_store_id,
                    $order->order_marketplace_id
                );

                $trackingNo = $response['tracking_number'] ?? $response['package_list'][0]['tracking_number'] ?? null;

                if (empty($trackingNo)) {
                    // Fallback: ambil dari detail order Shopee jika get_tracking_number belum mengembalikan resi
                    try {
                        $shopeeOrder = $this->shopeeService->getOrderDetail(
                            $accessToken,
                            (int) $store->marketplace_store_id,
                            [$order->order_marketplace_id]
                        );
                        $ordersList = $shopeeOrder['order_list'] ?? [];
                        if (!empty($ordersList[0]['package_list'][0]['tracking_number'])) {
                            $trackingNo = $ordersList[0]['package_list'][0]['tracking_number'];
                        }
                    } catch (\Throwable $e) {
                        Log::warning("[OrderTrackingService] Shopee getOrderDetail fallback error: " . $e->getMessage());
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

                $detailData = $this->tiktokService->getOrderDetail(
                    $accessToken,
                    $shopCipher,
                    [$order->order_marketplace_id]
                );

                $tOrders = $detailData['order_list'] ?? [];
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

                if (empty($trackingNo)) {
                    try {
                        $docRes = $this->tiktokService->getShippingDocument(
                            $accessToken,
                            $shopCipher,
                            $order->order_marketplace_id
                        );
                        $trackingNo = $docRes['tracking_number'] ?? $docRes['tracking_no'] ?? $docRes['express_tracking_number'] ?? null;
                    } catch (\Throwable $e) {}
                }
            } elseif ($channelCode === 'lazada') {
                $response = $this->lazadaService->getTrackingNumber(
                    $store->getValidAccessToken(),
                    $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                $trackingNo = $response['tracking_number'] ?? null;
            }

            $trackingNo = trim((string) $trackingNo);
            if ($trackingNo !== '' && $trackingNo !== '-') {
                $order->tracking_number = $trackingNo;
                $order->save();
                return $trackingNo;
            }
        } catch (\Throwable $e) {
            Log::error("[OrderTrackingService] Gagal menarik resi untuk pesanan {$order->id}: " . $e->getMessage());
        }

        return null;
    }
}
