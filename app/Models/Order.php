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
            $allDeducted = true;
            foreach ($this->items as $item) {
                $masterProductId = $item->master_product_id;

                // Fallback: jika di item belum ter-set, coba cari dari MarketplaceProduct
                if (!$masterProductId && $item->marketplace_product_id) {
                    $mp = MarketplaceProduct::find($item->marketplace_product_id);
                    if ($mp && $mp->master_product_id) {
                        $masterProductId = $mp->master_product_id;
                        $item->update(['master_product_id' => $masterProductId]);
                    }
                }

                if ($masterProductId) {
                    $masterProduct = MasterProduct::find($masterProductId);
                    if ($masterProduct) {
                        // Cek apakah pergerakan stok untuk item ini di order ini sudah pernah dicatat
                        $reference = 'Pesanan Masuk: ' . $this->order_marketplace_id;
                        $alreadyDeducted = StockMovement::where('master_product_id', $masterProductId)
                            ->where('reference', $reference)
                            ->exists();

                        if (!$alreadyDeducted) {
                            // Cek jika produk merupakan Pre-Order dan stok fisik di gudang tidak cukup
                            if ($masterProduct->is_preorder && $masterProduct->stock < $item->quantity) {
                                $allDeducted = false;
                                continue;
                            }

                            $masterProduct->recordStockMovement(
                                $item->quantity,
                                'out',
                                $reference,
                                null
                            );
                        }
                    }
                } else {
                    // Ada item yang belum ter-map ke master product
                    $allDeducted = false;
                }
            }
            
            if ($allDeducted && $this->items->count() > 0) {
                $this->update(['is_stock_deducted' => true]);
            }
        }

        // 2. Return stock if cancelled, and not returned yet
        if ($this->order_status === self::STATUS_CANCELLED && !$this->is_stock_returned) {
            $allReturned = true;
            foreach ($this->items as $item) {
                if ($item->master_product_id) {
                    $masterProduct = MasterProduct::find($item->master_product_id);
                    if ($masterProduct) {
                        // Hanya kembalikan jika pernah dipotong (ada stock movement "Pesanan Masuk")
                        $deductionRef = 'Pesanan Masuk: ' . $this->order_marketplace_id;
                        $wasDeducted = StockMovement::where('master_product_id', $item->master_product_id)
                            ->where('reference', $deductionRef)
                            ->exists();

                        if ($wasDeducted) {
                            $reference = 'Pembatalan Pesanan: ' . $this->order_marketplace_id;
                            $alreadyReturned = StockMovement::where('master_product_id', $item->master_product_id)
                                ->where('reference', $reference)
                                ->exists();

                            if (!$alreadyReturned) {
                                $masterProduct->recordStockMovement(
                                    $item->quantity,
                                    'in',
                                    $reference,
                                    null
                                );
                            }
                        }
                    }
                } else {
                    $allReturned = false;
                }
            }
            if ($allReturned && $this->items->count() > 0) {
                $this->update(['is_stock_returned' => true]);
            }
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
