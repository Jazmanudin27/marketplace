<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterProductionStage extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Seed default production stages for a tenant if none exist.
     */
    public static function seedDefaultsForTenant(int $tenantId): void
    {
        if (self::where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Potong', 'sort_order' => 1],
            ['name' => 'Sablon / Bordir', 'sort_order' => 2],
            ['name' => 'Jahit', 'sort_order' => 3],
            ['name' => 'QC', 'sort_order' => 4],
            ['name' => 'Finishing / Packing', 'sort_order' => 5],
        ];

        foreach ($defaults as $stage) {
            self::create(array_merge($stage, [
                'tenant_id' => $tenantId,
                'is_active' => true,
            ]));
        }
    }
}
