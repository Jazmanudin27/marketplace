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
        'price',
        'stock',
        'image_url',
        'sync_stock',
        'safety_stock',
        'last_synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sync_stock' => 'boolean',
        'safety_stock' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'marketplace_sku', 'sku');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
