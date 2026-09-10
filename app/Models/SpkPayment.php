<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpkPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'spk_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_source',
        'bank_account_id',
        'recipient_name',
        'payment_type',
        'notes',
        'created_by',
        'expense_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Generate unique payment number for SPK installment payment.
     * Format: PAY-SPK-YYYYMMDD-0001
     */
    public static function generatePaymentNumber($tenantId): string
    {
        $datePrefix = 'PAY-SPK-' . date('Ymd') . '-';
        $latest = self::where('tenant_id', $tenantId)
            ->where('payment_number', 'like', "{$datePrefix}%")
            ->orderByDesc('id')
            ->value('payment_number');

        if ($latest) {
            $lastSeq = (int) substr($latest, strlen($datePrefix));
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $datePrefix . $seq;
    }
}
