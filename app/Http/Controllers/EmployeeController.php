<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $employees = Employee::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('employees.index', compact('employees'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        Employee::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'position' => $request->position,
            'address' => $request->address,
            'join_date' => $request->join_date,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        return back()->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee)
    {
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $employee->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'position' => $request->position,
            'address' => $request->address,
            'join_date' => $request->join_date,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);
        $employee->delete();
        return back()->with('success', 'Data karyawan berhasil dihapus.');
    }
}
