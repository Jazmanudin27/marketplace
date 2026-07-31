<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceProduct extends Model
{
    protected $fillable = [
        'store_id',
        'master_product_id',
        'marketplace_product_id',
        'marketplace_variant_id',
        'marketplace_sku',
        'name',
        'description',
        'price',
        'stock',
        'image_url',
        'sync_stock',
        'sync_price',
        'safety_stock',
        'is_pre_order',
        'last_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sync_stock' => 'boolean',
        'sync_price' => 'boolean',
        'safety_stock' => 'integer',
        'is_pre_order' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Cek apakah produk ini Pre-Order murni dari data Marketplace (Nama / Flag API Marketplace)
     */
    public function isPreOrderFromMarketplace(): bool
    {
        if ($this->is_pre_order) {
            return true;
        }
        $nameUpper = strtoupper($this->name ?? '');
        if (str_contains($nameUpper, 'PRE ORDER') || str_contains($nameUpper, 'PREORDER') || str_contains($nameUpper, 'PRE-ORDER')) {
            return true;
        }
        if (str_starts_with($nameUpper, 'PO ') || str_contains($nameUpper, ' PO ')) {
            return true;
        }
        return false;
    }

    /**
     * Cek apakah produk ini Pre-Order.
     * Jika terhubung ke Master Product, maka status is_preorder di Master Product adalah acuan utama.
     */
    public function isPreOrder(bool $includeMaster = true): bool
    {
        if ($includeMaster) {
            $master = $this->relationLoaded('masterProduct') ? $this->masterProduct : $this->masterProduct()->first();
            if ($master) {
                return (bool) $master->is_preorder;
            }
        }

        return $this->isPreOrderFromMarketplace();
    }

    protected static function booted()
    {
        static::saving(function (MarketplaceProduct $product) {
            // Bersihkan deskripsi dari tag HTML dan format agar rapi sebelum disimpan
            if (!empty($product->description)) {
                $product->description = static::cleanHtmlDescription($product->description);
            }

            // Otomatis sinkronkan master_product_id berdasarkan marketplace_sku terbaru
            if (!empty($product->marketplace_sku)) {
                $store = $product->store;
                if ($store) {
                    $skuClean = trim($product->marketplace_sku);
                    $master = MasterProduct::where('tenant_id', $store->tenant_id)
                        ->whereRaw('LOWER(TRIM(sku)) = LOWER(TRIM(?))', [$skuClean])
                        ->first();
                    if ($master) {
                        $product->master_product_id = $master->id;
                        $product->sync_stock = true; // Otomatis aktifkan sinkronisasi stok
                    } else {
                        // Jika SKU di marketplace berubah dan tidak cocok dengan Master Product manapun, hilangkan tautan lama
                        if ($product->isDirty('marketplace_sku')) {
                            $product->master_product_id = null;
                        }
                    }
                }
            } else {
                if ($product->isDirty('marketplace_sku')) {
                    $product->master_product_id = null;
                }
            }

            // Jika produk sudah ditautkan ke Master Product, dan Master Product belum memiliki deskripsi
            // atau deskripsi lamanya berbeda dengan marketplace, perbarui secara otomatis.
            if (!empty($product->master_product_id) && !empty($product->description)) {
                $master = $product->masterProduct;
                if ($master && (empty($master->description) || $master->description !== $product->description)) {
                    $master->update(['description' => $product->description]);
                }
            }
        });
    }

    /**
     * Bersihkan deskripsi HTML dari entitas karakter yang rusak untuk ditampilkan di Text Editor.
     */
    public static function cleanHtmlDescription(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        // Decode HTML entities (misal: &amp; menjadi &, &lt; menjadi <, dll)
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Perbaiki jika ada string "amp;" mentah (tanpa tanda & di depan)
        $html = preg_replace('/amp;/i', '&', $html);

        return trim($html);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
