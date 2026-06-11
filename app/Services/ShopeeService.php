<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopeeService
{
    private int $partnerId;
    private string $partnerKey;
    private string $baseUrl;
    private string $redirectUrl;

    public function __construct()
    {
        $this->partnerId = (int) config('shopee.partner_id');
        $this->partnerKey = config('shopee.partner_key');
        $this->baseUrl = rtrim(config('shopee.base_url'), '/');
        $this->redirectUrl = config('shopee.redirect_url');
    }
    public function getAuthorizationUrl(): string
    {
        $path = '/api/v2/shop/auth_partner';
        $timestamp = time();
        $sign = $this->signBaseRequest($path, $timestamp);

        $params = http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'redirect' => $this->redirectUrl,
        ]);

        $url = $this->baseUrl . $path . '?' . $params;

        Log::info('[Shopee] Authorization URL generated', [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'path' => $path,
            'base_string' => $this->partnerId . $path . $timestamp,
            'sign' => $sign,
            'url' => $url,
        ]);

        return $url;
    }

    public function getAccessToken(string $code, int $shopId): array
    {
        $path = '/api/v2/auth/token/get';
        $timestamp = time();
        $sign = $this->signBaseRequest($path, $timestamp);

        // Query string — auth params
        $queryString = http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ]);

        $url = $this->baseUrl . $path . '?' . $queryString;

        Log::info('[Shopee] getAccessToken request', [
            'url' => $url,
            'base_string' => $this->partnerId . $path . $timestamp,
            'sign' => $sign,
            'code' => $code,
            'shop_id' => $shopId,
        ]);

        // POST body — data spesifik endpoint
        $response = Http::asJson()->post($url, [
            'code' => $code,
            'shop_id' => $shopId,
            'partner_id' => $this->partnerId,
        ]);

        Log::info('[Shopee] getAccessToken response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Gagal mendapatkan access token dari Shopee (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException(
                'Shopee API error [' . $data['error'] . ']: ' . ($data['message'] ?? 'Unknown error')
            );
        }

        return $data;
    }

    public function refreshAccessToken(string $refreshToken, int $shopId): array
    {
        $path = '/api/v2/auth/access_token/get';
        $timestamp = time();
        $sign = $this->signBaseRequest($path, $timestamp);

        $queryString = http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ]);

        $url = $this->baseUrl . $path . '?' . $queryString;

        $response = Http::asJson()->post($url, [
            'refresh_token' => $refreshToken,
            'shop_id' => $shopId,
            'partner_id' => $this->partnerId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal refresh token Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException(
                'Shopee refresh token error [' . $data['error'] . ']: ' . ($data['message'] ?? '')
            );
        }

        return $data;
    }

    public function getShopInfo(string $accessToken, int $shopId): array
    {
        $path = '/api/v2/shop/get_shop_info';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ]);

        Log::info('[Shopee] getShopInfo response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil info toko Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            // Jika gagal ambil info toko, tidak perlu throw — kembalikan array kosong
            Log::warning('[Shopee] getShopInfo error (non-fatal)', ['data' => $data]);
            return [];
        }

        return $data['response'] ?? [];
    }

    public function getItemList(string $accessToken, int $shopId, int $offset = 0, int $pageSize = 50, array $itemStatus = ['NORMAL']): array
    {
        $path = '/api/v2/product/get_item_list';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'offset' => $offset,
            'page_size' => $pageSize,
            'item_status' => implode(',', $itemStatus), // NORMAL, BANNED, DELETED, UNLIST
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil daftar produk Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getItemBaseInfo(string $accessToken, int $shopId, array $itemIds): array
    {
        $path = '/api/v2/product/get_item_base_info';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'item_id_list' => implode(',', $itemIds),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil detail produk Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getModelList(string $accessToken, int $shopId, int $itemId): array
    {
        $path = '/api/v2/product/get_model_list';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'item_id' => $itemId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil detail varian Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getOrderList(string $accessToken, int $shopId, int $timeFrom, int $timeTo, string $timeRangeField = 'create_time', string $cursor = '', int $pageSize = 50): array
    {
        $path = '/api/v2/order/get_order_list';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'time_range_field' => $timeRangeField,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'page_size' => $pageSize,
            'cursor' => $cursor,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil daftar pesanan Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getOrderDetail(string $accessToken, int $shopId, array $orderSnList): array
    {
        $path = '/api/v2/order/get_order_detail';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'order_sn_list' => implode(',', $orderSnList),
            'response_optional_fields' => 'buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,checkout_shipping_carrier,reverse_shipping_fee,order_chargeable_weight_gram,edt,escrow_amount,cancel_reason_ext,shopee_discount_amount,seller_discount_amount'
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil detail pesanan Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getEscrowDetail(string $accessToken, int $shopId, string $orderSn): array
    {
        $path = '/api/v2/payment/get_escrow_detail';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'order_sn' => $orderSn,
        ];

        $response = Http::get($this->baseUrl . $path . '?' . http_build_query($queryParams));

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil escrow detail pesanan Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function updateStock(string $accessToken, int $shopId, int $itemId, int $stock, ?string $variantId = null): array
    {
        $modelId = $variantId ? (int) $variantId : 0;

        // Cek model untuk mendapatkan location_id
        $modelsData = $this->getModelList($accessToken, $shopId, $itemId);
        $models = $modelsData['model'] ?? [];

        $locationId = 'IDZ'; // default fallback

        if ($modelId > 0) {
            foreach ($models as $m) {
                if ($m['model_id'] == $modelId) {
                    $locationId = $m['stock_info_v2']['seller_stock'][0]['location_id'] ?? 'IDZ';
                    break;
                }
            }
        } else {
            // Jika tidak ada varian, cari lokasi dari base info
            // getModelList tidak mengembalikan base info stock_info_v2 jika has_model false, 
            // tapi tidak apa-apa, fallback ke IDZ biasanya aman untuk sandbox/lokal.
            // Bisa disempurnakan dengan panggil getItemBaseInfo jika perlu.
        }

        $stockList = [
            [
                'model_id' => $modelId,
                'seller_stock' => [
                    [
                        'location_id' => $locationId,
                        'stock' => $stock
                    ]
                ]
            ]
        ];

        $path = '/api/v2/product/update_stock';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ];

        $response = Http::post($this->baseUrl . $path . '?' . http_build_query($queryParams), [
            'item_id' => $itemId,
            'stock_list' => $stockList
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal update stok Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function updatePrice(string $accessToken, int $shopId, int $itemId, float $price, ?string $variantId = null): array
    {
        $path = '/api/v2/product/update_price';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $priceList = [
            [
                'model_id' => $variantId ? (int) $variantId : 0,
                'original_price' => $price
            ]
        ];

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ];

        $response = Http::post($this->baseUrl . $path . '?' . http_build_query($queryParams), [
            'item_id' => $itemId,
            'price_list' => $priceList
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal update harga Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function shipOrder(string $accessToken, int $shopId, string $orderSn): array
    {
        $path = '/api/v2/logistics/ship_order';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        // Opsi Drop-off untuk mempermudah (Sandbox)
        $body = [
            'order_sn' => $orderSn,
            'dropoff' => [
                'branch_id' => 0, // Sandbox usually accepts 0 or default values if required
                'sender_real_name' => 'Sender',
                'tracking_no' => '' // For non-integrated, but leave empty string or omit for integrated
            ]
        ];

        $response = Http::post($this->baseUrl . $path . '?' . http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ]), $body);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ship pesanan Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getTrackingNumber(string $accessToken, int $shopId, string $orderSn): array
    {
        $path = '/api/v2/logistics/get_tracking_number';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'order_sn' => $orderSn,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil resi Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getReturnList(string $accessToken, int $shopId, int $pageNo = 0, int $pageSize = 50, int $timeFrom = 0, int $timeTo = 0): array
    {
        $path = '/api/v2/returns/get_return_list';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $params = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];

        if ($timeFrom > 0 && $timeTo > 0) {
            $params['create_time_from'] = $timeFrom;
            $params['create_time_to'] = $timeTo;
        }

        $response = Http::get($this->baseUrl . $path, $params);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil daftar retur Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getReturnDetail(string $accessToken, int $shopId, string $returnSn): array
    {
        $path = '/api/v2/returns/get_return_detail';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'return_sn' => $returnSn,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil detail retur Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function getChannelList(string $accessToken, int $shopId): array
    {
        $path = '/api/v2/logistics/get_channel_list';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ];

        $response = Http::get($this->baseUrl . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mengambil daftar jasa kirim Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response']['logistics_channel_list'] ?? [];
    }

    public function uploadImage(string $accessToken, int $shopId, string $imagePath): array
    {
        $path = '/api/v2/media_space/upload_image';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ];

        $response = Http::asMultipart()
            ->attach('image', file_get_contents($imagePath), basename($imagePath))
            ->post($this->baseUrl . $path . '?' . http_build_query($queryParams), [
                'scene' => 'normal'
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal upload gambar ke Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    public function addItem(string $accessToken, int $shopId, array $itemData): array
    {
        $path = '/api/v2/product/add_item';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ];

        $response = Http::asJson()->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $itemData);

        Log::info('[Shopee] addItem response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal menambahkan produk ke Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            $msg = 'Shopee API Error [' . $data['error'] . ']: ' . ($data['message'] ?? '');
            if (!empty($data['debug_message'])) {
                $msg .= ' | DEBUG: ' . $data['debug_message'];
            }
            throw new \RuntimeException($msg);
        }

        return $data['response'] ?? [];
    }

    // =========================================================================
    // Shopee Sellerchat API
    // =========================================================================

    /**
     * Ambil daftar percakapan dari Shopee Sellerchat.
     * GET /api/v2/sellerchat/get_conversation_list
     */
    public function getChatConversationList(string $accessToken, int $shopId, int $pageSize = 25, string $nextCursor = ''): array
    {
        $path = '/api/v2/sellerchat/get_conversation_list';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $params = [
            'partner_id'   => $this->partnerId,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
            'access_token' => $accessToken,
            'shop_id'      => $shopId,
            'page_size'    => $pageSize,
            'filter'       => 'all',
        ];

        if ($nextCursor !== '') {
            $params['next_cursor'] = $nextCursor;
        }

        $response = Http::timeout(30)->get($this->baseUrl . $path, $params);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil daftar chat Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '' && $data['error'] !== 'OK') {
            throw new \RuntimeException('Shopee Chat API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    /**
     * Ambil daftar pesan dalam satu percakapan.
     * GET /api/v2/sellerchat/get_message
     */
    public function getChatMessages(string $accessToken, int $shopId, string $conversationId, int $pageSize = 25, string $nextCursor = ''): array
    {
        $path = '/api/v2/sellerchat/get_message';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $params = [
            'partner_id'      => $this->partnerId,
            'timestamp'       => $timestamp,
            'sign'            => $sign,
            'access_token'    => $accessToken,
            'shop_id'         => $shopId,
            'conversation_id' => $conversationId,
            'page_size'       => $pageSize,
        ];

        if ($nextCursor !== '') {
            $params['next_cursor'] = $nextCursor;
        }

        $response = Http::timeout(30)->get($this->baseUrl . $path, $params);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil pesan chat Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '' && $data['error'] !== 'OK') {
            throw new \RuntimeException('Shopee Chat API Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    /**
     * Kirim pesan balasan ke buyer di Shopee.
     * POST /api/v2/sellerchat/send_message
     */
    public function sendChatMessage(string $accessToken, int $shopId, string $conversationId, string $messageText): array
    {
        $path = '/api/v2/sellerchat/send_message';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id'   => $this->partnerId,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
            'access_token' => $accessToken,
            'shop_id'      => $shopId,
        ];

        $body = [
            'toId'    => $conversationId,
            'content' => [
                'text' => $messageText,
            ],
            'message_type' => 'text',
        ];

        $response = Http::asJson()
            ->timeout(30)
            ->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        Log::info('[Shopee] sendChatMessage response', [
            'status'          => $response->status(),
            'body'            => $response->body(),
            'conversation_id' => $conversationId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mengirim pesan Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '' && $data['error'] !== 'OK') {
            throw new \RuntimeException('Shopee Chat Send Error [' . $data['error'] . ']: ' . ($data['message'] ?? ''));
        }

        return $data['response'] ?? [];
    }

    /**
     * Tandai percakapan sudah dibaca.
     * POST /api/v2/sellerchat/read_conversation
     */
    public function readChatConversation(string $accessToken, int $shopId, string $conversationId): array
    {
        $path = '/api/v2/sellerchat/read_conversation';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id'   => $this->partnerId,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
            'access_token' => $accessToken,
            'shop_id'      => $shopId,
        ];

        $body = ['conversation_id' => $conversationId];

        $response = Http::asJson()
            ->timeout(30)
            ->post($this->baseUrl . $path . '?' . http_build_query($queryParams), $body);

        if ($response->failed()) {
            Log::warning('[Shopee] readChatConversation failed', ['body' => $response->body()]);
        }

        return $response->json() ?? [];
    }

    public function debugSign(string $path): array
    {
        $timestamp = time();
        $sign = $this->signBaseRequest($path, $timestamp);
        $baseString = $this->partnerId . $path . $timestamp;

        return [
            'partner_id' => $this->partnerId,
            'path' => $path,
            'timestamp' => $timestamp,
            'base_string' => $baseString,
            'sign' => $sign,
        ];
    }

    private function signBaseRequest(string $path, int $timestamp): string
    {
        $base = $this->partnerId . $path . $timestamp;
        return hash_hmac('sha256', $base, $this->partnerKey);
    }

    public function signShopRequest(string $path, int $timestamp, string $accessToken, int $shopId): string
    {
        $baseString = sprintf("%s%s%s%s%s", $this->partnerId, $path, $timestamp, $accessToken, $shopId);
        return hash_hmac('sha256', $baseString, $this->partnerKey);
    }

    /**
     * Ambil daftar kategori produk Shopee.
     * Endpoint get_category memerlukan shop-level signature (access_token + shop_id).
     *
     * @param  string  $accessToken  Access token toko
     * @param  int     $shopId       Shop ID marketplace_store_id dari model Store
     * @param  string  $language     Bahasa respons: 'id' untuk Bahasa Indonesia
     * @return array   Array kategori flat dari Shopee
     */
    public function getCategoryTree(string $accessToken, int $shopId, string $language = 'id'): array
    {
        // Cache kategori selama 1 jam untuk menghindari repeated API call
        $cacheKey = "shopee_categories_{$shopId}_{$language}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHour(), function () use ($accessToken, $shopId, $language) {
            $path = '/api/v2/product/get_category';
            $timestamp = time();
            $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

            $queryParams = [
                'partner_id' => $this->partnerId,
                'timestamp' => $timestamp,
                'sign' => $sign,
                'access_token' => $accessToken,
                'shop_id' => $shopId,
                'language' => $language,
            ];

            $response = Http::timeout(30)->get($this->baseUrl . $path, $queryParams);

            if ($response->failed()) {
                throw new \RuntimeException('Gagal mengambil kategori Shopee: ' . $response->body());
            }

            $data = $response->json();

            if (($data['error'] ?? '') !== '' && ($data['error'] ?? 'OK') !== 'OK') {
                throw new \RuntimeException('Shopee error mengambil kategori: ' . ($data['message'] ?? 'Unknown'));
            }

            return $data['response']['category_list'] ?? [];
        });
    }

    public function getCategoryAttributes(string $accessToken, int $shopId, int $categoryId): array
    {
        $path = '/api/v2/product/get_attribute_tree';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
            'language' => 'id',
            'category_id_list' => $categoryId,
        ];

        $response = Http::timeout(30)->get($this->baseUrl . $path, $queryParams);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mengambil atribut kategori Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (($data['error'] ?? '') !== '' && ($data['error'] ?? 'OK') !== 'OK') {
            throw new \RuntimeException('Shopee error mengambil atribut kategori: ' . ($data['message'] ?? 'Unknown'));
        }

        return $data['response']['list'][0]['attribute_tree'] ?? [];
    }

}
