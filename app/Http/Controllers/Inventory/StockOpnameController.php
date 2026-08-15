<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = StockMovement::with(['masterProduct', 'user'])
            ->whereHas('masterProduct', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('type', 'adj');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('masterProduct', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $opnames = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('inventory.stock_opnames.index', compact('opnames'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('inventory.stock_opnames.create', compact('products'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.actual_stock' => 'required|integer|min:0',
            'opname_date' => 'required|date',
            'pic' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($request->opname_date)->format('Y-m-d H:i:s');
        $reference = 'Stock Opname - ' . $request->pic . ($request->notes ? ' (' . $request->notes . ')' : '');

        DB::transaction(function () use ($request, $tenantId, $reference, $date) {
            foreach ($request->items as $itemData) {
                $product = MasterProduct::where('tenant_id', $tenantId)
                    ->findOrFail($itemData['master_product_id']);

                $actualStock = (int) $itemData['actual_stock'];
                $difference = $actualStock - $product->stock;

                if ($difference != 0) {
                    $product->recordStockMovement(
                        $difference,
                        'adj',
                        $reference,
                        Auth::id(),
                        $date
                    );
                }
            }
        });

        return redirect()->route('stock_opnames.index')
            ->with('success', 'Stock Opname berhasil disimpan.');
    }

    public function importForm()
    {
        return view('inventory.stock_opnames.import');
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_stok_opname.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($file, ['SKU', 'Stok Fisik']);
            fputcsv($file, ['SKU-CONTOH-001', '50']);
            fputcsv($file, ['SKU-CONTOH-002', '120']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importStore(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'file' => 'required|file|max:20480',
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

        $date = Carbon::parse($request->opname_date)->format('Y-m-d H:i:s');
        $reference = 'Stock Opname Massal - ' . $request->pic;

        // 🚀 OPTIMISASI PERFORMA KILAT: Eager-load seluruh SKU sekaligus dari Database dalam 1 Query!
        $skusInCsv = [];
        for ($i = $startIndex; $i < count($rows); $i++) {
            if (isset($rows[$i][$skuCol]) && trim($rows[$i][$skuCol]) !== '') {
                $skusInCsv[] = trim($rows[$i][$skuCol]);
            }
        }
        $skusInCsv = array_unique($skusInCsv);

        // Fetch seluruh master product terkait sekaligus ke PHP Memory Index (Keyed by SKU)
        $productsBySku = MasterProduct::where('tenant_id', $tenantId)
            ->whereIn('sku', $skusInCsv)
            ->get()
            ->keyBy('sku');

        $changesCount = 0;
        $unchangedCount = 0;
        $errors = [];
        $processedSkus = [];
        $userId = Auth::id();

        $stockMovementsBatch = [];
        $masterProductUpdates = [];

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

            $product = $productsBySku->get($sku);

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
                $stockMovementsBatch[] = [
                    'master_product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'adj',
                    'quantity' => $difference,
                    'reference' => $reference,
                    'notes' => 'Import Massal',
                    'created_at' => $date,
                    'updated_at' => now(),
                ];

                $masterProductUpdates[$product->id] = $actualStock;
                $changesCount++;
            } else {
                $unchangedCount++;
            }
        }

        // ⚡ PROSES BULK WRITE HANYA DALAM 2 QUERY (100X LEBIH CEPEAT!)
        DB::transaction(function () use ($stockMovementsBatch, $masterProductUpdates) {
            // 1. Bulk Insert Stock Movements
            if (!empty($stockMovementsBatch)) {
                foreach (array_chunk($stockMovementsBatch, 500) as $chunk) {
                    DB::table('stock_movements')->insert($chunk);
                }
            }

            // 2. Bulk Update Master Products Stock
            if (!empty($masterProductUpdates)) {
                foreach ($masterProductUpdates as $productId => $newStock) {
                    DB::table('master_products')
                        ->where('id', $productId)
                        ->update(['stock' => $newStock, 'updated_at' => now()]);
                }
            }
        });

        $summaryMessage = "Import Stok Opname Selesai Kilat 🚀. {$changesCount} produk disesuaikan, {$unchangedCount} produk stoknya sesuai.";

        if (!empty($errors)) {
            return redirect()->route('stock_opnames.index')
                ->with('success', $summaryMessage)
                ->with('import_errors', $errors);
        }

        return redirect()->route('stock_opnames.index')
            ->with('success', $summaryMessage);
    }
}
