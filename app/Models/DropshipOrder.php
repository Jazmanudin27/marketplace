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
        'paid_amount',
        'payment_status', // 'unpaid', 'partial', 'paid'
        'payment_method',
        'total_qty',
        'status', // 'completed', 'pending', 'cancelled'
    ];

    protected $casts = [
        'order_date'   => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
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

    public function payments(): HasMany
    {
        return $this->hasMany(DropshipOrderPayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public function getIsPaidAttribute(): bool
    {
        return (float) $this->paid_amount >= (float) $this->total_amount && (float) $this->total_amount > 0;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if ($this->is_paid) {
            return 'Lunas';
        }
        if ((float) $this->paid_amount > 0) {
            return 'Dicicil';
        }
        return 'Belum Lunas';
    }

    public function getPaymentStatusBadgeAttribute(): string
    {
        if ($this->is_paid) {
            return 'success';
        }
        if ((float) $this->paid_amount > 0) {
            return 'warning text-dark';
        }
        return 'danger';
    }

    /**
     * Hitung ulang total pembayaran dan update status lunas.
     */
    public function recalculatePaymentStatus(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $totalAmount = (float) $this->total_amount;

        $status = 'unpaid';
        if ($totalPaid >= $totalAmount && $totalAmount > 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        $lastPayment = $this->payments()->latest('payment_date')->first();

        $this->update([
            'paid_amount'    => $totalPaid,
            'payment_status' => $status,
            'payment_method' => $lastPayment ? $lastPayment->payment_method : $this->payment_method,
        ]);
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
