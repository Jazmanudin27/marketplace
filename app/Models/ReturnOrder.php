<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    protected $fillable = [
        'tenant_id',
        'store_id',
        'order_id',
        'return_sn',
        'return_tracking_number',
        'shipping_provider',
        'reason',
        'status',
        'sla_deadline',
        'refund_amount',
        'is_restocked',
        'inspection_status',
        'inspection_notes',
        'checked_by',
        'replacement_order_id',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'is_restocked' => 'boolean',
        'sla_deadline' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnOrderItem::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function replacementOrder()
    {
        return $this->belongsTo(Order::class, 'replacement_order_id');
    }
}
