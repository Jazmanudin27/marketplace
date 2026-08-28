<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceWalletTransaction extends Model
{
    protected $fillable = [
        'tenant_id',
        'store_id',
        'transaction_id',
        'transaction_date',
        'type',
        'description',
        'amount',
        'direction',
        'current_balance',
        'raw_data',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'raw_data'         => 'array',
        'amount'           => 'float',
        'current_balance'  => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
