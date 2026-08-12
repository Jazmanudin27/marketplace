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
        'fee_platform_amount',
        'fee_free_shipping_amount',
        'fee_service_amount',
        'fee_promo_amount',
        'fee_other_amount',
        'net_amount',
        'courier',
        'tracking_number',
        'order_date',
        'completed_at',
        'ship_before_date',
        'packed_at',
        'is_stock_deducted',
        'is_stock_returned',
        'financial_breakdown',
        'utm_campaign',
        'utm_source',
        'ads_campaign_id',
        'capi_sent_at',
        'tiktok_creator_name',
        'tiktok_creator_id',
        'affiliate_commission',
        'tiktok_live_session_id',
        'voucher_code',
        'shopee_utm_keyword',
        'is_dropship',
        'dropshipper_name',
        'dropshipper_phone',
        'recon_status',
        'recon_notes',
        'cancel_reason',
        'cancelled_by',
        'is_printed',
        'printed_at',
        'approved_warehouse_at',
        'approved_warehouse_by',
        'approved_production_at',
        'approved_production_by',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'completed_at' => 'datetime',
        'ship_before_date' => 'datetime',
        'packed_at' => 'datetime',
        'printed_at' => 'datetime',
        'is_printed' => 'boolean',
        'approved_warehouse_at' => 'datetime',
        'approved_production_at' => 'datetime',
        'financial_breakdown' => 'array',
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'marketplace_fee' => 'decimal:2',
        'fee_platform_amount' => 'decimal:2',
        'fee_free_shipping_amount' => 'decimal:2',
        'fee_service_amount' => 'decimal:2',
        'fee_promo_amount' => 'decimal:2',
        'fee_other_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_stock_deducted' => 'boolean',
        'is_stock_returned' => 'boolean',
        'is_dropship' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($order) {
            if (in_array($order->order_status, [self::STATUS_COMPLETED, self::STATUS_DELIVERED, 'SELESAI', 'FINISHED']) && empty($order->completed_at)) {
                $order->completed_at = now();
            }

            // Sync 5 fee breakdown columns & marketplace_fee & net_amount
            $details = $order->fee_breakdown_details;
            $order->fee_platform_amount = abs($details['platform_fee'] ?? 0);
            $order->fee_free_shipping_amount = abs($details['free_shipping'] ?? 0);
            $order->fee_service_amount = abs($details['service_fee'] ?? 0);
            $order->fee_promo_amount = abs($details['promo_fee'] ?? 0);
            $order->fee_other_amount = abs($details['other_fee'] ?? 0);

            $totalFee = abs($details['total_fee'] ?? 0);
            if ($totalFee > 0) {
                $order->marketplace_fee = $totalFee;
                if (empty($order->financial_breakdown['escrow_amount'])) {
                    $order->net_amount = max(0.0, (float) $order->total_amount - $totalFee);
                }
            }
        });
    }

    // Status constants
    const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    const STATUS_UNPAID = 'UNPAID';
    const STATUS_READY_TO_SHIP = 'READY_TO_SHIP';
    const STATUS_SHIPPED = 'SHIPPED';
    const STATUS_DELIVERED = 'DELIVERED';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_RETURN = 'RETURN';

    public function approvedWarehouseBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_warehouse_by');
    }

    public function approvedProductionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_production_by');
    }

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

    public function adsCampaign(): BelongsTo
    {
        return $this->belongsTo(AdsCampaign::class, 'ads_campaign_id');
    }

    public function tiktokLiveSession(): BelongsTo
    {
        return $this->belongsTo(TiktokLiveSession::class, 'tiktok_live_session_id');
    }

    public function shopeeLiveSession(): BelongsTo
    {
        return $this->belongsTo(ShopeeLiveSession::class, 'shopee_live_session_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->order_status) {
            self::STATUS_PENDING_APPROVAL => 'warning',
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
        $this->load('items');

        // 1. Deduct stock if not already deducted and not cancelled
        if (!$this->is_stock_deducted && $this->order_status !== self::STATUS_CANCELLED) {
            $allDeducted = true;
            foreach ($this->items as $item) {
                $masterProductId = $item->master_product_id;

                // Fallback 1: Jika di item belum ter-set, coba cari dari MarketplaceProduct
                if (!$masterProductId && $item->marketplace_product_id) {
                    $mp = MarketplaceProduct::find($item->marketplace_product_id);
                    if ($mp && $mp->master_product_id) {
                        $masterProductId = $mp->master_product_id;
                        $item->update(['master_product_id' => $masterProductId]);
                    }
                }

                // Fallback 2: Jika masih belum ter-set, cari ke MasterProduct berdasarkan SKU di OrderItem
                if (!$masterProductId && !empty($item->sku)) {
                    $skuClean = trim($item->sku);
                    $mpDirect = MasterProduct::where('tenant_id', $this->tenant_id)
                        ->where(function ($q) use ($skuClean) {
                            $q->where('sku', $skuClean)
                              ->orWhereRaw('LOWER(sku) = LOWER(?)', [$skuClean]);
                        })->first();
                    if ($mpDirect) {
                        $masterProductId = $mpDirect->id;
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
                                null,
                                $this->order_date ? $this->order_date->format('Y-m-d H:i:s') : null
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
                        // Kembalikan jika pesanan pernah memotong stok (is_stock_deducted = true) atau ada catatan movement pesanan masuk
                        $wasDeducted = $this->is_stock_deducted || StockMovement::where('master_product_id', $item->master_product_id)
                            ->where('reference', 'like', '%' . $this->order_marketplace_id . '%')
                            ->where('type', 'out')
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
     * Get 5 Shopee/TikTok Fee Breakdown Components:
     * - platform_fee (Biaya Platform)
     * - free_shipping (Biaya Gratis Ongkir)
     * - service_fee (Biaya Layanan)
     * - promo_fee (Biaya Promosi)
     * - other_fee (Biaya Lainnya)
     */
    public function getFeeBreakdownDetailsAttribute(): array
    {
        $fb = $this->financial_breakdown ?? [];

        // 1. Biaya Platform (Commission Fee + Seller Order Processing Fee)
        $commissionFee = (float) ($fb['commission_fee'] ?? $fb['platform_fee'] ?? $fb['net_platform_commission'] ?? 0);
        $processingFee = (float) ($fb['seller_order_processing_fee'] ?? 0);
        $platformFee = $commissionFee + $processingFee;

        // 2. Biaya Gratis Ongkir (Service Fee / Free Shipping Fee / Growth Xtra Fee)
        $freeShipping = (float) (
            $fb['free_shipping_fee'] 
            ?? $fb['service_fee'] 
            ?? $fb['shopee_shipping_rebate_fee'] 
            ?? $fb['growth_xtra_fee'] 
            ?? 0
        );

        // 3. Biaya Layanan (Seller Transaction Fee / Buyer Transaction Fee / Processing Fee)
        $serviceFee = (float) (
            $fb['seller_transaction_fee'] 
            ?? $fb['buyer_transaction_fee'] 
            ?? $fb['order_processing_fee'] 
            ?? $fb['preorder_service_fee'] 
            ?? 0
        );

        // 4. Biaya Promosi (Order AMS Commission / Voucher Seller / Seller Discount)
        $promoFee = (float) (
            $fb['order_ams_commission_fee'] 
            ?? $fb['ams_commission_fee'] 
            ?? $fb['voucher_from_seller'] 
            ?? $fb['seller_discount'] 
            ?? $fb['voucher_seller'] 
            ?? 0
        );

        // 5. Biaya Lainnya (Shipping Seller Protection / Coins / Tax / Adjustments)
        $otherFee = (float) (
            $fb['shipping_seller_protection_fee_amount'] 
            ?? $fb['other_fees'] 
            ?? $fb['coins'] 
            ?? $fb['ddu_custom_tax_fee'] 
            ?? $fb['dynamic_commission'] 
            ?? 0
        );

        // Fallback: If marketplace_fee is filled on Order model but fb has no breakdown, assign to platform_fee
        $rawMarketplaceFee = (float) ($this->attributes['marketplace_fee'] ?? 0);
        if ($platformFee == 0 && $freeShipping == 0 && $serviceFee == 0 && $promoFee == 0 && $otherFee == 0 && $rawMarketplaceFee > 0) {
            $platformFee = $rawMarketplaceFee;
        }

        return [
            'platform_fee'   => $platformFee > 0 ? -$platformFee : ($platformFee < 0 ? $platformFee : 0),
            'free_shipping'  => $freeShipping > 0 ? -$freeShipping : ($freeShipping < 0 ? $freeShipping : 0),
            'service_fee'    => $serviceFee > 0 ? -$serviceFee : ($serviceFee < 0 ? $serviceFee : 0),
            'promo_fee'      => $promoFee > 0 ? -$promoFee : ($promoFee < 0 ? $promoFee : 0),
            'other_fee'      => $otherFee > 0 ? -$otherFee : ($otherFee < 0 ? $otherFee : 0),
            'total_fee'      => -($platformFee + $freeShipping + $serviceFee + $promoFee + $otherFee),
        ];
    }

    /**
     * Potongan Biaya Marketplace Presisi.
     * Mengambil total rincian potongan jika tersedia di financial_breakdown.
     */
    public function getMarketplaceFeeAttribute($value): float
    {
        $details = $this->fee_breakdown_details;
        $totalFee = abs($details['total_fee'] ?? 0);
        if ($totalFee > 0) {
            return (float) $totalFee;
        }

        $val = (float) $value;
        if ($val > 0) {
            return $val;
        }

        return round((float) $this->total_amount * 0.05);
    }

    /**
     * Pendapatan Bersih (Escrow).
     * Jika ada financial_breakdown['escrow_amount'] > 0, gunakan escrow_amount resmi marketplace.
     * Jika ada rincian 5 komponen biaya, hitung (total_amount - total_fee).
     * Fallback: hitung estimasi (total_amount - discount_amount - marketplace_fee).
     */
    public function getNetAmountAttribute($value): float
    {
        $fb = $this->financial_breakdown;

        if (!empty($fb['escrow_amount']) && (float) $fb['escrow_amount'] > 0) {
            return (float) $fb['escrow_amount'];
        }

        $details = $this->fee_breakdown_details;
        $totalFee = abs($details['total_fee'] ?? 0);
        if ($totalFee > 0) {
            return max(0.0, (float) $this->total_amount - $totalFee);
        }

        if (!empty($fb['net_platform_commission']) || !empty($fb['growth_xtra_fee']) || !empty($fb['preorder_service_fee'])) {
            $subtotal = (float) ($fb['subtotal_after_seller_discounts'] ?? ($this->total_amount - $this->discount_amount));
            $fees = (float) ($fb['net_platform_commission'] ?? 0)
                  + (float) ($fb['preorder_service_fee'] ?? 0)
                  + (float) ($fb['dynamic_commission'] ?? 0)
                  + (float) ($fb['growth_xtra_fee'] ?? 0)
                  + (float) ($fb['order_processing_fee'] ?? 0);
            if ($fees > 0) {
                return max(0.0, $subtotal - $fees);
            }
        }

        $val = (float) $value;
        if ($val > 0) {
            return $val;
        }

        $total = (float) $this->total_amount;
        $disc = (float) $this->discount_amount;
        $fee = (float) $this->marketplace_fee;

        $estimated = $total - $disc - $fee;
        return max(0.0, $estimated);
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
    /**
     * Apakah pesanan mendekati / sudah melewati batas pengiriman.
     * true jika ship_before_date ada dan <= 24 jam dari sekarang (termasuk sudah lewat).
     */
    public function getIsShipUrgentAttribute(): bool
    {
        if (!$this->ship_before_date) {
            return false;
        }
        return $this->ship_before_date->isFuture() && now()->diffInHours($this->ship_before_date) <= 24;
    }

    /**
     * Apakah pesanan sudah melewati batas pengiriman.
     */
    public function getIsShipOverdueAttribute(): bool
    {
        return $this->ship_before_date && $this->ship_before_date->isPast();
    }

    /**
     * Scope: pesanan READY_TO_SHIP dengan deadline dalam 24 jam atau sudah lewat.
     */
    public function scopeDeadlineUrgent($query)
    {
        return $query
            ->where('order_status', self::STATUS_READY_TO_SHIP)
            ->whereNotNull('ship_before_date')
            ->where('ship_before_date', '<=', now()->addHours(24));
    }

    public function spks(): HasMany
    {
        return $this->hasMany(Spk::class);
    }

    public function hasPreorderItems(): bool
    {
        return $this->items->contains(function ($item) {
            // 1. Ambil Master Product langsung atau dari relation MarketplaceProduct
            $master = $item->masterProduct ?: ($item->marketplaceProduct ? $item->marketplaceProduct->masterProduct : null);

            // 2. Jika belum terhubung via relasi, cari Master Product berdasarkan SKU item (fleksibel case & trim)
            if (!$master && !empty($item->sku)) {
                $skuClean = trim($item->sku);
                $master = MasterProduct::where('tenant_id', $this->tenant_id)
                    ->where(function ($q) use ($skuClean) {
                        $q->where('sku', $skuClean)
                          ->orWhereRaw('LOWER(sku) = LOWER(?)', [$skuClean]);
                    })
                    ->first();
            }

            // 3. JIKA MASTER PRODUK DITEMUKAN: ACUAN MASTER PRODUK DIJADIKAN KEPUTUSAN MUTLAK!
            if ($master) {
                return (bool) $master->is_preorder;
            }

            // 4. Hanya jika produk tidak ada di Master Produk ERP, gunakan fallback dari Marketplace
            if ($item->marketplaceProduct) {
                return (bool) $item->marketplaceProduct->is_pre_order;
            }

            return false;
        });
    }
}
