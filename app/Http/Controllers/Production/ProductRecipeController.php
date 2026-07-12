<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\ProductRecipe;
use App\Models\InventoryItem;
use App\Models\LaborService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductRecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->with(['activeRecipe.items.inventoryItem', 'activeRecipe.labors']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'has_recipe') {
                $query->whereHas('activeRecipe');
            } elseif ($request->status === 'no_recipe') {
                $query->whereDoesntHave('activeRecipe');
            }
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('production.recipes.index', compact('products'));
    }

    public function create()
    {
        $products = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $productsWithRecipe = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->whereHas('activeRecipe')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $inventoryItems = InventoryItem::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->orderBy('name')
            ->get();

        $laborServices = LaborService::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        return view('production.recipes.form', compact('products', 'productsWithRecipe', 'inventoryItems', 'laborServices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'batch_qty' => 'required|integer|min:1',
            'items' => 'nullable|array',
            'items.*.inventory_item_id' => 'required_with:items|exists:inventory_items,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.0001',
            'labors' => 'nullable|array',
            'labors.*.service_name' => 'required_with:labors|string|max:255',
            'labors.*.qty' => 'required_with:labors|integer|min:1',
            'labors.*.unit_cost' => 'required_with:labors|numeric|min:0',
        ]);

        $product = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($request->master_product_id);

        DB::transaction(function () use ($request, $product) {
            ProductRecipe::where('master_product_id', $product->id)
                ->update(['is_active' => false]);

            $recipe = ProductRecipe::create([
                'tenant_id' => Auth::user()->tenant_id,
                'master_product_id' => $product->id,
                'name' => 'Resep Utama ' . date('d/m/Y H:i'),
                'batch_qty' => $request->batch_qty,
                'is_active' => true,
            ]);

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $recipe->items()->create([
                        'inventory_item_id' => $item['inventory_item_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            if ($request->has('labors')) {
                foreach ($request->labors as $labor) {
                    $recipe->labors()->create([
                        'service_name' => $labor['service_name'],
                        'qty' => $labor['qty'],
                        'unit_cost' => $labor['unit_cost'],
                        'default_cost' => $labor['qty'] * $labor['unit_cost'],
                    ]);
                }
            }
        });

        return redirect()->route('product_recipes.index')
            ->with('success', 'Formula produk berhasil ditambahkan.');
    }

    public function edit($id)
    {
        // Bind to product instead of recipe ID to make it super intuitive (one recipe per product)
        $product = MasterProduct::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        
        $recipe = ProductRecipe::with(['items.inventoryItem', 'labors'])
            ->where('master_product_id', $product->id)
            ->where('is_active', true)
            ->first();

        $productsWithRecipe = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->whereHas('activeRecipe')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $inventoryItems = InventoryItem::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->orderBy('name')
            ->get();

        $laborServices = LaborService::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        return view('production.recipes.form', compact('product', 'recipe', 'productsWithRecipe', 'inventoryItems', 'laborServices'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'batch_qty' => 'required|integer|min:1',
            'items' => 'nullable|array',
            'items.*.inventory_item_id' => 'required_with:items|exists:inventory_items,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.0001',
            'labors' => 'nullable|array',
            'labors.*.service_name' => 'required_with:labors|string|max:255',
            'labors.*.qty' => 'required_with:labors|integer|min:1',
            'labors.*.unit_cost' => 'required_with:labors|numeric|min:0',
        ]);

        $product = MasterProduct::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        DB::transaction(function () use ($request, $product) {
            ProductRecipe::where('master_product_id', $product->id)
                ->update(['is_active' => false]);

            $recipe = ProductRecipe::create([
                'tenant_id' => Auth::user()->tenant_id,
                'master_product_id' => $product->id,
                'name' => 'Resep Utama ' . date('d/m/Y H:i'),
                'batch_qty' => $request->batch_qty,
                'is_active' => true,
            ]);

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $recipe->items()->create([
                        'inventory_item_id' => $item['inventory_item_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            if ($request->has('labors')) {
                foreach ($request->labors as $labor) {
                    $recipe->labors()->create([
                        'service_name' => $labor['service_name'],
                        'qty' => $labor['qty'],
                        'unit_cost' => $labor['unit_cost'],
                        'default_cost' => $labor['qty'] * $labor['unit_cost'],
                    ]);
                }
            }
        });

        return redirect()->route('product_recipes.index')
            ->with('success', 'Formula produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = MasterProduct::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        
        ProductRecipe::where('master_product_id', $product->id)
            ->update(['is_active' => false]);

        return redirect()->route('product_recipes.index')
            ->with('success', 'Formula produk berhasil dihapus/dinonaktifkan.');
    }

    public function getRecipeJson($productId)
    {
        $product = MasterProduct::where('tenant_id', Auth::user()->tenant_id)->findOrFail($productId);
        
        $recipe = ProductRecipe::with(['items', 'labors'])
            ->where('master_product_id', $product->id)
            ->where('is_active', true)
            ->first();

        if (!$recipe) {
            return response()->json(['error' => 'Produk ini belum memiliki formula resep aktif.'], 422);
        }

        return response()->json([
            'batch_qty' => $recipe->batch_qty,
            'items' => $recipe->items->map(fn($item) => [
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => (float)$item->quantity
            ]),
            'labors' => $recipe->labors->map(fn($labor) => [
                'service_name' => $labor->service_name,
                'qty' => (int)($labor->qty ?? 1),
                'unit_cost' => (float)($labor->unit_cost ?? $labor->default_cost),
                'default_cost' => (float)$labor->default_cost
            ])
        ]);
    }
}
