<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'username',
        'password',
        'email',
        'phone',
        'position',
        'address',
        'join_date',
        'is_active',
        'salary_type',
        'basic_salary',
        'allowance',
        'overtime_rate',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'join_date' => 'date',
        'is_active' => 'boolean',
        'basic_salary' => 'decimal:2',
        'allowance' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function allowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function holidays()
    {
        return $this->belongsToMany(Holiday::class, 'holiday_employee');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function getScheduleForDate($date)
    {
        $dateStr = \Carbon\Carbon::parse($date)->toDateString();
        
        // Cek apakah ada data presensi (attendance) pada tanggal ini yang menyimpan jadwal override
        $attendance = $this->attendances()->where('date', $dateStr)->first();
        if ($attendance && ($attendance->schedule_clock_in || $attendance->schedule_clock_out || $attendance->schedule_is_off)) {
            return (object)[
                'is_off' => (bool)$attendance->schedule_is_off,
                'clock_in' => $attendance->schedule_clock_in,
                'clock_out' => $attendance->schedule_clock_out,
            ];
        }

        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeekIso; // 1 = Senin, 7 = Minggu
        
        $schedule = $this->schedules()->where('day_of_week', $dayOfWeek)->first();
        if ($schedule) {
            return $schedule;
        }
        
        // Fallback default schedule jika belum terkonfigurasi di tabel employee_schedules
        $isSat = $dayOfWeek === 6;
        $isSun = $dayOfWeek === 7;
        
        return (object)[
            'is_off' => $isSun,
            'clock_in' => $isSat ? '07:00:00' : ($isSun ? null : '08:00:00'),
            'clock_out' => $isSat ? '12:00:00' : ($isSun ? null : '16:00:00'),
        ];
    }
}
