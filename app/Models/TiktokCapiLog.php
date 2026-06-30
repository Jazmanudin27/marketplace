<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiktokCapiLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'order_id',
        'ads_account_id',
        'event_id',
        'status',
        'http_status',
        'response_body',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'sent_at'       => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENT    = 'sent';
    const STATUS_FAILED  = 'failed';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function adsAccount(): BelongsTo
    {
        return $this->belongsTo(AdsAccount::class);
    }
}
