<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProduct extends Model
{
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'image_url',
        'price',
        'cost_price',
        'stock',
        'min_stock',
        'unit',
        'category',
        'brand',
        'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'cost_price'=> 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Kurangi stok master dan sinkronkan ke semua produk marketplace yang terhubung.
     */
    public function decrementStock(int $qty): void
    {
        $this->decrement('stock', $qty);
        // Sinkronisasi ke semua marketplace produk yang terhubung
        $this->marketplaceProducts()
             ->where('sync_stock', true)
             ->update(['stock' => $this->fresh()->stock]);
    }
}
