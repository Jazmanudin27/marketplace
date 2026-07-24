<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierConsignmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_consignment_id',
        'master_product_id',
        'qty_received',
        'unit_cost_price',
        'unit_selling_price',
        'notes',
    ];

    protected $casts = [
        'unit_cost_price' => 'decimal:2',
        'unit_selling_price' => 'decimal:2',
    ];

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(SupplierConsignment::class, 'supplier_consignment_id');
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function settlementItems(): HasMany
    {
        return $this->hasMany(SupplierConsignmentSettlementItem::class, 'supplier_consignment_item_id');
    }

    public function getQtySettledAttribute(): int
    {
        return (int) $this->settlementItems()
            ->whereHas('settlement', function ($q) {
                $q->where('status', 'approved');
            })
            ->sum('qty_settled');
    }

    public function getSubtotalHppAttribute(): float
    {
        return (float) ($this->qty_received * $this->unit_cost_price);
    }

    public function getSubtotalSellingAttribute(): float
    {
        return (float) ($this->qty_received * $this->unit_selling_price);
    }

    public function getPotentialProfitAttribute(): float
    {
        return (float) ($this->qty_received * ($this->unit_selling_price - $this->unit_cost_price));
    }
}
