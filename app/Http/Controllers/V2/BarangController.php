<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $activeTab = $request->query('tab', 'items');

        // Tab 1: Inventory Items Query
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

        $items = $query->orderBy('name')->paginate(15, ['*'], 'items_page')->withQueryString();

        // Tab 2: Stock Opname History Query
        $opnameQuery = StockMovement::with(['inventoryItem', 'masterProduct', 'user'])
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['adj', 'adjustment'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('opname_search')) {
            $search = $request->opname_search;
            $opnameQuery->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                  ->orWhereHas('inventoryItem', function ($iq) use ($search) {
                      $iq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('masterProduct', function ($mq) use ($search) {
                      $mq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('opname_date')) {
            $opnameQuery->whereDate('created_at', $request->opname_date);
        }

        $opnames = $opnameQuery->paginate(15, ['*'], 'opname_page')->withQueryString();

        return view('v2.barang.index', compact('items', 'counts', 'opnames', 'activeTab'));
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

        return redirect()->to(url('/v2/barang?tab=opname'))->with('success', "Stok barang \"{$item->name}\" berhasil disesuaikan.");
    }

    /**
     * Download CSV template for Stock Opname import.
     */
    public function downloadOpnameTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_stok_opname.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($file, ['SKU', 'Stok']);
            fputcsv($file, ['BRG-KMN-001', '100']);
            fputcsv($file, ['BRG-BHN-002', '45.5']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import Stock Opname from CSV file.
     */
    public function importOpname(Request $request)
    {
        $this->checkAccess('inventory-items.edit');
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'file' => 'required|file|max:10240',
            'pic'  => 'nullable|string|max:255',
        ]);

        $pic  = $request->filled('pic') ? trim($request->pic) : Auth::user()->name;
        $file = $request->file('file');
        $path = $file->getRealPath();

        $content = file_get_contents($path);
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return redirect()->back()->with('error', 'File import kosong.');
        }

        $delimiters = [',', ';', "\t", '|'];
        $chosenDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($lines[0], $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $chosenDelimiter = $delim;
            }
        }

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $rows[] = str_getcsv($line, $chosenDelimiter);
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Tidak ada data valid dalam file CSV.');
        }

        $skuCol = 0;
        $qtyCol = 1;
        $hasHeader = false;

        $firstRow = array_map('strtolower', array_map('trim', $rows[0]));
        foreach ($firstRow as $idx => $headerName) {
            $cleanHeader = preg_replace('/[^a-z0-9_]/', '', $headerName);
            if (in_array($cleanHeader, ['sku', 'kode', 'kode_barang', 'product_sku'])) {
                $skuCol = $idx;
                $hasHeader = true;
            }
            if (in_array($cleanHeader, ['stok', 'stock', 'qty', 'stok_fisik', 'actual_stock'])) {
                $qtyCol = $idx;
                $hasHeader = true;
            }
        }

        if ($hasHeader) {
            array_shift($rows);
        }

        $successCount = 0;
        $notFoundSkus = [];
        $date = now()->format('Y-m-d H:i:s');
        $reference = "Import Stock Opname — PIC: {$pic}";

        DB::transaction(function () use ($rows, $skuCol, $qtyCol, $tenantId, $reference, $date, &$successCount, &$notFoundSkus) {
            foreach ($rows as $row) {
                if (!isset($row[$skuCol]) || !isset($row[$qtyCol])) continue;

                $sku = strtoupper(trim($row[$skuCol]));
                $actualStock = (float) trim($row[$qtyCol]);

                if (empty($sku)) continue;

                // Search InventoryItem first
                $item = InventoryItem::where('tenant_id', $tenantId)->where('sku', $sku)->first();
                if ($item) {
                    $diff = $actualStock - $item->stock;
                    if ($diff != 0) {
                        $item->recordStockMovement((int) round($diff), 'adjustment', $reference, Auth::id(), $date);
                    }
                    $successCount++;
                    continue;
                }

                // Search MasterProduct fallback
                $product = MasterProduct::where('tenant_id', $tenantId)->where('sku', $sku)->first();
                if ($product) {
                    $diff = $actualStock - $product->stock;
                    if ($diff != 0) {
                        $product->recordStockMovement((int) round($diff), 'adjustment', $reference, Auth::id(), $date);
                    }
                    $successCount++;
                    continue;
                }

                $notFoundSkus[] = $sku;
            }
        });

        $msg = "✅ Success import stock opname untuk {$successCount} item barang.";
        if (count($notFoundSkus) > 0) {
            $msg .= " (SKU tidak ditemukan: " . implode(', ', array_slice($notFoundSkus, 0, 5)) . (count($notFoundSkus) > 5 ? ' dll' : '') . ")";
        }

        return redirect()->to(url('/v2/barang?tab=opname'))->with('success', $msg);
    }
}
