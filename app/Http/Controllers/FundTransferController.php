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

        return view('finance.transfers.index', compact('transfers', 'dateFrom', 'dateTo'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'source' => 'required|string|in:kas_besar,kas_kecil',
            'destination' => 'required|string|in:kas_besar,kas_kecil|different:source',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'description' => 'nullable|string',
        ], [
            'destination.different' => 'Kas tujuan harus berbeda dengan kas asal.',
        ]);

        $validated['tenant_id'] = $tenantId;

        FundTransfer::create($validated);

        return redirect()->route('finance.transfers.index')->with('success', 'Transfer dana berhasil dicatat.');
    }

    public function update(Request $request, FundTransfer $transfer)
    {
        if ($transfer->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'source' => 'required|string|in:kas_besar,kas_kecil',
            'destination' => 'required|string|in:kas_besar,kas_kecil|different:source',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'description' => 'nullable|string',
        ], [
            'destination.different' => 'Kas tujuan harus berbeda dengan kas asal.',
        ]);

        $transfer->update($validated);

        return redirect()->route('finance.transfers.index')->with('success', 'Transfer dana berhasil diperbarui.');
    }

    public function destroy(FundTransfer $transfer)
    {
        if ($transfer->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $transfer->delete();

        return redirect()->route('finance.transfers.index')->with('success', 'Transfer dana berhasil dihapus.');
    }
}
