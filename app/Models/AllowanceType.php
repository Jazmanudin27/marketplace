<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowanceType extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employeeAllowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }
}
