<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Filters
        $search = $request->get('search');
        $category = $request->get('category');
        $paymentSource = $request->get('payment_source');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Expense::where('tenant_id', $tenantId)->with('employee');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($paymentSource) {
            $query->where('payment_source', $paymentSource);
        }

        if ($dateFrom) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(15)->withQueryString();

        // Get employees for dropdown
        $employees = Employee::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('finance.expenses.index', compact('expenses', 'employees', 'search', 'category', 'paymentSource', 'dateFrom', 'dateTo'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:salary,rent,utilities,pembelian_supplier,other',
            'payment_source' => 'required|string|in:kas_besar,kas_kecil',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'employee_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string',
        ]);

        if ($request->filled('employee_id')) {
            $employeeExists = Employee::where('tenant_id', $tenantId)->where('id', $request->employee_id)->exists();
            if (!$employeeExists) {
                return back()->withErrors(['employee_id' => 'Karyawan tidak valid untuk perusahaan Anda.']);
            }
        }

        $validated['tenant_id'] = $tenantId;

        Expense::create($validated);

        return redirect()->route('finance.expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:salary,rent,utilities,pembelian_supplier,other',
            'payment_source' => 'required|string|in:kas_besar,kas_kecil',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'employee_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string',
        ]);

        if ($request->filled('employee_id')) {
            $employeeExists = Employee::where('tenant_id', Auth::user()->tenant_id)->where('id', $request->employee_id)->exists();
            if (!$employeeExists) {
                return back()->withErrors(['employee_id' => 'Karyawan tidak valid untuk perusahaan Anda.']);
            }
        }

        $expense->update($validated);

        return redirect()->route('finance.expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $expense->delete();

        return redirect()->route('finance.expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
