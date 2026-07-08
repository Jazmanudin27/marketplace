<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'inventory_item_id',
        'master_product_id',
        'quantity',
        'unit_price',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function getItemNameAttribute(): string
    {
        if ($this->inventory_item_id) {
            return $this->inventoryItem ? $this->inventoryItem->name : 'Item Terhapus';
        }
        return $this->masterProduct ? $this->masterProduct->name : 'Produk Terhapus';
    }

    public function getItemSkuAttribute(): string
    {
        if ($this->inventory_item_id) {
            return $this->inventoryItem ? ($this->inventoryItem->sku ?: '-') : '-';
        }
        return $this->masterProduct ? ($this->masterProduct->sku ?: '-') : '-';
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
