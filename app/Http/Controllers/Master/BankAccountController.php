<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->input('search');
        $status = $request->input('status');

        $query = BankAccount::where('tenant_id', $tenantId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('branch_name', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status == '1');
        }

        $bankAccounts = $query->orderBy('bank_name')->paginate(15);

        // Stats
        $totalAccounts = BankAccount::where('tenant_id', $tenantId)->count();
        $activeAccounts = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $totalBalance = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->sum('current_balance');

        return view('master.bank_accounts.index', compact(
            'bankAccounts',
            'totalAccounts',
            'activeAccounts',
            'totalBalance',
            'search',
            'status'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name'       => 'required|string|max:100',
            'account_number'  => 'nullable|string|max:50',
            'account_name'    => 'nullable|string|max:150',
            'branch_name'     => 'nullable|string|max:100',
            'initial_balance' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:500',
        ], [
            'bank_name.required' => 'Nama Bank / Jenis Rekening wajib diisi.',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $initialBalance = $request->input('initial_balance', 0);

        BankAccount::create([
            'tenant_id'       => $tenantId,
            'bank_name'       => strtoupper(trim($request->bank_name)),
            'account_number'  => $request->account_number,
            'account_name'    => $request->account_name,
            'branch_name'     => $request->branch_name,
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
            'is_active'       => true,
            'notes'           => $request->notes,
        ]);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Master Rekening Bank / Kas berhasil ditambahkan!');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        if ($bankAccount->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name'   => 'nullable|string|max:150',
            'branch_name'    => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ], [
            'bank_name.required' => 'Nama Bank / Jenis Rekening wajib diisi.',
        ]);

        $bankAccount->update([
            'bank_name'      => strtoupper(trim($request->bank_name)),
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
            'branch_name'    => $request->branch_name,
            'notes'          => $request->notes,
        ]);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Data Rekening Bank berhasil diperbarui!');
    }

    public function toggleStatus(BankAccount $bankAccount)
    {
        if ($bankAccount->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $bankAccount->update([
            'is_active' => !$bankAccount->is_active
        ]);

        $statusText = $bankAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('bank-accounts.index')
            ->with('success', "Status Rekening Bank {$bankAccount->bank_name} berhasil {$statusText}.");
    }

    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Master Rekening Bank berhasil dihapus.');
    }
}
