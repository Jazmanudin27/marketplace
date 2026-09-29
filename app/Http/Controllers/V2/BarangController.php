<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangController extends Controller
{
    private function checkAccess($permission = 'inventory-items.index')
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->role !== 'admin' && !$user->can($permission)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola Data Barang.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess('inventory-items.index');
        $tenantId = Auth::user()->tenant_id;

        $query = InventoryItem::where('tenant_id', $tenantId);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%' . $request->sku . '%');
        }

        if ($request->filled('type')) {
            if ($request->type === 'bahan_kemasan') {
                $query->whereIn('type', ['bahan', 'kemasan']);
            } elseif ($request->type === 'atk_inventaris') {
                $query->whereIn('type', ['atk', 'inventaris']);
            } else {
                $query->where('type', $request->type);
            }
        }

        // Summary Counts
        $allTenantItems = InventoryItem::where('tenant_id', $tenantId)->get();
        $counts = [
            'total_items' => $allTenantItems->count(),
            'total_bahan' => $allTenantItems->where('type', 'bahan')->count(),
            'total_kemasan' => $allTenantItems->where('type', 'kemasan')->count(),
            'low_stock' => $allTenantItems->filter(function($i) {
                return $i->stock <= $i->min_stock;
            })->count(),
        ];

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('v2.barang.index', compact('items', 'counts'));
    }

    public function store(Request $request)
    {
        $this->checkAccess('inventory-items.create');

        $data = $request->validate([
            'sku' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:bahan,kemasan,atk,inventaris',
            'unit' => 'required|string|max:20',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|string',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;
        $data['sku'] = $request->filled('sku') ? strtoupper(trim($request->sku)) : 'BRG-' . strtoupper(uniqid());
        $data['stock'] = $request->filled('stock') ? (float) $request->stock : 0;
        $data['min_stock'] = $request->filled('min_stock') ? (int) $request->min_stock : 0;

        if ($request->filled('cost_price')) {
            $cleanPrice = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $request->cost_price);
            $data['cost_price'] = (float) $cleanPrice;
        } else {
            $data['cost_price'] = 0;
        }

        $item = InventoryItem::create($data);

        // Record initial stock movement if stock > 0
        if ($data['stock'] > 0) {
            $item->recordStockMovement($data['stock'], 'in', 'Saldo Awal', Auth::id());
        }

        return redirect()->route('v2.barang.index')->with('success', "Barang \"{$item->name}\" berhasil ditambahkan!");
    }

    public function show($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $this->checkAccess('inventory-items.edit');

        $tenantId = Auth::user()->tenant_id;
        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);

        $data = $request->validate([
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:bahan,kemasan,atk,inventaris',
            'unit' => 'required|string|max:20',
            'min_stock' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|string',
        ]);

        $data['sku'] = strtoupper(trim($request->sku));
        $data['min_stock'] = $request->filled('min_stock') ? (int) $request->min_stock : 0;

        if ($request->filled('cost_price')) {
            $cleanPrice = str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $request->cost_price);
            $data['cost_price'] = (float) $cleanPrice;
        } else {
            $data['cost_price'] = 0;
        }

        $item->update($data);

        return redirect()->route('v2.barang.index')->with('success', "Data barang \"{$item->name}\" berhasil diperbarui!");
    }

    public function destroy($id)
    {
        $this->checkAccess('inventory-items.destroy');

        $tenantId = Auth::user()->tenant_id;
        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);

        // Check if has stock movements other than "Saldo Awal"
        $movementsCount = $item->stockMovements()->where('reference', '!=', 'Saldo Awal')->count();
        if ($movementsCount > 0) {
            return redirect()->route('v2.barang.index')->with('error', "Barang \"{$item->name}\" tidak dapat dihapus karena sudah memiliki histori transaksi/mutasi stok.");
        }

        $itemName = $item->name;
        // Clean up stock movements
        $item->stockMovements()->delete();
        $item->delete();

        return redirect()->route('v2.barang.index')->with('success', "Barang \"{$itemName}\" berhasil dihapus!");
    }

    public function adjust(Request $request, $id)
    {
        $this->checkAccess('inventory-items.edit');

        $tenantId = Auth::user()->tenant_id;
        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);

        $request->validate([
            'quantity' => 'required|numeric|not_in:0',
            'reference' => 'required|string|max:255',
        ]);

        $item->recordStockMovement(
            $request->quantity,
            'adjustment',
            $request->reference,
            Auth::id()
        );

        return redirect()->route('v2.barang.index')->with('success', "Stok barang \"{$item->name}\" berhasil disesuaikan.");
    }
}
