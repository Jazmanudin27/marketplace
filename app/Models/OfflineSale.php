<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineSale extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'customer_id', 'sale_number', 'status',
        'buyer_name', 'buyer_phone', 'payment_method',
        'total_amount', 'discount_amount', 'grand_total',
        'paid_amount', 'change_amount', 'notes', 'sold_at',
    ];

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total'     => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'change_amount'   => 'decimal:2',
        'sold_at'         => 'datetime',
    ];

    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_METHODS = [
        'tunai'    => 'Tunai',
        'transfer' => 'Transfer Bank',
        'qris'     => 'QRIS',
        'kartu'    => 'Kartu Debit/Kredit',
        'piutang'  => 'Piutang / Bayar Nanti',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfflineSaleItem::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status === self::STATUS_COMPLETED ? 'success' : 'danger';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_COMPLETED ? 'Selesai' : 'Dibatalkan';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    /**
     * Generate nomor penjualan otomatis: OS-YYYYMMDD-XXXX
     */
    public static function generateSaleNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = 'OS-' . $date . '-';
        $last   = static::where('sale_number', 'like', $prefix . '%')
                        ->orderByDesc('id')
                        ->value('sale_number');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Total HPP seluruh item penjualan offline.
     */
    public function getHppTotalAttribute(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            if ($item->masterProduct) {
                $total += (float) $item->masterProduct->cost_price * $item->quantity;
            }
        }
        return $total;
    }

    /**
     * Net Profit = Pendapatan Bersih (Grand Total) - HPP
     */
    public function getNetProfitAttribute(): float
    {
        return (float) $this->grand_total - $this->hpp_total;
    }
}
