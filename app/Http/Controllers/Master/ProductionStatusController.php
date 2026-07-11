<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionStatusController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Auto-seed defaults if empty
        ProductionStatus::seedDefaultsForTenant($tenantId);

        $statuses = ProductionStatus::where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('master.production_statuses.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name'       => 'required|string|max:100',
            'color'      => 'required|string|in:secondary,dark,warning,info,primary,success,danger',
            'sort_order' => 'required|integer|min:0',
        ]);

        ProductionStatus::create([
            'tenant_id'  => $tenantId,
            'name'       => $request->name,
            'color'      => $request->color,
            'sort_order' => $request->sort_order,
        ]);

        return redirect()->route('production-statuses.index')
            ->with('success', 'Status produksi baru berhasil ditambahkan.');
    }

    public function update(Request $request, ProductionStatus $productionStatus)
    {
        abort_unless($productionStatus->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name'       => 'required|string|max:100',
            'color'      => 'required|string|in:secondary,dark,warning,info,primary,success,danger',
            'sort_order' => 'required|integer|min:0',
        ]);

        $productionStatus->update([
            'name'       => $request->name,
            'color'      => $request->color,
            'sort_order' => $request->sort_order,
        ]);

        return redirect()->route('production-statuses.index')
            ->with('success', 'Status produksi berhasil diperbarui.');
    }

    public function destroy(ProductionStatus $productionStatus)
    {
        abort_unless($productionStatus->tenant_id === Auth::user()->tenant_id, 403);
        
        $productionStatus->delete();

        return redirect()->route('production-statuses.index')
            ->with('success', 'Status produksi berhasil dihapus.');
    }
}
