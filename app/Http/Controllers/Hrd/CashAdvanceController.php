<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\CashAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashAdvanceController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $cashAdvances = CashAdvance::with('employee')->where('tenant_id', $tenantId)->orderBy('date', 'desc')->get();
        $employees = Employee::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('hrd.cash_advances.index', compact('cashAdvances', 'employees'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string|max:255',
        ]);

        $employee = Employee::find($request->employee_id);
        abort_unless($employee->tenant_id === $tenantId, 403);

        CashAdvance::create([
            'tenant_id' => $tenantId,
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan kasbon berhasil diajukan.');
    }

    public function update(Request $request, CashAdvance $cashAdvance)
    {
        abort_unless($cashAdvance->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($cashAdvance->status === 'pending', 400, 'Hanya kasbon status pending yang dapat diedit.');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string|max:255',
        ]);

        $employee = Employee::find($request->employee_id);
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);

        $cashAdvance->update([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'amount' => $request->amount,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Kasbon berhasil diperbarui.');
    }

    public function destroy(CashAdvance $cashAdvance)
    {
        abort_unless($cashAdvance->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($cashAdvance->status === 'pending', 400, 'Hanya kasbon status pending yang dapat dihapus.');

        $cashAdvance->delete();
        return back()->with('success', 'Kasbon berhasil dihapus.');
    }

    public function approve(CashAdvance $cashAdvance)
    {
        abort_unless($cashAdvance->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($cashAdvance->status === 'pending', 400, 'Kasbon sudah diproses.');

        $cashAdvance->update(['status' => 'approved']);
        return back()->with('success', 'Kasbon disetujui.');
    }

    public function reject(CashAdvance $cashAdvance)
    {
        abort_unless($cashAdvance->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($cashAdvance->status === 'pending', 400, 'Kasbon sudah diproses.');

        $cashAdvance->update(['status' => 'rejected']);
        return back()->with('success', 'Kasbon ditolak.');
    }
}
