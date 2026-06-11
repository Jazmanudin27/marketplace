<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandMapping extends Model
{
    protected $fillable = [
        'tenant_id',
        'brand_id',
        'store_id',
        'marketplace_brand_id',
        'marketplace_brand_name',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
