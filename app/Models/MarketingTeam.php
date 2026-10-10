<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketingTeam extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'target_qty',
        'reward_per_qty',
        'target_omset',
        'commission_type',
        'commission_rate',
        'reward_fixed_nominal',
        'period_month',
        'period_year',
        'date_from',
        'date_to',
        'is_active',
    ];

    protected $casts = [
        'target_qty' => 'integer',
        'reward_per_qty' => 'float',
        'target_omset' => 'float',
        'commission_type' => 'string',
        'commission_rate' => 'float',
        'reward_fixed_nominal' => 'float',
        'period_month' => 'integer',
        'period_year' => 'integer',
        'date_from' => 'date',
        'date_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'marketing_team_stores', 'marketing_team_id', 'store_id')
                    ->withTimestamps();
    }

    /**
     * Scope query per tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Hitung metrik penjualan aktual tim (Qty, Omset, Nilai Dilepas, HPP, Margin)
     * Menggunakan filter dinamis (bulan/tahun atau date_from/date_to)
     */
    public function calculateActualMetrics(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $storeIds = $this->stores->pluck('id')->toArray();
        if (empty($storeIds)) {
            return [
                'qty' => 0,
                'omset' => 0.0,
                'value' => 0.0,
                'hpp' => 0.0,
                'margin' => 0.0,
            ];
        }

        $validStatuses = [
            'COMPLETED', 'RELEASED', 'COMPLETED_ESCROW', 'SELESAI', 'DELIVERED', 'FINISHED',
            'completed', 'released', 'selesai', 'delivered', 'finished'
        ];
        $invalidStatuses = [
            'CANCELLED', 'CANCELED', 'BATAL', 'RETURNED', 'REFUNDED', 'RETUR', 'IN_CANCEL', 'FAILED',
            'cancelled', 'canceled', 'batal', 'returned', 'refunded'
        ];

        $query = \App\Models\Order::whereIn('store_id', $storeIds)
            ->whereIn('order_status', $validStatuses)
            ->whereNotIn('order_status', $invalidStatuses)
            ->with(['items.masterProduct', 'items.marketplaceProduct.masterProduct', 'returnOrder.items']);

        // Prioritas rentang tanggal:
        // 1. Parameter eksplisit $dateFrom & $dateTo
        // 2. Tanggal acuan tersimpan di data tim ($this->date_from & $this->date_to)
        // 3. Parameter $month & $year
        // 4. Bulan & Tahun tersimpan di data tim
        $effectiveDateFrom = $dateFrom ?? ($this->date_from ? ($this->date_from instanceof \Carbon\Carbon ? $this->date_from->format('Y-m-d') : (string)$this->date_from) : null);
        $effectiveDateTo   = $dateTo ?? ($this->date_to ? ($this->date_to instanceof \Carbon\Carbon ? $this->date_to->format('Y-m-d') : (string)$this->date_to) : null);
        $effectiveMonth    = $month ?? $this->period_month;
        $effectiveYear     = $year ?? $this->period_year;

        if ($effectiveDateFrom && $effectiveDateTo) {
            $from = $effectiveDateFrom . ' 00:00:00';
            $to   = $effectiveDateTo . ' 23:59:59';
            $query->whereBetween(DB::raw('COALESCE(completed_at, updated_at, order_date)'), [$from, $to]);
        } elseif ($effectiveMonth && $effectiveYear) {
            $query->whereYear(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $effectiveYear)
                  ->whereMonth(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $effectiveMonth);
        }

        $orders = $query->get();

        $totalQty = 0;
        $totalOmset = 0.0;
        $totalVal = 0.0;
        $totalHpp = 0.0;
        $totalMargin = 0.0;

        foreach ($orders as $order) {
            // Hindari pesanan yang full refund
            if ($order->refund_amount > 0 && $order->total_amount > 0 && $order->refund_amount >= $order->total_amount) {
                continue;
            }

            $effectiveOmset = (float) $order->total_amount - (float) $order->refund_amount;
            $totalOmset += max(0.0, $effectiveOmset);

            // Hitung nilai dasar pesanan yang dilepas
            $orderReleased = (float) $order->net_amount;
            if ($orderReleased <= 0) {
                $orderReleased = max(0.0, (float) $order->total_amount - (float) $order->refund_amount - (float) $order->marketplace_fee);
                if ($orderReleased <= 0) {
                    $orderReleased = max(0.0, (float) $order->total_amount - (float) $order->refund_amount);
                }
            }

            $totalOrderItemsQty = $order->items->sum('quantity');
            $eligibleQty = 0;
            $orderEligibleHpp = 0.0;

            foreach ($order->items as $item) {
                $isExcluded = false;
                if ($item->masterProduct && $item->masterProduct->exclude_commission) {
                    $isExcluded = true;
                } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->exclude_commission) {
                    $isExcluded = true;
                }

                if (!$isExcluded) {
                    $returnedQty = 0;
                    if ($order->returnOrder) {
                        $returnedQty = $order->returnOrder->items
                            ->where('order_item_id', $item->id)
                            ->sum('quantity');
                    }

                    if ($returnedQty == 0 && $order->refund_amount > 0 && $order->total_amount > 0) {
                        if ($order->refund_amount >= $order->total_amount) {
                            $returnedQty = $item->quantity;
                        } else {
                            $ratio = (float)$order->refund_amount / (float)$order->total_amount;
                            $returnedQty = min($item->quantity, (int) round($item->quantity * $ratio));
                        }
                    }

                    $itemNetQty = max(0, $item->quantity - $returnedQty);
                    $eligibleQty += $itemNetQty;

                    // HPP unit
                    $unitHpp = 0.0;
                    if ($item->hpp_subtotal > 0 && $item->quantity > 0) {
                        $unitHpp = (float)$item->hpp_subtotal / (float)$item->quantity;
                    } elseif ($item->cost_price > 0) {
                        $unitHpp = (float)$item->cost_price;
                    } elseif ($item->masterProduct && $item->masterProduct->cost_price > 0) {
                        $unitHpp = (float)$item->masterProduct->cost_price;
                    } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->cost_price > 0) {
                        $unitHpp = (float)$item->marketplaceProduct->masterProduct->cost_price;
                    }

                    $orderEligibleHpp += ($unitHpp * $itemNetQty);
                }
            }

            if ($totalOrderItemsQty > 0 && $eligibleQty < $totalOrderItemsQty) {
                $ratio = max(0.0, min(1.0, $eligibleQty / $totalOrderItemsQty));
                $orderReleased = $orderReleased * $ratio;
            }

            $orderReleased = max(0.0, $orderReleased);
            $orderMargin = $orderReleased - $orderEligibleHpp;

            $totalQty += $eligibleQty;
            $totalVal += $orderReleased;
            $totalHpp += $orderEligibleHpp;
            $totalMargin += $orderMargin;
        }

        return [
            'qty' => $totalQty,
            'omset' => round($totalOmset, 2),
            'value' => round($totalVal, 2),
            'hpp' => round($totalHpp, 2),
            'margin' => round($totalMargin, 2),
        ];
    }

    public function calculateActualQty(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        return $this->calculateActualMetrics($month, $year, $dateFrom, $dateTo)['qty'];
    }

    public function getActualQtyAttribute(): int
    {
        return $this->calculateActualQty();
    }

    public function calculateActualOmset(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return $this->calculateActualMetrics($month, $year, $dateFrom, $dateTo)['omset'];
    }

    public function getActualOmsetAttribute(): float
    {
        return $this->calculateActualOmset();
    }

    public function calculateActualValue(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return $this->calculateActualMetrics($month, $year, $dateFrom, $dateTo)['value'];
    }

    public function getActualValueAttribute(): float
    {
        return $this->calculateActualValue();
    }

    public function calculateActualHpp(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return $this->calculateActualMetrics($month, $year, $dateFrom, $dateTo)['hpp'];
    }

    public function getActualHppAttribute(): float
    {
        return $this->calculateActualHpp();
    }

    public function calculateActualMargin(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return $this->calculateActualMetrics($month, $year, $dateFrom, $dateTo)['margin'];
    }

    public function getActualMarginAttribute(): float
    {
        return $this->calculateActualMargin();
    }

    /**
     * Label Periode Target Tim
     */
    public function getPeriodLabelAttribute(): string
    {
        if ($this->date_from && $this->date_to) {
            $from = \Carbon\Carbon::parse($this->date_from)->translatedFormat('d M Y');
            $to   = \Carbon\Carbon::parse($this->date_to)->translatedFormat('d M Y');
            return "{$from} – {$to}";
        }

        if ($this->period_month && $this->period_year) {
            $monthName = \Carbon\Carbon::create($this->period_year, $this->period_month, 1)->translatedFormat('F');
            return "{$monthName} {$this->period_year}";
        }

        return 'Semua Periode';
    }

    /**
     * Total Insentif / Komisi Rupiah yang didapat
     * Acuan utama: Margin (Rp) = Nilai Dilepas - HPP
     */
    public function getTotalRewardAttribute(): float
    {
        $type = $this->commission_type ?: 'percentage';

        if ($type === 'percentage') {
            $basisMargin = (float) $this->actual_margin;
            $reward = max(0.0, $basisMargin) * ($this->commission_rate / 100);
            // Tambahkan bonus flat jika mencapai target margin
            if ($this->target_omset > 0 && $basisMargin >= $this->target_omset && $this->reward_fixed_nominal > 0) {
                $reward += (float) $this->reward_fixed_nominal;
            }
            return (float) round($reward, 2);
        }

        if ($type === 'nominal') {
            if ($this->target_omset > 0 && $this->actual_margin >= $this->target_omset) {
                return (float) $this->reward_fixed_nominal;
            }
            return 0.0;
        }

        // Default / Skema Qty
        return (float) ($this->actual_qty * $this->reward_per_qty);
    }

    /**
     * Persentase pencapaian Target Qty
     */
    public function getQtyProgressPercentAttribute(): float
    {
        if ($this->target_qty <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->actual_qty / $this->target_qty) * 100, 1));
    }

    /**
     * Persentase pencapaian Target Margin (Rp)
     */
    public function getValueProgressPercentAttribute(): float
    {
        if ($this->target_omset <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->actual_margin / $this->target_omset) * 100, 1));
    }

    public function getMarginProgressPercentAttribute(): float
    {
        return $this->getValueProgressPercentAttribute();
    }

    public function getOmsetProgressPercentAttribute(): float
    {
        return $this->getValueProgressPercentAttribute();
    }
}
