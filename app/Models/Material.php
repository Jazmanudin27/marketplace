<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'type', // bahan / kemasan
        'unit',
        'stock',
        'min_stock',
        'cost_price',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function recordStockMovement(int $quantity, string $type, string $reference, ?int $userId = null, ?string $date = null): void
    {
        if ($type === 'out') {
            $this->decrement('stock', abs($quantity));
            $actualQty = -abs($quantity);
        } else if ($type === 'in') {
            $this->increment('stock', abs($quantity));
            $actualQty = abs($quantity);
        } else {
            $this->increment('stock', $quantity);
            $actualQty = $quantity;
        }

        $newStock = $this->fresh()->stock;

        $movementData = [
            'tenant_id' => $this->tenant_id,
            'material_id' => $this->id,
            'user_id' => $userId,
            'type' => $type,
            'quantity' => $actualQty,
            'reference' => $reference,
            'balance_after' => $newStock,
        ];
        
        if ($date) {
            $movementData['created_at'] = $date;
            $movementData['updated_at'] = $date;
        }

        StockMovement::create($movementData);
    }
}
