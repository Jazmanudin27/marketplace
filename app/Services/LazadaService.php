<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LazadaService
{
    protected $appKey;
    protected $appSecret;
    protected $redirectUri;
    protected $baseUrl = 'https://api.lazada.co.id/rest';
    protected $authUrl = 'https://auth.lazada.com/oauth/authorize';

    public function __construct()
    {
        $this->appKey = config('services.lazada.app_key');
        $this->appSecret = config('services.lazada.app_secret');
        $this->redirectUri = config('services.lazada.redirect_uri');
    }

    /**
     * Memeriksa apakah integrasi berjalan dalam mode simulasi
     */
    public function isSimulated(): bool
    {
        return empty($this->appKey) || empty($this->appSecret);
    }

    /**
     * Membuat URL Otorisasi Lazada
     */
    public function getAuthUrl(string $state = ''): string
    {
        if ($this->isSimulated()) {
            // Jika simulasi, arahkan langsung kembali ke callback lokal dengan mock code
            return route('lazada.callback') . '?' . http_build_query([
                'code' => 'mock_lazada_code_99',
                'state' => $state
            ]);
        }

        return $this->authUrl . '?' . http_build_query([
            'response_type' => 'code',
            'force_auth' => 'true',
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->appKey,
            'state' => $state,
        ]);
    }

    /**
     * Mendapatkan Access Token dari Authorization Code
     */
    public function getAccessToken(string $authCode): array
    {
        if ($this->isSimulated()) {
            return [
                'access_token' => 'dummy_lazada_access_token_123',
                'refresh_token' => 'dummy_lazada_refresh_token_123',
                'expires_in' => 86400 * 7, // 7 hari
                'refresh_expires_in' => 86400 * 30, // 30 hari
                'account' => 'Toko Lazada Demo',
                'country' => 'id',
            ];
        }

        $path = '/auth/token/create';
        $timestamp = time() . '000'; // Unix timestamp in milliseconds

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'code' => $authCode,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 30,
        ])->get('https://auth.lazada.com/rest' . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mendapatkan token Lazada: ' . $response->body());
        }

        $data = $response->json();

        // Lazada response usually has 'code' or directly data
        if (isset($data['code']) && $data['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data;
    }

    /**
     * Menyegarkan Access Token menggunakan Refresh Token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        if ($this->isSimulated()) {
            return [
                'access_token' => 'dummy_lazada_access_token_refreshed_' . time(),
                'refresh_token' => $refreshToken,
                'expires_in' => 86400 * 7,
            ];
        }

        $path = '/auth/token/refresh';
        $timestamp = time() . '000';

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'refresh_token' => $refreshToken,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 30,
        ])->get('https://auth.lazada.com/rest' . $path, $queryParams);


        if ($response->failed()) {
            throw new \RuntimeException('Gagal menyegarkan token Lazada: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['code']) && $data['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data;
    }

    /**
     * Mendapatkan Info Toko Lazada
     */
    public function getShopInfo(string $accessToken): array
    {
        if ($this->isSimulated()) {
            return [
                'shop_name' => 'Toko Lazada Demo',
                'seller_id' => 'LAZ-DEMO-991',
            ];
        }

        $path = '/seller/get';
        $timestamp = time() . '000';

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::get($this->baseUrl . $path, $queryParams);
        $data = $response->json();

        if (isset($data['code']) && $data['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        $seller = $data['data'] ?? [];
        return [
            'shop_name' => $seller['name'] ?? 'Lazada Shop',
            'seller_id' => (string) ($seller['seller_id'] ?? 'lazada_store'),
        ];
    }

    /**
     * Sinkronisasi Produk dari Lazada
     */
    public function getProductSearch(string $accessToken, string $shopId, int $tenantId): array
    {
        if ($this->isSimulated()) {
            $masterProducts = \App\Models\MasterProduct::where('tenant_id', $tenantId)->get();
            $products = [];

            if ($masterProducts->isEmpty()) {
                $products[] = [
                    'id' => 'laz-prod-111',
                    'name' => 'Lazada Kemeja Flanel Premium',
                    'sku' => 'TSHIRT-FLANEL',
                    'price' => 125000.00,
                    'stock' => 99,
                    'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Lazada_Logo.svg/512px-Lazada_Logo.svg.png',
                ];
                $products[] = [
                    'id' => 'laz-prod-222',
                    'name' => 'Lazada Celana Chino Slim Fit',
                    'sku' => 'CHINO-SLIM',
                    'price' => 180000.00,
                    'stock' => 75,
                    'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Lazada_Logo.svg/512px-Lazada_Logo.svg.png',
                ];
            } else {
                foreach ($masterProducts as $mp) {
                    $products[] = [
                        'id' => 'laz-prod-' . $mp->id,
                        'name' => '[Lazada] ' . $mp->name,
                        'sku' => $mp->sku,
                        'price' => (float) $mp->price,
                        'stock' => (int) $mp->stock,
                        'image_url' => $mp->image_url ?: 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Lazada_Logo.svg/512px-Lazada_Logo.svg.png',
                    ];
                }
            }

            return ['products' => $products];
        }

        // Real Lazada API call to get products list
        $path = '/products/get';
        $timestamp = time() . '000';

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'filter' => 'all',
            'limit' => 50,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::get($this->baseUrl . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mengambil data produk Lazada: ' . $response->body());
        }

        $data = $response->json();
        if (isset($data['code']) && $data['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        $productsRaw = $data['data']['products'] ?? [];
        $products = [];

        foreach ($productsRaw as $p) {
            $skus = $p['skus'] ?? [];
            $primarySku = $skus[0] ?? [];
            $products[] = [
                'id' => (string) ($p['item_id'] ?? ''),
                'name' => $p['attributes']['name'] ?? 'Lazada Item',
                'sku' => $primarySku['SellerSku'] ?? null,
                'price' => (float) ($primarySku['price'] ?? 0),
                'stock' => (int) ($primarySku['quantity'] ?? 0),
                'image_url' => $p['images'][0] ?? $primarySku['Images'][0] ?? null,
                'skus' => $skus, // Simpan skus lengkap untuk parsing varian
            ];
        }

        return ['products' => $products];
    }

    /**
     * Sinkronisasi Daftar Pesanan dari Lazada
     */
    public function getOrderList(string $accessToken, string $shopId, int $timeFrom, int $timeTo): array
    {
        if ($this->isSimulated()) {
            $orderMarketplaceIds = [
                'LAZ-' . date('Ymd') . '-883192',
                'LAZ-' . date('Ymd') . '-994821'
            ];
            return ['order_ids' => $orderMarketplaceIds];
        }

        $path = '/orders/get';
        $timestamp = time() . '000';

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'created_after' => date('Y-m-d\TH:i:s\Z', $timeFrom),
            'created_before' => date('Y-m-d\TH:i:s\Z', $timeTo),
            'limit' => 50,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::get($this->baseUrl . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mengambil daftar pesanan Lazada: ' . $response->body());
        }

        $data = $response->json();
        if (isset($data['code']) && $data['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        $ordersRaw = $data['data']['orders'] ?? [];
        $orderIds = [];

        foreach ($ordersRaw as $o) {
            if (!empty($o['order_id'])) {
                $orderIds[] = (string) $o['order_id'];
            }
        }

        return ['order_ids' => $orderIds];
    }

    /**
     * Mengambil Detail Satu Pesanan Lazada
     */
    public function getOrderDetail(string $accessToken, string $shopId, string $orderId, int $tenantId): array
    {
        if ($this->isSimulated()) {
            $masterProducts = \App\Models\MasterProduct::where('tenant_id', $tenantId)->get();
            $buyerNames = ['Mega Lestari', 'Doni Wijaya', 'Sinta Amelia'];
            $couriers = ['Lazada Express (LEX)', 'J&T Express', 'Ninja Xpress'];

            $items = [];
            $totalAmount = 0;

            if ($masterProducts->isNotEmpty()) {
                $selected = $masterProducts->random(min(2, $masterProducts->count()));
                foreach ($selected as $sp) {
                    $qty = rand(1, 2);
                    $price = (float) $sp->price;
                    $items[] = [
                        'product_id' => 'laz-prod-' . $sp->id,
                        'product_name' => '[Lazada] ' . $sp->name,
                        'sku' => $sp->sku,
                        'price' => $price,
                        'quantity' => $qty,
                    ];
                    $totalAmount += $price * $qty;
                }
            } else {
                $items[] = [
                    'product_id' => 'laz-prod-111',
                    'product_name' => 'Lazada Kemeja Flanel Premium',
                    'sku' => 'TSHIRT-FLANEL',
                    'price' => 125000.00,
                    'quantity' => 1,
                ];
                $totalAmount = 125000.00;
            }

            $shippingFee = 12000.00;
            $discount = 3000.00;
            $totalAmount += $shippingFee - $discount;

            return [
                'order_id' => $orderId,
                'invoice_number' => 'Lazada/INV/' . date('Ymd') . '/' . rand(1000, 9999),
                'order_status' => 'READY_TO_SHIP',
                'buyer_name' => $buyerNames[array_rand($buyerNames)],
                'buyer_phone' => '0857' . rand(10000000, 99999999),
                'shipping_address' => 'Perum Indah Permai Blok B-' . rand(1, 20) . ', Surabaya, Jawa Timur 60111',
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discount,
                'marketplace_fee' => $totalAmount * 0.04,
                'net_amount' => $totalAmount - ($totalAmount * 0.04) - $shippingFee,
                'courier' => $couriers[array_rand($couriers)],
                'tracking_number' => 'LXAD-' . rand(100000000, 999999999),
                'create_time' => time() - rand(1800, 43200),
                'items' => $items,
            ];
        }

        // Real API Calls:
        // 1. Get Order
        $pathOrder = '/order/get';
        $timestamp = time() . '000';
        $queryParamsOrder = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'order_id' => $orderId,
        ];
        $signOrder = $this->generateSignature($pathOrder, $queryParamsOrder);
        $queryParamsOrder['sign'] = $signOrder;

        $responseOrder = Http::get($this->baseUrl . $pathOrder, $queryParamsOrder);
        $orderData = $responseOrder->json();

        if (isset($orderData['code']) && $orderData['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($orderData['message'] ?? 'Unknown Error'));
        }

        $o = $orderData['data'] ?? [];

        // 2. Get Order Items
        $pathItems = '/order/items/get';
        $queryParamsItems = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'order_id' => $orderId,
        ];
        $signItems = $this->generateSignature($pathItems, $queryParamsItems);
        $queryParamsItems['sign'] = $signItems;

        $responseItems = Http::get($this->baseUrl . $pathItems, $queryParamsItems);
        $itemsData = $responseItems->json();

        if (isset($itemsData['code']) && $itemsData['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($itemsData['message'] ?? 'Unknown Error'));
        }

        $itemsRaw = $itemsData['data'] ?? [];
        $items = [];
        foreach ($itemsRaw as $item) {
            $items[] = [
                'product_id' => (string) ($item['item_id'] ?? ''),
                'product_name' => $item['name'] ?? 'Lazada Product',
                'sku' => $item['sku'] ?? $item['shop_sku'] ?? null,
                'price' => (float) ($item['item_price'] ?? 0),
                'quantity' => 1, // Lazada /order/items/get returns one row per piece
            ];
        }

        // Map status Lazada ke ERP Status
        // Lazada: pending, packed, ready_to_ship, shipped, delivered, canceled
        $rawStatus = strtolower($o['statuses'][0] ?? $o['status'] ?? 'pending');
        $erpStatus = 'UNPAID';
        if (in_array($rawStatus, ['packed', 'ready_to_ship'])) {
            $erpStatus = 'READY_TO_SHIP';
        } elseif ($rawStatus === 'shipped') {
            $erpStatus = 'SHIPPED';
        } elseif ($rawStatus === 'delivered') {
            $erpStatus = 'DELIVERED';
        } elseif ($rawStatus === 'canceled') {
            $erpStatus = 'CANCELLED';
        }

        return [
            'order_id' => (string) ($o['order_id'] ?? $orderId),
            'invoice_number' => $o['order_number'] ?? null,
            'order_status' => $erpStatus,
            'buyer_name' => ($o['address_billing']['first_name'] ?? 'Buyer') . ' ' . ($o['address_billing']['last_name'] ?? 'Lazada'),
            'buyer_phone' => $o['address_billing']['phone'] ?? null,
            'shipping_address' => $o['address_shipping']['address1'] ?? null,
            'total_amount' => (float) ($o['price'] ?? 0),
            'shipping_fee' => (float) ($o['shipping_fee'] ?? 0),
            'discount_amount' => (float) ($o['voucher'] ?? 0),
            'marketplace_fee' => 0.0, // Default 0 jika tidak ada di API
            'net_amount' => (float) (($o['price'] ?? 0) - ($o['shipping_fee'] ?? 0)),
            'courier' => $o['shipment_provider'] ?? null,
            'tracking_number' => $o['tracking_code'] ?? null,
            'create_time' => strtotime($o['created_at'] ?? 'now'),
            'items' => $items,
        ];
    }

    /**
     * Memperbarui Stok di Lazada
     */
    public function updateStock(string $accessToken, string $shopId, string $productId, ?string $variantId, int $stock): array
    {
        if ($this->isSimulated()) {
            Log::info("[Lazada Simulation] Update stok untuk produk ID: $productId, variant ID: $variantId menjadi $stock");
            return ['status' => 'success'];
        }

        $path = '/product/price_quantity/update';
        $timestamp = time() . '000';

        $skuXml = "<Product><Skus><Sku><SellerSku>{$variantId}</SellerSku><Quantity>{$stock}</Quantity></Sku></Skus></Product>";

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'payload' => $skuXml,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::asForm()->post($this->baseUrl . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal update stok Lazada: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Memperbarui Harga di Lazada
     */
    public function updatePrice(string $accessToken, string $shopId, string $productId, ?string $variantId, float $price): array
    {
        if ($this->isSimulated()) {
            Log::info("[Lazada Simulation] Update harga untuk produk ID: $productId, variant ID: $variantId menjadi $price");
            return ['status' => 'success'];
        }

        $path = '/product/price_quantity/update';
        $timestamp = time() . '000';

        $skuXml = "<Product><Skus><Sku><SellerSku>{$variantId}</SellerSku><Price>{$price}</Price></Sku></Skus></Product>";

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'payload' => $skuXml,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::asForm()->post($this->baseUrl . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal update harga Lazada: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Memproses Pengiriman di Lazada
     */
    public function shipOrder(string $accessToken, string $shopId, string $orderId, string $handoverMethod): array
    {
        if ($this->isSimulated()) {
            Log::info("[Lazada Simulation] Mengirim pesanan ID: $orderId dengan metode: $handoverMethod");
            return ['status' => 'success'];
        }

        // Lazada Shipping Flow:
        // 1. Pack order (status -> packed)
        // 2. Set ready to ship (status -> ready_to_ship)
        $timestamp = time() . '000';

        // Pack order
        $pathPack = '/order/pack';
        $queryParamsPack = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'order_ids' => json_encode([(int) $orderId]),
            'shipping_method' => $handoverMethod === 'PICK_UP' ? 'pickup' : 'dropoff',
        ];
        $signPack = $this->generateSignature($pathPack, $queryParamsPack);
        $queryParamsPack['sign'] = $signPack;

        $responsePack = Http::post($this->baseUrl . $pathPack, $queryParamsPack);

        // Set ready to ship
        $pathRts = '/order/rts';
        $queryParamsRts = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'order_ids' => json_encode([(int) $orderId]),
            'shipping_method' => $handoverMethod === 'PICK_UP' ? 'pickup' : 'dropoff',
        ];
        $signRts = $this->generateSignature($pathRts, $queryParamsRts);
        $queryParamsRts['sign'] = $signRts;

        $responseRts = Http::post($this->baseUrl . $pathRts, $queryParamsRts);

        return [
            'pack' => $responsePack->json(),
            'rts' => $responseRts->json(),
        ];
    }

    /**
     * Mendapatkan Nomor Resi / Tracking Number Lazada
     */
    public function getTrackingNumber(string $accessToken, string $shopId, string $orderId): array
    {
        if ($this->isSimulated()) {
            return [
                'tracking_number' => 'LXAD-MOCK-' . rand(100000, 999999),
            ];
        }

        $detail = $this->getOrderDetail($accessToken, $shopId, $orderId, 0);
        return [
            'tracking_number' => $detail['tracking_number'] ?? null,
        ];
    }

    /**
     * Simulasi Publikasi Produk Baru Ke Lazada
     */
    public function addProduct(string $accessToken, string $shopId, array $productData): array
    {
        if ($this->isSimulated()) {
            Log::info("[Lazada Simulation] Menambahkan produk baru ke Lazada: " . json_encode($productData));
            return [
                'product_id' => 'laz-new-prod-' . time(),
                'skus' => [
                    [
                        'id' => 'laz-variant-new-' . time(),
                    ]
                ]
            ];
        }

        $path = '/product/create';
        $timestamp = time() . '000';

        // Prepare XML payload from productData
        $xml = "<Request><Product><PrimaryCategory>{$productData['category_id']}</PrimaryCategory><Attributes><name>{$productData['name']}</name></Attributes><Skus><Sku><SellerSku>{$productData['sku']}</SellerSku><quantity>{$productData['stock']}</quantity><price>{$productData['price']}</price></Sku></Skus></Product></Request>";

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'sign_method' => 'sha256',
            'access_token' => $accessToken,
            'payload' => $xml,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;

        $response = Http::post($this->baseUrl . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal publikasi produk ke Lazada: ' . $response->body());
        }

        $data = $response->json();
        if (isset($data['code']) && $data['code'] !== '0') {
            throw new \RuntimeException('Lazada API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return [
            'product_id' => (string) ($data['data']['item_id'] ?? ''),
            'skus' => array_map(fn($s) => ['id' => (string) ($s['sku_id'] ?? '')], $data['data']['sku_list'] ?? []),
        ];
    }

    /**
     * Men-generate signature standar Lazada API
     */
    protected function generateSignature(string $path, array $params): string
    {
        // 1. Sort parameters alphabetically by key
        ksort($params);

        // 2. Concatenate parameters
        $stringToBeSigned = '';
        foreach ($params as $key => $value) {
            $stringToBeSigned .= $key . $value;
        }

        // 3. Prepend api path
        $stringToBeSigned = $path . $stringToBeSigned;

        // 4. Compute HMAC-SHA256 signature in hex representation (uppercase)
        return strtoupper(hash_hmac('sha256', $stringToBeSigned, $this->appSecret));
    }
}
