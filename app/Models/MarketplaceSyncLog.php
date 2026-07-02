<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSyncLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'marketplace_product_id',
        'channel_code',
        'sku',
        'pushed_stock',
        'status',
        'error_message',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function marketplaceProduct(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status === 'success' ? 'success' : 'danger';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'success' ? 'Sukses' : 'Gagal';
    }
}
