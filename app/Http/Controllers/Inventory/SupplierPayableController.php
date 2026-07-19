<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
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

        return view('inventory.supplier_payables.index', compact(
            'payables', 'suppliers',
            'totalHutang', 'totalLunas', 'totalBelumBayar', 'totalSupplier'
        ));
    }

    /* ------------------------------------------------------------------ */
    /*  Show – Detail Hutang + History Pembayaran                          */
    /* ------------------------------------------------------------------ */

    public function show(SupplierPayable $supplierPayable)
    {
        abort_unless($supplierPayable->tenant_id === Auth::user()->tenant_id, 403);
        $supplierPayable->load(['supplier', 'goodsReceipt', 'payments.createdBy', 'createdBy']);

        return view('inventory.supplier_payables.show', compact('supplierPayable'));
    }

    /* ------------------------------------------------------------------ */
    /*  Store Payment – Simpan Pembayaran                                  */
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

        DB::transaction(function () use ($request, $supplierPayable) {
            // Simpan record pembayaran
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
            ]);

            // Update paid_amount & status
            $newPaid = (float) $supplierPayable->paid_amount + (float) $request->amount;
            $newStatus = $newPaid >= (float) $supplierPayable->total_amount ? 'paid' : 'partial';

            $supplierPayable->update([
                'paid_amount' => $newPaid,
                'status'      => $newStatus,
            ]);
        });

        $msg = $supplierPayable->fresh()->status === 'paid'
            ? 'Pembayaran berhasil! Hutang ke ' . $supplierPayable->supplier->name . ' sudah LUNAS. ✅'
            : 'Pembayaran berhasil dicatat. Sisa hutang: Rp ' . number_format($supplierPayable->fresh()->remaining_amount, 0, ',', '.');

        return redirect()->route('supplier_payables.show', $supplierPayable)->with('success', $msg);
    }
}
