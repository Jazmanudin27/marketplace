<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'department_id',
        'receipt_number',
        'receipt_date',
        'source',
        'notes',
        'total_amount',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'direct'    => 'Pembelian Langsung',
            'emergency' => 'Pembelian Darurat',
            'walk_in'   => 'Walk-in / Beli di Toko',
            default     => ucfirst($this->source),
        };
    }

    public function getSourceBadgeAttribute(): string
    {
        return match ($this->source) {
            'direct'    => 'primary',
            'emergency' => 'danger',
            'walk_in'   => 'info',
            default     => 'secondary',
        };
    }

    public static function generateReceiptNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = 'GR-' . $date . '-';
        $last   = static::where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('receipt_number');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
