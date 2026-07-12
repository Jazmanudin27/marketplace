<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaborService extends Model
{
    protected $fillable = ['tenant_id', 'name', 'default_cost'];

    protected $casts = [
        'default_cost' => 'decimal:2',
    ];
}
