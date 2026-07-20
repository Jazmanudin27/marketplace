<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\WarehouseMutation;
use App\Models\WarehouseMutationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseMutationController extends Controller
{


    // ==========================================
    // LAPORAN-LAPORAN PEMBELIAN (semua tipe barang)
    // ==========================================

    public function stockReportPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        $items = $query->orderBy('type')->orderBy('name')->paginate(25)->withQueryString();
        return view('inventory.pembelian.stock_report', compact('items'));
    }

    public function printStockReportPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        $items = $query->orderBy('type')->orderBy('name')->get();
        return view('inventory.pembelian.print_stock_report', compact('items'));
    }

    public function reportMutationPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $type     = $request->input('type', 'all');
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo) {
            $q->where('tenant_id', $tenantId)->whereBetween('mutation_date', [$dateFrom, $dateTo]);
            if ($type !== 'all') $q->where('type', $type);
        })->whereHas('inventoryItem', function ($q) use ($itemType) {
            $q->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
            if ($itemType !== 'all') $q->where('type', $itemType);
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);
        $mutations = $query->orderByDesc('id')->paginate(30)->withQueryString();
        return view('inventory.pembelian.report_mutation', compact('mutations', 'type', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function printReportMutationPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $type     = $request->input('type', 'all');
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo) {
            $q->where('tenant_id', $tenantId)->whereBetween('mutation_date', [$dateFrom, $dateTo]);
            if ($type !== 'all') $q->where('type', $type);
        })->whereHas('inventoryItem', function ($q) use ($itemType) {
            $q->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
            if ($itemType !== 'all') $q->where('type', $itemType);
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);
        $mutations = $query->orderByDesc('id')->get();
        return view('inventory.pembelian.print_report_mutation', compact('mutations', 'type', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function reportSummaryPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('type')->orderBy('name');
        if ($itemType !== 'all') $query->where('type', $itemType);
        $rekap = [];
        foreach ($query->get() as $item) {
            $afterQty = StockMovement::where('inventory_item_id', $item->id)
                ->where('created_at', '>', $dateTo . ' 23:59:59')
                ->sum('quantity');
            $stokAkhir = $item->stock - $afterQty;

            $movements = StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->with('warehouseMutation.toDepartment')
                ->get();

            $pembelian = 0;
            $returPenjualan = 0;
            $penyesuaianMasuk = 0;
            
            $produksi = 0;
            $percetakan = 0;
            $returPembelian = 0;
            $penyesuaianKeluar = 0;

            foreach ($movements as $m) {
                $qty = $m->quantity;
                $ref = $m->reference;
                
                if ($qty > 0) {
                    if (str_contains($ref, 'Terima') || str_contains($ref, 'Penerimaan')) {
                        $pembelian += $qty;
                    } elseif (str_contains($ref, 'Retur Penjualan') || str_contains($ref, 'Retur dari Pelanggan')) {
                        $returPenjualan += $qty;
                    } elseif (str_contains($ref, 'Pengembalian Bahan Baku SPK')) {
                        $produksi -= $qty; // net from production consumption
                    } else {
                        $penyesuaianMasuk += $qty;
                    }
                } else {
                    $absQty = abs($qty);
                    if (str_contains($ref, 'Retur ke Supplier') || str_contains($ref, 'Purchase Return') || str_contains($ref, 'Retur Pembelian')) {
                        $returPembelian += $absQty;
                    } elseif (str_contains($ref, 'SPK') || str_contains($ref, 'Konsumsi Bahan Baku SPK') || (str_contains(strtolower($ref), 'pengeluaran') && $m->warehouseMutation && $m->warehouseMutation->toDepartment && str_contains(strtolower($m->warehouseMutation->toDepartment->name), 'produksi'))) {
                        $produksi += $absQty;
                    } elseif (str_contains(strtolower($ref), 'percetakan') || ($m->warehouseMutation && $m->warehouseMutation->toDepartment && str_contains(strtolower($m->warehouseMutation->toDepartment->name), 'percetakan'))) {
                        $percetakan += $absQty;
                    } else {
                        $penyesuaianKeluar += $absQty;
                    }
                }
            }

            $totalMasuk = $pembelian + $returPenjualan + $penyesuaianMasuk;
            $totalKeluar = $produksi + $percetakan + $returPembelian + $penyesuaianKeluar;
            $stokAwal = $stokAkhir - ($totalMasuk - $totalKeluar);

            $rekap[] = [
                'sku' => $item->sku,
                'name' => $item->name,
                'unit' => $item->unit,
                'type' => $item->type,
                'stok_awal' => $stokAwal,
                'pembelian' => $pembelian,
                'retur_penjualan' => $returPenjualan,
                'penyesuaian_masuk' => $penyesuaianMasuk,
                'produksi' => $produksi,
                'percetakan' => $percetakan,
                'retur_pembelian' => $returPembelian,
                'penyesuaian_keluar' => $penyesuaianKeluar,
                'stok_akhir' => $stokAkhir,
                'cost_price' => $item->cost_price ?? 0,
                'total_value' => $stokAkhir * ($item->cost_price ?? 0)
            ];
        }
        return view('inventory.pembelian.report_summary', compact('rekap', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function printReportSummaryPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('type')->orderBy('name');
        if ($itemType !== 'all') $query->where('type', $itemType);
        $rekap = [];
        foreach ($query->get() as $item) {
            $afterQty = StockMovement::where('inventory_item_id', $item->id)
                ->where('created_at', '>', $dateTo . ' 23:59:59')
                ->sum('quantity');
            $stokAkhir = $item->stock - $afterQty;

            $movements = StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->with('warehouseMutation.toDepartment')
                ->get();

            $pembelian = 0;
            $returPenjualan = 0;
            $penyesuaianMasuk = 0;
            
            $produksi = 0;
            $percetakan = 0;
            $returPembelian = 0;
            $penyesuaianKeluar = 0;

            foreach ($movements as $m) {
                $qty = $m->quantity;
                $ref = $m->reference;
                
                if ($qty > 0) {
                    if (str_contains($ref, 'Terima') || str_contains($ref, 'Penerimaan')) {
                        $pembelian += $qty;
                    } elseif (str_contains($ref, 'Retur Penjualan') || str_contains($ref, 'Retur dari Pelanggan')) {
                        $returPenjualan += $qty;
                    } elseif (str_contains($ref, 'Pengembalian Bahan Baku SPK')) {
                        $produksi -= $qty;
                    } else {
                        $penyesuaianMasuk += $qty;
                    }
                } else {
                    $absQty = abs($qty);
                    if (str_contains($ref, 'Retur ke Supplier') || str_contains($ref, 'Purchase Return') || str_contains($ref, 'Retur Pembelian')) {
                        $returPembelian += $absQty;
                    } elseif (str_contains($ref, 'SPK') || str_contains($ref, 'Konsumsi Bahan Baku SPK') || (str_contains(strtolower($ref), 'pengeluaran') && $m->warehouseMutation && $m->warehouseMutation->toDepartment && str_contains(strtolower($m->warehouseMutation->toDepartment->name), 'produksi'))) {
                        $produksi += $absQty;
                    } elseif (str_contains(strtolower($ref), 'percetakan') || ($m->warehouseMutation && $m->warehouseMutation->toDepartment && str_contains(strtolower($m->warehouseMutation->toDepartment->name), 'percetakan'))) {
                        $percetakan += $absQty;
                    } else {
                        $penyesuaianKeluar += $absQty;
                    }
                }
            }

            $totalMasuk = $pembelian + $returPenjualan + $penyesuaianMasuk;
            $totalKeluar = $produksi + $percetakan + $returPembelian + $penyesuaianKeluar;
            $stokAwal = $stokAkhir - ($totalMasuk - $totalKeluar);

            $rekap[] = [
                'sku' => $item->sku,
                'name' => $item->name,
                'unit' => $item->unit,
                'type' => $item->type,
                'stok_awal' => $stokAwal,
                'pembelian' => $pembelian,
                'retur_penjualan' => $returPenjualan,
                'penyesuaian_masuk' => $penyesuaianMasuk,
                'produksi' => $produksi,
                'percetakan' => $percetakan,
                'retur_pembelian' => $returPembelian,
                'penyesuaian_keluar' => $penyesuaianKeluar,
                'stok_akhir' => $stokAkhir,
                'cost_price' => $item->cost_price ?? 0,
                'total_value' => $stokAkhir * ($item->cost_price ?? 0)
            ];
        }
        return view('inventory.pembelian.print_report_summary', compact('rekap', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function stockCardPembelian(Request $request)
    {
        $tenantId       = Auth::user()->tenant_id;
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('type')->orderBy('name')->get();
        $movements    = collect();
        $selectedItem = null;
        $dateFrom     = $request->input('date_from', date('Y-m-01'));
        $dateTo       = $request->input('date_to', date('Y-m-d'));
        if ($request->filled('item_id')) {
            $selectedItem = InventoryItem::where('tenant_id', $tenantId)->findOrFail($request->item_id);
            $movements = StockMovement::where('inventory_item_id', $selectedItem->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->with('department')->orderBy('created_at')->get();
        }
        return view('inventory.pembelian.stock_card', compact('inventoryItems', 'selectedItem', 'movements', 'dateFrom', 'dateTo'));
    }

    public function printStockCardPembelian(Request $request)
    {
        $tenantId     = Auth::user()->tenant_id;
        $dateFrom     = $request->input('date_from', date('Y-m-01'));
        $dateTo       = $request->input('date_to', date('Y-m-d'));
        $selectedItem = InventoryItem::where('tenant_id', $tenantId)->findOrFail($request->item_id);
        $movements    = StockMovement::where('inventory_item_id', $selectedItem->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('department')->orderBy('created_at')->get();
        return view('inventory.pembelian.print_stock_card', compact('selectedItem', 'movements', 'dateFrom', 'dateTo'));
    }

    // ==========================================
    // PENGELUARAN BARANG (Goods Issue) PEMBELIAN
    // ==========================================

    public function goodsIssueIndex(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = WarehouseMutation::with(['items.inventoryItem', 'toDepartment'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'out')
            ->orderByDesc('mutation_date');

        if ($request->filled('search')) {
            $query->where('mutation_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('mutation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('mutation_date', '<=', $request->date_to);
        }

        $mutations = $query->paginate(20)->withQueryString();
        return view('inventory.pembelian.goods_issue.index', compact('mutations'));
    }

    public function goodsIssueCreate()
    {
        $tenantId = Auth::user()->tenant_id;
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('inventory.pembelian.goods_issue.create', compact('inventoryItems'));
    }

    private function getDepartmentIdByName($name)
    {
        $tenantId = Auth::user()->tenant_id;
        $dept = Department::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();
            
        if (!$dept) {
            $dept = Department::create([
                'tenant_id' => $tenantId,
                'name'      => $name,
                'code'      => strtoupper(str_replace(' ', '_', $name)),
                'is_active' => true,
            ]);
        }
        return $dept->id;
    }

    public function goodsIssueStore(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'mutation_date'    => 'required|date',
            'tujuan'           => 'required|in:produksi,percetakan,lain_lain',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        foreach ($request->items as $row) {
            $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
            if ($item->stock < (int)$row['quantity']) {
                return back()->withInput()->withErrors(['items' => 'Stok barang ' . $item->name . ' tidak mencukupi. Sisa stok: ' . $item->stock]);
            }
        }

        $mutation = DB::transaction(function () use ($request, $tenantId) {
            $userId         = Auth::id();
            $mutationNumber = WarehouseMutation::generateMutationNumber('out');

            $toDeptId = null;
            if ($request->tujuan === 'produksi') {
                $toDeptId = $this->getDepartmentIdByName('Produksi');
            } elseif ($request->tujuan === 'percetakan') {
                $toDeptId = $this->getDepartmentIdByName('Percetakan');
            } elseif ($request->tujuan === 'lain_lain') {
                $toDeptId = $this->getDepartmentIdByName('Lain-lain');
            }

            $mutation = WarehouseMutation::create([
                'tenant_id'       => $tenantId,
                'mutation_number' => $mutationNumber,
                'type'            => 'out',
                'to_department_id'=> $toDeptId,
                'mutation_date'   => $request->mutation_date,
                'status'          => 'approved',
                'notes'           => $request->notes,
                'created_by'      => $userId,
            ]);

            foreach ($request->items as $row) {
                $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
                $qty  = (int) $row['quantity'];

                $mutation->items()->create([
                    'inventory_item_id' => $item->id,
                    'quantity'          => $qty,
                    'unit_price'        => $item->cost_price ?: 0,
                    'notes'             => $row['notes'] ?? null,
                ]);

                $item->decrement('stock', $qty);
                $newStock = $item->fresh()->stock;

                StockMovement::create([
                    'tenant_id'             => $tenantId,
                    'inventory_item_id'     => $item->id,
                    'warehouse_mutation_id' => $mutation->id,
                    'user_id'               => $userId,
                    'type'                  => 'out',
                    'quantity'              => -$qty,
                    'reference'             => 'Pengeluaran Barang (' . $mutationNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $mutation;
        });

        return redirect()->route('pembelian.goods_issue.show', $mutation)
            ->with('success', 'Transaksi Pengeluaran Barang berhasil disimpan.');
    }

    public function goodsIssueShow(WarehouseMutation $warehouseMutation)
    {
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);
        $warehouseMutation->load(['items.inventoryItem', 'createdBy', 'toDepartment']);

        return view('inventory.pembelian.goods_issue.show', compact('warehouseMutation'));
    }

    public function goodsIssueDestroy(WarehouseMutation $warehouseMutation)
    {
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($warehouseMutation) {
            $tenantId = Auth::user()->tenant_id;
            $userId   = Auth::id();

            foreach ($warehouseMutation->items as $item) {
                $invItem = $item->inventoryItem;
                if (!$invItem) continue;

                $invItem->increment('stock', $item->quantity);
                $newStock = $invItem->fresh()->stock;

                StockMovement::create([
                    'tenant_id'         => $tenantId,
                    'inventory_item_id' => $invItem->id,
                    'user_id'           => $userId,
                    'type'              => 'adjustment',
                    'quantity'          => $item->quantity,
                    'reference'         => 'Batal Pengeluaran Barang (' . $warehouseMutation->mutation_number . ')',
                    'balance_after'     => $newStock,
                ]);
            }

            $warehouseMutation->delete();
        });

        return redirect()->route('pembelian.goods_issue.index')
            ->with('success', 'Transaksi pengeluaran barang berhasil dibatalkan.');
    }
}