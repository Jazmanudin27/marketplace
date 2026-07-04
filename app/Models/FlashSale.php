<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class FlashSale extends Model
{
    protected $table = 'flash_sales';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'title',
        'banner_url',
        'start_time',
        'end_time',
        'status',
        'notes',
        'is_synced',
        'last_synced_at',
        'sync_notes',
    ];

    protected $casts = [
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
        'last_synced_at' => 'datetime',
        'is_synced'      => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class, 'flash_sale_id');
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'CANCELLED' || $this->status === 'DRAFT') {
            return $this->status;
        }

        $now = Carbon::now();
        if ($now->lt($this->start_time)) {
            return 'UPCOMING';
        } elseif ($now->gte($this->start_time) && $now->lte($this->end_time)) {
            return 'ACTIVE';
        } else {
            return 'ENDED';
        }
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->computed_status) {
            'ACTIVE'    => 'bg-danger text-white',
            'UPCOMING'  => 'bg-warning text-dark',
            'ENDED'     => 'bg-secondary text-white',
            'DRAFT'     => 'bg-info text-dark',
            'CANCELLED' => 'bg-dark text-white',
            default     => 'bg-light text-dark',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->computed_status) {
            'ACTIVE'    => '⚡ SEDANG BERLANGSUNG',
            'UPCOMING'  => '⏳ AKAN DATANG',
            'ENDED'     => '🏁 BERAKHIR',
            'DRAFT'     => '📝 DRAFT',
            'CANCELLED' => '❌ DIBATALKAN',
            default     => $this->status,
        };
    }

    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->sold_count * $item->flash_sale_price;
        });
    }

    public function getTotalSoldCountAttribute(): int
    {
        return (int) $this->items->sum('sold_count');
    }

    public function getTotalQuotaAttribute(): int
    {
        return (int) $this->items->sum('quota');
    }

    public function getSellThroughRateAttribute(): float
    {
        $quota = $this->total_quota;
        if ($quota <= 0) return 0;
        return round(($this->total_sold_count / $quota) * 100, 1);
    }

    public function getEstimatedProfitAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            $cost = $item->masterProduct->cost_price ?? 0;
            return $item->sold_count * ($item->flash_sale_price - $cost);
        });
    }
}
