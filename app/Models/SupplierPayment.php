<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'supplier_payable_id',
        'supplier_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_source',       // kas_besar | kas_kecil (hanya untuk tunai)
        'reference_number',
        'bank_name',            // nama bank (untuk transfer/giro)
        'account_number',       // no. rekening tujuan
        'account_name',         // nama pemilik rekening
        'notes',
        'created_by',
        'expense_id',           // link ke jurnal kas (setelah diapprove)
        'approval_status',      // pending | approved | rejected
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function payable(): BelongsTo
    {
        return $this->belongsTo(SupplierPayable::class, 'supplier_payable_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /* ------------------------------------------------------------------ */
    /*  Accessors                                                           */
    /* ------------------------------------------------------------------ */

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'transfer' => 'Transfer Bank',
            'cash'     => 'Tunai',
            'giro'     => 'Giro / Cek',
            default    => ucfirst($this->payment_method),
        };
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            'pending'  => 'Menunggu Approval',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => ucfirst($this->approval_status),
        };
    }

    public function getApprovalStatusBadgeAttribute(): string
    {
        return match ($this->approval_status) {
            'pending'  => 'warning text-dark',
            'approved' => 'success',
            'rejected' => 'danger',
            default    => 'secondary',
        };
    }
}
