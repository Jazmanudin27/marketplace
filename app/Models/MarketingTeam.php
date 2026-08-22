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
     * Hitung Qty Pesanan Aktual untuk toko-toko di tim ini pada periode target
     */
    public function getActualQtyAttribute(): int
    {
        $storeIds = $this->stores->pluck('id')->toArray();
        if (empty($storeIds)) {
            return 0;
        }

        $query = DB::table('orders')
            ->whereIn('store_id', $storeIds)
            ->whereNotIn('order_status', ['CANCELLED', 'CANCELED', 'RETURNED', 'REFUNDED']);

        if ($this->period_month && $this->period_year) {
            $query->whereYear('order_date', $this->period_year)
                  ->whereMonth('order_date', $this->period_month);
        }

        // Check if order_items quantity sum is available or use count of orders
        if (Schema::hasTable('order_items')) {
            $qty = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('orders.store_id', $storeIds)
                ->whereNotIn('orders.order_status', ['CANCELLED', 'CANCELED', 'RETURNED', 'REFUNDED']);

            if ($this->period_month && $this->period_year) {
                $qty->whereYear('orders.order_date', $this->period_year)
                    ->whereMonth('orders.order_date', $this->period_month);
            }

            return (int) $qty->sum('order_items.quantity');
        }

        return (int) $query->count();
    }

    /**
     * Hitung Omset (Total Sales Rp) Aktual untuk toko-toko di tim ini pada periode target
     */
    public function getActualOmsetAttribute(): float
    {
        $storeIds = $this->stores->pluck('id')->toArray();
        if (empty($storeIds)) {
            return 0.0;
        }

        $query = DB::table('orders')
            ->whereIn('store_id', $storeIds)
            ->whereNotIn('order_status', ['CANCELLED', 'CANCELED', 'RETURNED', 'REFUNDED']);

        if ($this->period_month && $this->period_year) {
            $query->whereYear('order_date', $this->period_year)
                  ->whereMonth('order_date', $this->period_month);
        }

        return (float) ($query->sum('total_amount') ?? 0.0);
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
