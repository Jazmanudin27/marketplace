<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierConsignmentDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'supplier_consignment_item_id',
        'order_id',
        'order_item_id',
        'quantity',
        'scanned_barcode',
        'user_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(SupplierConsignmentItem::class, 'supplier_consignment_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
