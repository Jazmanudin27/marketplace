<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $date = $request->get('date', today()->format('Y-m-d'));

        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $attendances = Attendance::where('tenant_id', $tenantId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $approvedLeaves = \App\Models\LeaveRequest::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get()
            ->keyBy('employee_id');

        $pendingCorrections = \App\Models\AttendanceCorrection::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->with('employee')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('hrd.attendance.index', compact('employees', 'attendances', 'date', 'approvedLeaves', 'pendingCorrections'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $date = $request->input('date', today()->format('Y-m-d'));
        $data = $request->input('attendance', []);

        foreach ($data as $employeeId => $record) {
            $status = $record['status'] ?? 'present';

            $clockIn = ($status === 'present') ? ($record['clock_in'] ?? null) : null;
            $clockOut = ($status === 'present') ? ($record['clock_out'] ?? null) : null;
            $notes = $record['notes'] ?? null;

            $isDeducted = true;

            $emp = Employee::where('tenant_id', $tenantId)->find($employeeId);
            if (!$emp) {
                continue;
            }
            $scheduleClockIn = null;
            $scheduleClockOut = null;
            $scheduleIsOff = false;

            if ($emp) {
                if (isset($record['schedule_clock_in']) || isset($record['schedule_clock_out'])) {
                    $scheduleClockIn = $record['schedule_clock_in'] ?? null;
                    $scheduleClockOut = $record['schedule_clock_out'] ?? null;
                    $scheduleIsOff = empty($scheduleClockIn) || empty($scheduleClockOut);

                    if ($scheduleClockIn && strlen($scheduleClockIn) === 5) {
                        $scheduleClockIn .= ':00';
                    }
                    if ($scheduleClockOut && strlen($scheduleClockOut) === 5) {
                        $scheduleClockOut .= ':00';
                    }
                } else {
                    $existingAtt = Attendance::where('tenant_id', $tenantId)
                        ->where('employee_id', $employeeId)
                        ->where('date', $date)
                        ->first();
                    if ($existingAtt && ($existingAtt->schedule_clock_in || $existingAtt->schedule_clock_out || $existingAtt->schedule_is_off)) {
                        $scheduleClockIn = $existingAtt->schedule_clock_in;
                        $scheduleClockOut = $existingAtt->schedule_clock_out;
                        $scheduleIsOff = (bool) $existingAtt->schedule_is_off;
                    } else {
                        $stdSched = $emp->getScheduleForDate($date);
                        $scheduleClockIn = $stdSched->clock_in;
                        $scheduleClockOut = $stdSched->clock_out;
                        $scheduleIsOff = (bool) $stdSched->is_off;
                    }
                }
            }

            $lateMinutes = 0;
            $latePenalty = 0;

            if ($status === 'present' && $clockIn && $emp) {
                try {
                    if (!$scheduleIsOff && $scheduleClockIn) {
                        $schedTime = \Carbon\Carbon::parse($date . ' ' . $scheduleClockIn);
                        $inTime = \Carbon\Carbon::parse($date . ' ' . $clockIn);
                        if ($inTime->greaterThan($schedTime)) {
                            $lateMinutes = $inTime->diffInMinutes($schedTime, true);

                            $rule = \App\Models\LatePenaltyRule::where('tenant_id', $tenantId)
                                ->where('min_minutes', '<=', $lateMinutes)
                                ->orderBy('min_minutes', 'desc')
                                ->first();

                            if ($rule) {
                                $latePenalty = $rule->penalty_amount;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // ignore parsing error
                }
            }

            Attendance::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'employee_id' => $employeeId,
                    'date' => $date,
                ],
                [
                    'status' => $status,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'schedule_clock_in' => $scheduleClockIn,
                    'schedule_clock_out' => $scheduleClockOut,
                    'schedule_is_off' => $scheduleIsOff,
                    'is_deducted' => $isDeducted,
                    'late_minutes' => $lateMinutes,
                    'late_penalty' => $latePenalty,
                    'notes' => $notes,
                ]
            );
        }

        return back()->with('success', 'Data absensi tanggal ' . date('d-m-Y', strtotime($date)) . ' berhasil disimpan.');
    }

    public function report(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $tenantId = $tenant->id;

        $period = $request->get('period', today()->format('Y-m'));

        [$startDate, $endDate] = $tenant->getCutoffRange($period);

        $startDay = (int) ($tenant->cutoff_start_day ?? 21);
        if ($startDay === 1) {
            $cutoffType = 'full-month';
        } else {
            $cutoffType = $startDay . '-' . ($startDay - 1);
        }

        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $attendances = Attendance::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $holidays = \App\Models\Holiday::where('tenant_id', $tenantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn($h) => $h->date->toDateString());

        $overtimes = \App\Models\Overtime::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $leaveRequests = \App\Models\LeaveRequest::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get()
            ->groupBy('employee_id');

        $dates = [];
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        $dayNamesIndo = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateStr = $d->toDateString();
            $dates[] = [
                'date' => $dateStr,
                'day_num' => $d->format('d'),
                'day_name' => $dayNamesIndo[$d->dayOfWeek],
                'is_sunday' => $d->dayOfWeek === 0,
            ];
        }

        return view('hrd.attendance.report', compact(
            'employees',
            'attendances',
            'holidays',
            'overtimes',
            'leaveRequests',
            'dates',
            'period',
            'startDate',
            'endDate',
            'cutoffType'
        ));
    }

    public function update(Request $request, Attendance $attendance)
    {
        // Placeholder for resource update if needed
        return back();
    }

    public function destroy(Attendance $attendance)
    {
        abort_unless($attendance->tenant_id === Auth::user()->tenant_id, 403);
        $attendance->delete();
        return back()->with('success', 'Data absensi berhasil dihapus.');
    }

    public function approveCorrection(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasPermissionTo('approve-attendance-corrections')) {
            abort(403, 'Akses Ditolak: Hanya Owner yang dapat menyetujui pengajuan koreksi.');
        }

        $tenantId = $user->tenant_id;
        $correction = \App\Models\AttendanceCorrection::where('tenant_id', $tenantId)
            ->findOrFail($id);

        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $emp = $correction->employee;

        // Find or create attendance record for the correction date
        $att = Attendance::where('tenant_id', $tenantId)
            ->where('employee_id', $correction->employee_id)
            ->whereDate('date', $correction->date)
            ->first();

        if (!$att) {
            $stdSched = $emp->getScheduleForDate($correction->date->toDateString());
            $att = new Attendance();
            $att->tenant_id = $tenantId;
            $att->employee_id = $correction->employee_id;
            $att->date = $correction->date->toDateString();
            $att->schedule_clock_in = $stdSched->clock_in;
            $att->schedule_clock_out = $stdSched->clock_out;
            $att->schedule_is_off = (bool) $stdSched->is_off;
            $att->status = 'present';
            $att->is_deducted = false;
        }

        if ($correction->clock_in) {
            $att->clock_in = $correction->clock_in;
        }
        if ($correction->clock_out) {
            $att->clock_out = $correction->clock_out;
        }

        // Recalculate late minutes & penalty if clock_in is changed
        if ($correction->clock_in) {
            $lateMinutes = 0;
            $latePenalty = 0;
            if (!$att->schedule_is_off && $att->schedule_clock_in) {
                $schedTime = \Carbon\Carbon::parse($att->date->toDateString() . ' ' . $att->schedule_clock_in);
                $inTime = \Carbon\Carbon::parse($att->date->toDateString() . ' ' . $att->clock_in);
                if ($inTime->greaterThan($schedTime)) {
                    $lateMinutes = $inTime->diffInMinutes($schedTime, true);
                    $rule = \App\Models\LatePenaltyRule::where('tenant_id', $tenantId)
                        ->where('min_minutes', '<=', $lateMinutes)
                        ->orderBy('min_minutes', 'desc')
                        ->first();
                    if ($rule) {
                        $latePenalty = $rule->penalty_amount;
                    }
                }
            }
            $att->late_minutes = $lateMinutes;
            $att->late_penalty = $latePenalty;
        }

        $att->save();

        $correction->update([
            'status' => 'approved',
            'admin_notes' => $request->admin_notes,
            'approved_by' => $user->id,
        ]);

        return back()->with('success', 'Pengajuan koreksi presensi disetujui.');
    }

    public function rejectCorrection(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasPermissionTo('approve-attendance-corrections')) {
            abort(403, 'Akses Ditolak: Hanya Owner yang dapat menolak pengajuan koreksi.');
        }

        $tenantId = $user->tenant_id;
        $correction = \App\Models\AttendanceCorrection::where('tenant_id', $tenantId)
            ->findOrFail($id);

        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $correction->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
            'approved_by' => $user->id,
        ]);

        return back()->with('success', 'Pengajuan koreksi presensi ditolak.');
    }

    public function storeCorrection(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date|before_or_equal:today',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        $employeeExists = Employee::where('tenant_id', $tenantId)->where('id', $request->employee_id)->exists();
        if (!$employeeExists) {
            return back()->withErrors(['employee_id' => 'Karyawan tidak valid untuk perusahaan Anda.']);
        }

        if (!$request->clock_in && !$request->clock_out) {
            return back()->with('error', 'Harap isi salah satu atau kedua jam masuk / pulang untuk dikoreksi.');
        }

        if ($request->clock_in && $request->clock_out) {
            if (strtotime($request->clock_out) <= strtotime($request->clock_in)) {
                return back()->with('error', 'Jam pulang harus setelah jam masuk.');
            }
        }

        // Check if there's already a pending correction request for this employee on this date
        $exists = \App\Models\AttendanceCorrection::where('tenant_id', $tenantId)
            ->where('employee_id', $request->employee_id)
            ->whereDate('date', $request->date)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return back()->with('error', 'Sudah ada pengajuan koreksi yang tertunda (pending) untuk karyawan dan tanggal ini.');
        }

        \App\Models\AttendanceCorrection::create([
            'tenant_id' => $tenantId,
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'clock_in' => $request->clock_in ? $request->clock_in . ':00' : null,
            'clock_out' => $request->clock_out ? $request->clock_out . ':00' : null,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan koreksi presensi berhasil dikirim dan menunggu persetujuan Owner.');
    }
}
