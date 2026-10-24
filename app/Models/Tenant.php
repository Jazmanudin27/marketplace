<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Tenant extends Model
{
    public const MAX_WEEKLY_PRODUCT_IMPORTS = 3;

    protected $fillable = [
        'name',
        'status',
        'cutoff_start_day',
        'office_latitude',
        'office_longitude',
        'office_radius',
        'last_product_import_at',
        'weekly_product_import_limit',
    ];

    protected function casts(): array
    {
        return [
            'last_product_import_at' => 'datetime',
            'weekly_product_import_limit' => 'integer',
        ];
    }

    /**
     * Get start and end date of the cutoff period based on a month period (YYYY-MM).
     *
     * @param string $period Format: YYYY-MM
     * @return array [startDate, endDate]
     */
    public function getCutoffRange(string $period): array
    {
        $currentMonth = \Carbon\Carbon::parse($period . '-01');
        $startDay = (int) ($this->cutoff_start_day ?? 21);

        if ($startDay === 1) {
            $startDate = $currentMonth->copy()->startOfMonth()->toDateString();
            $endDate = $currentMonth->copy()->endOfMonth()->toDateString();
        } else {
            $startDate = $currentMonth->copy()->subMonth()->day($startDay)->toDateString();
            // Subtract 1 day from startDay for the end date.
            // Carbon will automatically handle wrapping if startDay - 1 is 0, but since startDay is >= 2, this is safe.
            $endDate = $currentMonth->copy()->day($startDay - 1)->toDateString();
        }

        return [$startDate, $endDate];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function masterProducts(): HasMany
    {
        return $this->hasMany(MasterProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function productImportLogs(): HasMany
    {
        return $this->hasMany(ProductImportLog::class);
    }

    /**
     * Get recent product imports within rolling N days (default 7 days).
     */
    public function getRecentProductImports(int $days = 7)
    {
        if (Schema::hasTable('product_import_logs')) {
            return $this->productImportLogs()
                ->where('created_at', '>=', now()->subDays($days))
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return collect();
    }

    /**
     * Get maximum allowed product imports per week for this tenant.
     * Hierarchy: Tenant specific limit -> config('app.product_import_weekly_limit') -> env() -> default (3)
     */
    public function getMaxWeeklyProductImports(): int
    {
        if (!empty($this->weekly_product_import_limit) && (int) $this->weekly_product_import_limit > 0) {
            return (int) $this->weekly_product_import_limit;
        }

        return (int) config('app.product_import_weekly_limit', env('PRODUCT_IMPORT_WEEKLY_LIMIT', self::MAX_WEEKLY_PRODUCT_IMPORTS));
    }

    /**
     * Get quota and lock status for master product import.
     */
    public function getProductImportQuotaStatus(int $days = 7): array
    {
        $maxLimit = $this->getMaxWeeklyProductImports();
        $recentImports = $this->getRecentProductImports($days);
        $usedCount = $recentImports->count();

        // Fallback for existing tenants when product_import_logs has no entry yet
        if ($usedCount === 0 && $this->last_product_import_at && $this->last_product_import_at->gte(now()->subDays($days))) {
            $usedCount = 1;
            $oldestInWindow = $this->last_product_import_at;
            $latestImportAt = $this->last_product_import_at;
        } else {
            $oldestInWindow = $recentImports->first()?->created_at;
            $latestImportAt = $recentImports->last()?->created_at ?? $this->last_product_import_at;
        }

        $canImport = $usedCount < $maxLimit;
        $remainingQuota = max(0, $maxLimit - $usedCount);
        $nextImportAt = null;

        if (!$canImport && $oldestInWindow) {
            $nextImportAt = $oldestInWindow->copy()->addDays($days);
        }

        return [
            'can_import' => $canImport,
            'used_count' => $usedCount,
            'max_limit' => $maxLimit,
            'remaining_quota' => $remainingQuota,
            'next_import_at' => $nextImportAt,
            'last_import_at' => $latestImportAt,
        ];
    }
}

