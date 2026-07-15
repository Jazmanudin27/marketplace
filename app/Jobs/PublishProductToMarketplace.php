<?php

namespace App\Jobs;

use App\Models\PublicationLog;
use App\Models\MarketplaceProduct;
use App\Models\BrandMapping;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishProductToMarketplace implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $logId;

    public $tries = 2;           // Coba ulang 1x jika gagal
    public $timeout = 120;       // Maksimum 2 menit per job
    public $backoff = 10;        // Tunggu 10 detik sebelum retry

    /**
     * Create a new job instance.
     */
    public function __construct(int $logId)
    {
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = PublicationLog::find($this->logId);
        if (!$log || $log->status === 'success') {
            return;
        }

        $log->update(['status' => 'processing']);

        try {
            $product = $log->masterProduct;
            $store = $log->store;

            if (!$product || !$store) {
                throw new \RuntimeException('Product or Store not found.');
            }

            if ($store->status === 'disconnected') {
                throw new \RuntimeException("Toko '{$store->store_name}' sedang dinonaktifkan (disconnected).");
            }

            $accessToken = $store->getValidAccessToken();

            // Check if already mapped
            $existingMapping = MarketplaceProduct::where('store_id', $store->id)
                ->where(function($q) use ($product) {
                    $q->where('master_product_id', $product->id);
                    if ($product->sku) {
                        $q->orWhere('marketplace_sku', $product->sku);
                    }
                })
                ->first();
            if ($existingMapping) {
                $log->update([
                    'status' => 'success',
                    'marketplace_product_id' => $existingMapping->marketplace_product_id,
                    'error_message' => null
                ]);
                return;
            }

            $catId = $log->category_id;
            $fallbackImageUrl = $product->image_url;
            if (empty($fallbackImageUrl)) {
                $linkedWithImage = MarketplaceProduct::where(function($q) use ($product) {
                        $q->where('master_product_id', $product->id);
                        if ($product->sku) {
                            $q->orWhere('marketplace_sku', $product->sku);
                        }
                    })
                    ->whereNotNull('image_url')
                    ->where('image_url', '!=', '')
                    ->first();
                if ($linkedWithImage) {
                    $fallbackImageUrl = $linkedWithImage->image_url;
                    $product->update(['image_url' => $fallbackImageUrl]);
                }
            }

            $shopeeService = app(ShopeeService::class);
            $tiktokService = app(TiktokService::class);

            if ($store->channel->code === 'shopee') {
                // 1. Get enabled shipping options
                $channels = $shopeeService->getChannelList($accessToken, (int)$store->marketplace_store_id);
                $logisticInfo = [];
                foreach ($channels as $chan) {
                    if (!empty($chan['enabled'])) {
                        $logisticInfo[] = [
                            'logistic_id' => (int) $chan['logistics_channel_id'],
                            'enabled' => true
                        ];
                    }
                }

                if (empty($logisticInfo)) {
                    throw new \RuntimeException('Toko Shopee tidak memiliki jasa kirim aktif yang terkonfigurasi.');
                }

                // 2. Upload image if exists
                $imageId = null;
                $localImgPath = null;
                if ($fallbackImageUrl) {
                    $localImgPath = $this->downloadImage($fallbackImageUrl);
                    if ($localImgPath) {
                        // Shopee: disarankan minimal 500x500
                        $localImgPath = $this->ensureMinImageSize($localImgPath, 500);
                        $imgRes = $shopeeService->uploadImage($accessToken, (int)$store->marketplace_store_id, $localImgPath);
                        Log::info("Shopee uploadImage response: " . json_encode($imgRes));
                        $imageId = $imgRes['image_info']['image_id'] ?? $imgRes['image_id'] ?? null;
                        @unlink($localImgPath);
                    }
                }

                if (!$imageId) {
                    throw new \RuntimeException('Produk wajib memiliki gambar utama untuk di-upload ke Shopee. Pastikan Master Produk sudah memiliki gambar dan valid.');
                }

                // 3. Brand ID (Otomatis menggunakan 100234 / No Brand Shopee)
                $brandId = 100234;

                // 4. Prepare Item Data
                $itemData = [
                    'original_price' => (float) $product->price,
                    'item_name' => $product->name,
                    'description' => $product->description ?: $product->name,
                    'category_id' => (int) $catId,
                    'weight' => (float) $product->weight,
                    'item_status' => 'NORMAL',
                    'logistic_info' => $logisticInfo,
                    'brand' => [
                        'brand_id' => $brandId
                    ],
                    'seller_stock' => [
                        [
                            'stock' => (int) $product->stock
                        ]
                    ]
                ];

                if ($product->length) {
                    $itemData['package_length'] = (int) $product->length;
                }
                if ($product->width) {
                    $itemData['package_width'] = (int) $product->width;
                }
                if ($product->height) {
                    $itemData['package_height'] = (int) $product->height;
                }
                if ($imageId) {
                    $itemData['image'] = [
                        'image_id_list' => [$imageId]
                    ];
                }

                // Auto-fill mandatory attributes
                $attributes = [];
                try {
                    $attributes = $shopeeService->getCategoryAttributes($accessToken, (int)$store->marketplace_store_id, (int)$catId);
                } catch (\Exception $e) {
                    Log::warning('Failed to get Shopee category attributes in Job: ' . $e->getMessage());
                }

                $attributeList = [];
                foreach ($attributes as $attr) {
                    if (!empty($attr['mandatory']) || !empty($attr['is_mandatory'])) {
                        $attrId = $attr['attribute_id'];
                        $values = $attr['attribute_value_list'] ?? [];

                        if (!empty($values)) {
                            $val = $values[0];
                            $valueId = $val['value_id'] ?? null;

                            if ($valueId) {
                                $valObj = [
                                    'value_id' => (int) $valueId
                                ];
                                if (!empty($val['value_unit'])) {
                                    $valObj['value_unit'] = $val['value_unit'];
                                }
                                $attributeList[] = [
                                    'attribute_id' => $attrId,
                                    'attributes_id' => $attrId,
                                    'attribute_value_list' => [$valObj]
                                ];
                                continue;
                            }
                        }

                        // Custom text fallback matching
                        $placeholder = 'Standard';
                        $attrName = $attr['name'] ?? '';
                        $displayName = '';
                        foreach ($attr['multi_lang'] ?? [] as $ml) {
                            if (($ml['language'] ?? '') === 'id') {
                                $displayName = $ml['value'] ?? '';
                            }
                        }
                        if (empty($displayName)) {
                            $displayName = $attrName;
                        }

                        $lowerName = strtolower($displayName . ' ' . $attrName);

                        if (strpos($lowerName, 'life') !== false || strpos($lowerName, 'expired') !== false || strpos($lowerName, 'kadaluarsa') !== false) {
                            $placeholder = '12 Bulan';
                        } elseif (strpos($lowerName, 'screen size') !== false || strpos($lowerName, 'ukuran layar') !== false) {
                            $placeholder = '14 Inci';
                        } elseif (strpos($lowerName, 'laptop type') !== false || strpos($lowerName, 'tipe laptop') !== false) {
                            $placeholder = 'Notebook';
                        } elseif (strpos($lowerName, 'warranty type') !== false || strpos($lowerName, 'jenis garansi') !== false) {
                            $placeholder = 'Garansi Resmi';
                        } elseif (strpos($lowerName, 'warranty duration') !== false || strpos($lowerName, 'masa garansi') !== false) {
                            $placeholder = '12 Bulan';
                        } elseif (strpos($lowerName, 'brand') !== false || strpos($lowerName, 'merek') !== false) {
                            $placeholder = 'No Brand';
                        } elseif (strpos($lowerName, 'model') !== false || strpos($lowerName, 'tipe') !== false) {
                            $placeholder = 'Standard';
                        }

                        $valObj = [
                            'value_id' => 0,
                            'original_value_name' => $placeholder
                        ];

                        $formatType = $attr['attribute_info']['format_type'] ?? null;
                        if ($formatType === 2) {
                            $unitList = $attr['attribute_info']['attribute_unit_list'] ?? [];
                            if (!empty($unitList)) {
                                $valObj['value_unit'] = $unitList[0];
                            }
                        }

                        $attributeList[] = [
                            'attribute_id' => $attrId,
                            'attributes_id' => $attrId,
                            'attribute_value_list' => [$valObj]
                        ];
                    }
                }

                if (!empty($attributeList)) {
                    $itemData['attribute_list'] = $attributeList;
                }

                $res = $shopeeService->addItem($accessToken, (int)$store->marketplace_store_id, $itemData);
                $marketplaceProductId = $res['item_id'] ?? null;

                if (!$marketplaceProductId) {
                    throw new \RuntimeException('Gagal mendapatkan ID Produk dari respons Shopee.');
                }

                MarketplaceProduct::create([
                    'store_id' => $store->id,
                    'master_product_id' => $product->id,
                    'marketplace_product_id' => (string) $marketplaceProductId,
                    'marketplace_variant_id' => null,
                    'marketplace_sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image_url' => $fallbackImageUrl,
                    'sync_stock' => true,
                    'last_synced_at' => now(),
                ]);

                $log->update([
                    'status' => 'success',
                    'marketplace_product_id' => (string)$marketplaceProductId,
                    'error_message' => null
                ]);

            } elseif ($store->channel->code === 'tiktok') {
                // 1. Get Warehouses
                $warehousesData = $tiktokService->getWarehouses($accessToken, $store->shop_cipher);
                $warehouses = $warehousesData['warehouses'] ?? [];
                $warehouseId = null;
                if (!empty($warehouses)) {
                    foreach ($warehouses as $wh) {
                        if (($wh['effect_status'] ?? '') === 'ENABLED' && ($wh['type'] ?? '') === 'SALES_WAREHOUSE') {
                            $warehouseId = (string) $wh['id'];
                            break;
                        }
                    }
                    if (!$warehouseId) {
                        foreach ($warehouses as $wh) {
                            if (($wh['effect_status'] ?? '') === 'ENABLED') {
                                $warehouseId = (string) $wh['id'];
                                break;
                            }
                        }
                    }
                    if (!$warehouseId && isset($warehouses[0]['id'])) {
                        $warehouseId = (string) $warehouses[0]['id'];
                    }
                }

                if (!$warehouseId) {
                    throw new \RuntimeException('Toko TikTok tidak memiliki warehouse aktif.');
                }

                // 2. Upload image if exists
                $mainImages = [];
                $localImgPath = null;
                if ($fallbackImageUrl) {
                    $localImgPath = $this->downloadImage($fallbackImageUrl);
                    if ($localImgPath) {
                        // TikTok: wajib minimal 300x300
                        $localImgPath = $this->ensureMinImageSize($localImgPath, 300);
                        $imgRes = $tiktokService->uploadImage($accessToken, $store->shop_cipher, $localImgPath);
                        $imgUri = $imgRes['uri'] ?? null;
                        if ($imgUri) {
                            $mainImages[] = ['uri' => $imgUri];
                        }
                        @unlink($localImgPath);
                    }
                }

                // 3. Prepare product attributes
                $productAttributes = [];
                try {
                    $rawAttributes = $tiktokService->getCategoryAttributes($accessToken, $store->shop_cipher, (string)$catId);
                    foreach ($rawAttributes as $attr) {
                        if (!empty($attr['is_requried'])) {
                            $attrId = (string) $attr['id'];
                            $valuesList = [];

                            if (!empty($attr['values'])) {
                                $firstVal = $attr['values'][0];
                                $valuesList[] = [
                                    'id' => (string) $firstVal['id'],
                                    'value_id' => (string) $firstVal['id'],
                                    'name' => (string) $firstVal['name']
                                ];
                            } else {
                                $placeholder = '123456789';
                                if (stripos($attr['name'] ?? '', 'sertifikasi') !== false || stripos($attr['name'] ?? '', 'registrasi') !== false) {
                                    $placeholder = 'P-IRT 1234567890';
                                }
                                $valuesList[] = [
                                    'name' => $placeholder
                                ];
                            }

                            $productAttributes[] = [
                                'id' => $attrId,
                                'attribute_id' => $attrId,
                                'values' => $valuesList
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to get TikTok category attributes in Job: ' . $e->getMessage());
                }

                // 4. Prepare product payload
                Log::info('[TikTok] Publishing product with category_id in Job', ['product_id' => $product->id, 'category_id' => $catId, 'attributes' => $productAttributes]);
                $productData = [
                    'title' => $product->name,
                    'description' => $product->description ?: $product->name,
                    'category_id' => (string) $catId,
                    'product_attributes' => $productAttributes,
                    'main_images' => $mainImages,
                    'package_weight' => [
                        'value' => (string) $product->weight,
                        'unit' => 'KILOGRAM'
                    ],
                    'package_dimensions' => [
                        'length' => (string) ($product->length ? (int)$product->length : 10),
                        'width' => (string) ($product->width ? (int)$product->width : 10),
                        'height' => (string) ($product->height ? (int)$product->height : 10),
                        'unit' => 'CENTIMETER'
                    ],
                    'skus' => [
                        [
                            'seller_sku' => $product->sku,
                            'price' => [
                                'currency' => 'IDR',
                                'amount' => (string) (int) $product->price
                            ],
                            'inventory' => [
                                [
                                    'warehouse_id' => $warehouseId,
                                    'quantity' => (int) $product->stock
                                ]
                            ]
                        ]
                    ]
                ];

                try {
                    $res = $tiktokService->addProduct($accessToken, $store->shop_cipher, $productData);
                } catch (\Exception $e) {
                    if ((strpos($e->getMessage(), '12052673') !== false || stripos($e->getMessage(), 'sizechart') !== false || stripos($e->getMessage(), 'size chart') !== false) && !empty($mainImages[0]['uri'])) {
                        Log::info('[TikTok] Retrying product publish with main image as size chart fallback for product ' . $product->id);
                        $productData['size_chart'] = [
                            'image' => [
                                'uri' => $mainImages[0]['uri']
                            ]
                        ];
                        $res = $tiktokService->addProduct($accessToken, $store->shop_cipher, $productData);
                    } else {
                        throw $e;
                    }
                }
                $marketplaceProductId = $res['product_id'] ?? null;
                $marketplaceVariantId = $res['skus'][0]['id'] ?? null;

                if (!$marketplaceProductId) {
                    throw new \RuntimeException('Gagal mendapatkan ID Produk dari respons TikTok.');
                }

                MarketplaceProduct::create([
                    'store_id' => $store->id,
                    'master_product_id' => $product->id,
                    'marketplace_product_id' => (string) $marketplaceProductId,
                    'marketplace_variant_id' => $marketplaceVariantId ? (string) $marketplaceVariantId : null,
                    'marketplace_sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image_url' => $fallbackImageUrl,
                    'sync_stock' => true,
                    'last_synced_at' => now(),
                ]);

                $log->update([
                    'status' => 'success',
                    'marketplace_product_id' => (string)$marketplaceProductId,
                    'error_message' => null
                ]);
            } elseif ($store->channel->code === 'lazada') {
                $lazadaService = app(\App\Services\LazadaService::class);
                
                $productData = [
                    'category_id' => $catId,
                    'name' => $product->name,
                    'sku' => $product->sku ?: ('SKU-' . time()),
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                ];

                $res = $lazadaService->addProduct($store->getValidAccessToken(), $store->marketplace_store_id, $productData);
                $marketplaceProductId = $res['product_id'] ?? null;
                $marketplaceVariantId = $res['skus'][0]['id'] ?? null;

                if (!$marketplaceProductId) {
                    throw new \RuntimeException('Gagal mendapatkan ID Produk dari respons Lazada.');
                }

                MarketplaceProduct::create([
                    'store_id' => $store->id,
                    'master_product_id' => $product->id,
                    'marketplace_product_id' => (string) $marketplaceProductId,
                    'marketplace_variant_id' => $marketplaceVariantId ? (string) $marketplaceVariantId : null,
                    'marketplace_sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image_url' => $fallbackImageUrl,
                    'sync_stock' => true,
                    'last_synced_at' => now(),
                ]);

                $log->update([
                    'status' => 'success',
                    'marketplace_product_id' => (string)$marketplaceProductId,
                    'error_message' => null
                ]);
            }
        } catch (\Exception $e) {

            $translatedError = $this->translateErrorMessage($e->getMessage());
            Log::error("Failed to publish product ID {$this->logId} via Job: " . $e->getMessage());
            $log->update([
                'status' => 'failed',
                'error_message' => $translatedError
            ]);
        }
    }

    /**
     * Menerjemahkan pesan error bahasa Inggris dari marketplace ke Bahasa Indonesia.
     */
    private function translateErrorMessage(string $error): string
    {
        $translations = [
            '/must be at least 300:300/i' => 'Lebar dan panjang gambar produk minimal harus 300x300 piksel (TikTok).',
            '/must be at least 500:500/i' => 'Lebar dan panjang gambar produk minimal harus 500x500 piksel (Shopee).',
            '/image_id_list.*must contain at least/i' => 'Produk wajib memiliki setidaknya 1 gambar utama.',
            '/package_weight.*must be greater than 0/i' => 'Berat paket harus lebih besar dari 0 kg.',
            '/weight.*must be/i' => 'Berat produk tidak valid.',
            '/price.*must be/i' => 'Harga produk tidak valid atau di luar batas ketentuan marketplace.',
            '/stock.*must be/i' => 'Stok produk tidak valid.',
            '/brand is mandatory/i' => 'Merk/Brand wajib diisi untuk kategori ini.',
            '/category_id.*invalid/i' => 'ID Kategori tidak valid atau tidak didukung di toko ini.',
            '/size chart is a mandatory/i' => 'Tabel ukuran (Size Chart) wajib disertakan untuk kategori ini.',
            '/gtin is a mandatory/i' => 'Kode barcode (GTIN) wajib disertakan untuk kategori ini.',
            '/token.*expired/i' => 'Token akses toko kedaluwarsa. Silakan hubungkan ulang toko Anda.',
            '/invalid.*access_token/i' => 'Token akses toko tidak valid. Silakan hubungkan ulang toko Anda.',
            '/sku.*unique/i' => 'Kode SKU produk harus unik dan belum digunakan di produk lain di marketplace.',
            '/duplicate.*sku/i' => 'Kode SKU produk sudah terdaftar di marketplace.',
            '/attribute.*mandatory/i' => 'Atribut kategori wajib ada yang belum terisi.',
            '/attribute.*required/i' => 'Atribut wajib untuk kategori ini belum lengkap.',
        ];

        foreach ($translations as $pattern => $translation) {
            if (preg_match($pattern, $error)) {
                return $translation;
            }
        }

        // Clean up common technical prefix/suffix if present
        $cleanError = preg_replace('/^(TikTok API Error:|Shopee API Error:)\s*/i', '', $error);
        
        return $cleanError;
    }

    /**
     * Dipanggil saat job gagal setelah semua percobaan habis.
     */
    public function failed(\Throwable $exception): void
    {
        $log = PublicationLog::find($this->logId);
        if ($log) {
            $translatedError = $this->translateErrorMessage($exception->getMessage());
            $log->update([
                'status'        => 'failed',
                'error_message' => 'Gagal setelah mencoba ulang: ' . $translatedError
            ]);
        }
        Log::error("Job PublishProductToMarketplace FAILED permanently for log #{$this->logId}: " . $exception->getMessage());
    }

    /**
     * Download gambar dari URL ke file temporary.
     */
    private function downloadImage(string $url): ?string
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($content === false || $httpCode >= 400) {
                Log::error("Failed to download image in Job from $url. HTTP Code: $httpCode");
                return null;
            }

            $tmpPath = tempnam(sys_get_temp_dir(), 'pim_img_') . '.jpg';
            file_put_contents($tmpPath, $content);
            return $tmpPath;
        } catch (\Exception $e) {
            Log::error('Exception downloading product image in Job: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pastikan gambar memiliki dimensi minimal $minSize x $minSize pixel.
     * Jika lebih kecil, resize/pad ke ukuran minimum menggunakan GD.
     * Return path file (baru atau lama jika sudah cukup besar).
     */
    private function ensureMinImageSize(string $imgPath, int $minSize = 300): string
    {
        try {
            if (!function_exists('imagecreatefromstring')) {
                Log::warning('[Image] GD extension tidak tersedia, skip resize.');
                return $imgPath;
            }

            $content = file_get_contents($imgPath);
            if (!$content) return $imgPath;

            $src = @imagecreatefromstring($content);
            if (!$src) {
                Log::warning('[Image] Tidak bisa membaca gambar: ' . $imgPath);
                return $imgPath;
            }

            $srcW = imagesx($src);
            $srcH = imagesy($src);

            Log::info("[Image] Ukuran asli: {$srcW}x{$srcH}, minimum: {$minSize}x{$minSize}");

            // Sudah cukup besar, tidak perlu resize
            if ($srcW >= $minSize && $srcH >= $minSize) {
                imagedestroy($src);
                return $imgPath;
            }

            // Hitung ukuran target: scale up agar sisi terpendek = minSize
            $scale = max($minSize / $srcW, $minSize / $srcH);
            $newW = (int) ceil($srcW * $scale);
            $newH = (int) ceil($srcH * $scale);

            $dst = imagecreatetruecolor($newW, $newH);
            // Background putih
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

            $newPath = tempnam(sys_get_temp_dir(), 'pim_resized_') . '.jpg';
            imagejpeg($dst, $newPath, 92);

            imagedestroy($src);
            imagedestroy($dst);

            // Hapus file asli
            @unlink($imgPath);

            Log::info("[Image] Berhasil resize ke: {$newW}x{$newH}, disimpan ke: {$newPath}");
            return $newPath;
        } catch (\Exception $e) {
            Log::error('[Image] Gagal resize gambar: ' . $e->getMessage());
            return $imgPath;
        }
    }
}
