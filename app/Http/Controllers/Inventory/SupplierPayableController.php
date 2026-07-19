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

        return view('inventory.supplier_payables.show', compact('supplierPayable', 'isAdmin'));
    }

    /* ------------------------------------------------------------------ */
    /*  Store Payment – Submit Pembayaran (pending approval)               */
    /* ------------------------------------------------------------------ */

    public function storePayment(Request $request, SupplierPayable $supplierPayable)
    {
        abort_unless($supplierPayable->tenant_id === Auth::user()->tenant_id, 403);

        if ($supplierPayable->status === 'paid') {
            return back()->with('error', 'Hutang ini sudah lunas.');
        }

        $remaining = $supplierPayable->remaining_amount;

        $request->validate([
            'payment_date'     => 'required|date',
            'amount'           => "required|numeric|min:1|max:{$remaining}",
            'payment_method'   => 'required|in:transfer,cash,giro',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
        ], [
            'amount.max' => 'Nominal bayar tidak boleh melebihi sisa hutang (Rp ' . number_format($remaining, 0, ',', '.') . ').',
        ]);

        SupplierPayment::create([
            'tenant_id'           => $supplierPayable->tenant_id,
            'supplier_payable_id' => $supplierPayable->id,
            'supplier_id'         => $supplierPayable->supplier_id,
            'payment_date'        => $request->payment_date,
            'amount'              => $request->amount,
            'payment_method'      => $request->payment_method,
            'reference_number'    => $request->reference_number,
            'notes'               => $request->notes,
            'created_by'          => Auth::id(),
            'approval_status'     => 'pending',
            'expense_id'          => null,
        ]);

        return redirect()
            ->route('supplier_payables.show', $supplierPayable)
            ->with('success', '✅ Pengajuan pembayaran berhasil dikirim! Menunggu persetujuan dari Admin/Finance.');
    }

    /* ------------------------------------------------------------------ */
    /*  Approve Payment – Setujui pembayaran & potong kas (jika tunai)     */
    /* ------------------------------------------------------------------ */

    public function approvePayment(Request $request, SupplierPayable $supplierPayable, SupplierPayment $payment)
    {
        abort_unless($supplierPayable->tenant_id === Auth::user()->tenant_id, 403);

        // Cek hak approve
        $user = Auth::user();
        abort_unless(
            $user->isSuperAdmin() || $user->role === 'admin' || $user->can('supplier-payables.approve'),
            403,
            'Anda tidak memiliki hak untuk menyetujui pembayaran.'
        );

        if ($payment->approval_status !== 'pending') {
            return back()->with('error', 'Pembayaran ini sudah diproses sebelumnya.');
        }

        // Validasi detail approval sesuai metode
        $approvalRules = [
            'approval_notes' => 'nullable|string|max:500',
        ];
        $approvalMessages = [];

        if ($payment->payment_method === 'cash') {
            $approvalRules['payment_source'] = 'required|in:kas_besar,kas_kecil';
            $approvalMessages['payment_source.required'] = 'Pilih sumber kas untuk pembayaran tunai.';
        } else {
            $approvalRules['bank_name']      = 'required|string|max:100';
            $approvalRules['account_number'] = 'nullable|string|max:50';
            $approvalRules['account_name']   = 'nullable|string|max:100';
            $approvalMessages['bank_name.required'] = 'Nama bank wajib diisi.';
        }

        $request->validate($approvalRules, $approvalMessages);

        // Jika bank dipilih "Lainnya", gunakan input manual
        if ($request->bank_name === '__other__') {
            $request->merge(['bank_name' => $request->bank_name_other]);
        }

        // Simpan ID payment yang sedang di-approve (untuk re-open modal jika error)
        session(['approval_payment_id' => $payment->id]);

        DB::transaction(function () use ($request, $payment, $supplierPayable, $user) {
            $expenseId = null;

            // Simpan detail bank/kas ke payment record
            $payment->update([
                'payment_source' => $request->payment_source,
                'bank_name'      => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name'   => $request->account_name,
            ]);

            // Jika tunai → buat Expense (potong kas) saat diapprove
            if ($payment->payment_method === 'cash') {
                $expense = Expense::create([
                    'tenant_id'      => $supplierPayable->tenant_id,
                    'title'          => 'Bayar Hutang Supplier: ' . ($supplierPayable->supplier->name ?? '—')
                                        . ' (' . $supplierPayable->reference_number . ')',
                    'category'       => 'pembelian_supplier',
                    'payment_source' => $request->payment_source,
                    'amount'         => $payment->amount,
                    'expense_date'   => $payment->payment_date,
                    'description'    => 'Disetujui oleh: ' . $user->name
                                        . ($request->approval_notes ? '. ' . $request->approval_notes : '')
                                        . '. ' . ($payment->notes ?? ''),
                ]);
                $expenseId = $expense->id;
            }

            // Update status pembayaran
            $payment->update([
                'approval_status' => 'approved',
                'approved_by'     => $user->id,
                'approved_at'     => now(),
                'expense_id'      => $expenseId,
            ]);

            // Update paid_amount & status hutang
            $newPaid   = (float) $supplierPayable->paid_amount + (float) $payment->amount;
            $newStatus = $newPaid >= (float) $supplierPayable->total_amount ? 'paid' : 'partial';

            $supplierPayable->update([
                'paid_amount' => $newPaid,
                'status'      => $newStatus,
            ]);
        });

        $fresh = $supplierPayable->fresh();
        $msg = $fresh->status === 'paid'
            ? '✅ Pembayaran disetujui! Hutang ke ' . ($supplierPayable->supplier->name ?? '—') . ' sudah LUNAS.'
            : '✅ Pembayaran disetujui dan dicatat. Sisa hutang: Rp ' . number_format($fresh->remaining_amount, 0, ',', '.');

        return redirect()->route('supplier_payables.show', $supplierPayable)->with('success', $msg);
    }

    /* ------------------------------------------------------------------ */
    /*  Reject Payment – Tolak pembayaran dengan alasan                    */
    /* ------------------------------------------------------------------ */

    public function rejectPayment(Request $request, SupplierPayable $supplierPayable, SupplierPayment $payment)
    {
        abort_unless($supplierPayable->tenant_id === Auth::user()->tenant_id, 403);

        $user = Auth::user();
        abort_unless(
            $user->isSuperAdmin() || $user->role === 'admin' || $user->can('supplier-payables.approve'),
            403,
            'Anda tidak memiliki hak untuk menolak pembayaran.'
        );

        if ($payment->approval_status !== 'pending') {
            return back()->with('error', 'Pembayaran ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $payment->update([
            'approval_status'  => 'rejected',
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()
            ->route('supplier_payables.show', $supplierPayable)
            ->with('info', '❌ Pembayaran telah ditolak. Alasan: ' . $request->rejection_reason);
    }
}
