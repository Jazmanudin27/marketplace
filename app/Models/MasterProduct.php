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
        'sku_induk',
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
        'sub_kategori',
        'brand_id',
        'is_active',
        'ukuran',
        'warna',
        'is_preorder',
        'preorder_days',
        'is_bundle',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'cost_price'=> 'decimal:2',
        'is_active' => 'boolean',
        'is_preorder' => 'boolean',
        'preorder_days' => 'integer',
        'is_bundle' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class, 'master_product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function activeRecipe()
    {
        return $this->hasOne(ProductRecipe::class, 'master_product_id')->where('is_active', true);
    }

    public function components()
    {
        return $this->belongsToMany(MasterProduct::class, 'master_product_bundles', 'parent_id', 'child_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getStockAttribute()
    {
        if ($this->is_bundle) {
            $comps = $this->components;
            if ($comps->isEmpty()) {
                return 0;
            }
            return $comps->map(function ($comp) {
                $qtyNeeded = max(1, $comp->pivot->quantity);
                return (int) floor($comp->stock / $qtyNeeded);
            })->min();
        }
        return $this->attributes['stock'] ?? 0;
    }

    public function getCostPriceAttribute()
    {
        if ($this->is_bundle) {
            $comps = $this->components;
            if ($comps->isEmpty()) {
                return 0.0;
            }
            return (float) $comps->sum(function ($comp) {
                return (float) $comp->cost_price * $comp->pivot->quantity;
            });
        }
        return (float) ($this->attributes['cost_price'] ?? 0.0);
    }

    /**
     * Catat pergerakan stok, perbarui stok lokal, dan sinkronisasikan ke marketplace.
     */
    public function recordStockMovement(int $quantity, string $type, string $reference, ?int $userId = null, ?string $date = null): void
    {
        if ($this->is_bundle) {
            // Deduct components instead of bundle parent directly
            foreach ($this->components as $component) {
                $compQty = $quantity * $component->pivot->quantity;
                $component->recordStockMovement($compQty, $type, $reference . " (Komponen dari Set: " . $this->sku . ")", $userId, $date);
            }

            // Sync parent set stock to connected marketplace listings
            $newStock = $this->stock; // Calls getStockAttribute() dynamically
            $this->marketplaceProducts()
                 ->where('sync_stock', true)
                 ->update(['stock' => $newStock]);

            \App\Jobs\PushStockToMarketplaces::dispatch($this->id, $newStock);
            return;
        }

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

        // 5. Update bundle parent stocks if this product is a component of any bundle
        $parentBundles = MasterProduct::where('is_bundle', true)
            ->whereHas('components', function ($q) {
                $q->where('child_id', $this->id);
            })->get();

        foreach ($parentBundles as $parent) {
            $parentStock = $parent->stock; // Recalculates dynamically
            $parent->marketplaceProducts()
                   ->where('sync_stock', true)
                   ->update(['stock' => $parentStock]);
            \App\Jobs\PushStockToMarketplaces::dispatch($parent->id, $parentStock);
        }
    }

    /**
     * Backward compatibility
     */
    public function decrementStock(int $qty): void
    {
        $this->recordStockMovement($qty, 'out', 'System decrement', null);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }
}
