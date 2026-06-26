<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $leaveRequests = LeaveRequest::with(['employee', 'approvedBy'])
            ->where('tenant_id', $tenantId)
            ->orderBy('start_date', 'desc')
            ->get();

        return view('hrd.leave_requests.index', compact('employees', 'leaveRequests'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:sick,permission,leave',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $employeeExists = Employee::where('tenant_id', $tenantId)->where('id', $request->employee_id)->exists();
        if (!$employeeExists) {
            return back()->withErrors(['employee_id' => 'Karyawan tidak valid untuk perusahaan Anda.']);
        }

        $isDeducted = $request->has('is_deducted');

        LeaveRequest::create([
            'tenant_id' => $tenantId,
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'status' => 'pending',
            'is_deducted' => $isDeducted,
        ]);

        return back()->with('success', 'Pengajuan izin/cuti berhasil diajukan. Menunggu persetujuan.');
    }

    public function update(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:sick,permission,leave',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $employeeExists = Employee::where('tenant_id', Auth::user()->tenant_id)->where('id', $request->employee_id)->exists();
        if (!$employeeExists) {
            return back()->withErrors(['employee_id' => 'Karyawan tidak valid untuk perusahaan Anda.']);
        }

        $isDeducted = $request->has('is_deducted');

        if ($leave->status === 'approved') {
            $oldStart = Carbon::parse($leave->start_date);
            $oldEnd = Carbon::parse($leave->end_date);
            Attendance::where('tenant_id', $leave->tenant_id)
                ->where('employee_id', $leave->employee_id)
                ->whereBetween('date', [$oldStart->toDateString(), $oldEnd->toDateString()])
                ->delete();
        }

        $leave->update([
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'is_deducted' => $isDeducted,
        ]);

        if ($leave->status === 'approved') {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                Attendance::updateOrCreate(
                    [
                        'tenant_id' => $leave->tenant_id,
                        'employee_id' => $leave->employee_id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'status' => $leave->type,
                        'clock_in' => null,
                        'clock_out' => null,
                        'is_deducted' => $leave->is_deducted,
                        'late_minutes' => 0,
                        'late_penalty' => 0,
                        'notes' => 'Disetujui ' . $this->getTypeLabel($leave->type) . ': ' . $leave->notes,
                    ]
                );
            }
        }

        return back()->with('success', 'Pengajuan izin/cuti berhasil diperbarui.');
    }

    public function destroy(LeaveRequest $leave)
    {
        abort_unless($leave->tenant_id === Auth::user()->tenant_id, 403);

        if ($leave->status === 'approved') {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            Attendance::where('tenant_id', $leave->tenant_id)
                ->where('employee_id', $leave->employee_id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->delete();
        }

        $leave->delete();

        return back()->with('success', 'Pengajuan izin/cuti berhasil dihapus.');
    }

    public function approve(LeaveRequest $leave)
    {
        abort_unless($leave->tenant_id === Auth::user()->tenant_id, 403);

        $leave->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            Attendance::updateOrCreate(
                [
                    'tenant_id' => $leave->tenant_id,
                    'employee_id' => $leave->employee_id,
                    'date' => $date->toDateString(),
                ],
                [
                    'status' => $leave->type,
                    'clock_in' => null,
                    'clock_out' => null,
                    'is_deducted' => $leave->is_deducted,
                    'late_minutes' => 0,
                    'late_penalty' => 0,
                    'notes' => 'Disetujui ' . $this->getTypeLabel($leave->type) . ': ' . $leave->notes,
                ]
            );
        }

        return back()->with('success', 'Pengajuan izin/cuti berhasil disetujui.');
    }

    public function reject(LeaveRequest $leave)
    {
        abort_unless($leave->tenant_id === Auth::user()->tenant_id, 403);

        $leave->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        Attendance::where('tenant_id', $leave->tenant_id)
            ->where('employee_id', $leave->employee_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->delete();

        return back()->with('success', 'Pengajuan izin/cuti telah ditolak.');
    }

    private function getTypeLabel($type)
    {
        switch ($type) {
            case 'sick': return 'Sakit';
            case 'permission': return 'Izin';
            case 'leave': return 'Cuti';
            default: return $type;
        }
    }
}
