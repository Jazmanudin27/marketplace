<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPayable extends Model
{
    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'goods_receipt_id',
        'reference_number',
        'payable_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payable_date'  => 'date',
        'due_date'      => 'date',
        'total_amount'  => 'decimal:2',
        'paid_amount'   => 'decimal:2',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ------------------------------------------------------------------ */
    /*  Computed / Accessors                                                */
    /* ------------------------------------------------------------------ */

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'unpaid'  => 'Belum Dibayar',
            'partial' => 'Dibayar Sebagian',
            'paid'    => 'Lunas',
            default   => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'unpaid'  => 'danger',
            'partial' => 'warning text-dark',
            'paid'    => 'success',
            default   => 'secondary',
        };
    }

    /* ------------------------------------------------------------------ */
    /*  Static helpers                                                      */
    /* ------------------------------------------------------------------ */

    public static function generateReferenceNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = 'HUT-' . $date . '-';
        $last   = static::where('reference_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('reference_number');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
