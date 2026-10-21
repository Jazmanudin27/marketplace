<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierConsignmentDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'supplier_consignment_item_id',
        'order_id',
        'order_item_id',
        'quantity',
        'system_stock',
        'actual_stock',
        'difference_qty',
        'scanned_barcode',
        'reason',
        'reference_no',
        'notes',
        'deduction_date',
        'deduct_master_stock',
        'user_id',
    ];

    protected $casts = [
        'quantity'            => 'integer',
        'system_stock'        => 'integer',
        'actual_stock'        => 'integer',
        'difference_qty'      => 'integer',
        'deduction_date'      => 'datetime',
        'deduct_master_stock' => 'boolean',
    ];

    /**
     * Label representasi perbedaan stok opname (selisih fisik vs ERP).
     */
    public function getDifferenceLabelAttribute(): string
    {
        if ($this->difference_qty === null) {
            return '-' . number_format($this->quantity) . ' PCS';
        }
        if ($this->difference_qty < 0) {
            return number_format($this->difference_qty) . ' PCS (Kurang)';
        }
        if ($this->difference_qty > 0) {
            return '+' . number_format($this->difference_qty) . ' PCS (Lebih)';
        }
        return '0 PCS (Sesuai)';
    }

    /**
     * Badge CSS class untuk selisih stok opname.
     */
    public function getDifferenceBadgeClassAttribute(): string
    {
        if ($this->difference_qty === null || $this->difference_qty < 0) {
            return 'bg-danger-subtle text-danger border border-danger-subtle';
        }
        if ($this->difference_qty > 0) {
            return 'bg-info-subtle text-info-emphasis border border-info-subtle';
        }
        return 'bg-success-subtle text-success border border-success-subtle';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(SupplierConsignmentItem::class, 'supplier_consignment_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label representasi teks untuk alasan pengeluaran stok titipan.
     */
    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            'unscanned_order'   => 'Pesanan Terlewat Scan',
            'manual_adjustment' => 'Koreksi Stok Manual',
            'return_supplier'   => 'Retur / Balik ke Supplier',
            'damaged'           => 'Barang Rusak / Hilang',
            'direct_sale'       => 'Penjualan Langsung / Offline',
            default             => 'Lainnya / Manual',
        };
    }

    /**
     * Badge CSS class untuk alasan pengeluaran.
     */
    public function getReasonBadgeClassAttribute(): string
    {
        return match ($this->reason) {
            'unscanned_order'   => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'manual_adjustment' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            'return_supplier'   => 'bg-danger-subtle text-danger border border-danger-subtle',
            'damaged'           => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            'direct_sale'       => 'bg-primary-subtle text-primary border border-primary-subtle',
            default             => 'bg-light text-dark border',
        };
    }
}
