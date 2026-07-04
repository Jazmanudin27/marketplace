<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleItem extends Model
{
    protected $table = 'flash_sale_items';

    protected $fillable = [
        'flash_sale_id',
        'master_product_id',
        'original_price',
        'flash_sale_price',
        'discount_percentage',
        'quota',
        'sold_count',
        'max_purchase_per_user',
    ];

    protected $casts = [
        'original_price'      => 'decimal:2',
        'flash_sale_price'    => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'quota'               => 'integer',
        'sold_count'          => 'integer',
        'max_purchase_per_user' => 'integer',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class, 'flash_sale_id');
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    public function getSellThroughRateAttribute(): float
    {
        if ($this->quota <= 0) return 0;
        return round(($this->sold_count / $this->quota) * 100, 1);
    }

    public function getTotalRevenueAttribute(): float
    {
        return (float) ($this->sold_count * $this->flash_sale_price);
    }

    public function getEstimatedProfitAttribute(): float
    {
        $cost = $this->masterProduct->cost_price ?? 0;
        return (float) ($this->sold_count * ($this->flash_sale_price - $cost));
    }

    public function getUnitProfitAttribute(): float
    {
        $cost = $this->masterProduct->cost_price ?? 0;
        return (float) ($this->flash_sale_price - $cost);
    }

    public function getMarginPercentageAttribute(): float
    {
        if ($this->flash_sale_price <= 0) return 0;
        return round(($this->unit_profit / $this->flash_sale_price) * 100, 1);
    }
}
