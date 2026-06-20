<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'schedule_clock_in',
        'schedule_clock_out',
        'schedule_is_off',
        'status',
        'is_deducted',
        'late_minutes',
        'late_penalty',
        'notes',
        'latitude_in',
        'longitude_in',
        'latitude_out',
        'longitude_out',
        'photo_in',
        'photo_out',
    ];

    protected $casts = [
        'date' => 'date',
        'is_deducted' => 'boolean',
        'schedule_is_off' => 'boolean',
        'late_minutes' => 'integer',
        'late_penalty' => 'decimal:2',
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
