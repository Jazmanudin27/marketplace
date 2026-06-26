<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\AllowanceType;
use App\Models\EmployeeAllowance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $employees = Employee::with('allowances')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $allowanceTypes = AllowanceType::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('hrd.employees.index', compact('employees', 'allowanceTypes'));
    }

    public function create()
    {
        $employee = new Employee();
        return view('hrd.employees.form', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);
        return view('hrd.employees.form', compact('employee'));
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
            'salary_type' => 'monthly',
            'basic_salary' => 0,
            'allowance' => 0,
            'overtime_rate' => 0,
        ]);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
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

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function updateSalary(Request $request, Employee $employee)
    {
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'salary_type' => 'required|in:monthly,hourly',
            'basic_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|array',
            'allowances.*' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'schedules' => 'sometimes|array|min:7',
            'schedules.*.day_of_week' => 'required_with:schedules|integer|between:1,7',
            'schedules.*.clock_in' => 'nullable|string',
            'schedules.*.clock_out' => 'nullable|string',
            'schedules.*.is_off' => 'nullable',
        ]);

        $employee->update([
            'salary_type' => $request->salary_type,
            'basic_salary' => $request->basic_salary ?? 0,
            'overtime_rate' => $request->overtime_rate ?? 0,
        ]);

        if ($request->has('schedules')) {
            foreach ($request->schedules as $schedData) {
                $dayOfWeek = (int)$schedData['day_of_week'];
                $isOff = isset($schedData['is_off']) && $schedData['is_off'] == '1';

                \App\Models\EmployeeSchedule::updateOrCreate(
                    [
                        'tenant_id'   => $employee->tenant_id,
                        'employee_id' => $employee->id,
                        'day_of_week' => $dayOfWeek,
                    ],
                    [
                        'clock_in'  => $isOff ? null : ($schedData['clock_in'] ?? null),
                        'clock_out' => $isOff ? null : ($schedData['clock_out'] ?? null),
                        'is_off'    => $isOff,
                    ]
                );
            }
        }

        $totalAllowance = 0;
        EmployeeAllowance::where('employee_id', $employee->id)->delete();
        if ($request->has('allowances')) {
            $allowedTypeIds = AllowanceType::where('tenant_id', Auth::user()->tenant_id)->pluck('id')->toArray();
            foreach ($request->allowances as $typeId => $amount) {
                if (!in_array($typeId, $allowedTypeIds)) {
                    return back()->withErrors(['allowances' => 'Tipe tunjangan tidak valid untuk perusahaan Anda.']);
                }
                if ($amount > 0) {
                    $totalAllowance += $amount;
                    EmployeeAllowance::create([
                        'tenant_id' => $employee->tenant_id,
                        'employee_id' => $employee->id,
                        'allowance_type_id' => $typeId,
                        'amount' => $amount,
                    ]);
                }
            }
        }

        $employee->update(['allowance' => $totalAllowance]);

        return back()->with('success', 'Pengaturan gaji dan tunjangan karyawan berhasil diperbarui.');
    }

    public function updateCredentials(Request $request, Employee $employee)
    {
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'username' => 'required|string|max:50|unique:employees,username,' . $employee->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $data = ['username' => $request->username];
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $employee->update($data);

        return back()->with('success', 'Akun karyawan ' . $employee->name . ' berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        abort_unless($employee->tenant_id === Auth::user()->tenant_id, 403);
        $employee->delete();
        return back()->with('success', 'Data karyawan berhasil dihapus.');
    }
}
