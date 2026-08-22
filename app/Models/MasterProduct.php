<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MasterProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'brand_id',
        'name',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'price',
        'stock',
        'min_stock',
        'safety_stock',
        'is_preorder',
        'is_bundle',
        'image',
        'status',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'safety_stock' => 'integer',
        'is_preorder' => 'boolean',
        'is_bundle' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class, 'master_product_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'master_product_id');
    }

    public function activeRecipe(): HasOne
    {
        return $this->hasOne(ProductRecipe::class, 'master_product_id')
                    ->where('is_active', true);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'master_product_id');
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(MasterProduct::class, 'master_product_bundles', 'parent_id', 'child_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(MasterProduct::class, 'master_product_bundles', 'child_id', 'parent_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function getStockAttribute()
    {
        if ($this->is_bundle && Schema::hasTable('master_product_bundles')) {
            $comps = $this->relationLoaded('components') ? $this->components : $this->components()->get();
            if ($comps->isEmpty()) {
                return 0;
            }
            $calculated = $comps->map(function ($comp) {
                $qtyNeeded = max(1, (int) ($comp->pivot->quantity ?? 1));
                $compStock = (int) ($comp->stock ?? $comp->attributes['stock'] ?? 0);
                return (int) floor($compStock / $qtyNeeded);
            })->min();

            return max(0, (int) $calculated);
        }
        return (int) ($this->attributes['stock'] ?? 0);
    }

    /**
     * Hitung & perbarui ulang kolom stok DB untuk semua produk Set/Bundle berdasarkan komponennya.
     */
    public static function recalculateAllBundleStocks(?int $tenantId = null): int
    {
        if (!Schema::hasTable('master_product_bundles')) {
            return 0;
        }

        $query = static::where('is_bundle', true)->with('components');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $bundles = $query->get();
        $updatedCount = 0;

        foreach ($bundles as $bundle) {
            $calcStock = $bundle->stock; // getStockAttribute()

            DB::table('master_products')
                ->where('id', $bundle->id)
                ->update(['stock' => $calcStock]);

            DB::table('marketplace_products')
                ->where('master_product_id', $bundle->id)
                ->when($bundle->sku, fn($q) => $q->orWhere('marketplace_sku', $bundle->sku))
                ->update([
                    'master_product_id' => $bundle->id,
                    'stock' => $calcStock,
                    'sync_stock' => true,
                    'updated_at' => now(),
                ]);

            $updatedCount++;
        }

        return $updatedCount;
    }

    public function getCostPriceAttribute()
    {
        if ($this->is_bundle && Schema::hasTable('master_product_bundles')) {
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
     * Catat pergerakan stok, perbarui stok lokal kilat, dan push async ke toko marketplace.
     */
    public function recordStockMovement(int $quantity, string $type, string $reference, ?int $userId = null, ?string $date = null): void
    {
        if ($this->is_bundle && Schema::hasTable('master_product_bundles')) {
            // Deduct components instead of bundle parent directly
            foreach ($this->components as $component) {
                $compQty = $quantity * $component->pivot->quantity;
                $component->recordStockMovement($compQty, $type, $reference . " (Komponen dari Set: " . $this->sku . ")", $userId, $date);
            }

            // Sync parent set stock to connected marketplace listings
            $newStock = $this->stock;
            
            DB::table('marketplace_products')
                ->where('master_product_id', $this->id)
                ->when($this->sku, fn($q) => $q->orWhere('marketplace_sku', $this->sku))
                ->update([
                    'master_product_id' => $this->id,
                    'stock' => $newStock,
                    'sync_stock' => true,
                    'updated_at' => now(),
                ]);

            try {
                DB::afterCommit(function() use ($newStock) {
                    if (function_exists('dispatchAfterResponse')) {
                        \App\Jobs\PushStockToMarketplaces::dispatchAfterResponse($this->id, $newStock);
                    } else {
                        \App\Jobs\PushStockToMarketplaces::dispatch($this->id, $newStock);
                    }
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('[StockSync] Push bundle stock error: ' . $e->getMessage());
            }
            return;
        }

        // 1. Update stok master
        if ($type === 'out') {
            $this->decrement('stock', abs($quantity));
            $actualQty = -abs($quantity);
        } else if ($type === 'in') {
            $this->increment('stock', abs($quantity));
            $actualQty = abs($quantity);
        } else {
            // type == 'adj' (penyesuaian manual)
            $this->increment('stock', $quantity);
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

        // 3. ⚡ Bulk Update ke seluruh marketplace_products lokal dalam 1 Query (KILAT)
        DB::table('marketplace_products')
            ->where('master_product_id', $this->id)
            ->when($this->sku, fn($q) => $q->orWhere('marketplace_sku', $this->sku))
            ->update([
                'master_product_id' => $this->id,
                'stock' => $newStock,
                'sync_stock' => true,
                'updated_at' => now(),
            ]);
             
        // 4. 🚀 Push stok ke API Marketplace secara ASYNC / NON-BLOCKING
        try {
            DB::afterCommit(function() use ($newStock) {
                if (function_exists('dispatchAfterResponse')) {
                    \App\Jobs\PushStockToMarketplaces::dispatchAfterResponse($this->id, $newStock);
                } else {
                    \App\Jobs\PushStockToMarketplaces::dispatch($this->id, $newStock);
                }
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('[StockSync] Push stock error: ' . $e->getMessage());
        }

        // 5. 🎁 Jika produk ini adalah komponen dari Set/Bundle, perbarui stok Set/Bundle induknya!
        if (Schema::hasTable('master_product_bundles') && $this->bundles->isNotEmpty()) {
            foreach ($this->bundles as $parentBundle) {
                $calcStock = $parentBundle->stock;

                DB::table('master_products')
                    ->where('id', $parentBundle->id)
                    ->update(['stock' => $calcStock]);

                DB::table('marketplace_products')
                    ->where('master_product_id', $parentBundle->id)
                    ->when($parentBundle->sku, fn($q) => $q->orWhere('marketplace_sku', $parentBundle->sku))
                    ->update([
                        'master_product_id' => $parentBundle->id,
                        'stock' => $calcStock,
                        'sync_stock' => true,
                        'updated_at' => now(),
                    ]);

                try {
                    DB::afterCommit(function() use ($parentBundle, $calcStock) {
                        \App\Jobs\PushStockToMarketplaces::dispatch($parentBundle->id, $calcStock);
                    });
                } catch (\Exception $e) {}
            }
        }
    }
}
