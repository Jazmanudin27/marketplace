<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function masterProducts(): HasMany
    {
        return $this->hasMany(MasterProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
