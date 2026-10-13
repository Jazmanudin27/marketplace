<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    private function checkAccess($permission = 'stock-opnames.index')
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->role !== 'admin' && !$user->can($permission)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola Opname Stok V2.');
        }
    }

    /**
     * Display V2 Stock Opname Index page.
     */
    public function index(Request $request)
    {
        $this->checkAccess('stock-opnames.index');
        $tenantId = Auth::user()->tenant_id;

        $query = StockMovement::with(['masterProduct', 'inventoryItem', 'user'])
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['adj', 'adjustment'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                  ->orWhereHas('masterProduct', function ($mq) use ($search) {
                      $mq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('inventoryItem', function ($iq) use ($search) {
                      $iq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('target_type')) {
            if ($request->target_type === 'product') {
                $query->whereNotNull('master_product_id');
            } elseif ($request->target_type === 'inventory') {
                $query->whereNotNull('inventory_item_id');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Summary KPI statistics
        $statsQuery = StockMovement::where('tenant_id', $tenantId)->whereIn('type', ['adj', 'adjustment']);
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $totalTransactions = (clone $statsQuery)->count();
        $totalDiffQty      = (clone $statsQuery)->sum('quantity');
        $opnames            = $query->paginate(15)->withQueryString();

        return view('v2.stock_opname.index', compact(
            'opnames',
            'totalTransactions',
            'totalDiffQty'
        ));
    }

    /**
     * Show form to create Stock Opname V2.
     */
    public function create()
    {
        $this->checkAccess('stock-opnames.index');
        $tenantId = Auth::user()->tenant_id;

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $masterProducts = MasterProduct::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('v2.stock_opname.create', compact('inventoryItems', 'masterProducts'));
    }

    /**
     * Store Stock Opname V2.
     */
    public function store(Request $request)
    {
        $this->checkAccess('stock-opnames.index');
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'opname_date' => 'required|date',
            'pic'         => 'required|string|max:255',
            'notes'       => 'nullable|string|max:1000',
            'items'       => 'required|array|min:1',
            'items.*.item_type'    => 'required|in:inventory,product',
            'items.*.item_id'      => 'required|integer',
            'items.*.actual_stock' => 'required|numeric|min:0',
        ], [
            'items.required' => 'Minimal pilih 1 item untuk dilakukan opname stok.',
            'pic.required'   => 'Nama PIC / Petugas Opname wajib diisi.',
        ]);

        $date      = Carbon::parse($request->opname_date)->format('Y-m-d H:i:s');
        $pic       = trim($request->pic);
        $userNotes = trim($request->notes ?? '');
        $reference = "Stock Opname - {$pic}" . ($userNotes ? " ({$userNotes})" : '');

        DB::transaction(function () use ($request, $tenantId, $reference, $date) {
            $userId = Auth::id();

            foreach ($request->items as $itemData) {
                $actualStock = (float) $itemData['actual_stock'];

                if ($itemData['item_type'] === 'inventory') {
                    $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($itemData['item_id']);
                    $currentStock = (float) $item->stock;
                    $difference   = $actualStock - $currentStock;

                    if ($difference != 0) {
                        $item->recordStockMovement(
                            (int) round($difference),
                            'adj',
                            $reference,
                            $userId,
                            $date
                        );
                    }
                } else {
                    $product = MasterProduct::where('tenant_id', $tenantId)->findOrFail($itemData['item_id']);
                    $currentStock = (float) $product->stock;
                    $difference   = $actualStock - $currentStock;

                    if ($difference != 0) {
                        $product->recordStockMovement(
                            (int) round($difference),
                            'adj',
                            $reference,
                            $userId,
                            $date
                        );
                    }
                }
            }
        });

        return redirect()->route('v2.stock_opname.index')
            ->with('success', '✅ Stock Opname V2 berhasil disimpan dan stok fisik telah disesuaikan.');
    }

    /**
     * Download template CSV/Excel Stock Opname V2.
     */
    public function downloadImportTemplate(Request $request)
    {
        $this->checkAccess('stock-opnames.index');
        $tenantId = Auth::user()->tenant_id;
        $withData = $request->query('with_data') === '1';

        $filename = $withData 
            ? 'Template_Stock_Opname_Data_Aktif_' . date('Ymd_His') . '.csv' 
            : 'Template_Import_Stock_Opname.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($tenantId, $withData) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($withData) {
                fputcsv($file, ['SKU', 'Nama Produk', 'Stok Sistem', 'Stok Fisik', 'Catatan'], ';');

                $products = MasterProduct::where('tenant_id', $tenantId)
                    ->orderBy('name')
                    ->get();

                foreach ($products as $p) {
                    fputcsv($file, [
                        $p->sku,
                        $p->name,
                        $p->stock,
                        '',
                        ''
                    ], ';');
                }
            } else {
                fputcsv($file, ['SKU', 'Nama Produk (Opsional)', 'Stok Fisik', 'Catatan (Opsional)'], ';');
                fputcsv($file, ['SMP-PJG-L', 'Seragam SMP Lengan Panjang Size L', '50', 'Hitung Rak A'], ';');
                fputcsv($file, ['SMA-PDK-M', 'Seragam SMA Lengan Pendek Size M', '35', 'Hitung Rak B'], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process import Stock Opname (CSV / Excel).
     */
    public function importOpname(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $this->checkAccess('stock-opnames.index');
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'file'        => 'required|file|max:20480',
            'opname_date' => 'required|date',
            'pic'         => 'required|string|max:255',
            'notes'       => 'nullable|string|max:1000',
        ], [
            'file.required'        => 'File CSV/Excel opname wajib diunggah.',
            'opname_date.required' => 'Tanggal opname wajib diisi.',
            'pic.required'         => 'Nama PIC / Petugas Opname wajib diisi.',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $content = file_get_contents($filePath);
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return redirect()->back()->with('error', 'File yang diunggah kosong.');
        }

        // Auto detect delimiter
        $firstLine = $lines[0];
        $counts = [
            ';' => substr_count($firstLine, ';'),
            ',' => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($counts);
        $delimiter = key($counts) ?: ';';

        $header = str_getcsv($firstLine, $delimiter);
        if (!$header) {
            return redirect()->back()->with('error', 'Format header file CSV tidak valid.');
        }

        $headerMap = [];
        foreach ($header as $idx => $colName) {
            $clean = strtolower(trim(str_replace([' ', '_', '-', "\t", "\r", "\n"], '', (string)$colName)));
            $headerMap[$clean] = $idx;
        }

        $getValue = function($row, $keyNames) use ($headerMap) {
            if (!is_array($keyNames)) $keyNames = [$keyNames];
            foreach ($keyNames as $k) {
                $cleanKey = strtolower(trim(str_replace([' ', '_', '-'], '', $k)));
                if (isset($headerMap[$cleanKey]) && isset($row[$headerMap[$cleanKey]])) {
                    return trim((string)$row[$headerMap[$cleanKey]]);
                }
            }
            return '';
        };

        $parseQty = function($val) {
            if ($val === null || $val === '') return null;
            $trimmed = trim((string)$val);
            if ($trimmed === '' || $trimmed === '-') return null;
            $cleaned = preg_replace('/[^\d.,]/', '', $trimmed);
            if ($cleaned === '') return null;
            if ($cleaned === '0' || $cleaned === '0.00' || $cleaned === '0,00') return 0;

            if (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false) {
                if (strrpos($cleaned, ',') > strrpos($cleaned, '.')) {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    $cleaned = str_replace(',', '', $cleaned);
                }
            } elseif (strpos($cleaned, ',') !== false) {
                $parts = explode(',', $cleaned);
                if (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] > 0) {
                    $cleaned = str_replace(',', '', $cleaned);
                } else {
                    $cleaned = str_replace(',', '.', $cleaned);
                }
            } elseif (strpos($cleaned, '.') !== false) {
                $parts = explode('.', $cleaned);
                if (count($parts) > 2) {
                    $cleaned = str_replace('.', '', $cleaned);
                } elseif (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] >= 10) {
                    $cleaned = str_replace('.', '', $cleaned);
                }
            }

            return is_numeric($cleaned) ? (int) round((float) $cleaned) : null;
        };

        $date = Carbon::parse($request->opname_date)->format('Y-m-d H:i:s');
        $pic = trim($request->pic);
        $userNotes = trim($request->notes ?? '');
        $baseReference = "Stock Opname - {$pic}" . ($userNotes ? " ({$userNotes})" : '');
        $userId = Auth::id();

        $adjustedCount = 0;
        $matchingCount = 0;
        $skippedCount  = 0;
        $skippedDetails = [];

        DB::transaction(function () use (
            $lines, $delimiter, $getValue, $parseQty, $tenantId, $baseReference, $userId, $date,
            &$adjustedCount, &$matchingCount, &$skippedCount, &$skippedDetails
        ) {
            for ($i = 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if ($line === '') continue;

                $row = str_getcsv($line, $delimiter);
                if (count($row) < 1) continue;

                $sku = strtoupper($getValue($row, ['sku', 'kodesku', 'kode', 'kodebarang', 'kodeproduk', 'productsku', 'barcode']));
                if (empty($sku)) {
                    $skippedCount++;
                    continue;
                }

                $rawActualStock = $getValue($row, ['stokfisik', 'stok', 'jumlah', 'qty', 'quantity', 'actualstock', 'jumlahfisik', 'hasilhitung', 'fisik']);
                if ($rawActualStock === '') {
                    $skippedCount++;
                    continue;
                }

                $actualStock = $parseQty($rawActualStock);
                if ($actualStock === null || $actualStock < 0) {
                    $skippedCount++;
                    $skippedDetails[] = "SKU {$sku}: Format stok tidak valid ('{$rawActualStock}')";
                    continue;
                }

                $rowNote = $getValue($row, ['catatan', 'keterangan', 'notes', 'note']);
                $reference = $baseReference . ($rowNote ? " [{$rowNote}]" : '');

                // Cari MasterProduct terlebih dahulu
                $product = MasterProduct::where('tenant_id', $tenantId)
                    ->where(function($q) use ($sku) {
                        $q->where('sku', $sku)
                          ->orWhereRaw('LOWER(TRIM(sku)) = ?', [strtolower(trim($sku))])
                          ->orWhere('barcode', $sku);
                    })
                    ->first();

                if ($product) {
                    $currentStock = (int) $product->stock;
                    $diff = $actualStock - $currentStock;

                    if ($diff != 0) {
                        $product->recordStockMovement($diff, 'adj', $reference, $userId, $date);
                        $adjustedCount++;
                    } else {
                        $matchingCount++;
                    }
                    continue;
                }

                // Fallback: Cari InventoryItem (Bahan Baku / Inventaris)
                $inventoryItem = InventoryItem::where('tenant_id', $tenantId)
                    ->where(function($q) use ($sku) {
                        $q->where('sku', $sku)
                          ->orWhereRaw('LOWER(TRIM(sku)) = ?', [strtolower(trim($sku))]);
                    })
                    ->first();

                if ($inventoryItem) {
                    $currentStock = (int) $inventoryItem->stock;
                    $diff = $actualStock - $currentStock;

                    if ($diff != 0) {
                        $inventoryItem->recordStockMovement($diff, 'adj', $reference, $userId, $date);
                        $adjustedCount++;
                    } else {
                        $matchingCount++;
                    }
                    continue;
                }

                $skippedCount++;
                $skippedDetails[] = "SKU '{$sku}' tidak ditemukan";
            }

            // Recalculate bundle stocks
            if (method_exists(MasterProduct::class, 'recalculateAllBundleStocks')) {
                MasterProduct::recalculateAllBundleStocks($tenantId);
            }
        });

        $msg = "✅ Import Stock Opname berhasil! {$adjustedCount} produk disesuaikan stoknya, {$matchingCount} produk stoknya sudah sesuai";
        if ($skippedCount > 0) {
            $msg .= ", {$skippedCount} baris dilewati (SKU tidak ditemukan atau stok kosong).";
        } else {
            $msg .= ".";
        }

        $response = redirect()->route('v2.stock_opname.index')->with('success', $msg);
        if (!empty($skippedDetails) && count($skippedDetails) <= 10) {
            $response->with('info', "Catatan baris dilewati: " . implode(', ', $skippedDetails));
        }

        return $response;
    }
}
