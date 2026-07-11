<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionStatus extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'sort_order',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Seed default statuses for a tenant if none exist.
     */
    public static function seedDefaultsForTenant(int $tenantId): void
    {
        if (self::where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Belum Mulai', 'color' => 'secondary', 'sort_order' => 1],
            ['name' => 'Potong', 'color' => 'dark', 'sort_order' => 2],
            ['name' => 'Printing/Sablon/Bordir', 'color' => 'warning', 'sort_order' => 3],
            ['name' => 'Jahit', 'color' => 'info', 'sort_order' => 4],
            ['name' => 'QC & Finishing', 'color' => 'primary', 'sort_order' => 5],
            ['name' => 'Selesai', 'color' => 'success', 'sort_order' => 6],
        ];

        foreach ($defaults as $status) {
            self::create(array_merge($status, ['tenant_id' => $tenantId]));
        }
    }
}
