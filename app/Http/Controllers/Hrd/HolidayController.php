<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $holidays = Holiday::with('employees')->where('tenant_id', $tenantId)->orderBy('date', 'desc')->get();
        $employees = \App\Models\Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return view('hrd.holidays.index', compact('holidays', 'employees'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date,NULL,id,tenant_id,' . $tenantId,
        ]);

        Holiday::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'date' => $request->date,
        ]);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function update(Request $request, Holiday $holiday)
    {
        abort_unless($holiday->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date|unique:holidays,date,' . $holiday->id . ',id,tenant_id,' . Auth::user()->tenant_id,
        ]);

        $holiday->update([
            'name' => $request->name,
            'date' => $request->date,
        ]);

        return back()->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday)
    {
        abort_unless($holiday->tenant_id === Auth::user()->tenant_id, 403);
        $holiday->delete();
        return back()->with('success', 'Hari libur berhasil dihapus.');
    }

    public function updateEmployees(Request $request, Holiday $holiday)
    {
        abort_unless($holiday->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        if ($request->filled('employee_ids')) {
            $allowedCount = \App\Models\Employee::where('tenant_id', Auth::user()->tenant_id)
                ->whereIn('id', $request->employee_ids)
                ->count();
            if ($allowedCount !== count($request->employee_ids)) {
                return back()->withErrors(['employee_ids' => 'Karyawan tidak valid untuk perusahaan Anda.']);
            }
        }

        $holiday->employees()->sync($request->employee_ids ?? []);

        return back()->with('success', 'Daftar karyawan libur berhasil diperbarui.');
    }
}
