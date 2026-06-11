<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSaleItem extends Model
{
    protected $fillable = [
        'offline_sale_id', 'master_product_id',
        'product_name', 'sku', 'quantity', 'unit_price', 'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function offlineSale(): BelongsTo
    {
        return $this->belongsTo(OfflineSale::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}
