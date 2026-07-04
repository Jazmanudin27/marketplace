<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TieredDiscountTier extends Model
{
    protected $table = 'tiered_discount_tiers';

    protected $fillable = [
        'tiered_discount_id',
        'min_qty',
        'max_qty',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'min_qty'        => 'integer',
        'max_qty'        => 'integer',
        'discount_value' => 'decimal:2',
    ];

    public function tieredDiscount(): BelongsTo
    {
        return $this->belongsTo(TieredDiscount::class, 'tiered_discount_id');
    }
}
