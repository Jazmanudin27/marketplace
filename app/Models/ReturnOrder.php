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
        'reason',
        'status',
        'refund_amount',
        'is_restocked',
        'inspection_status',
        'inspection_notes',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'is_restocked' => 'boolean',
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
}
