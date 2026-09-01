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
     * Hitung Qty Pesanan Aktual dengan filter dinamis (bulan/tahun atau date_from/date_to)
     */
    public function calculateActualQty(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        $storeIds = $this->stores->pluck('id')->toArray();
        if (empty($storeIds)) {
            return 0;
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

        $qty = 0;
        foreach ($orders as $order) {
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
                    
                    $qty += max(0, $item->quantity - $returnedQty);
                }
            }
        }

        return $qty;
    }

    public function getActualQtyAttribute(): int
    {
        return $this->calculateActualQty();
    }

    /**
     * Hitung Omset (Total Sales Rp) Aktual dengan filter dinamis
     */
    public function calculateActualOmset(?int $month = null, ?int $year = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        $storeIds = $this->stores->pluck('id')->toArray();
        if (empty($storeIds)) {
            return 0.0;
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
            ->whereNotIn('order_status', $invalidStatuses);

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

        $omset = 0.0;
        foreach ($orders as $order) {
            $effectiveOmset = (float) $order->total_amount - (float) $order->refund_amount;
            $omset += max(0.0, $effectiveOmset);
        }

        return $omset;
    }

    public function getActualOmsetAttribute(): float
    {
        return $this->calculateActualOmset();
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
     * Total Insentif / Reward Rupiah yang didapat (Actual Qty * Reward per Qty)
     */
    public function getTotalRewardAttribute(): float
    {
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
     * Persentase pencapaian Target Omset
     */
    public function getOmsetProgressPercentAttribute(): float
    {
        if ($this->target_omset <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->actual_omset / $this->target_omset) * 100, 1));
    }
}
