<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TiktokLiveSession extends Model
{
    protected $fillable = [
        'tenant_id',
        'store_id',
        'title',
        'host_name',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    const STATUS_LIVE      = 'live';
    const STATUS_COMPLETED = 'completed';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'tiktok_live_session_id');
    }

    // Accesor: Total Revenue dari LIVE
    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->orders()
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->sum('net_amount');
    }

    // Accesor: Total Jumlah Pesanan dari LIVE
    public function getTotalOrdersAttribute(): int
    {
        return $this->orders()
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->count();
    }
}
