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

        return redirect()->route('product_recipes.index', $request->query())
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

        return redirect()->route('product_recipes.index', $request->query())
            ->with('success', 'Formula produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $product = MasterProduct::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        
        ProductRecipe::where('master_product_id', $product->id)
            ->update(['is_active' => false]);

        return redirect()->route('product_recipes.index', request()->query())
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

    public function print($id)
    {
        $product = MasterProduct::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        
        $recipe = ProductRecipe::with(['items.inventoryItem', 'labors'])
            ->where('master_product_id', $product->id)
            ->where('is_active', true)
            ->first();

        if (!$recipe) {
            abort(404, 'Formula resep aktif tidak ditemukan untuk produk ini.');
        }

        return view('production.recipes.print', compact('product', 'recipe'));
    }

    public function printReport(Request $request)
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

        $products = $query->orderBy('name')->get();

        return view('production.recipes.print_report', compact('products'));
    }

    public function export(Request $request)
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

        $products = $query->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="laporan_hpp_formula_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compliance
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'NAMA PRODUK',
                'SKU',
                'TIPE PRODUK',
                'NAMA FORMULA TERPASANG',
                'JUMLAH BAHAN BAKU',
                'JUMLAH JASA/OPERASIONAL',
                'TOTAL HPP FORMULA (RP)'
            ]);

            foreach ($products as $p) {
                $recipe = $p->activeRecipe;
                $materialsCount = $recipe ? $recipe->items->count() : 0;
                $laborsCount = $recipe ? $recipe->labors->count() : 0;

                // Calculate total materials cost
                $materialsCost = 0;
                if ($recipe) {
                    foreach ($recipe->items as $item) {
                        $materialsCost += $item->quantity * ($item->inventoryItem->cost_price ?? 0);
                    }
                }

                // Calculate total labor cost
                $laborCost = $recipe ? $recipe->labors->sum('default_cost') : 0;

                // Total cost (HPP)
                $totalCost = 0;
                if ($recipe) {
                    $totalCost = ($materialsCost + $laborCost) / ($recipe->batch_qty ?? 1);
                }

                fputcsv($file, [
                    $p->name,
                    $p->sku ?? '—',
                    $p->is_bundle ? 'Set / Bundle' : 'Single',
                    $recipe ? $recipe->name : 'Belum dikonfigurasi',
                    $recipe && $materialsCount > 0 ? $materialsCount . ' item' : '—',
                    $recipe && $laborsCount > 0 ? $laborsCount . ' operasional' : '—',
                    $recipe ? (int) $totalCost : '0'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkEdit(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = MasterProduct::where('tenant_id', $tenantId)
            ->with(['activeRecipe.items.inventoryItem', 'activeRecipe.labors']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('sku_induk')) {
            $skuInduk = $request->sku_induk;
            $query->where('sku_induk', 'like', '%' . $skuInduk . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'has_recipe') {
                $query->whereHas('activeRecipe');
            } elseif ($request->status === 'no_recipe') {
                $query->whereDoesntHave('activeRecipe');
            }
        }

        $products = $query->orderBy('name')->paginate(10)->withQueryString();

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->orderBy('name')
            ->get();

        $laborServices = LaborService::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        return view('production.recipes.bulk', compact('products', 'inventoryItems', 'laborServices'));
    }

    public function bulkSaveAjax(Request $request)
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

        $hasItems = $request->has('items') && count($request->items) > 0;
        $hasLabors = $request->has('labors') && count($request->labors) > 0;

        if (!$hasItems && !$hasLabors) {
            DB::transaction(function () use ($product) {
                ProductRecipe::where('master_product_id', $product->id)
                    ->update(['is_active' => false]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Formula berhasil dikosongkan/dinonaktifkan.',
                'status' => 'empty'
            ]);
        }

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

        return response()->json([
            'success' => true,
            'message' => 'Formula berhasil disimpan.',
            'status' => 'saved'
        ]);
    }
}
