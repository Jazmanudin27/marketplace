<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsPerformanceLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'ads_campaign_id',
        'date',
        'ad_spend',
        'clicks',
        'impressions',
    ];

    protected $casts = [
        'date' => 'date',
        'ad_spend' => 'decimal:2',
        'clicks' => 'integer',
        'impressions' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdsCampaign::class, 'ads_campaign_id');
    }
}
