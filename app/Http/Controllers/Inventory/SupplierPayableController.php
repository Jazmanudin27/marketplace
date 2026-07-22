<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierPayableController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  Index – Daftar Hutang Supplier                                     */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = SupplierPayable::with('supplier')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('payable_date');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payable_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payable_date', '<=', $request->date_to);
        }

        $payables  = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        // KPI summary (all, no filter)
        $allPayables     = SupplierPayable::where('tenant_id', $tenantId)->get();
        $totalHutang     = $allPayables->sum('total_amount');
        $totalLunas      = $allPayables->where('status', 'paid')->sum('total_amount');
        $totalBelumBayar = $allPayables->whereIn('status', ['unpaid', 'partial'])->sum(fn ($p) => $p->remaining_amount);
        $totalSupplier   = $allPayables->whereIn('status', ['unpaid', 'partial'])->unique('supplier_id')->count();

        // Badge: jumlah pembayaran menunggu approval
        $pendingApprovalCount = SupplierPayment::whereHas('payable', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('approval_status', 'pending')
            ->count();

        return view('inventory.supplier_payables.index', compact(
            'payables', 'suppliers',
            'totalHutang', 'totalLunas', 'totalBelumBayar', 'totalSupplier',
            'pendingApprovalCount'
        ));
    }

    /* ------------------------------------------------------------------ */
    /*  Show – Detail Hutang + History Pembayaran                          */
    /* ------------------------------------------------------------------ */

    public function show(SupplierPayable $supplierPayable)
    {
        abort_unless($supplierPayable->tenant_id === Auth::user()->tenant_id, 403);
        $supplierPayable->load([
            'supplier',
            'goodsReceipt',
            'payments.createdBy',
            'payments.approvedBy',
            'payments.rejectedBy',
            'payments.expense',
            'createdBy',
        ]);

        $isAdmin = Auth::user()->isSuperAdmin()
            || Auth::user()->role === 'admin'
            || Auth::user()->can('supplier-payables.approve');

        $bankAccounts = \App\Models\BankAccount::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('inventory.supplier_payables.show', compact('supplierPayable', 'isAdmin', 'bankAccounts'));
    }

    /* ------------------------------------------------------------------ */
    /*  Store Payment – Submit Pembayaran                                  */
    /* ------------------------------------------------------------------ */

    public function storePayment(Request $request, SupplierPayable $supplierPayable)
    {
        abort_unless($supplierPayable->tenant_id === Auth::user()->tenant_id, 403);

        if ($supplierPayable->status === 'paid') {
            return back()->with('error', 'Hutang ini sudah lunas.');
        }

        $remaining = $supplierPayable->remaining_amount;

        $rules = [
            'payment_date'     => 'required|date',
            'amount'           => "required|numeric|min:1|max:{$remaining}",
            'payment_method'   => 'required|in:transfer,cash,giro',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
        ];

        if ($request->payment_method === 'cash') {
            $rules['payment_source'] = 'required|in:kas_besar,kas_kecil';
        } else {
            $rules['bank_name']      = 'required|string|max:100';
            $rules['account_number'] = 'nullable|string|max:50';
            $rules['account_name']   = 'nullable|string|max:100';
        }

        $request->validate($rules, [
            'amount.max' => 'Nominal bayar tidak boleh melebihi sisa hutang (Rp ' . number_format($remaining, 0, ',', '.') . ').',
            'payment_source.required' => 'Pilih sumber kas untuk pembayaran tunai.',
            'bank_name.required' => 'Nama bank wajib diisi.',
        ]);

        $bankName = $request->bank_name;
        if ($bankName === '__other__') {
            $bankName = $request->bank_name_other;
        }

        DB::transaction(function () use ($request, $supplierPayable, $bankName) {
            $user = Auth::user();
            $expenseId = null;

            // Jika tunai → buat Expense (potong kas) langsung
            if ($request->payment_method === 'cash') {
                $expense = Expense::create([
                    'tenant_id'      => $supplierPayable->tenant_id,
                    'title'          => 'Bayar Hutang Supplier: ' . ($supplierPayable->supplier->name ?? '—')
                                        . ' (' . $supplierPayable->reference_number . ')',
                    'category'       => 'pembelian_supplier',
                    'payment_source' => $request->payment_source,
                    'amount'         => $request->amount,
                    'expense_date'   => $request->payment_date,
                    'description'    => 'Dicatat oleh: ' . $user->name . ($request->notes ? '. ' . $request->notes : ''),
                ]);
                $expenseId = $expense->id;
            }

            // Buat Record Pembayaran langsung approved
            SupplierPayment::create([
                'tenant_id'           => $supplierPayable->tenant_id,
                'supplier_payable_id' => $supplierPayable->id,
                'supplier_id'         => $supplierPayable->supplier_id,
                'payment_date'        => $request->payment_date,
                'amount'              => $request->amount,
                'payment_method'      => $request->payment_method,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
                'payment_source'      => $request->payment_source,
                'bank_name'           => $bankName,
                'account_number'      => $request->account_number,
                'account_name'        => $request->account_name,
                'created_by'          => $user->id,
                'approved_by'         => $user->id,
                'approved_at'         => now(),
                'approval_status'     => 'approved',
                'expense_id'          => $expenseId,
            ]);

            // Update paid_amount & status hutang
            $newPaid   = (float) $supplierPayable->paid_amount + (float) $request->amount;
            $newStatus = $newPaid >= (float) $supplierPayable->total_amount ? 'paid' : 'partial';

            $supplierPayable->update([
                'paid_amount' => $newPaid,
                'status'      => $newStatus,
            ]);
        });

        $fresh = $supplierPayable->fresh();
        $msg = $fresh->status === 'paid'
            ? '✅ Pembayaran berhasil dicatat! Hutang ke ' . ($supplierPayable->supplier->name ?? '—') . ' sudah LUNAS.'
            : '✅ Pembayaran berhasil dicatat. Sisa hutang: Rp ' . number_format($fresh->remaining_amount, 0, ',', '.');

        return redirect()->route('supplier_payables.show', $supplierPayable)->with('success', $msg);
    }
}
