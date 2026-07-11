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
        'purchase_order_id',
        'department_id',
        'receipt_number',
        'receipt_date',
        'source',
        'status', // pending, approved, cancelled
        'notes',
        'total_amount',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at'  => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'pembelian' => 'Pembelian',
            'percetakan'=> 'Percetakan',
            'produksi'  => 'Produksi',
            'lain_lain' => 'Lain-lain',
            'direct'    => 'Pembelian Langsung',
            'emergency' => 'Pembelian Darurat',
            'walk_in'   => 'Walk-in / Beli di Toko',
            'po'        => 'Penerimaan PO',
            default     => ucfirst($this->source),
        };
    }

    public function getSourceBadgeAttribute(): string
    {
        return match ($this->source) {
            'pembelian' => 'primary',
            'percetakan'=> 'info',
            'produksi'  => 'warning text-dark',
            'lain_lain' => 'secondary',
            'direct'    => 'primary',
            'emergency' => 'danger',
            'walk_in'   => 'info',
            'po'        => 'success',
            default     => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Menunggu Persetujuan',
            'approved'  => 'Disetujui (Stok Masuk)',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning text-dark',
            'approved'  => 'success',
            'cancelled' => 'danger',
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
