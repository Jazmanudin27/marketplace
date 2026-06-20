<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'period',
        'salary_type',
        'basic_salary',
        'hours_worked',
        'allowance',
        'overtime_pay',
        'cash_advance_deduction',
        'attendance_deduction',
        'late_deduction',
        'other_deductions',
        'salary_adjustment_addition',
        'salary_adjustment_deduction',
        'salary_adjustment_notes',
        'net_salary',
        'status',
        'payment_date',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'allowance' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'cash_advance_deduction' => 'decimal:2',
        'attendance_deduction' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'salary_adjustment_addition' => 'decimal:2',
        'salary_adjustment_deduction' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowances()
    {
        return $this->hasMany(PayrollAllowance::class);
    }
}
