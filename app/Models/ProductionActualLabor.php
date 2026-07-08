<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionActualLabor extends Model
{
    protected $fillable = [
        'production_order_id',
        'service_name',
        'actual_cost',
    ];

    protected $casts = [
        'actual_cost' => 'decimal:2',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}
