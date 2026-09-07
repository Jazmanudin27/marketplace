<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Filters
        $search = $request->get('search');
        $category = $request->get('category');
        $paymentDestination = $request->get('payment_destination');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Income::where('tenant_id', $tenantId);

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($paymentDestination) {
            $query->where('payment_destination', $paymentDestination);
        }

        if ($dateFrom) {
            $query->whereDate('income_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('income_date', '<=', $dateTo);
        }

        $incomes = $query->orderByDesc('income_date')->paginate(15)->withQueryString();
        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        // Auto-seed default categories if empty
        \App\Models\FinanceCategory::seedDefaultsForTenant($tenantId);
        $categories = \App\Models\FinanceCategory::where('tenant_id', $tenantId)
            ->income()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('finance.incomes.index', compact('incomes', 'bankAccounts', 'categories', 'search', 'category', 'paymentDestination', 'dateFrom', 'dateTo'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'category'            => 'required|string|max:100',
            'payment_destination' => 'required|string|max:100',
            'amount'              => 'required|numeric|min:0',
            'income_date'         => 'required|date',
            'description'         => 'nullable|string',
        ]);

        $validated['tenant_id'] = $tenantId;

        $income = Income::create($validated);

        // Update balance on matching bank account if exists
        $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($request) {
                $q->where('bank_name', $request->payment_destination)
                  ->orWhere('id', $request->payment_destination);
            })->first();

        if ($bank) {
            $bank->increment('current_balance', $income->amount);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Pemasukan berhasil dicatat.');
        }

        return redirect()->route('finance.incomes.index')->with('success', 'Pemasukan berhasil dicatat.');
    }

    public function update(Request $request, Income $income)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($income->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'category'            => 'required|string|max:100',
            'payment_destination' => 'required|string|max:100',
            'amount'              => 'required|numeric|min:0',
            'income_date'         => 'required|date',
            'description'         => 'nullable|string',
        ]);

        $oldAmount = (float) $income->amount;
        $oldDest = $income->payment_destination;

        $income->update($validated);

        // Adjust BankAccount balances
        $oldBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldDest) {
                $q->where('bank_name', $oldDest)->orWhere('id', $oldDest);
            })->first();
        if ($oldBank) {
            $oldBank->decrement('current_balance', $oldAmount);
        }

        $newBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($income) {
                $q->where('bank_name', $income->payment_destination)->orWhere('id', $income->payment_destination);
            })->first();
        if ($newBank) {
            $newBank->increment('current_balance', $income->amount);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Pemasukan berhasil diperbarui.');
        }

        return redirect()->route('finance.incomes.index')->with('success', 'Pemasukan berhasil diperbarui.');
    }

    public function destroy(Income $income)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($income->tenant_id !== $tenantId) {
            abort(403);
        }

        $oldAmount = (float) $income->amount;
        $oldDest = $income->payment_destination;

        $income->delete();

        $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldDest) {
                $q->where('bank_name', $oldDest)->orWhere('id', $oldDest);
            })->first();
        if ($bank) {
            $bank->decrement('current_balance', $oldAmount);
        }

        if (request()->filled('redirect_to')) {
            return redirect(request()->input('redirect_to'))->with('success', 'Pemasukan berhasil dihapus.');
        }

        return redirect()->route('finance.incomes.index')->with('success', 'Pemasukan berhasil dihapus.');
    }
}
