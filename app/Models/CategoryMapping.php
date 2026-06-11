<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryMapping extends Model
{
    protected $fillable = [
        'tenant_id',
        'category_id',
        'store_id',
        'marketplace_category_id',
        'marketplace_category_name',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
