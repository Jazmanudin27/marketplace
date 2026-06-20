<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Overtime;
use App\Models\CashAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Default ke bulan saat ini jika tidak dipilih
        $period = $request->get('period', Carbon::now()->format('Y-m'));

        $payrolls = Payroll::with('employee')
            ->where('tenant_id', $tenantId)
            ->where('period', $period)
            ->get();

        return view('hrd.payroll.index', compact('payrolls', 'period'));
    }

    public function generate(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $tenantId = $tenant->id;
        
        $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $period = $request->period;
        [$startDate, $endDate] = $tenant->getCutoffRange($period);

        // Hari libur dan Jam kerja standar dihitung dinamis per karyawan di dalam loop

        // Cari karyawan aktif beserta tunjangan mereka
        $employees = Employee::with(['allowances.allowanceType'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return back()->with('error', 'Tidak ada karyawan aktif untuk digenerate gajinya.');
        }

        $generatedCount = 0;

        foreach ($employees as $employee) {
            // Cek apakah gaji periode ini sudah "paid"
            $existingPayroll = Payroll::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('period', $period)
                ->first();

            if ($existingPayroll && $existingPayroll->status === 'paid') {
                continue; // Jangan timpa gaji yang sudah dibayarkan
            }

            // Hitung Hari Libur Spesifik untuk Karyawan ini
            $employeeHolidays = \App\Models\Holiday::where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where(function ($query) use ($employee) {
                    $query->whereDoesntHave('employees')
                          ->orWhereHas('employees', function ($q) use ($employee) {
                              $q->where('employee_id', $employee->id);
                          });
                })
                ->pluck('date')
                ->map(fn($date) => $date->toDateString())
                ->toArray();

            // Hitung Jam Kerja Standar untuk Karyawan ini
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $standardHours = 0;
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateStr = $date->toDateString();
                if (in_array($dateStr, $employeeHolidays)) {
                    continue; // Libur nasional/perusahaan tidak dihitung sebagai jam standar bulanan
                }
                
                $sched = $employee->getScheduleForDate($dateStr);
                $dayHours = 0;
                if (!$sched->is_off && $sched->clock_in && $sched->clock_out) {
                    try {
                        $sIn = Carbon::parse($dateStr . ' ' . $sched->clock_in);
                        $sOut = Carbon::parse($dateStr . ' ' . $sched->clock_out);
                        if ($sOut->greaterThan($sIn)) {
                            $durationMinutes = $sOut->diffInMinutes($sIn, true);
                            $breakMinutes = 0;
                            // Apply generic shift break rule: > 5 hours (300 minutes) duration -> deduct 1 hour break (if overlap)
                            if ($durationMinutes > 300) {
                                $breakStart = Carbon::parse($dateStr . ' 11:00:00');
                                $breakEnd = Carbon::parse($dateStr . ' 12:00:00');
                                $overlapStart = $sIn->greaterThan($breakStart) ? $sIn : $breakStart;
                                $overlapEnd = $sOut->lessThan($breakEnd) ? $sOut : $breakEnd;
                                if ($overlapEnd->greaterThan($overlapStart)) {
                                    $breakMinutes = $overlapEnd->diffInMinutes($overlapStart, true);
                                }
                            }
                            $dayHours = round(($durationMinutes - $breakMinutes) / 60, 1);
                        }
                    } catch (\Exception $e) {
                        // fallback
                    }
                }
                $standardHours += $dayHours;
            }

            // Hitung Lembur disetujui (Approved Overtimes) pada periode ini
            $totalOvertimeHours = Overtime::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('hours');

            $overtimePay = $totalOvertimeHours * $employee->overtime_rate;

            // Hitung Kasbon disetujui (Approved Cash Advances) pada periode ini
            $cashAdvanceDeduction = CashAdvance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $basicSalary = $employee->basic_salary;
            $totalAllowance = $employee->allowances->sum('amount');
            $hoursWorked = 0;
            $attendanceDeduction = 0;

            if ($employee->salary_type === 'hourly') {
                $attendances = \App\Models\Attendance::where('tenant_id', $tenantId)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'present')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get();

                foreach ($attendances as $att) {
                    $attDate = $att->date;
                    $sched = $employee->getScheduleForDate($attDate);
                    $schedDurationMinutes = 0;
                    if (!$sched->is_off && $sched->clock_in && $sched->clock_out) {
                        try {
                            $sIn = Carbon::parse($attDate->toDateString() . ' ' . $sched->clock_in);
                            $sOut = Carbon::parse($attDate->toDateString() . ' ' . $sched->clock_out);
                            if ($sOut->greaterThan($sIn)) {
                                $schedDurationMinutes = $sOut->diffInMinutes($sIn, true);
                            }
                        } catch (\Exception $e) {}
                    }

                    if ($att->clock_in && $att->clock_out) {
                        try {
                            $in = Carbon::parse($att->clock_in);
                            $out = Carbon::parse($att->clock_out);
                            
                            // Jika clock_in sebelum jam 11:00, bulatkan ke jam masuk standar agar keterlambatan tidak memotong jam kerja
                            $calcIn = $in;
                            $schedInLimit = Carbon::parse($attDate->toDateString() . ' 11:00:00');
                            if ($in->lessThanOrEqualTo($schedInLimit) && $sched->clock_in) {
                                $schedInTime = Carbon::parse($attDate->toDateString() . ' ' . $sched->clock_in);
                                $calcIn = $schedInTime;
                            }
                            
                            if ($out->greaterThan($calcIn)) {
                                $rawMinutes = $out->diffInMinutes($calcIn, true);
                                
                                // Potongan istirahat 11:00 - 12:00 jika durasi jam kerja terjadwal > 5 jam (300 menit)
                                $breakMinutes = 0;
                                if ($schedDurationMinutes > 300) {
                                    $breakStart = Carbon::parse($attDate->toDateString() . ' 11:00:00');
                                    $breakEnd = Carbon::parse($attDate->toDateString() . ' 12:00:00');
                                    
                                    $overlapStart = $calcIn->greaterThan($breakStart) ? $calcIn : $breakStart;
                                    $overlapEnd = $out->lessThan($breakEnd) ? $out : $breakEnd;
                                    
                                    if ($overlapEnd->greaterThan($overlapStart)) {
                                        $breakMinutes = $overlapEnd->diffInMinutes($overlapStart, true);
                                    }
                                }
                                
                                $hoursWorked += round(($rawMinutes - $breakMinutes) / 60, 1);
                            } else {
                                $hoursWorked += 8; // standard fallback
                            }
                        } catch (\Exception $e) {
                            $hoursWorked += 8;
                        }
                    } else {
                        $hoursWorked += 8; // standard fallback
                    }
                }

                // Basic salary is hourly rate * hours worked
                $basicSalary = $hoursWorked * $employee->basic_salary;
            } else {
                // Bulanan (Fixed): Hitung potongan absensi
                // Ambil semua data absensi dalam periode ini
                $attendances = \App\Models\Attendance::where('tenant_id', $tenantId)
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get()
                    ->keyBy(function ($att) {
                        return \Carbon\Carbon::parse($att->date)->toDateString();
                    });

                $deductionHours = 0;
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $dateStr = $date->toDateString();

                    // Lewati jika hari libur nasional/perusahaan
                    if (in_array($dateStr, $employeeHolidays)) {
                        continue;
                    }

                    $sched = $employee->getScheduleForDate($dateStr);
                    // Jika hari ini libur terjadwal untuk karyawan tersebut, lewati (tidak dihitung denda absen / alpha)
                    if ($sched->is_off) {
                        continue;
                    }

                    // Tentukan jam kerja standar hari ini
                    $dayHours = 0;
                    $schedDurationMinutes = 0;
                    if ($sched->clock_in && $sched->clock_out) {
                        try {
                            $sIn = Carbon::parse($dateStr . ' ' . $sched->clock_in);
                            $sOut = Carbon::parse($dateStr . ' ' . $sched->clock_out);
                            if ($sOut->greaterThan($sIn)) {
                                $schedDurationMinutes = $sOut->diffInMinutes($sIn, true);
                                $schedBreak = 0;
                                if ($schedDurationMinutes > 300) {
                                    $breakStart = Carbon::parse($dateStr . ' 11:00:00');
                                    $breakEnd = Carbon::parse($dateStr . ' 12:00:00');
                                    $overlapStart = $sIn->greaterThan($breakStart) ? $sIn : $breakStart;
                                    $overlapEnd = $sOut->lessThan($breakEnd) ? $sOut : $breakEnd;
                                    if ($overlapEnd->greaterThan($overlapStart)) {
                                        $schedBreak = $overlapEnd->diffInMinutes($overlapStart, true);
                                    }
                                }
                                $dayHours = round(($schedDurationMinutes - $schedBreak) / 60, 1);
                            }
                        } catch (\Exception $e) {
                            // fallback
                        }
                    }

                    if ($attendances->has($dateStr)) {
                        $att = $attendances->get($dateStr);
                        
                        if ($att->status === 'present') {
                            $workHours = $dayHours;
                            if ($att->clock_in && $att->clock_out) {
                                try {
                                    $inTime = Carbon::parse($dateStr . ' ' . $att->clock_in);
                                    $outTime = Carbon::parse($dateStr . ' ' . $att->clock_out);
                                    
                                    // Jika clock_in sebelum jam 11:00, bulatkan ke jam masuk standar agar keterlambatan tidak memotong jam kerja
                                    $calcInTime = $inTime;
                                    $schedInLimit = Carbon::parse($dateStr . ' 11:00:00');
                                    if ($inTime->lessThanOrEqualTo($schedInLimit) && $sched->clock_in) {
                                        $schedInTime = Carbon::parse($dateStr . ' ' . $sched->clock_in);
                                        $calcInTime = $schedInTime;
                                    }
                                    
                                    if ($outTime->greaterThan($calcInTime)) {
                                        $rawMinutes = $outTime->diffInMinutes($calcInTime, true);
                                        
                                        // Potongan istirahat 11:00 - 12:00 jika durasi jam kerja terjadwal > 5 jam (300 menit)
                                        $breakMinutes = 0;
                                        if ($schedDurationMinutes > 300) {
                                            $breakStart = Carbon::parse($dateStr . ' 11:00:00');
                                            $breakEnd = Carbon::parse($dateStr . ' 12:00:00');
                                            
                                            $overlapStart = $calcInTime->greaterThan($breakStart) ? $calcInTime : $breakStart;
                                            $overlapEnd = $outTime->lessThan($breakEnd) ? $outTime : $breakEnd;
                                            
                                            if ($overlapEnd->greaterThan($overlapStart)) {
                                                $breakMinutes = $overlapEnd->diffInMinutes($overlapStart, true);
                                            }
                                        }
                                        
                                        $workHours = round(($rawMinutes - $breakMinutes) / 60, 1);
                                    }
                                } catch (\Exception $e) {
                                    $workHours = $dayHours;
                                }
                            }
                            
                            // Jika ada potongan (is_deducted = true) dan jam kerja kurang dari standar
                            if ($att->is_deducted && $workHours < $dayHours) {
                                $deductionHours += ($dayHours - $workHours);
                            }
                        } else {
                            // Jika status bukan present (misal alpha/sakit/izin) dan dipotong gaji
                            if ($att->is_deducted) {
                                $deductionHours += $dayHours;
                            }
                        }
                    } else {
                        // Jika tidak ada data presensi sama sekali, otomatis dianggap Mangkir (Alfa) dan dipotong gaji
                        $deductionHours += $dayHours;
                    }
                }

                if ($standardHours > 0 && $deductionHours > 0) {
                    $hourlyRate = ($employee->basic_salary + $totalAllowance) / $standardHours;
                    $attendanceDeduction = $deductionHours * $hourlyRate;
                }
            }

            // totalAllowance already defined above

            // Hitung Denda Keterlambatan dari data kehadiran
            $lateDeduction = \App\Models\Attendance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('late_penalty');

            // Cek apakah sudah ada data slip gaji sebelumnya untuk mengambil manual adjustments
            $existingAddition = 0;
            $existingDeduction = 0;
            $existingNotes = null;
            $existingOtherDeductions = 0;

            if ($existingPayroll) {
                $existingAddition = $existingPayroll->salary_adjustment_addition;
                $existingDeduction = $existingPayroll->salary_adjustment_deduction;
                $existingNotes = $existingPayroll->salary_adjustment_notes;
                $existingOtherDeductions = $existingPayroll->other_deductions;
            }

            $netSalary = $basicSalary + $totalAllowance + $overtimePay + $existingAddition - $cashAdvanceDeduction - $attendanceDeduction - $lateDeduction - $existingOtherDeductions - $existingDeduction;
            if ($netSalary < 0) {
                $netSalary = 0; // Gaji tidak boleh minus
            }

            $payroll = Payroll::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'employee_id' => $employee->id,
                    'period' => $period,
                ],
                [
                    'salary_type' => $employee->salary_type,
                    'basic_salary' => $basicSalary,
                    'hours_worked' => $hoursWorked,
                    'allowance' => $totalAllowance,
                    'overtime_pay' => $overtimePay,
                    'cash_advance_deduction' => $cashAdvanceDeduction,
                    'attendance_deduction' => $attendanceDeduction,
                    'late_deduction' => $lateDeduction,
                    'other_deductions' => $existingOtherDeductions,
                    'salary_adjustment_addition' => $existingAddition,
                    'salary_adjustment_deduction' => $existingDeduction,
                    'salary_adjustment_notes' => $existingNotes,
                    'net_salary' => $netSalary,
                    'status' => 'draft',
                ]
            );

            // Snapshot dynamic allowances for historical purposes
            $payroll->allowances()->delete();
            foreach ($employee->allowances as $empAllowance) {
                if ($empAllowance->amount > 0 && $empAllowance->allowanceType) {
                    \App\Models\PayrollAllowance::create([
                        'tenant_id' => $tenantId,
                        'payroll_id' => $payroll->id,
                        'name' => $empAllowance->allowanceType->name,
                        'amount' => $empAllowance->amount,
                    ]);
                }
            }

            $generatedCount++;
        }

        return back()->with('success', "Berhasil men-generate/memperbarui gaji untuk {$generatedCount} karyawan pada periode {$period}.");
    }

    public function show(Payroll $payroll)
    {
        abort_unless($payroll->tenant_id === Auth::user()->tenant_id, 403);
        $payroll->load(['employee', 'allowances']);

        // Dapatkan rincian lembur disetujui
        $tenant = Auth::user()->tenant;
        $tenantId = $tenant->id;
        [$startDate, $endDate] = $tenant->getCutoffRange($payroll->period);

        // Ambil semua hari libur dalam periode bulan ini yang berlaku untuk karyawan ini
        $employee = $payroll->employee;
        $employeeHolidays = \App\Models\Holiday::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) use ($employee) {
                $query->whereDoesntHave('employees')
                      ->orWhereHas('employees', function ($q) use ($employee) {
                          $q->where('employee_id', $employee->id);
                      });
            })
            ->pluck('date')
            ->map(fn($date) => $date->toDateString())
            ->toArray();

        // Hitung Jam Kerja Standar untuk bulan ini
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $standardHours = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->toDateString();
            if (in_array($dateStr, $employeeHolidays)) {
                continue; // Libur nasional/perusahaan tidak dihitung sebagai jam standar bulanan
            }
            
            $sched = $employee->getScheduleForDate($dateStr);
            if (!$sched->is_off && $sched->clock_in && $sched->clock_out) {
                try {
                    $sIn = Carbon::parse($dateStr . ' ' . $sched->clock_in);
                    $sOut = Carbon::parse($dateStr . ' ' . $sched->clock_out);
                    if ($sOut->greaterThan($sIn)) {
                        $durationMinutes = $sOut->diffInMinutes($sIn, true);
                        $breakMinutes = 0;
                        if ($durationMinutes > 300) {
                            $breakStart = Carbon::parse($dateStr . ' 11:00:00');
                            $breakEnd = Carbon::parse($dateStr . ' 12:00:00');
                            $overlapStart = $sIn->greaterThan($breakStart) ? $sIn : $breakStart;
                            $overlapEnd = $sOut->lessThan($breakEnd) ? $sOut : $breakEnd;
                            if ($overlapEnd->greaterThan($overlapStart)) {
                                $breakMinutes = $overlapEnd->diffInMinutes($overlapStart, true);
                            }
                        }
                        $standardHours += round(($durationMinutes - $breakMinutes) / 60, 1);
                    }
                } catch (\Exception $e) {}
            }
        }

        $overtimeHours = Overtime::where('employee_id', $payroll->employee_id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('hours');

        // Dapatkan rincian kasbon
        $cashAdvances = CashAdvance::where('employee_id', $payroll->employee_id)
            ->whereIn('status', ['approved', 'settled'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Hitung jam absen (potongan) untuk ditampilkan
        $deductionHours = 0;
        $hourlyRate = 0;
        if ($standardHours > 0) {
            $hourlyRate = ($payroll->basic_salary + $payroll->allowance) / $standardHours;
        }

        if ($payroll->salary_type === 'monthly' && $hourlyRate > 0 && $payroll->attendance_deduction > 0) {
            $deductionHours = round($payroll->attendance_deduction / $hourlyRate, 1);
        }

        // Hitung data keterlambatan untuk slip
        $lateRecords = \App\Models\Attendance::where('tenant_id', $tenantId)
            ->where('employee_id', $payroll->employee_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('late_minutes', '>', 0)
            ->get();
        $totalLateMinutes = $lateRecords->sum('late_minutes');
        $totalLateDays = $lateRecords->count();

        return view('hrd.payroll.slip', compact(
            'payroll', 
            'overtimeHours', 
            'cashAdvances', 
            'standardHours', 
            'deductionHours', 
            'hourlyRate',
            'totalLateMinutes',
            'totalLateDays'
        ));
    }

    public function pay(Payroll $payroll)
    {
        abort_unless($payroll->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($payroll->status === 'paid') {
            return back()->with('error', 'Gaji ini sudah dibayarkan.');
        }

        // Tandai kasbon di bulan tersebut sebagai 'settled' agar tidak terpotong double nantinya
        $tenant = Auth::user()->tenant;
        [$startDate, $endDate] = $tenant->getCutoffRange($payroll->period);

        CashAdvance::where('employee_id', $payroll->employee_id)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->update(['status' => 'settled']);

        $payroll->update([
            'status' => 'paid',
            'payment_date' => today(),
        ]);

        return back()->with('success', 'Gaji karyawan berhasil dibayarkan dan status diperbarui.');
    }

    public function update(Request $request, Payroll $payroll)
    {
        abort_unless($payroll->tenant_id === Auth::user()->tenant_id, 403);

        if ($payroll->status === 'paid') {
            return back()->with('error', 'Tidak dapat mengubah slip gaji yang sudah dibayarkan.');
        }

        $request->validate([
            'salary_adjustment_addition' => 'nullable|numeric|min:0',
            'salary_adjustment_deduction' => 'nullable|numeric|min:0',
            'salary_adjustment_notes' => 'nullable|string|max:1000',
        ]);

        $addition = $request->input('salary_adjustment_addition', 0) ?: 0;
        $deduction = $request->input('salary_adjustment_deduction', 0) ?: 0;
        $notes = $request->input('salary_adjustment_notes');

        // Hitung ulang net salary
        $netSalary = $payroll->basic_salary + $payroll->allowance + $payroll->overtime_pay + $addition 
            - $payroll->cash_advance_deduction - $payroll->attendance_deduction - $payroll->late_deduction 
            - $payroll->other_deductions - $deduction;

        if ($netSalary < 0) {
            $netSalary = 0;
        }

        $payroll->update([
            'salary_adjustment_addition' => $addition,
            'salary_adjustment_deduction' => $deduction,
            'salary_adjustment_notes' => $notes,
            'net_salary' => $netSalary,
        ]);

        return back()->with('success', 'Penyesuaian gaji berhasil disimpan.');
    }

    public function destroy(Payroll $payroll)
    {
        abort_unless($payroll->tenant_id === Auth::user()->tenant_id, 403);

        if ($payroll->status === 'paid') {
            return back()->with('error', 'Tidak dapat menghapus gaji yang sudah dibayarkan.');
        }

        $payroll->delete();
        return back()->with('success', 'Slip gaji berhasil dihapus.');
    }
}

