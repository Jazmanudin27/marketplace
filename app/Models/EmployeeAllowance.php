<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAllowance extends Model
{
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'allowance_type_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
