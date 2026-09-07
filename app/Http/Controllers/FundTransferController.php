<?php

namespace App\Http\Controllers;

use App\Models\FundTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FundTransferController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Filters
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = FundTransfer::where('tenant_id', $tenantId);

        if ($dateFrom) {
            $query->whereDate('transfer_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('transfer_date', '<=', $dateTo);
        }

        $transfers = $query->orderByDesc('transfer_date')->paginate(15)->withQueryString();
        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        return view('finance.transfers.index', compact('transfers', 'bankAccounts', 'dateFrom', 'dateTo'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'source'        => 'required|string|max:100',
            'destination'   => 'required|string|max:100|different:source',
            'amount'        => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'description'   => 'nullable|string',
        ], [
            'destination.different' => 'Kas / Bank tujuan harus berbeda dengan kas asal.',
        ]);

        $validated['tenant_id'] = $tenantId;

        $transfer = FundTransfer::create($validated);

        // Deduct from source bank
        $sourceBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($request) {
                $q->where('bank_name', $request->source)
                  ->orWhere('id', $request->source);
            })->first();
        if ($sourceBank) {
            $sourceBank->decrement('current_balance', $transfer->amount);
        }

        // Add to destination bank
        $destBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($request) {
                $q->where('bank_name', $request->destination)
                  ->orWhere('id', $request->destination);
            })->first();
        if ($destBank) {
            $destBank->increment('current_balance', $transfer->amount);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Transfer dana berhasil dicatat.');
        }

        return redirect()->route('finance.transfers.index')->with('success', 'Transfer dana berhasil dicatat.');
    }

    public function update(Request $request, FundTransfer $transfer)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($transfer->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'source'        => 'required|string|max:100',
            'destination'   => 'required|string|max:100|different:source',
            'amount'        => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'description'   => 'nullable|string',
        ], [
            'destination.different' => 'Kas / Bank tujuan harus berbeda dengan kas asal.',
        ]);

        $oldAmount = (float) $transfer->amount;
        $oldSource = $transfer->source;
        $oldDest = $transfer->destination;

        $transfer->update($validated);

        // Revert old source & destination
        $oldSrcBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldSource) {
                $q->where('bank_name', $oldSource)->orWhere('id', $oldSource);
            })->first();
        if ($oldSrcBank) {
            $oldSrcBank->increment('current_balance', $oldAmount);
        }

        $oldDstBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldDest) {
                $q->where('bank_name', $oldDest)->orWhere('id', $oldDest);
            })->first();
        if ($oldDstBank) {
            $oldDstBank->decrement('current_balance', $oldAmount);
        }

        // Apply new source & destination
        $newSrcBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($transfer) {
                $q->where('bank_name', $transfer->source)->orWhere('id', $transfer->source);
            })->first();
        if ($newSrcBank) {
            $newSrcBank->decrement('current_balance', $transfer->amount);
        }

        $newDstBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($transfer) {
                $q->where('bank_name', $transfer->destination)->orWhere('id', $transfer->destination);
            })->first();
        if ($newDstBank) {
            $newDstBank->increment('current_balance', $transfer->amount);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Transfer dana berhasil diperbarui.');
        }

        return redirect()->route('finance.transfers.index')->with('success', 'Transfer dana berhasil diperbarui.');
    }

    public function destroy(FundTransfer $transfer)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($transfer->tenant_id !== $tenantId) {
            abort(403);
        }

        $oldAmount = (float) $transfer->amount;
        $oldSource = $transfer->source;
        $oldDest = $transfer->destination;

        $transfer->delete();

        $srcBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldSource) {
                $q->where('bank_name', $oldSource)->orWhere('id', $oldSource);
            })->first();
        if ($srcBank) {
            $srcBank->increment('current_balance', $oldAmount);
        }

        $dstBank = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where(function($q) use ($oldDest) {
                $q->where('bank_name', $oldDest)->orWhere('id', $oldDest);
            })->first();
        if ($dstBank) {
            $dstBank->decrement('current_balance', $oldAmount);
        }

        if (request()->filled('redirect_to')) {
            return redirect(request()->input('redirect_to'))->with('success', 'Transfer dana berhasil dihapus.');
        }

        return redirect()->route('finance.transfers.index')->with('success', 'Transfer dana berhasil dihapus.');
    }
}
