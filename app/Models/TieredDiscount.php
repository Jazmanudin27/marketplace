<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TieredDiscount extends Model
{
    protected $table = 'tiered_discounts';

    protected $fillable = [
        'tenant_id',
        'name',
        'master_product_id',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(TieredDiscountTier::class, 'tiered_discount_id')->orderBy('min_qty');
    }
}
