<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipeLabor extends Model
{
    protected $fillable = [
        'product_recipe_id',
        'service_name',
        'default_cost',
    ];

    protected $casts = [
        'default_cost' => 'decimal:2',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id');
    }
}
