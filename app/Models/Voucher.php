<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    protected $fillable = [
        'tenant_id',
        'store_id',
        'name',
        'code',
        'type',
        'value',
        'min_purchase',
        'max_discount',
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'is_active',
        'marketplace_voucher_id',
        'marketplace_status',
    ];

    protected $casts = [
        'value'        => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Apakah voucher sedang aktif (waktu berlaku + flag is_active).
     */
    public function getIsRunningAttribute(): bool
    {
        return $this->is_active
            && now()->between($this->start_date, $this->end_date);
    }

    /**
     * Apakah voucher sudah expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return now()->isAfter($this->end_date);
    }

    /**
     * Status display (Aktif / Akan Datang / Berakhir / Nonaktif).
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) return 'Nonaktif';
        if (now()->isBefore($this->start_date)) return 'Akan Datang';
        if ($this->is_expired) return 'Berakhir';
        return 'Aktif';
    }

    /**
     * Warna badge status.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status_label) {
            'Aktif'        => '#10b981',
            'Akan Datang'  => '#3b82f6',
            'Berakhir'     => '#6b7280',
            default        => '#ef4444',
        };
    }

    /**
     * Format diskon: "10%" atau "Rp 15.000".
     */
    public function getDiscountDisplayAttribute(): string
    {
        if ($this->type === 'percentage') {
            return number_format($this->value, 0) . '%';
        }
        return 'Rp ' . number_format($this->value, 0, ',', '.');
    }
}
