<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LatePenaltyRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendanceController extends Controller
{
    protected function employee()
    {
        return Auth::guard('employee')->user();
    }

    public function dashboard(Request $request)
    {
        $employee = $this->employee();
        $today = today()->toDateString();

        $period = $request->get('period', Carbon::now()->format('Y-m'));
        $carbonPeriod = Carbon::parse($period . '-01');
        $startOfMonth = $carbonPeriod->copy()->startOfMonth()->toDateString();
        $endOfMonth = $carbonPeriod->copy()->endOfMonth()->toDateString();

        $todayAttendance = Attendance::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        // Attendance history for the selected month
        $history = Attendance::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('date', '>=', $startOfMonth)
            ->where('date', '<=', $endOfMonth)
            ->orderBy('date', 'asc')
            ->get();

        $now = Carbon::now()->format('H:i');
        $sched = $employee->getScheduleForDate($today);
        $scheduleIn = $sched->clock_in ? date('H:i', strtotime($sched->clock_in)) : ($sched->is_off ? 'Libur' : '-');
        $scheduleOut = $sched->clock_out ? date('H:i', strtotime($sched->clock_out)) : ($sched->is_off ? 'Libur' : '-');

        // Statistics for current period
        $monthlyAttendances = Attendance::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $stats = [
            'hadir' => $monthlyAttendances->where('status', 'present')->count(),
            'terlambat' => $monthlyAttendances->where('status', 'present')->where('late_minutes', '>', 0)->count(),
            'izin' => $monthlyAttendances->whereIn('status', ['izin', 'permission'])->count(),
            'sakit' => $monthlyAttendances->whereIn('status', ['sakit', 'sick'])->count(),
            'cuti' => $monthlyAttendances->whereIn('status', ['cuti', 'leave'])->count(),
            'alpha' => $monthlyAttendances->where('status', 'alpha')->count(),
            'late_minutes' => $monthlyAttendances->sum('late_minutes'),
        ];

        // Payrolls, cash advances, and leave requests
        $payrolls = $employee->payrolls()->with('allowances')->orderByDesc('period')->get();
        $cashAdvances = $employee->cashAdvances()->orderByDesc('date')->get();
        $leaveRequests = \App\Models\LeaveRequest::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->get();
        $corrections = \App\Models\AttendanceCorrection::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->get();

        return view('employee.dashboard', compact(
            'employee',
            'todayAttendance',
            'history',
            'now',
            'scheduleIn',
            'scheduleOut',
            'stats',
            'payrolls',
            'cashAdvances',
            'leaveRequests',
            'corrections',
            'period'
        ));
    }

    public function clockIn(Request $request)
    {
        $employee = $this->employee();
        $today    = today()->toDateString();
        $now      = Carbon::now();

        // Prevent double clock-in
        $existing = Attendance::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            return back()->with('error', 'Anda sudah melakukan Clock In hari ini pada pukul ' . date('H:i', strtotime($existing->clock_in)) . '.');
        }

        // Validate location & photo if not testing
        $tenant = $employee->tenant;
        if (!app()->environment('testing')) {
            if ($tenant && $tenant->office_latitude && $tenant->office_longitude) {
                if (!$request->latitude || !$request->longitude) {
                    return back()->with('error', 'Lokasi GPS diperlukan untuk melakukan absensi.');
                }
                $distance = $this->calculateDistance($request->latitude, $request->longitude, $tenant->office_latitude, $tenant->office_longitude);
                if ($distance > ($tenant->office_radius ?: 20)) {
                    return back()->with('error', 'Anda berada di luar radius kantor. Jarak: ' . round($distance, 1) . ' meter. Maksimum: ' . ($tenant->office_radius ?: 20) . ' meter.');
                }
            }
            if (!$request->photo) {
                return back()->with('error', 'Foto selfie diperlukan untuk melakukan absensi.');
            }
        }

        $photoPath = null;
        if ($request->photo) {
            $photoPath = $this->saveSelfie($request->photo, $employee->id, 'in');
        }

        // Calculate late minutes & penalty
        $lateMinutes = 0;
        $latePenalty = 0;
        $sched = $employee->getScheduleForDate($today);
        if (!$sched->is_off && $sched->clock_in) {
            $schedTime = Carbon::parse($today . ' ' . $sched->clock_in);
            if ($now->greaterThan($schedTime)) {
                $lateMinutes = $now->diffInMinutes($schedTime, true);
                $rule = LatePenaltyRule::where('tenant_id', $employee->tenant_id)
                    ->where('min_minutes', '<=', $lateMinutes)
                    ->orderBy('min_minutes', 'desc')
                    ->first();
                if ($rule) {
                    $latePenalty = $rule->penalty_amount;
                }
            }
        }

        Attendance::updateOrCreate(
            ['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => $today],
            [
                'status'       => 'present',
                'clock_in'     => $now->format('H:i:s'),
                'schedule_clock_in'  => $sched->clock_in,
                'schedule_clock_out' => $sched->clock_out,
                'schedule_is_off'    => (bool)$sched->is_off,
                'is_deducted'  => false,
                'late_minutes' => $lateMinutes,
                'late_penalty' => $latePenalty,
                'latitude_in'  => $request->latitude,
                'longitude_in' => $request->longitude,
                'photo_in'     => $photoPath,
            ]
        );

        return back()->with('success', 'Clock In berhasil pada pukul ' . $now->format('H:i') . ($lateMinutes > 0 ? '. Terlambat ' . $lateMinutes . ' menit.' : '.'));
    }

    public function clockOut(Request $request)
    {
        $employee = $this->employee();
        $today    = today()->toDateString();
        $now      = Carbon::now();

        $attendance = Attendance::where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return back()->with('error', 'Anda belum melakukan Clock In hari ini.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'Anda sudah melakukan Clock Out pada pukul ' . date('H:i', strtotime($attendance->clock_out)) . '.');
        }

        // Validate location & photo if not testing
        $tenant = $employee->tenant;
        if (!app()->environment('testing')) {
            if ($tenant && $tenant->office_latitude && $tenant->office_longitude) {
                if (!$request->latitude || !$request->longitude) {
                    return back()->with('error', 'Lokasi GPS diperlukan untuk melakukan absensi.');
                }
                $distance = $this->calculateDistance($request->latitude, $request->longitude, $tenant->office_latitude, $tenant->office_longitude);
                if ($distance > ($tenant->office_radius ?: 20)) {
                    return back()->with('error', 'Anda berada di luar radius kantor. Jarak: ' . round($distance, 1) . ' meter. Maksimum: ' . ($tenant->office_radius ?: 20) . ' meter.');
                }
            }
            if (!$request->photo) {
                return back()->with('error', 'Foto selfie diperlukan untuk melakukan absensi.');
            }
        }

        $photoPath = null;
        if ($request->photo) {
            $photoPath = $this->saveSelfie($request->photo, $employee->id, 'out');
        }

        $attendance->update([
            'clock_out' => $now->format('H:i:s'),
            'latitude_out' => $request->latitude,
            'longitude_out' => $request->longitude,
            'photo_out' => $photoPath,
        ]);

        return back()->with('success', 'Clock Out berhasil pada pukul ' . $now->format('H:i') . '. Sampai jumpa!');
    }

    public function storeLeave(Request $request)
    {
        $employee = $this->employee();

        $request->validate([
            'type' => 'required|in:sick,permission,leave',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Cek bentrok tanggal (apakah sudah ada pengajuan aktif pada rentang tanggal tersebut)
        $exists = \App\Models\LeaveRequest::where('employee_id', $employee->id)
            ->where('status', '!=', 'rejected')
            ->where(function($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                      });
            })
            ->exists();

        if ($exists) {
            return back()->with('error', 'Anda sudah memiliki pengajuan izin/sakit/cuti yang aktif pada rentang tanggal ini.');
        }

        \App\Models\LeaveRequest::create([
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'status' => 'pending',
            'is_deducted' => false,
        ]);

        return back()->with('success', 'Pengajuan izin/sakit/cuti berhasil dikirim dan menunggu persetujuan HRD.');
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in meters
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            
        return $angle * $earthRadius;
    }

    private function saveSelfie($base64Data, $employeeId, $type)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $typeMatch)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $ext = strtolower($typeMatch[1]);
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $ext = 'jpg';
            }
            $data = base64_decode($data);
        } else {
            $data = base64_decode($base64Data);
            $ext = 'jpg';
        }

        if ($data === false) {
            return null;
        }

        $fileName = $employeeId . '_' . $type . '_' . time() . '_' . uniqid() . '.' . $ext;
        $dir = public_path('storage/attendance');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($dir . '/' . $fileName, $data);
        return 'storage/attendance/' . $fileName;
    }

}
