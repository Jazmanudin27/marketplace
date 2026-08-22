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

        $validStatuses = ['COMPLETED', 'RELEASED', 'COMPLETED_ESCROW'];

        if (Schema::hasTable('order_items')) {
            $qtyQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('orders.store_id', $storeIds)
                ->whereIn('orders.order_status', $validStatuses)
                ->whereNotIn('orders.order_status', ['CANCELLED', 'CANCELED', 'RETURNED', 'REFUNDED', 'IN_CANCEL', 'FAILED']);

            if ($dateFrom && $dateTo) {
                $from = $dateFrom . ' 00:00:00';
                $to = $dateTo . ' 23:59:59';
                $qtyQuery->where(function($q) use ($from, $to) {
                    $q->where(function($sub) use ($from, $to) {
                        $sub->whereNotNull('orders.completed_at')
                            ->whereBetween('orders.completed_at', [$from, $to]);
                    })->orWhere(function($sub) use ($from, $to) {
                        $sub->whereNull('orders.completed_at')
                            ->whereBetween('orders.order_date', [$from, $to]);
                    });
                });
            } elseif ($month && $year) {
                $qtyQuery->where(function($q) use ($month, $year) {
                    $q->where(function($sub) use ($month, $year) {
                        $sub->whereNotNull('orders.completed_at')
                            ->whereYear('orders.completed_at', $year)
                            ->whereMonth('orders.completed_at', $month);
                    })->orWhere(function($sub) use ($month, $year) {
                        $sub->whereNull('orders.completed_at')
                            ->whereYear('orders.order_date', $year)
                            ->whereMonth('orders.order_date', $month);
                    });
                });
            }

            return (int) $qtyQuery->sum('order_items.quantity');
        }

        $query = DB::table('orders')
            ->whereIn('store_id', $storeIds)
            ->whereIn('order_status', $validStatuses)
            ->whereNotIn('order_status', ['CANCELLED', 'CANCELED', 'RETURNED', 'REFUNDED', 'IN_CANCEL', 'FAILED']);

        if ($dateFrom && $dateTo) {
            $from = $dateFrom . ' 00:00:00';
            $to = $dateTo . ' 23:59:59';
            $query->where(function($q) use ($from, $to) {
                $q->where(function($sub) use ($from, $to) {
                    $sub->whereNotNull('completed_at')
                        ->whereBetween('completed_at', [$from, $to]);
                })->orWhere(function($sub) use ($from, $to) {
                    $sub->whereNull('completed_at')
                        ->whereBetween('order_date', [$from, $to]);
                });
            });
        } elseif ($month && $year) {
            $query->where(function($q) use ($month, $year) {
                $q->where(function($sub) use ($month, $year) {
                    $sub->whereNotNull('completed_at')
                        ->whereYear('completed_at', $year)
                        ->whereMonth('completed_at', $month);
                })->orWhere(function($sub) use ($month, $year) {
                    $sub->whereNull('completed_at')
                        ->whereYear('order_date', $year)
                        ->whereMonth('order_date', $month);
                });
            });
        }

        return (int) $query->count();
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

        $validStatuses = ['COMPLETED', 'RELEASED', 'COMPLETED_ESCROW'];

        $query = DB::table('orders')
            ->whereIn('store_id', $storeIds)
            ->whereIn('order_status', $validStatuses)
            ->whereNotIn('order_status', ['CANCELLED', 'CANCELED', 'RETURNED', 'REFUNDED', 'IN_CANCEL', 'FAILED']);

        if ($dateFrom && $dateTo) {
            $from = $dateFrom . ' 00:00:00';
            $to = $dateTo . ' 23:59:59';
            $query->where(function($q) use ($from, $to) {
                $q->where(function($sub) use ($from, $to) {
                    $sub->whereNotNull('completed_at')
                        ->whereBetween('completed_at', [$from, $to]);
                })->orWhere(function($sub) use ($from, $to) {
                    $sub->whereNull('completed_at')
                        ->whereBetween('order_date', [$from, $to]);
                });
            });
        } elseif ($month && $year) {
            $query->where(function($q) use ($month, $year) {
                $q->where(function($sub) use ($month, $year) {
                    $sub->whereNotNull('completed_at')
                        ->whereYear('completed_at', $year)
                        ->whereMonth('completed_at', $month);
                })->orWhere(function($sub) use ($month, $year) {
                    $sub->whereNull('completed_at')
                        ->whereYear('order_date', $year)
                        ->whereMonth('order_date', $month);
                });
            });
        }

        return (float) ($query->sum('total_amount') ?? 0.0);
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
