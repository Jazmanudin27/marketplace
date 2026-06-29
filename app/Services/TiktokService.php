<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TiktokService
{
    protected $appKey;
    protected $appSecret;
    protected $redirectUri;
    protected $baseUrl = 'https://open-api.tiktokglobalshop.com';
    protected $authUrl = 'https://services.tiktokshop.com/open/authorize';

    public function __construct()
    {
        $this->appKey = config('services.tiktok.app_key');
        $this->appSecret = config('services.tiktok.app_secret');
        $this->redirectUri = config('services.tiktok.redirect_uri');
    }

    /**
     * Membuat URL Otorisasi untuk Seller TikTok
     */
    public function getAuthUrl(string $state = '')
    {
        // TikTok Shop OAuth URL structure
        return $this->authUrl . '?' . http_build_query([
            'app_key' => $this->appKey,
            'state' => $state,
            'redirect_uri' => $this->redirectUri,
        ]);
    }

    /**
     * Mendapatkan Access Token dari Authorization Code
     */
    public function getAccessToken(string $authCode)
    {
        $path = '/api/v2/token/get';
        
        $url = 'https://auth.tiktok-shops.com' . $path . '?' . http_build_query([
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
            'auth_code' => $authCode,
            'grant_type' => 'authorized_code'
        ]);

        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 30,
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ]
        ])->get($url);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mendapatkan token TikTok: ' . $response->body());
        }

        $data = $response->json();

        if ($data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data']; // Berisi access_token, refresh_token, dll
    }

    /**
     * Mendapatkan Access Token baru menggunakan Refresh Token
     */
    public function refreshAccessToken(string $refreshToken)
    {
        $path = '/api/v2/token/refresh';
        
        $url = 'https://auth.tiktok-shops.com' . $path . '?' . http_build_query([
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ]);

        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 30,
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ]
        ])->get($url);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal me-refresh token TikTok: ' . $response->body());
        }

        $data = $response->json();

        if ($data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data']; // Berisi access_token, refresh_token, dll
    }

    /**
     * Mendapatkan daftar toko (shop info) untuk mendapatkan shop_id dan shop_cipher
     */
    public function getShopInfo(string $accessToken)
    {
        $path = '/authorization/202309/shops';
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->get($this->baseUrl . $path, $queryParams);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data'] ?? [];
    }

    /**
     * Men-generate HMAC-SHA256 signature sesuai standar TikTok Shop API v2
     */
    protected function generateSignature(string $path, array $query, string $body = ''): string
    {
        // TikTok Signature rules:
        // 1. Extract all query params except 'sign' and 'access_token'
        $params = $query;
        unset($params['sign'], $params['access_token']);

        // 2. Sort keys alphabetically
        ksort($params);

        // 3. Concatenate key and value
        $str = '';
        foreach ($params as $k => $v) {
            $str .= $k . $v;
        }

        // 4. Wrap with app_secret, prepend path, append body
        $baseString = $this->appSecret . $path . $str . $body . $this->appSecret;

        // 5. HMAC-SHA256
        return hash_hmac('sha256', $baseString, $this->appSecret);
    }

    /**
     * Call API GET
     */
    protected function get(string $path, array $queryParams, string $accessToken, string $shopCipher)
    {
        $queryParams['app_key'] = $this->appKey;
        $queryParams['timestamp'] = time();
        $queryParams['shop_cipher'] = $shopCipher;

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->get($this->baseUrl . $path, $queryParams);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data'] ?? [];
    }

    /**
     * Mendapatkan daftar order berdasarkan rentang waktu (API v202309)
     */
    public function getOrderList(string $accessToken, string $shopCipher, int $createTimeFrom, int $createTimeTo, string $cursor = '')
    {
        $path = '/order/202309/orders/search';
        
        $body = [
            'create_time_ge' => $createTimeFrom,
            'create_time_lt' => $createTimeTo,
            'sort_field' => 'create_time',
            'sort_order' => 'DESC',
        ];

        $bodyJson = json_encode($body);

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
            'page_size' => 50,
        ];

        if ($cursor) {
            // Parameter pagination di v202309 ditaruh di query string
            $queryParams['page_token'] = $cursor;
        }

        $sign = $this->generateSignature($path, $queryParams, $bodyJson);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::timeout(15)->withHeaders([
            'x-tts-access-token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        $result = $data['data'] ?? [];

        // Standarisasi field pagination agar kompatibel dengan PullOrdersFromTiktok
        if (isset($result['next_page_token'])) {
            $result['next_cursor'] = $result['next_page_token'];
            $result['more'] = !empty($result['next_page_token']);
        } else {
            $result['next_cursor'] = '';
            $result['more'] = false;
        }

        return $result;
    }

    /**
     * Mendapatkan detail dari banyak order sekaligus (API v202309 - GET Method)
     */
    public function getOrderDetail(string $accessToken, string $shopCipher, array $orderIdList)
    {
        $path = '/order/202309/orders';
        
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
            'ids' => implode(',', $orderIdList),
        ];

        // signature generateSignature untuk GET (body kosong)
        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::timeout(15)->withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->get($this->baseUrl . $path, $queryParams);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        $result = $data['data'] ?? [];

        // Standarisasi response order_list agar kompatibel dengan pemanggil (PullOrdersFromTiktok)
        return [
            'order_list' => $result['orders'] ?? []
        ];
    }
    /**
     * Mendapatkan daftar produk dari TikTok Shop
     */
    public function getProductSearch(string $accessToken, string $shopCipher, string $pageToken = '')
    {
        $path = '/product/202309/products/search';
        
        $body = [
            'status' => 'ACTIVATE'
        ];
        $bodyJson = json_encode($body);

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
            'page_size' => 50,
        ];

        if ($pageToken) {
            $queryParams['page_token'] = $pageToken;
        }

        $sign = $this->generateSignature($path, $queryParams, $bodyJson);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data'] ?? [];
    }

    public function getProductDetail(string $accessToken, string $shopCipher, string $productId)
    {
        $path = '/product/202309/products/' . $productId;
        
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->get($this->baseUrl . $path, $queryParams);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data'] ?? [];
    }

    public function updatePrice(string $accessToken, string $shopCipher, string $productId, string $skuId, float $price)
    {
        $path = '/product/202309/products/' . $productId . '/prices/update';
        
        $body = [
            'skus' => [
                [
                    'id' => $skuId,
                    'price' => [
                        'currency' => 'IDR',
                        'amount' => (string) (int) $price
                    ]
                ]
            ]
        ];

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
        ];

        $bodyJson = json_encode($body);
        $sign = $this->generateSignature($path, $queryParams, $bodyJson);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data;
    }

    public function updateStock(string $accessToken, string $shopCipher, string $productId, string $skuId, int $stock)
    {
        // 1. Get Product Detail to find warehouse_id
        $detail = $this->getProductDetail($accessToken, $shopCipher, $productId);
        $warehouseId = null;

        if (!empty($detail['skus'])) {
            foreach ($detail['skus'] as $sku) {
                if ($sku['id'] == $skuId) {
                    if (!empty($sku['inventory'][0]['warehouse_id'])) {
                        $warehouseId = $sku['inventory'][0]['warehouse_id'];
                    }
                    break;
                }
            }
        }

        if (!$warehouseId) {
            throw new \RuntimeException("Warehouse ID tidak ditemukan untuk SKU $skuId");
        }

        // 2. Update Inventory
        $path = '/product/202309/products/' . $productId . '/inventory/update';
        
        $body = [
            'skus' => [
                [
                    'id' => $skuId,
                    'inventory' => [
                        [
                            'quantity' => $stock,
                            'warehouse_id' => $warehouseId
                        ]
                    ]
                ]
            ]
        ];

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
        ];

        $bodyJson = json_encode($body);
        $sign = $this->generateSignature($path, $queryParams, $bodyJson);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data;
    }

    /**
     * Memproses pengiriman pesanan (Request Pickup / Drop-off)
     */
    public function shipOrder(string $accessToken, string $shopCipher, string $orderId, string $handoverMethod = 'DROP_OFF')
    {
        $path = '/logistics/202309/orders/' . $orderId . '/ship';
        
        $body = [
            'handover_method' => $handoverMethod
        ];

        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
        ];

        $bodyJson = json_encode($body);
        $sign = $this->generateSignature($path, $queryParams, $bodyJson);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data;
    }

    /**
     * Mengambil dokumen pengiriman (PDF / HTML Resi)
     */
    public function getShippingDocument(string $accessToken, string $shopCipher, string $orderId)
    {
        // Path dan params untuk GET shipping document
        $path = '/logistics/202309/orders/' . $orderId . '/documents';
        
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
            'document_type' => 'SHIPPING_LABEL',
            'document_size' => 'A6',
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->get($this->baseUrl . $path, $queryParams);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data'] ?? [];
    }

    /**
     * Mendapatkan daftar warehouse TikTok Shop
     */
    public function getWarehouses(string $accessToken, string $shopCipher)
    {
        $path = '/logistics/202309/warehouses';
        return $this->get($path, [], $accessToken, $shopCipher);
    }

    /**
     * Upload gambar ke TikTok Shop
     */
    public function uploadImage(string $accessToken, string $shopCipher, string $imagePath)
    {
        $path = '/product/202309/images/upload';
        
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
        ];

        // For multipart/form-data, body is not included in the signature
        $sign = $this->generateSignature($path, $queryParams, '');
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->asMultipart()
          ->attach('data', file_get_contents($imagePath), basename($imagePath))
          ->post($this->baseUrl . $path . '?' . http_build_query($queryParams));

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error') . ' - Response: ' . json_encode($data));
        }

        return $data['data'] ?? [];
    }

    /**
     * Membuat produk baru di TikTok Shop
     */
    public function addProduct(string $accessToken, string $shopCipher, array $productData)
    {
        $path = '/product/202309/products';
        
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
            'category_version' => 'v2',
        ];

        $bodyJson = json_encode($productData);
        $sign = $this->generateSignature($path, $queryParams, $bodyJson);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $productData);

        Log::info('[TikTok] addProduct response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error') . ' - Response: ' . json_encode($data));
        }

        return $data['data'] ?? [];
    }

    public function getCategoryAttributes(string $accessToken, string $shopCipher, string $categoryId)
    {
        $path = '/product/202309/categories/' . $categoryId . '/attributes';
        
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
            'category_version' => 'v2',
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->get($this->baseUrl . $path, $queryParams);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data']['attributes'] ?? [];
    }

    public function getCategories(string $accessToken, string $shopCipher)
    {
        $path = '/product/202309/categories';
        
        $queryParams = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'shop_cipher' => $shopCipher,
            'category_version' => 'v2',
        ];

        $sign = $this->generateSignature($path, $queryParams);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
        ])->get($this->baseUrl . $path, $queryParams);

        $data = $response->json();
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data']['categories'] ?? [];
    }

    // =========================================================================
    // TikTok Shop CS Chat API
    // =========================================================================

    /**
     * Helper POST dengan json body untuk TikTok (dipakai oleh chat methods)
     */
    protected function post(string $path, array $queryParams, array $body, string $accessToken): array
    {
        $queryParams['app_key'] = $this->appKey;
        $queryParams['timestamp'] = time();

        $bodyJson = json_encode($body);
        $sign = $this->generateSignature($path, $queryParams, $bodyJson);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $response = Http::withHeaders([
            'x-tts-access-token' => $accessToken,
            'Content-Type'       => 'application/json',
        ])->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        $data = $response->json();

        if (isset($data['code']) && $data['code'] !== 0) {
            throw new \RuntimeException('TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
        }

        return $data['data'] ?? [];
    }

    /**
     * Ambil daftar percakapan CS TikTok Shop.
     * GET /customer_service/202309/conversations
     */
    public function getChatConversationList(string $accessToken, string $shopCipher, int $pageSize = 20, string $pageToken = ''): array
    {
        $path = '/customer_service/202309/conversations';

        $queryParams = [
            'page_size'   => $pageSize,
            'shop_cipher' => $shopCipher,
        ];

        if ($pageToken !== '') {
            $queryParams['page_token'] = $pageToken;
        }

        return $this->get($path, $queryParams, $accessToken, $shopCipher);
    }

    /**
     * Ambil daftar pesan dalam satu percakapan CS TikTok.
     * GET /customer_service/202309/conversations/{conversation_id}/messages
     */
    public function getChatMessages(string $accessToken, string $shopCipher, string $conversationId, int $pageSize = 50, string $pageToken = ''): array
    {
        $path = '/customer_service/202309/conversations/' . $conversationId . '/messages';

        $queryParams = [
            'page_size'   => $pageSize,
            'shop_cipher' => $shopCipher,
            'sort_order'  => 'ASC',
        ];

        if ($pageToken !== '') {
            $queryParams['page_token'] = $pageToken;
        }

        return $this->get($path, $queryParams, $accessToken, $shopCipher);
    }

    /**
     * Kirim pesan text ke buyer melalui CS TikTok.
     * POST /customer_service/202309/conversations/{conversation_id}/messages
     */
    public function sendChatMessage(string $accessToken, string $shopCipher, string $conversationId, string $messageText): array
    {
        $path = '/customer_service/202309/conversations/' . $conversationId . '/messages';

        $body = [
            'type'    => 'TEXT',
            'content' => [
                'text' => $messageText,
            ],
        ];

        $queryParams = ['shop_cipher' => $shopCipher];

        return $this->post($path, $queryParams, $body, $accessToken);
    }

    /**
     * Buat/dapatkan conversation_id dari buyer user_id.
     * POST /customer_service/202309/conversations
     */
    public function createOrGetConversation(string $accessToken, string $shopCipher, string $buyerUserId): array
    {
        $path = '/customer_service/202309/conversations';

        $body = ['buyer_user_id' => $buyerUserId];

        $queryParams = ['shop_cipher' => $shopCipher];

        return $this->post($path, $queryParams, $body, $accessToken);
    }
}
