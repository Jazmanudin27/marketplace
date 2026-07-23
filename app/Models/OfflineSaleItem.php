<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineSaleItem extends Model
{
    protected $fillable = [
        'offline_sale_id', 'master_product_id',
        'product_name', 'sku', 'quantity', 'unit_price',
        'discount_type', 'discount_value', 'discount_amount', 'subtotal',
    ];

    protected $casts = [
        'unit_price'      => 'decimal:2',
        'discount_value'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    public function offlineSale(): BelongsTo
    {
        return $this->belongsTo(OfflineSale::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(OfflineSaleReturnItem::class);
    }

    public function getReturnedQuantityAttribute(): int
    {
        return (int) $this->returnItems()->sum('quantity');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }
}
