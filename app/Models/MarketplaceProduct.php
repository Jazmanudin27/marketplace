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

            // Jika produk sudah ditautkan ke Master Product, dan Master Product belum memiliki deskripsi,
            // isi deskripsi di Master Product secara otomatis.
            if (!empty($product->master_product_id) && !empty($product->description)) {
                $master = $product->masterProduct;
                if ($master && empty($master->description)) {
                    $master->update(['description' => $product->description]);
                }
            }
        });
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
