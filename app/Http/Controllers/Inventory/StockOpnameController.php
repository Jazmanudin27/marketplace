<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = \App\Models\StockMovement::with(['masterProduct', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('reference', 'like', 'Stock Opname Massal%')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->whereHas('masterProduct', function($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%');
                })->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $histories = $query->paginate(30)->withQueryString();

        return view('inventory.stock_opnames.index', compact('histories'));
    }

    public function create(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::with('category', 'brand')->where('tenant_id', $tenantId)->where('is_active', true);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $products = $query->paginate(50)->withQueryString();

        $categories = \App\Models\Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = \App\Models\Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('inventory.stock_opnames.create', compact('products', 'categories', 'brands'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'actual_stocks' => 'required|array',
            'actual_stocks.*' => 'nullable|integer|min:0',
            'opname_date' => 'required|date',
            'pic' => 'required|string|max:255',
        ]);

        $actualStocks = $request->actual_stocks;
        $productIds = array_keys($actualStocks);

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->whereIn('id', $productIds)
            ->get();

        $changesCount = 0;

        $date = \Carbon\Carbon::parse($request->opname_date)->format('Y-m-d H:i:s');
        $reference = 'Stock Opname Massal - ' . $request->pic;

        foreach ($products as $product) {
            $actualStock = $actualStocks[$product->id];

            if ($actualStock === null || $actualStock === '') {
                continue;
            }

            $difference = $actualStock - $product->stock;

            if ($difference != 0) {
                $product->recordStockMovement(
                    $difference,
                    'adj',
                    $reference,
                    Auth::id(),
                    $date
                );
                $changesCount++;
            }
        }

        return redirect()->route('stock_opnames.index')->with('success', "Stock Opname berhasil disimpan. Terdapat {$changesCount} produk yang disesuaikan pada tanggal {$request->opname_date} oleh {$request->pic}.");
    }

    public function importForm()
    {
        return view('inventory.stock_opnames.import');
    }

    public function downloadTemplate()
    {
        $csvContent = "SKU,Jumlah\nPROD-001,50\nPROD-002,120\nPROD-003,0\n";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_stok_opname.csv"',
        ]);
    }

    public function importStore(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'file' => 'required|file|max:10240',
            'opname_date' => 'required|date',
            'pic' => 'required|string|max:255',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $content = file_get_contents($path);

        // Remove UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return redirect()->back()->with('error', 'File yang diunggah kosong.');
        }

        $firstLine = $lines[0];
        $delimiters = [',', ';', "\t", '|'];
        $chosenDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
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
            return redirect()->back()->with('error', 'Tidak ada baris data yang valid ditemukan dalam berkas.');
        }

        $skuCol = 0;
        $qtyCol = 1;

        $firstRow = array_map('strtolower', array_map('trim', $rows[0]));
        $hasHeader = false;

        foreach ($firstRow as $idx => $headerName) {
            $cleanHeader = preg_replace('/[^a-z0-9_]/', '', $headerName);
            if (in_array($cleanHeader, ['sku', 'kode', 'kode_barang', 'skuinbuk', 'kodeproduk', 'product_sku'])) {
                $skuCol = $idx;
                $hasHeader = true;
            }
            if (in_array($cleanHeader, ['jumlah', 'stok', 'qty', 'quantity', 'stok_fisik', 'actual_stock', 'jumlah_fisik', 'stokfisik'])) {
                $qtyCol = $idx;
                $hasHeader = true;
            }
        }

        $startIndex = $hasHeader ? 1 : 0;

        $date = \Carbon\Carbon::parse($request->opname_date)->format('Y-m-d H:i:s');
        $reference = 'Stock Opname Massal - ' . $request->pic;

        $changesCount = 0;
        $unchangedCount = 0;
        $errors = [];
        $processedSkus = [];
        $userId = Auth::id();

        DB::transaction(function () use (
            $startIndex,
            $rows,
            $skuCol,
            $qtyCol,
            $tenantId,
            $reference,
            $userId,
            $date,
            &$changesCount,
            &$unchangedCount,
            &$errors,
            &$processedSkus
        ) {
            for ($i = $startIndex; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNum = $i + 1;

                if (!isset($row[$skuCol]) || trim($row[$skuCol]) === '') {
                    continue;
                }

                $sku = trim($row[$skuCol]);
                $rawQty = isset($row[$qtyCol]) ? trim($row[$qtyCol]) : null;

                if ($rawQty === null || $rawQty === '') {
                    $errors[] = "Baris #{$rowNum}: SKU {$sku} dilewati (kolom Jumlah kosong).";
                    continue;
                }

                $cleanQtyStr = str_replace([' ', ','], ['', ''], $rawQty);
                if (!is_numeric($cleanQtyStr)) {
                    $errors[] = "Baris #{$rowNum}: SKU {$sku} memiliki format Jumlah tidak valid ('{$rawQty}').";
                    continue;
                }

                $actualStock = (int) round((float) $cleanQtyStr);
                if ($actualStock < 0) {
                    $errors[] = "Baris #{$rowNum}: SKU {$sku} memiliki Jumlah negatif ('{$rawQty}').";
                    continue;
                }

                $product = MasterProduct::where('tenant_id', $tenantId)
                    ->where('sku', $sku)
                    ->first();

                if (!$product) {
                    $errors[] = "Baris #{$rowNum}: SKU '{$sku}' tidak ditemukan di sistem.";
                    continue;
                }

                if (in_array($product->id, $processedSkus)) {
                    $errors[] = "Baris #{$rowNum}: SKU '{$sku}' ganda/duplikat dalam berkas, hanya baris pertama yang diproses.";
                    continue;
                }

                $processedSkus[] = $product->id;
                $difference = $actualStock - $product->stock;

                if ($difference != 0) {
                    $product->recordStockMovement(
                        $difference,
                        'adj',
                        $reference,
                        $userId,
                        $date
                    );
                    $changesCount++;
                } else {
                    $unchangedCount++;
                }
            }
        });

        $summaryMessage = "Import Stok Opname Selesai. {$changesCount} produk disesuaikan, {$unchangedCount} produk stoknya sesuai.";

        if (!empty($errors)) {
            return redirect()->route('stock_opnames.index')
                ->with('success', $summaryMessage)
                ->with('import_errors', $errors);
        }

        return redirect()->route('stock_opnames.index')->with('success', $summaryMessage);
    }
}
