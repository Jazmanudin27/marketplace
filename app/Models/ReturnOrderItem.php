<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrderItem extends Model
{
    protected $fillable = [
        'return_order_id',
        'order_item_id',
        'quantity',
    ];

    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
