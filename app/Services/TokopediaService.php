<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TokopediaService
{
    protected $clientId;
    protected $clientSecret;
    protected $fsId;
    protected $baseUrl;

    public function __construct()
    {
        $this->clientId = config('tokopedia.client_id');
        $this->clientSecret = config('tokopedia.client_secret');
        $this->fsId = config('tokopedia.fs_id');
        $this->baseUrl = rtrim(config('tokopedia.base_url', 'https://fs.tokopedia.net'), '/');
    }

    /**
     * Memeriksa apakah integrasi berjalan dalam mode simulasi
     */
    public function isSimulated(?string $shopId = null): bool
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            return true;
        }
        if ($shopId && str_ends_with($shopId, '_DEMO')) {
            return true;
        }
        return false;
    }

    /**
     * Mendapatkan Access Token Tokopedia
     */
    public function getAccessToken(?string $shopId = null): string
    {
        if ($this->isSimulated($shopId)) {
            return 'dummy_tokopedia_access_token_12345';
        }

        $url = 'https://accounts.tokopedia.com/token?grant_type=client_credentials';

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post($url);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mendapatkan token Tokopedia: ' . $response->body());
        }

        $data = $response->json();
        return $data['access_token'] ?? '';
    }

    /**
     * Mengambil daftar produk Tokopedia
     */
    public function getProductSearch(string $accessToken, string $shopId, int $tenantId): array
    {
        if ($this->isSimulated($shopId)) {
            $masterProducts = \App\Models\MasterProduct::where('tenant_id', $tenantId)->get();
            $products = [];

            if ($masterProducts->isEmpty()) {
                $products[] = [
                    'id' => 'tokped-prod-111',
                    'name' => 'Tokopedia Kemeja Flanel Premium',
                    'sku' => 'TSHIRT-FLANEL',
                    'price' => 125000.00,
                    'stock' => 99,
                    'image_url' => 'https://images.tokopedia.net/img/cache/700/VqbcmM/2021/11/2/3a7c645e-7a96-41fb-89c0-93a8cf822cb5.jpg',
                ];
                $products[] = [
                    'id' => 'tokped-prod-222',
                    'name' => 'Tokopedia Celana Chino Slim Fit',
                    'sku' => 'CHINO-SLIM',
                    'price' => 180000.00,
                    'stock' => 75,
                    'image_url' => 'https://images.tokopedia.net/img/cache/700/VqbcmM/2022/8/16/fa2dd4aa-648b-4a57-b248-cb54e3d3fe5e.jpg',
                ];
            } else {
                foreach ($masterProducts as $mp) {
                    $products[] = [
                        'id' => 'tokped-prod-' . $mp->id,
                        'name' => '[Tokopedia] ' . $mp->name,
                        'sku' => $mp->sku,
                        'price' => (float) $mp->price,
                        'stock' => (int) $mp->stock,
                        'image_url' => 'https://images.tokopedia.net/img/cache/700/VqbcmM/2021/11/2/3a7c645e-7a96-41fb-89c0-93a8cf822cb5.jpg',
                    ];
                }
            }

            return ['products' => $products];
        }

        // Real API Call to get product list
        $url = "{$this->baseUrl}/inventory/v1/fs/{$this->fsId}/product/info";
        $response = Http::withToken($accessToken)->get($url, [
            'shop_id' => $shopId,
            'limit' => 50,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal memanggil API Produk Tokopedia: ' . $response->body());
        }

        $data = $response->json();
        $productsRaw = $data['data'] ?? [];
        $products = [];

        foreach ($productsRaw as $p) {
            $products[] = [
                'id' => (string) ($p['product_id'] ?? ''),
                'name' => $p['product_name'] ?? 'Unknown Item',
                'sku' => $p['sku'] ?? null,
                'price' => (float) ($p['price'] ?? 0),
                'stock' => (int) ($p['stock'] ?? 0),
                'image_url' => $p['pictures'][0]['url'] ?? null,
            ];
        }

        return ['products' => $products];
    }

    /**
     * Mengambil daftar pesanan Tokopedia
     */
    public function getOrderList(string $accessToken, string $shopId, int $timeFrom, int $timeTo): array
    {
        if ($this->isSimulated($shopId)) {
            // Generate 2 demo orders
            $orderMarketplaceIds = [
                'TKP-' . date('Ymd') . '-991823',
                'TKP-' . date('Ymd') . '-772183'
            ];

            return ['order_ids' => $orderMarketplaceIds];
        }

        // Real API Call
        $url = "{$this->baseUrl}/v1/order/list";
        $response = Http::withToken($accessToken)->get($url, [
            'shop_id' => $shopId,
            'from_date' => date('Y-m-d', $timeFrom),
            'to_date' => date('Y-m-d', $timeTo),
            'fs_id' => $this->fsId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mengambil daftar pesanan Tokopedia: ' . $response->body());
        }

        $data = $response->json();
        $ordersRaw = $data['data'] ?? [];
        $orderIds = [];

        foreach ($ordersRaw as $o) {
            if (!empty($o['order_id'])) {
                $orderIds[] = (string) $o['order_id'];
            }
        }

        return ['order_ids' => $orderIds];
    }

    /**
     * Mengambil detail satu pesanan Tokopedia
     */
    public function getOrderDetail(string $accessToken, string $shopId, string $orderId, int $tenantId): array
    {
        if ($this->isSimulated($shopId)) {
            $masterProducts = \App\Models\MasterProduct::where('tenant_id', $tenantId)->get();

            $buyerNames = ['Budi Santoso', 'Siti Rahma', 'Joko Susilo', 'Ahmad Dani'];
            $couriers = ['JNE REG', 'SiCepat Halu', 'J&T Express', 'Anteraja Regular'];

            // Pilih produk secara acak dari database jika ada
            $items = [];
            $totalAmount = 0;

            if ($masterProducts->isNotEmpty()) {
                $selected = $masterProducts->random(min(2, $masterProducts->count()));
                foreach ($selected as $sp) {
                    $qty = rand(1, 2);
                    $price = (float) $sp->price;
                    $items[] = [
                        'product_id' => 'tokped-prod-' . $sp->id,
                        'product_name' => '[Tokopedia] ' . $sp->name,
                        'sku' => $sp->sku,
                        'price' => $price,
                        'quantity' => $qty,
                    ];
                    $totalAmount += $price * $qty;
                }
            } else {
                $items[] = [
                    'product_id' => 'tokped-prod-111',
                    'product_name' => 'Tokopedia Kemeja Flanel Premium',
                    'sku' => 'TSHIRT-FLANEL',
                    'price' => 125000.00,
                    'quantity' => 1,
                ];
                $totalAmount = 125000.00;
            }

            $shippingFee = 15000.00;
            $discount = 5000.00;
            $totalAmount += $shippingFee - $discount;

            return [
                'order_id' => $orderId,
                'invoice_number' => 'INV/' . date('Ymd') . '/XXI/' . rand(100000, 999999),
                'order_status' => 'READY_TO_SHIP', // READY_TO_SHIP, SHIPPED, DELIVERED, CANCELLED
                'buyer_name' => $buyerNames[array_rand($buyerNames)],
                'buyer_phone' => '0812' . rand(10000000, 99999999),
                'shipping_address' => 'Jl. Jend. Sudirman No. ' . rand(1, 100) . ', Jakarta Pusat, DKI Jakarta 10210',
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discount,
                'marketplace_fee' => $totalAmount * 0.05, // 5% commision
                'net_amount' => $totalAmount - ($totalAmount * 0.05) - $shippingFee,
                'courier' => $couriers[array_rand($couriers)],
                'tracking_number' => 'TKP' . rand(100000000, 999999999),
                'create_time' => time() - rand(3600, 86400),
                'items' => $items,
            ];
        }

        // Real API Call
        $url = "{$this->baseUrl}/v1/order/detail";
        $response = Http::withToken($accessToken)->get($url, [
            'order_id' => $orderId,
            'fs_id' => $this->fsId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("Gagal mengambil detail pesanan Tokopedia ID $orderId: " . $response->body());
        }

        $data = $response->json();
        $o = $data['data'] ?? [];

        // Map status Tokopedia ke ERP Status
        // Tokopedia: 0 (Unpaid), 220 (Ready to Ship), 400 (Shipped), 700 (Delivered), 10 (Cancelled)
        $rawStatus = (int) ($o['order_status'] ?? 0);
        $erpStatus = 'UNPAID';
        if ($rawStatus === 220) {
            $erpStatus = 'READY_TO_SHIP';
        } elseif ($rawStatus === 400 || $rawStatus === 500) {
            $erpStatus = 'SHIPPED';
        } elseif ($rawStatus === 700) {
            $erpStatus = 'DELIVERED';
        } elseif ($rawStatus === 10 || $rawStatus === 15) {
            $erpStatus = 'CANCELLED';
        }

        $items = [];
        $products = $o['products'] ?? [];
        foreach ($products as $p) {
            $items[] = [
                'product_id' => (string) ($p['product_id'] ?? ''),
                'product_name' => $p['product_name'] ?? 'Unknown Item',
                'sku' => $p['sku'] ?? null,
                'price' => (float) ($p['product_price'] ?? 0),
                'quantity' => (int) ($p['quantity'] ?? 1),
            ];
        }

        return [
            'order_id' => (string) ($o['order_id'] ?? $orderId),
            'invoice_number' => $o['invoice_number'] ?? null,
            'order_status' => $erpStatus,
            'buyer_name' => $o['recipient']['name'] ?? 'Buyer Tokopedia',
            'buyer_phone' => $o['recipient']['phone'] ?? null,
            'shipping_address' => $o['recipient']['address']['address_full'] ?? null,
            'total_amount' => (float) ($o['amt']['total'] ?? 0),
            'shipping_fee' => (float) ($o['amt']['shipping'] ?? 0),
            'discount_amount' => (float) ($o['amt']['discount'] ?? 0),
            'marketplace_fee' => (float) ($o['amt']['marketplace_fee'] ?? 0),
            'net_amount' => (float) ($o['amt']['net_amount'] ?? 0),
            'courier' => $o['logistic']['shipping_agency'] ?? null,
            'tracking_number' => $o['logistic']['awb'] ?? null,
            'create_time' => strtotime($o['payment_date'] ?? $o['create_time'] ?? 'now'),
            'items' => $items,
        ];
    }

    /**
     * Memperbarui Stok Tokopedia
     */
    public function updateStock(string $accessToken, string $shopId, string $productId, ?string $variantId, int $stock): array
    {
        if ($this->isSimulated($shopId)) {
            Log::info("[Tokopedia Simulation] Update stok untuk produk ID: $productId, variant ID: $variantId menjadi $stock");
            return ['status' => 'success'];
        }

        $url = "{$this->baseUrl}/inventory/v1/fs/{$this->fsId}/stock/update";

        $body = [
            'shop_id' => $shopId,
            'products' => [
                [
                    'product_id' => $productId,
                    'stock' => $stock,
                ]
            ]
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $body);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal update stok Tokopedia: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Memperbarui Harga Tokopedia
     */
    public function updatePrice(string $accessToken, string $shopId, string $productId, ?string $variantId, float $price): array
    {
        if ($this->isSimulated($shopId)) {
            Log::info("[Tokopedia Simulation] Update harga untuk produk ID: $productId, variant ID: $variantId menjadi $price");
            return ['status' => 'success'];
        }

        $url = "{$this->baseUrl}/inventory/v1/fs/{$this->fsId}/price/update";

        $body = [
            'shop_id' => $shopId,
            'products' => [
                [
                    'product_id' => $productId,
                    'price' => (int) $price,
                ]
            ]
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $body);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal update harga Tokopedia: ' . $response->body());
        }

        return $response->json();
    }
}
