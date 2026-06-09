<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'marketplace_product_id',
        'master_product_id',
        'sku',
        'product_name',
        'product_image',
        'price',
        'quantity',
        'total_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function marketplaceProduct(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}
