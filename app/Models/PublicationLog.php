<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicationLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'master_product_id',
        'store_id',
        'status',
        'marketplace_product_id',
        'category_id',
        'category_name',
        'error_message',
    ];

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
