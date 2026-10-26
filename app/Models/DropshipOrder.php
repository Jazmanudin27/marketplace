<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DropshipOrder extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_number',
        'channel_type', // 'dropship' atau 'lazada'
        'order_date',
        'customer_name',
        'customer_phone',
        'notes',
        'total_amount',
        'total_qty',
        'status', // 'completed', 'pending', 'cancelled'
    ];

    protected $casts = [
        'order_date'   => 'date',
        'total_amount' => 'decimal:2',
        'total_qty'    => 'integer',
    ];

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
        return $this->hasMany(DropshipOrderItem::class);
    }

    public static function generateOrderNumber(string $channelType = 'dropship'): string
    {
        $prefix = ($channelType === 'lazada' ? 'LZD-' : 'DRP-') . date('Ymd') . '-';
        $last = self::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($last) {
            $lastNum = (int) substr($last->order_number, -4);
            $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '0001';
        }

        return $prefix . $nextNum;
    }
}
