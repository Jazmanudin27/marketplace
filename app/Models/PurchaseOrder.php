<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'po_number',
        'po_date',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
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

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft / Rencana',
            'ordered' => 'Dipesan (Ordered)',
            'partially_received' => 'Diterima Sebagian',
            'received' => 'Selesai Diterima',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'ordered' => 'primary',
            'partially_received' => 'warning text-dark',
            'received' => 'success',
            'cancelled' => 'danger',
            default => 'dark',
        };
    }

    public static function generatePoNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'PO-' . $date . '-';
        $last = static::where('po_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('po_number');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
