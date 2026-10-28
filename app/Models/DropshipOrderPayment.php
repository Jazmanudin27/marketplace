<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropshipOrderPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'dropship_order_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'payment_destination',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dropshipOrder(): BelongsTo
    {
        return $this->belongsTo(DropshipOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'tunai'    => 'Tunai (Cash)',
            'transfer' => 'Transfer Bank',
            'qris'     => 'QRIS',
            'piutang'  => 'Piutang / Tempo',
            default    => ucfirst($this->payment_method ?? 'Tunai'),
        };
    }

    public static function generatePaymentNumber(int $tenantId): string
    {
        $prefix = 'PAY-DRP-' . date('Ymd') . '-';
        $last = self::where('tenant_id', $tenantId)
            ->where('payment_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('payment_number');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
