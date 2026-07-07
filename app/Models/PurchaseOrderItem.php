<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'master_product_id',
        'inventory_item_id',
        'quantity',
        'unit_price',
        'received_quantity',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getItemNameAttribute(): string
    {
        if ($this->inventory_item_id) {
            return $this->inventoryItem ? $this->inventoryItem->name : 'Item Deleted';
        }
        return $this->masterProduct ? $this->masterProduct->name : 'Product Deleted';
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
        return (float) $this->quantity * $this->unit_price;
    }
}
