<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineSaleReturn extends Model
{
    protected $fillable = [
        'tenant_id',
        'offline_sale_id',
        'return_number',
        'user_id',
        'total_return_amount',
        'refund_method',
        'payment_destination',
        'reason',
        'returned_at',
    ];

    protected $casts = [
        'total_return_amount' => 'decimal:2',
        'returned_at'         => 'datetime',
    ];

    public function offlineSale(): BelongsTo
    {
        return $this->belongsTo(OfflineSale::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfflineSaleReturnItem::class);
    }

    public static function generateReturnNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = 'RET-OS-' . $date . '-';
        $last   = static::where('return_number', 'like', $prefix . '%')
                        ->orderByDesc('id')
                        ->value('return_number');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
