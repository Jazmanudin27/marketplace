<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerBalanceTransaction extends Model
{
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'type',
        'amount',
        'description',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'in' ? 'Kredit (Masuk)' : 'Debit (Keluar)';
    }

    public function getTypeBadgeAttribute(): string
    {
        return $this->type === 'in' ? 'success' : 'danger';
    }
}
