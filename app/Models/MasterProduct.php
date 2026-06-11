<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProduct extends Model
{
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'weight',
        'length',
        'width',
        'height',
        'image_url',
        'price',
        'cost_price',
        'stock',
        'min_stock',
        'unit',
        'category_id',
        'brand_id',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    protected $casts = [
        'price'     => 'decimal:2',
        'cost_price'=> 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Catat pergerakan stok, perbarui stok lokal, dan sinkronisasikan ke marketplace.
     */
    public function recordStockMovement(int $quantity, string $type, string $reference, ?int $userId = null, ?string $date = null): void
    {
        // 1. Update stok
        if ($type === 'out') {
            $this->decrement('stock', abs($quantity));
            $actualQty = -abs($quantity);
        } else if ($type === 'in') {
            $this->increment('stock', abs($quantity));
            $actualQty = abs($quantity);
        } else {
            // type == 'adj' (penyesuaian manual)
            $this->increment('stock', $quantity); // quantity can be negative or positive
            $actualQty = $quantity;
        }

        $newStock = $this->fresh()->stock;

        $movementData = [
            'tenant_id' => $this->tenant_id,
            'master_product_id' => $this->id,
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

        // 2. Catat ke stock_movements
        StockMovement::create($movementData);

        // 3. Sinkronisasi ke semua marketplace produk yang terhubung
        $this->marketplaceProducts()
             ->where('sync_stock', true)
             ->update(['stock' => $newStock]);
             
        // 4. Push stok ke API Marketplace secara otomatis (Shopee, Tokopedia, dll)
        \App\Jobs\PushStockToMarketplaces::dispatch($this->id, $newStock);
    }

    /**
     * Backward compatibility
     */
    public function decrementStock(int $qty): void
    {
        $this->recordStockMovement($qty, 'out', 'System decrement', null);
    }
}
