<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\LaborService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaborServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = LaborService::where('tenant_id', Auth::user()->tenant_id);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $services = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('master.labor_services.index', compact('services'));
    }

    public function create()
    {
        return redirect()->route('labor_services.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'default_cost' => 'nullable|string',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;

        if ($request->filled('default_cost')) {
            $cleanPrice = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $request->default_cost);
            $data['default_cost'] = (float) $cleanPrice;
        } else {
            $data['default_cost'] = 0;
        }

        LaborService::create($data);

        return redirect()->route('labor_services.index')->with('success', 'Jasa produksi berhasil ditambahkan.');
    }

    public function edit(LaborService $laborService)
    {
        return redirect()->route('labor_services.index');
    }

    public function update(Request $request, LaborService $laborService)
    {
        abort_unless($laborService->tenant_id === Auth::user()->tenant_id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'default_cost' => 'nullable|string',
        ]);

        if ($request->filled('default_cost')) {
            $cleanPrice = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $request->default_cost);
            $data['default_cost'] = (float) $cleanPrice;
        } else {
            $data['default_cost'] = 0;
        }

        $laborService->update($data);

        return redirect()->route('labor_services.index')->with('success', 'Jasa produksi berhasil diupdate.');
    }

    public function destroy(LaborService $laborService)
    {
        abort_unless($laborService->tenant_id === Auth::user()->tenant_id, 403);
        $laborService->delete();

        return redirect()->route('labor_services.index')->with('success', 'Jasa produksi berhasil dihapus.');
    }
}
