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
        $employees = Employee::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        // Auto-seed default categories if empty
        \App\Models\FinanceCategory::seedDefaultsForTenant($tenantId);
        $categories = \App\Models\FinanceCategory::where('tenant_id', $tenantId)
            ->expense()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('finance.expenses.index', compact('expenses', 'employees', 'bankAccounts', 'categories', 'search', 'category', 'paymentSource', 'dateFrom', 'dateTo'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category'       => 'required|string|max:100',
            'payment_source' => 'required|string|max:100',
            'amount'         => 'required|numeric|min:0',
            'expense_date'   => 'required|date',
            'employee_id'    => 'nullable|exists:employees,id',
            'description'    => 'nullable|string',
        ]);

        if ($request->filled('employee_id')) {
            $employeeExists = Employee::where('tenant_id', $tenantId)->where('id', $request->employee_id)->exists();
            if (!$employeeExists) {
                return back()->withErrors(['employee_id' => 'Karyawan tidak valid untuk perusahaan Anda.']);
            }
        }

        $validated['tenant_id'] = $tenantId;

        $expense = Expense::create($validated);

        // Update balance on matching bank account if exists
        $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($request) {
                $q->where('bank_name', $request->payment_source)
                  ->orWhere('id', $request->payment_source);
            })->first();

        if ($bank) {
            $bank->decrement('current_balance', $expense->amount);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Pengeluaran berhasil dicatat.');
        }

        return redirect()->route('finance.expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function update(Request $request, Expense $expense)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($expense->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'category'       => 'required|string|max:100',
            'payment_source' => 'required|string|max:100',
            'amount'         => 'required|numeric|min:0',
            'expense_date'   => 'required|date',
            'employee_id'    => 'nullable|exists:employees,id',
            'description'    => 'nullable|string',
        ]);

        if ($request->filled('employee_id')) {
            $employeeExists = Employee::where('tenant_id', $tenantId)->where('id', $request->employee_id)->exists();
            if (!$employeeExists) {
                return back()->withErrors(['employee_id' => 'Karyawan tidak valid untuk perusahaan Anda.']);
            }
        }

        $oldAmount = (float) $expense->amount;
        $oldSource = $expense->payment_source;

        $expense->update($validated);

        // Revert old bank balance
        $oldBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldSource) {
                $q->where('bank_name', $oldSource)->orWhere('id', $oldSource);
            })->first();
        if ($oldBank) {
            $oldBank->increment('current_balance', $oldAmount);
        }

        // Apply new bank balance
        $newBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($expense) {
                $q->where('bank_name', $expense->payment_source)->orWhere('id', $expense->payment_source);
            })->first();
        if ($newBank) {
            $newBank->decrement('current_balance', $expense->amount);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Pengeluaran berhasil diperbarui.');
        }

        return redirect()->route('finance.expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($expense->tenant_id !== $tenantId) {
            abort(403);
        }

        $oldAmount = (float) $expense->amount;
        $oldSource = $expense->payment_source;

        $expense->delete();

        $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldSource) {
                $q->where('bank_name', $oldSource)->orWhere('id', $oldSource);
            })->first();
        if ($bank) {
            $bank->increment('current_balance', $oldAmount);
        }

        if (request()->filled('redirect_to')) {
            return redirect(request()->input('redirect_to'))->with('success', 'Pengeluaran berhasil dihapus.');
        }

        return redirect()->route('finance.expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
