<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterProductionStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterProductionStageController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        // Auto-seed defaults if empty
        MasterProductionStage::seedDefaultsForTenant($tenantId);

        $stages = MasterProductionStage::where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('master.production_stages.index', compact('stages'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name'       => 'required|string|max:100',
            'sort_order' => 'required|integer|min:1',
        ]);

        MasterProductionStage::create([
            'tenant_id'  => $tenantId,
            'name'       => $request->name,
            'sort_order' => $request->sort_order,
            'is_active'  => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->route('production-stages.index')
            ->with('success', 'Tahapan produksi baru berhasil ditambahkan.');
    }

    public function update(Request $request, MasterProductionStage $productionStage)
    {
        abort_unless($productionStage->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name'       => 'required|string|max:100',
            'sort_order' => 'required|integer|min:1',
        ]);

        $productionStage->update([
            'name'       => $request->name,
            'sort_order' => $request->sort_order,
            'is_active'  => $request->has('is_active') ? $request->boolean('is_active') : false,
        ]);

        return redirect()->route('production-stages.index')
            ->with('success', 'Tahapan produksi berhasil diperbarui.');
    }

    public function destroy(MasterProductionStage $productionStage)
    {
        abort_unless($productionStage->tenant_id === Auth::user()->tenant_id, 403);

        $productionStage->delete();

        return redirect()->route('production-stages.index')
            ->with('success', 'Tahapan produksi berhasil dihapus.');
    }
}
