<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FinanceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Auto-seed default categories if empty
        FinanceCategory::seedDefaultsForTenant($tenantId);

        $type   = $request->input('type');   // 'expense', 'income', or null (all)
        $search = $request->input('search');
        $status = $request->input('status');

        $query = FinanceCategory::where('tenant_id', $tenantId);

        if ($type && in_array($type, ['expense', 'income'])) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status == '1');
        }

        $categories = $query->orderBy('type')->orderBy('name')->paginate(20)->withQueryString();

        // Summary stats
        $totalCount   = FinanceCategory::where('tenant_id', $tenantId)->count();
        $expenseCount = FinanceCategory::where('tenant_id', $tenantId)->expense()->count();
        $incomeCount  = FinanceCategory::where('tenant_id', $tenantId)->income()->count();

        return view('master.finance_categories.index', compact(
            'categories',
            'type',
            'search',
            'status',
            'totalCount',
            'expenseCount',
            'incomeCount'
        ));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name'        => 'required|string|max:150',
            'type'        => 'required|in:expense,income',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'type.required' => 'Tipe kategori wajib dipilih.',
        ]);

        $code = Str::slug($request->name, '_');
        // Ensure uniqueness for code within tenant and type
        $count = FinanceCategory::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('type', $request->type)
            ->count();
        if ($count > 0) {
            $code .= '_' . ($count + 1);
        }

        FinanceCategory::create([
            'tenant_id'   => $tenantId,
            'name'        => trim($request->name),
            'code'        => $code,
            'type'        => $request->type,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        $label = $request->type === 'expense' ? 'Pengeluaran / Biaya' : 'Pemasukan';
        return redirect()->route('finance-categories.index', ['type' => $request->type])
            ->with('success', "Master Kategori {$label} '{$request->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, FinanceCategory $financeCategory)
    {
        if ($financeCategory->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name'        => 'required|string|max:150',
            'type'        => 'required|in:expense,income',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $financeCategory->update([
            'name'        => trim($request->name),
            'type'        => $request->type,
            'description' => $request->description,
            'is_active'   => $request->has('is_active') ? (bool)$request->is_active : $financeCategory->is_active,
        ]);

        return redirect()->back()
            ->with('success', "Kategori '{$financeCategory->name}' berhasil diperbarui.");
    }

    public function destroy(FinanceCategory $financeCategory)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($financeCategory->tenant_id !== $tenantId) {
            abort(403);
        }

        // Check if category is already used in existing expenses or incomes
        $isUsed = false;
        if ($financeCategory->type === 'expense') {
            $isUsed = Expense::where('tenant_id', $tenantId)
                ->where(function ($q) use ($financeCategory) {
                    $q->where('category', $financeCategory->code)
                      ->orWhere('category', $financeCategory->name);
                })->exists();
        } else {
            $isUsed = Income::where('tenant_id', $tenantId)
                ->where(function ($q) use ($financeCategory) {
                    $q->where('category', $financeCategory->code)
                      ->orWhere('category', $financeCategory->name);
                })->exists();
        }

        if ($isUsed) {
            // Deactivate instead of hard delete to keep history intact
            $financeCategory->update(['is_active' => false]);
            return redirect()->back()->with('warning', "Kategori '{$financeCategory->name}' sudah memiliki catatan transaksi. Kategori telah dinonaktifkan agar data histori pembukuan tetap aman.");
        }

        $catName = $financeCategory->name;
        $financeCategory->delete();

        return redirect()->back()->with('success', "Kategori '{$catName}' berhasil dihapus.");
    }

    public function toggleStatus(FinanceCategory $financeCategory)
    {
        if ($financeCategory->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $financeCategory->update([
            'is_active' => !$financeCategory->is_active,
        ]);

        $statusText = $financeCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Status kategori '{$financeCategory->name}' berhasil {$statusText}.");
    }
}
