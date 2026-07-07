<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryItemController extends Controller
{
    private function checkAccess()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->role !== 'admin') {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola Master Barang.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $query = InventoryItem::where('tenant_id', Auth::user()->tenant_id);

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

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('master.inventory_items.index', compact('items'));
    }

    public function create()
    {
        return redirect()->route('inventory_items.index');
    }

    public function store(Request $request)
    {
        $this->checkAccess();

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
        $data['sku'] = $request->filled('sku') ? $request->sku : 'BRG-' . strtoupper(uniqid());
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

        return redirect()->route('inventory_items.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(InventoryItem $inventoryItem)
    {
        return redirect()->route('inventory_items.index');
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $this->checkAccess();

        abort_unless($inventoryItem->tenant_id === Auth::user()->tenant_id, 403);

        $data = $request->validate([
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:bahan,kemasan,atk,inventaris',
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

        $inventoryItem->update($data);

        return redirect()->route('inventory_items.index')->with('success', 'Barang berhasil diupdate.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $this->checkAccess();

        abort_unless($inventoryItem->tenant_id === Auth::user()->tenant_id, 403);

        // Check if has stock movements other than "Saldo Awal"
        $movementsCount = $inventoryItem->stockMovements()->where('reference', '!=', 'Saldo Awal')->count();
        if ($movementsCount > 0) {
            return redirect()->route('inventory_items.index')->withErrors(['delete' => 'Barang ini tidak dapat dihapus karena sudah memiliki histori transaksi/mutasi stok.']);
        }

        // Clean up stock movements
        $inventoryItem->stockMovements()->delete();
        $inventoryItem->delete();

        return redirect()->route('inventory_items.index')->with('success', 'Barang berhasil dihapus.');
    }
}
