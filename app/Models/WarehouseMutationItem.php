<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseMutationItem extends Model
{
    protected $fillable = [
        'warehouse_mutation_id',
        'inventory_item_id',
        'quantity',
        'unit_price',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function warehouseMutation(): BelongsTo
    {
        return $this->belongsTo(WarehouseMutation::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
