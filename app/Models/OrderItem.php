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
        'seller_sku',
        'sku_id',
        'sku_name',
        'product_name',
        'product_image',
        'price',
        'original_price',
        'seller_discount',
        'platform_discount',
        'quantity',
        'total_price',
        'cost_price',
        'hpp_subtotal',
    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'original_price'    => 'decimal:2',
        'seller_discount'   => 'decimal:2',
        'platform_discount' => 'decimal:2',
        'total_price'       => 'decimal:2',
        'cost_price'        => 'decimal:2',
        'hpp_subtotal'      => 'decimal:2',
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
