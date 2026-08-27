<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    protected $fillable = [
        'tenant_id',
        'store_id',
        'order_id',
        'return_sn',
        'return_tracking_number',
        'shipping_provider',
        'reason',
        'status',
        'sla_deadline',
        'refund_amount',
        'is_restocked',
        'inspection_status',
        'inspection_notes',
        'checked_by',
        'replacement_order_id',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'is_restocked' => 'boolean',
        'sla_deadline' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnOrderItem::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function replacementOrder()
    {
        return $this->belongsTo(Order::class, 'replacement_order_id');
    }

    public function getFormattedReasonAttribute()
    {
        $reasonMap = [
            'DIFFERENT_DESCRIPTION' => 'Produk tidak sesuai deskripsi/foto',
            'WRONG_ITEM' => 'Produk yang dikirim salah/tidak sesuai pesanan',
            'DAMAGED_ITEM' => 'Produk rusak/cacat',
            'PHYSICAL_DAMAGE' => 'Kerusakan fisik produk',
            'EMPTY_PACKAGE' => 'Paket kosong/kurang barang',
            'MUTUAL_AGREE' => 'Kesepakatan bersama pembeli & penjual',
            'CHANGE_OF_MIND' => 'Pembeli berubah pikiran',
            'CHANGE_MIND' => 'Pembeli berubah pikiran',
            'ITEM_MISSING' => 'Barang kurang / hilang dalam paket',
            'NOT_RECEIPT' => 'Barang belum diterima oleh pembeli',
            'NOT_RECEIVED' => 'Barang belum diterima oleh pembeli',
            'SP_DIFFERENT_DESCRIPTION' => 'Produk tidak sesuai deskripsi/foto',
            'SP_WRONG_ITEM' => 'Produk yang dikirim salah/tidak sesuai pesanan',
            'SP_DAMAGED_ITEM' => 'Produk rusak/cacat',
            'SP_EMPTY_PACKAGE' => 'Paket kosong/kurang barang',
            'RET_EXPIRED' => 'Pengembalian kedaluwarsa',
        ];

        $key = strtoupper($this->reason ?? '');
        return $reasonMap[$key] ?? $this->reason ?? 'Tidak ada alasan';
    }
}
