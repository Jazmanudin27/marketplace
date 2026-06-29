<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdsCampaign extends Model
{
    protected $fillable = [
        'tenant_id',
        'ads_account_id',
        'campaign_id_platform',
        'name',
        'target_omzet',
        'target_roas',
        'status',
        'is_active',
    ];

    protected $casts = [
        'target_omzet' => 'decimal:2',
        'target_roas' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adsAccount(): BelongsTo
    {
        return $this->belongsTo(AdsAccount::class);
    }

    public function performanceLogs(): HasMany
    {
        return $this->hasMany(AdsPerformanceLog::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'ads_campaign_id');
    }

    // Helper to calculate total spend
    public function getTotalSpendAttribute(): float
    {
        return (float) $this->performanceLogs()->sum('ad_spend');
    }

    // Helper to calculate total revenue
    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->orders()->whereNotIn('order_status', [Order::STATUS_CANCELLED])->sum('net_amount');
    }

    // Helper to calculate ROAS
    public function getActualRoasAttribute(): float
    {
        $spend = $this->total_spend;
        if ($spend <= 0) {
            return 0;
        }
        return $this->total_revenue / $spend;
    }
}
