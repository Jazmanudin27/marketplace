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
        'paid_at',
        'payment_method',
        'buyer_email',
        'buyer_message',
        'seller_note',
        'package_id',
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
        static::creating(function ($order) {
            if (!empty($order->order_marketplace_id)) {
                $order->order_marketplace_id = trim((string)$order->order_marketplace_id);
            }
        });

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

            // KUNCI PRESISI 100%: 
            // 1. Omset (total_amount): Isi dari subtotal item jika total_amount di model masih kosong/0
            if ((float)$order->total_amount <= 0 && $order->relationLoaded('items') && $order->items->count() > 0) {
                $itemsSubtotal = (float) $order->items->sum('total_price');
                if ($itemsSubtotal > 0) {
                    $order->total_amount = $itemsSubtotal;
                }
            }

            $totalFee = abs($details['total_fee'] ?? 0);
            $refundAmt = $order->refund_amount;

            // 2. Biaya Admin Marketplace
            if ($totalFee > 0) {
                $order->marketplace_fee = $totalFee;
            }

            // 3. Omset Bersih / Net Amount = Omset Kotor - Total Refund - Total Potongan Marketplace (Presisi 100%)
            if ($refundAmt >= (float)$order->total_amount && (float)$order->total_amount > 0) {
                $order->net_amount = 0.0;
            } else {
                $order->net_amount = max(0.0, (float)$order->total_amount - $refundAmt - (float)$order->marketplace_fee);
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

    public function returnOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReturnOrder::class);
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
                        ->where('sku', $skuClean)
                        ->first();
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
                            ->where('reference', 'LIKE', '%' . $this->order_marketplace_id . '%')
                            ->where('type', 'out')
                            ->exists();

                        if (!$alreadyDeducted) {
                            // Cek jika produk merupakan Pre-Order dan stok fisik di gudang tidak cukup
                            if ($masterProduct->is_preorder && $masterProduct->stock < $item->quantity) {
                                $allDeducted = false;
                                continue;
                            }

                            $customOrderDate = null;
                            if ($this->order_date) {
                                try {
                                    $customOrderDate = \Carbon\Carbon::parse($this->order_date)->format('Y-m-d H:i:s');
                                } catch (\Exception $exDate) {
                                    $customOrderDate = (string) $this->order_date;
                                }
                            }

                            $masterProduct->recordStockMovement(
                                $item->quantity,
                                'out',
                                $reference,
                                null,
                                $customOrderDate
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
     * Accesor untuk mendeteksi nominal refund/retur secara akurat
     * Mendukung relasi returnOrder, top-level financial_breakdown, serta sub-array statement_transactions TikTok API.
     */
    public function getRefundAmountAttribute(): float
    {
        // 1. Cek relasi returnOrder jika terhubung
        if ($this->relationLoaded('returnOrder') && $this->returnOrder) {
            $rAmt = (float) $this->returnOrder->refund_amount;
            if ($rAmt > 0) return $rAmt;
        }

        $fb = $this->financial_breakdown ?? [];

        // 2. Cek field top-level financial_breakdown
        $keys = ['customer_refund_amount', 'gross_sales_refund_amount', 'seller_return_refund', 'buyer_return_refund_amount', 'refund_amount', 'return_amount', 'customer_order_refund_amount', 'total_adjustment_amount'];
        foreach ($keys as $k) {
            if (!empty($fb[$k]) && (float)$fb[$k] != 0) {
                return abs((float)$fb[$k]);
            }
        }

        // 3. Cek sub-array statement_transactions TikTok API
        $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? $fb['transactions'] ?? [];
        if (is_array($stmtList)) {
            foreach ($stmtList as $st) {
                if (!is_array($st)) continue;
                foreach ($keys as $k) {
                    if (!empty($st[$k]) && (float)$st[$k] != 0) {
                        return abs((float)$st[$k]);
                    }
                }
            }
        }

        // 4. Fallback: jika status pesanan bernilai RETURNED/REFUNDED/RETURN
        if (in_array(strtoupper($this->order_status), ['RETURNED', 'REFUNDED', 'RETURN', 'CANCELLED'])) {
            return (float) $this->total_amount;
        }

        return 0.0;
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
        $totalGross = (float) $this->total_amount;
        $sellerReturnRefund = $this->refund_amount;

        // Jika pesanan direfund penuh, biaya admin reguler = 0 (hanya sisa ongkir retur jika ada) dan tidak ada breakdown resmi
        if ($sellerReturnRefund >= $totalGross && $totalGross > 0 && empty($fb)) {
            $returnShipping = abs((float) ($fb['return_shipping_fee'] ?? $fb['actual_return_shipping_fee_amount'] ?? 0));
            return [
                'platform_fee'   => 0.0,
                'free_shipping'  => 0.0,
                'service_fee'    => 0.0,
                'promo_fee'      => 0.0,
                'other_fee'      => $returnShipping > 0 ? -$returnShipping : 0.0,
                'total_fee'      => $returnShipping > 0 ? -$returnShipping : 0.0,
            ];
        }

        $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? $fb['transactions'] ?? [];
        $st0 = (is_array($stmtList) && !empty($stmtList[0]) && is_array($stmtList[0])) ? $stmtList[0] : [];

        // 1. Biaya Platform Komisi (Shopee: commission_fee | TikTok: net_platform_commission / platform_commission / platform_commission_amount)
        $platformFee = abs((float) ($fb['commission_fee'] ?? $fb['net_platform_commission'] ?? $fb['platform_commission'] ?? $fb['platform_fee'] ?? $st0['platform_commission_amount'] ?? 0));

        // 2. Biaya Gratis Ongkir & Program XTRA (Shopee: service_fee | TikTok: growth_xtra_fee)
        $freeShipping = abs((float) ($fb['growth_xtra_fee'] ?? $fb['free_shipping_fee'] ?? $fb['shopee_shipping_rebate_fee'] ?? $st0['growth_xtra_fee_amount'] ?? 0));
        if ($freeShipping == 0 && isset($fb['service_fee']) && !isset($fb['net_platform_commission']) && !isset($fb['platform_commission']) && empty($st0['platform_commission_amount'])) {
            $freeShipping = abs((float) $fb['service_fee']);
        }

        // 3. Biaya Layanan & Penanganan (Shopee: seller_order_processing_fee | TikTok: preorder_service_fee + order_processing_fee + transaction_fee_amount)
        $serviceFee  = abs((float) ($fb['seller_order_processing_fee'] ?? 0))
                    + abs((float) ($fb['preorder_service_fee'] ?? $fb['preorder_fee'] ?? $st0['preorder_service_fee_amount'] ?? 0))
                    + abs((float) ($fb['order_processing_fee'] ?? $st0['transaction_fee_amount'] ?? 0));
        if (isset($fb['net_platform_commission']) && isset($fb['service_fee']) && $freeShipping == 0 && (float)$fb['service_fee'] != (float)($this->attributes['marketplace_fee'] ?? 0)) {
            $serviceFee += abs((float)$fb['service_fee']);
        }

        // 4. Biaya Promosi Seller (Hanya potongan koin/cashback/ams/affiliate yang merupakan biaya promosi)
        $promoFee    = abs((float) ($fb['seller_coin_cash_back'] ?? 0))
                    + abs((float) ($fb['order_ams_commission_fee'] ?? $fb['ams_commission_fee'] ?? 0))
                    + abs((float) ($fb['dynamic_commission'] ?? $fb['affiliate_commission'] ?? $st0['affiliate_commission_amount'] ?? $st0['dynamic_commission_amount'] ?? 0));

        // 5. Biaya Lainnya (Pajak, Selisih Ongkir, Asuransi, Penyesuaian/Adjustment)
        $actualShipping = abs((float) ($fb['actual_shipping_fee'] ?? $st0['actual_shipping_fee_amount'] ?? $st0['shipping_cost_amount'] ?? 0));
        $buyerPaidShipping = abs((float) ($fb['buyer_paid_shipping_fee'] ?? $fb['shipping_fee_paid_by_buyer'] ?? $st0['customer_paid_shipping_fee_amount'] ?? 0));
        $shopeeRebate = abs((float) ($fb['shopee_shipping_rebate'] ?? $fb['shipping_fee_subsidy'] ?? $st0['platform_shipping_fee_discount_amount'] ?? 0));
        
        $shippingAdjustment = abs((float) ($fb['shipping_fee_adjustment'] ?? 0));
        if ($shippingAdjustment <= 0 && $actualShipping > ($buyerPaidShipping + $shopeeRebate) && ($buyerPaidShipping + $shopeeRebate) > 0) {
            $shippingAdjustment = max(0.0, $actualShipping - ($buyerPaidShipping + $shopeeRebate));
        }

        $otherFee    = abs((float) ($fb['seller_transaction_fee'] ?? $fb['transaction_fee'] ?? 0))
                    + $shippingAdjustment
                    + abs((float) ($fb['shipping_seller_protection_fee_amount'] ?? $fb['delivery_seller_protection_fee_premium_amount'] ?? 0))
                    + abs((float) ($fb['withholding_tax'] ?? $fb['escrow_tax'] ?? $fb['vat'] ?? $fb['buyer_tax_amount'] ?? 0))
                    + abs((float) ($fb['return_shipping_fee'] ?? $fb['return_shipping_fee_amount'] ?? 0));

        // Jika ada adjustment eksplisit yang bukan pembatalan penuh
        $refundOrAdj = abs((float) ($fb['total_adjustment_amount'] ?? $fb['adjustment_amount'] ?? $st0['adjustment_amount'] ?? 0));
        if ($refundOrAdj > 0 && $refundOrAdj < $totalGross) {
            $otherFee += $refundOrAdj;
        }

        $totalFee = $platformFee + $freeShipping + $serviceFee + $promoFee + $otherFee;

        // Jika 5 rincian spesifik bernilai 0 tapi ada fee_amount mentah dari statement API TikTok
        if ($totalFee == 0 && !empty($st0['fee_amount'])) {
            $stmtFee = abs((float)$st0['fee_amount']);
            if ($stmtFee > 0) {
                $totalFee = $stmtFee;
                $otherFee = $stmtFee;
            }
        }

        // 🔒 PROTEKSI KETAT: Fee tidak boleh melebihi (Total Omset - Dana Cair Escrow API)
        $escrowAmt = (float) ($fb['escrow_amount'] ?? $fb['settlement_amount'] ?? $st0['settlement_amount'] ?? $fb['seller_settlement_amount'] ?? 0);

        if ($escrowAmt > 0 && $totalGross > $escrowAmt) {
            $actualMaxFee = $totalGross - $escrowAmt;
            if ($totalFee > $actualMaxFee) {
                $diffOver = $totalFee - $actualMaxFee;
                $otherFee = max(0.0, $otherFee - $diffOver);
                $totalFee = $actualMaxFee;
            }
        } elseif ($totalFee >= $totalGross && $totalGross > 0 && $escrowAmt == 0 && $sellerReturnRefund == 0) {
            $store = $this->store;
            $chCode = strtolower($store->channel->code ?? '');
            $isTiktok = (in_array($chCode, ['tiktok', 'tiktok_shop', 'tokopedia']) || ($store->channel_id ?? 0) == 3);
            $totalFee = round($totalGross * ($isTiktok ? 0.085 : 0.095));
            $otherFee = max(0.0, $totalFee - ($platformFee + $freeShipping + $serviceFee + $promoFee));
        }

        // Fallback: Jika rincian breakdown di financial_breakdown belum ada namun marketplace_fee di model diisi
        $rawMarketplaceFee = (float) ($this->attributes['marketplace_fee'] ?? 0);
        if (empty($this->financial_breakdown) && $totalFee == 0 && $rawMarketplaceFee > 0) {
            $platformFee = $rawMarketplaceFee;
            $totalFee = $rawMarketplaceFee;
        }

        return [
            'platform_fee'   => $platformFee > 0 ? -$platformFee : ($platformFee < 0 ? $platformFee : 0),
            'free_shipping'  => $freeShipping > 0 ? -$freeShipping : ($freeShipping < 0 ? $freeShipping : 0),
            'service_fee'    => $serviceFee > 0 ? -$serviceFee : ($serviceFee < 0 ? $serviceFee : 0),
            'promo_fee'      => $promoFee > 0 ? -$promoFee : ($promoFee < 0 ? $promoFee : 0),
            'other_fee'      => $otherFee > 0 ? -$otherFee : ($otherFee < 0 ? $otherFee : 0),
            'total_fee'      => -$totalFee,
        ];
    }

    /**
     * Potongan Biaya Marketplace Presisi.
     * Mengambil total rincian potongan jika tersedia di financial_breakdown.
     */
    public function getMarketplaceFeeAttribute($value): float
    {
        if (!empty($this->financial_breakdown)) {
            $details = $this->fee_breakdown_details;
            $calcFee = (float) abs($details['total_fee'] ?? 0);
            if ($calcFee > 0) {
                return $calcFee;
            }
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
        if (is_string($fb)) {
            $fb = json_decode($fb, true);
        }
        $refundDeduction = $this->refund_amount;

        $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? $fb['transactions'] ?? [];
        $st0 = (is_array($stmtList) && !empty($stmtList[0]) && is_array($stmtList[0])) ? $stmtList[0] : [];

        // Jika ada escrow_amount_after_adjustment resmi dari API (sudah terpotong penyesuaian)
        if (isset($fb['escrow_amount_after_adjustment'])) {
            return (float) $fb['escrow_amount_after_adjustment'];
        }

        $escrow = 0.0;
        $hasEscrowVal = false;
        if (isset($fb['escrow_amount']) && (float)$fb['escrow_amount'] > 0) {
            $escrow = (float) $fb['escrow_amount'];
            $hasEscrowVal = true;
        } elseif (isset($fb['settlement_amount']) && (float)$fb['settlement_amount'] > 0) {
            $escrow = (float) $fb['settlement_amount'];
            $hasEscrowVal = true;
        } elseif (isset($st0['settlement_amount']) && (float)$st0['settlement_amount'] > 0) {
            $escrow = (float) $st0['settlement_amount'];
            $hasEscrowVal = true;
        } elseif (isset($fb['seller_settlement_amount']) && (float)$fb['seller_settlement_amount'] > 0) {
            $escrow = (float) $fb['seller_settlement_amount'];
            $hasEscrowVal = true;
        }

        if ($hasEscrowVal) {
            return $escrow - $refundDeduction;
        }

        $details = $this->fee_breakdown_details;
        $totalFee = abs($details['total_fee'] ?? 0);
        if ($totalFee > 0) {
            return max(0.0, (float) $this->total_amount - $refundDeduction - $totalFee);
        }

        if (!empty($fb['net_platform_commission']) || !empty($fb['growth_xtra_fee']) || !empty($fb['preorder_service_fee'])) {
            $subtotal = (float) ($fb['subtotal_after_seller_discounts'] ?? ($this->total_amount - $this->discount_amount));
            $fees = (float) ($fb['net_platform_commission'] ?? 0)
                  + (float) ($fb['preorder_service_fee'] ?? 0)
                  + (float) ($fb['dynamic_commission'] ?? 0)
                  + (float) ($fb['growth_xtra_fee'] ?? 0)
                  + (float) ($fb['order_processing_fee'] ?? 0);
            if ($fees > 0) {
                return max(0.0, $subtotal - $refundDeduction - $fees);
            }
        }

        $val = (float) $value;
        if ($val > 0) {
            return max(0.0, $val - $refundDeduction);
        }

        $total = (float) $this->total_amount;
        $fee = (float) $this->marketplace_fee;

        $estimated = $total - $refundDeduction - $fee;
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
     * Apakah pesanan mendekati batas pengiriman (kurang dari 24 jam).
     * Hanya berlaku untuk pesanan yang BELUM dikirim/selesai.
     */
    public function getIsShipUrgentAttribute(): bool
    {
        if (!$this->ship_before_date || in_array($this->order_status, ['SHIPPED', 'DELIVERED', 'COMPLETED', 'FINISHED', 'CANCELLED', 'SELESAI', 'BATAL', 'IN_CANCEL'])) {
            return false;
        }
        return $this->ship_before_date->isFuture() && now()->diffInHours($this->ship_before_date) <= 24;
    }

    /**
     * Apakah pesanan sudah melewati batas pengiriman.
     * Hanya berlaku untuk pesanan yang BELUM dikirim/selesai (misal READY_TO_SHIP, UNPAID).
     */
    public function getIsShipOverdueAttribute(): bool
    {
        if (!$this->ship_before_date || in_array($this->order_status, ['SHIPPED', 'DELIVERED', 'COMPLETED', 'FINISHED', 'CANCELLED', 'SELESAI', 'BATAL', 'IN_CANCEL'])) {
            return false;
        }
        return $this->ship_before_date->isPast();
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
                    ->where('sku', $skuClean)
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
