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
        'last_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sync_stock' => 'boolean',
        'sync_price' => 'boolean',
        'safety_stock' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function (MarketplaceProduct $product) {
            // Bersihkan deskripsi dari tag HTML dan format agar rapi sebelum disimpan
            if (!empty($product->description)) {
                $product->description = static::cleanHtmlDescription($product->description);
            }

            if (empty($product->master_product_id) && !empty($product->marketplace_sku)) {
                $store = $product->store;
                if ($store) {
                    $skuClean = trim($product->marketplace_sku);
                    $master = MasterProduct::where('tenant_id', $store->tenant_id)
                        ->where('sku', $skuClean)
                        ->first();
                    if ($master) {
                        $product->master_product_id = $master->id;
                        $product->sync_stock = true; // Otomatis aktifkan sinkronisasi stok
                    }
                }
            }

            // Jika produk sudah ditautkan ke Master Product, dan Master Product belum memiliki deskripsi
            // atau deskripsi lamanya masih berformat HTML (belum bersih), perbarui secara otomatis.
            if (!empty($product->master_product_id) && !empty($product->description)) {
                $master = $product->masterProduct;
                if ($master) {
                    $hasHtml = str_contains($master->description, '<p>') || 
                               str_contains($master->description, '<br>') || 
                               str_contains($master->description, 'amp;');
                               
                    if (empty($master->description) || $hasHtml) {
                        $master->update(['description' => $product->description]);
                    }
                }
            }
        });
    }

    /**
     * Bersihkan deskripsi HTML menjadi teks biasa yang rapi untuk textarea.
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

        // Ganti tag <br> dan </p> menjadi baris baru (newline)
        $html = preg_replace('/<(br|br\s*\/)>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n", $html);
        $html = preg_replace('/<p>/i', "", $html);

        // Hapus semua tag HTML lainnya
        $text = strip_tags($html);

        // Batasi baris kosong berturut-turut maksimal 2 agar rapi
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
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
