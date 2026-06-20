<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'day_of_week',
        'clock_in',
        'clock_out',
        'is_off',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_off' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
