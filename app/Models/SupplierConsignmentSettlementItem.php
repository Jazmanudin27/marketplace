<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierConsignmentSettlementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_id',
        'supplier_consignment_item_id',
        'master_product_id',
        'qty_settled',
        'unit_cost_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_cost_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(SupplierConsignmentSettlement::class, 'settlement_id');
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(SupplierConsignmentItem::class, 'supplier_consignment_item_id');
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}
