<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductRecipe extends Model
{
    protected $fillable = [
        'tenant_id',
        'master_product_id',
        'name',
        'batch_qty',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'batch_qty' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    public function labors(): HasMany
    {
        return $this->hasMany(ProductRecipeLabor::class);
    }
}
