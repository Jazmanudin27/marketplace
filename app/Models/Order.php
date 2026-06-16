<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'tenant_id',
        'store_id',
        'customer_id',
        'order_marketplace_id',
        'invoice_number',
        'order_status',
        'packing_status',
        'buyer_name',
        'buyer_phone',
        'shipping_address',
        'total_amount',
        'shipping_fee',
        'discount_amount',
        'marketplace_fee',
        'net_amount',
        'courier',
        'tracking_number',
        'order_date',
        'packed_at',
        'is_stock_deducted',
        'is_stock_returned',
        'financial_breakdown',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'packed_at' => 'datetime',
        'financial_breakdown' => 'array',
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'marketplace_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_stock_deducted' => 'boolean',
        'is_stock_returned' => 'boolean',
    ];

    // Status constants
    const STATUS_UNPAID = 'UNPAID';
    const STATUS_READY_TO_SHIP = 'READY_TO_SHIP';
    const STATUS_SHIPPED = 'SHIPPED';
    const STATUS_DELIVERED = 'DELIVERED';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_RETURN = 'RETURN';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->order_status) {
            self::STATUS_UNPAID => 'warning',
            self::STATUS_READY_TO_SHIP => 'primary',
            self::STATUS_SHIPPED => 'info',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_RETURN => 'secondary',
            default => 'dark',
        };
    }

    public function processStockDeduction(): void
    {
        // 1. Deduct stock if not already deducted and not cancelled
        if (!$this->is_stock_deducted && $this->order_status !== self::STATUS_CANCELLED) {
            foreach ($this->items as $item) {
                if ($item->master_product_id) {
                    $masterProduct = MasterProduct::find($item->master_product_id);
                    if ($masterProduct) {
                        $masterProduct->recordStockMovement(
                            $item->quantity,
                            'out',
                            'Pesanan Masuk: ' . $this->order_marketplace_id,
                            null
                        );
                    }
                }
            }
            $this->update(['is_stock_deducted' => true]);
        }

        // 2. Return stock if cancelled, and it was previously deducted, and not returned yet
        if ($this->order_status === self::STATUS_CANCELLED && $this->is_stock_deducted && !$this->is_stock_returned) {
            foreach ($this->items as $item) {
                if ($item->master_product_id) {
                    $masterProduct = MasterProduct::find($item->master_product_id);
                    if ($masterProduct) {
                        $masterProduct->recordStockMovement(
                            $item->quantity,
                            'in',
                            'Pembatalan Pesanan: ' . $this->order_marketplace_id,
                            null
                        );
                    }
                }
            }
            $this->update(['is_stock_returned' => true]);
        }
    }

    /**
     * Total HPP seluruh item pesanan.
     * Prioritas: gunakan snapshot hpp_subtotal jika tersedia.
     * Fallback: hitung dari cost_price masterProduct saat ini.
     */
    public function getHppTotalAttribute(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            if ($item->hpp_subtotal > 0) {
                $total += (float) $item->hpp_subtotal;
            } elseif ($item->master_product_id) {
                $mp = $item->masterProduct;
                if ($mp) {
                    $total += (float) $mp->cost_price * $item->quantity;
                }
            }
        }
        return $total;
    }

    /**
     * Net Profit = Pendapatan Bersih (Escrow) - HPP
     */
    public function getNetProfitAttribute(): float
    {
        return (float) $this->net_amount - $this->hpp_total;
    }

    /**
     * Margin Profit dalam persen.
     */
    public function getProfitMarginAttribute(): float
    {
        if ((float) $this->net_amount <= 0) {
            return 0.0;
        }
        return round(($this->net_profit / (float) $this->net_amount) * 100, 2);
    }
}
