<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'supplier_id',
        'return_number',
        'return_date',
        'reason',
        'status',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'    => 'Draft',
            'approved' => 'Disetujui',
            'sent'     => 'Sudah Dikirim ke Supplier',
            default    => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'    => 'secondary',
            'approved' => 'warning text-dark',
            'sent'     => 'success',
            default    => 'dark',
        };
    }

    public static function generateReturnNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = 'RTN-' . $date . '-';
        $last   = static::where('return_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('return_number');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
