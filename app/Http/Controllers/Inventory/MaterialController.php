<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Material::where('tenant_id', Auth::user()->tenant_id);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%' . $request->sku . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $materials = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('inventory.materials.index', compact('materials'));
    }

    public function create()
    {
        return redirect()->route('materials.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:bahan,kemasan',
            'unit' => 'required|string|max:20',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|string',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;
        $data['sku'] = $request->filled('sku') ? $request->sku : 'MAT-' . strtoupper(uniqid());
        $data['stock'] = $request->filled('stock') ? (float) $request->stock : 0;
        $data['min_stock'] = $request->filled('min_stock') ? (int) $request->min_stock : 0;
        
        // Parse cost price
        if ($request->filled('cost_price')) {
            $cleanPrice = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $request->cost_price);
            $data['cost_price'] = (float) $cleanPrice;
        } else {
            $data['cost_price'] = 0;
        }

        $material = Material::create($data);

        // Record initial stock movement if stock > 0
        if ($data['stock'] > 0) {
            $material->recordStockMovement($data['stock'], 'in', 'Saldo Awal', Auth::id());
        }

        return redirect()->route('materials.index')->with('success', 'Bahan/Kemasan berhasil ditambahkan.');
    }

    public function edit(Material $material)
    {
        return redirect()->route('materials.index');
    }

    public function update(Request $request, Material $material)
    {
        abort_unless($material->tenant_id === Auth::user()->tenant_id, 403);

        $data = $request->validate([
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:bahan,kemasan',
            'unit' => 'required|string|max:20',
            'min_stock' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|string',
        ]);

        $data['min_stock'] = $request->filled('min_stock') ? (int) $request->min_stock : 0;

        if ($request->filled('cost_price')) {
            $cleanPrice = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $request->cost_price);
            $data['cost_price'] = (float) $cleanPrice;
        } else {
            $data['cost_price'] = 0;
        }

        $material->update($data);

        return redirect()->route('materials.index')->with('success', 'Bahan/Kemasan berhasil diupdate.');
    }

    public function destroy(Material $material)
    {
        abort_unless($material->tenant_id === Auth::user()->tenant_id, 403);

        // Check if has stock movements other than "Saldo Awal"
        $movementsCount = $material->stockMovements()->where('reference', '!=', 'Saldo Awal')->count();
        if ($movementsCount > 0) {
            return redirect()->route('materials.index')->withErrors(['delete' => 'Barang ini tidak dapat dihapus karena sudah memiliki histori transaksi/mutasi stok.']);
        }

        // Clean up stock movements
        $material->stockMovements()->delete();
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Bahan/Kemasan berhasil dihapus.');
    }
}
