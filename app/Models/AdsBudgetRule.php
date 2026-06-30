<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdsBudgetRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'ads_campaign_id',
        'name',
        'condition',
        'threshold_value',
        'action',
        'whatsapp_recipient',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'threshold_value'   => 'decimal:2',
        'is_active'         => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    // Conditions
    const CONDITION_ROAS_BELOW          = 'roas_below';
    const CONDITION_ROAS_ABOVE          = 'roas_above';
    const CONDITION_SPEND_EXCEEDS_DAILY = 'spend_exceeds_daily';
    const CONDITION_SPEND_EXCEEDS_TOTAL = 'spend_exceeds_total';

    // Actions
    const ACTION_NOTIFY              = 'notify';
    const ACTION_PAUSE_SUGGESTION    = 'pause_suggestion';
    const ACTION_INCREASE_SUGGESTION = 'increase_suggestion';
    const ACTION_PAUSE_CAMPAIGN_AUTO = 'pause_campaign_auto';
    const ACTION_ADJUST_BUDGET_AUTO  = 'adjust_budget_auto';

    public static function conditionLabels(): array
    {
        return [
            self::CONDITION_ROAS_BELOW          => 'ROAS di bawah threshold',
            self::CONDITION_ROAS_ABOVE          => 'ROAS di atas threshold',
            self::CONDITION_SPEND_EXCEEDS_DAILY => 'Pengeluaran harian melebihi batas',
            self::CONDITION_SPEND_EXCEEDS_TOTAL => 'Total pengeluaran melebihi batas',
        ];
    }

    public static function actionLabels(): array
    {
        return [
            self::ACTION_NOTIFY              => 'Beri Notifikasi',
            self::ACTION_PAUSE_SUGGESTION    => 'Saran: Pause Campaign (Manual)',
            self::ACTION_INCREASE_SUGGESTION => 'Saran: Tingkatkan Budget (Manual)',
            self::ACTION_PAUSE_CAMPAIGN_AUTO => '🔴 Otomatis Pause Campaign (API)',
            self::ACTION_ADJUST_BUDGET_AUTO  => '⚙️ Otomatis Sesuaikan Budget -20% (API)',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdsCampaign::class, 'ads_campaign_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(AdsBudgetAlert::class);
    }

    public function unreadAlerts(): HasMany
    {
        return $this->hasMany(AdsBudgetAlert::class)->where('is_read', false);
    }
}
