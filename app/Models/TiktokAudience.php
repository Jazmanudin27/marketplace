<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiktokAudience extends Model
{
    protected $fillable = [
        'tenant_id',
        'ads_account_id',
        'tiktok_audience_id',
        'name',
        'type',
        'customer_count',
        'status',
        'last_synced_at',
        'error_message',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'customer_count' => 'integer',
    ];

    const TYPE_PURCHASERS   = 'purchasers';
    const TYPE_HIGH_VALUE   = 'high_value_customers';
    const TYPE_ALL_BUYERS   = 'all_buyers';

    const STATUS_PENDING   = 'pending';
    const STATUS_UPLOADING = 'uploading';
    const STATUS_ACTIVE    = 'active';
    const STATUS_FAILED    = 'failed';

    public static function typeLabels(): array
    {
        return [
            self::TYPE_PURCHASERS => 'Semua Pembeli',
            self::TYPE_HIGH_VALUE => 'Pembeli High-Value (≥ Rp500rb)',
            self::TYPE_ALL_BUYERS => 'Semua Pembeli + Calon',
        ];
    }

    public function adsAccount(): BelongsTo
    {
        return $this->belongsTo(AdsAccount::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
