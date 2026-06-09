<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'name'];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function products()
    {
        return $this->hasMany(MasterProduct::class);
    }
}
