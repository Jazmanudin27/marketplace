<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $overtimes = Overtime::with('employee')->where('tenant_id', $tenantId)->orderBy('date', 'desc')->get();
        $employees = Employee::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('hrd.overtime.index', compact('overtimes', 'employees'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'description' => 'nullable|string|max:255',
        ]);

        $employee = Employee::find($request->employee_id);
        abort_unless($employee->tenant_id === $tenantId, 403);

        Overtime::create([
            'tenant_id' => $tenantId,
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'hours' => $request->hours,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan lembur berhasil diajukan.');
    }

    public function update(Request $request, Overtime $overtime)
    {
        abort_unless($overtime->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($overtime->status === 'pending', 400, 'Hanya pengajuan pending yang dapat diedit.');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'description' => 'nullable|string|max:255',
        ]);

        $employee = Employee::find($request->employee_id);
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);

        $overtime->update([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'hours' => $request->hours,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Data lembur berhasil diperbarui.');
    }

    public function destroy(Overtime $overtime)
    {
        abort_unless($overtime->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($overtime->status === 'pending', 400, 'Hanya pengajuan pending yang dapat dihapus.');

        $overtime->delete();
        return back()->with('success', 'Data lembur berhasil dihapus.');
    }

    public function approve(Overtime $overtime)
    {
        abort_unless($overtime->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($overtime->status === 'pending', 400, 'Pengajuan lembur sudah diproses.');

        $overtime->update(['status' => 'approved']);
        return back()->with('success', 'Pengajuan lembur berhasil disetujui.');
    }

    public function reject(Overtime $overtime)
    {
        abort_unless($overtime->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($overtime->status === 'pending', 400, 'Pengajuan lembur sudah diproses.');

        $overtime->update(['status' => 'rejected']);
        return back()->with('success', 'Pengajuan lembur ditolak.');
    }
}
