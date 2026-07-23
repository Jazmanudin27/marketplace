<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSaleReturnItem extends Model
{
    protected $fillable = [
        'offline_sale_return_id',
        'offline_sale_item_id',
        'master_product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function offlineSaleReturn(): BelongsTo
    {
        return $this->belongsTo(OfflineSaleReturn::class);
    }

    public function offlineSaleItem(): BelongsTo
    {
        return $this->belongsTo(OfflineSaleItem::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}
