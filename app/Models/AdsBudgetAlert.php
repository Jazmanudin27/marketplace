<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdsBudgetAlert extends Model
{
    protected $fillable = [
        'tenant_id',
        'ads_campaign_id',
        'ads_budget_rule_id',
        'level',
        'message',
        'context',
        'is_read',
        'triggered_at',
    ];

    protected $casts = [
        'context'      => 'array',
        'is_read'      => 'boolean',
        'triggered_at' => 'datetime',
    ];

    const LEVEL_INFO     = 'info';
    const LEVEL_WARNING  = 'warning';
    const LEVEL_CRITICAL = 'critical';

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdsCampaign::class, 'ads_campaign_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AdsBudgetRule::class, 'ads_budget_rule_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}
