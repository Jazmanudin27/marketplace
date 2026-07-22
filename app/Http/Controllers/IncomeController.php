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

        return view('finance.incomes.index', compact('incomes', 'bankAccounts', 'search', 'category', 'paymentDestination', 'dateFrom', 'dateTo'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'category'            => 'required|string|in:investment,refund,services,other',
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

        return redirect()->route('finance.incomes.index')->with('success', 'Pemasukan berhasil dicatat.');
    }

    public function update(Request $request, Income $income)
    {
        if ($income->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'category'            => 'required|string|in:investment,refund,services,other',
            'payment_destination' => 'required|string|max:100',
            'amount'              => 'required|numeric|min:0',
            'income_date'         => 'required|date',
            'description'         => 'nullable|string',
        ]);

        $income->update($validated);

        return redirect()->route('finance.incomes.index')->with('success', 'Pemasukan berhasil diperbarui.');
    }

    public function destroy(Income $income)
    {
        if ($income->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $income->delete();

        return redirect()->route('finance.incomes.index')->with('success', 'Pemasukan berhasil dihapus.');
    }
}
