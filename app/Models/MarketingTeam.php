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
        'is_active',
    ];

    protected $casts = [
        'target_qty' => 'integer',
        'reward_per_qty' => 'float',
        'target_omset' => 'float',
        'period_month' => 'integer',
        'period_year' => 'integer',
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
            ->with(['items.masterProduct', 'items.marketplaceProduct.masterProduct', 'returnOrder']);

        if ($dateFrom && $dateTo) {
            $from = $dateFrom . ' 00:00:00';
            $to = $dateTo . ' 23:59:59';
            $query->whereBetween(DB::raw('COALESCE(completed_at, updated_at, order_date)'), [$from, $to]);
        } elseif ($month && $year) {
            $query->whereYear(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $year)
                  ->whereMonth(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $month);
        }

        $orders = $query->get();

        $qty = 0;
        foreach ($orders as $order) {
            if ($order->refund_amount <= 0) {
                foreach ($order->items as $item) {
                    $isExcluded = false;
                    if ($item->masterProduct && $item->masterProduct->exclude_commission) {
                        $isExcluded = true;
                    } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->exclude_commission) {
                        $isExcluded = true;
                    }

                    if (!$isExcluded) {
                        $qty += $item->quantity;
                    }
                }
            }
        }

        return $qty;
    }

    public function getActualQtyAttribute(): int
    {
        return $this->calculateActualQty($this->period_month, $this->period_year);
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
            ->whereNotIn('order_status', $invalidStatuses)
            ->with(['returnOrder']);

        if ($dateFrom && $dateTo) {
            $from = $dateFrom . ' 00:00:00';
            $to = $dateTo . ' 23:59:59';
            $query->whereBetween(DB::raw('COALESCE(completed_at, updated_at, order_date)'), [$from, $to]);
        } elseif ($month && $year) {
            $query->whereYear(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $year)
                  ->whereMonth(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $month);
        }

        $orders = $query->get();

        $omset = 0.0;
        foreach ($orders as $order) {
            if ($order->refund_amount <= 0) {
                $omset += (float) $order->total_amount;
            }
        }

        return $omset;
    }

    public function getActualOmsetAttribute(): float
    {
        return $this->calculateActualOmset($this->period_month, $this->period_year);
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
