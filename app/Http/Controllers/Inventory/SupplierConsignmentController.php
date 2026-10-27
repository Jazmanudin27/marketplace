<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierConsignment;
use App\Models\SupplierConsignmentItem;
use App\Models\SupplierConsignmentSettlement;
use App\Models\SupplierConsignmentSettlementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierConsignmentController extends Controller
{
    /**
     * Daftar Penerimaan Barang Jadi Konsinyasi.
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = SupplierConsignment::with(['supplier', 'creator', 'approver', 'items'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('consignment_date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('reference_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('consignment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('consignment_date', '<=', $request->date_to);
        }

        $consignments = $query->paginate(20)->withQueryString();
        $suppliers    = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        // 🎯 Hitung Total Terjual yang harus disetorkan (Pcs & Rp HPP)
        $consignmentIds = SupplierConsignment::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->pluck('id');

        $itemsSummary = SupplierConsignmentItem::whereIn('supplier_consignment_id', $consignmentIds)
            ->select(
                DB::raw('COALESCE(SUM(qty_sold), 0) as total_qty_sold'),
                DB::raw('COALESCE(SUM(qty_sold * unit_cost_price), 0) as total_amount_sold'),
                DB::raw('COALESCE(SUM(qty_received), 0) as total_qty_received'),
                DB::raw('COALESCE(SUM(qty_received * unit_cost_price), 0) as total_amount_received')
            )
            ->first();

        $totalQtySoldAll   = (int) ($itemsSummary->total_qty_sold ?? 0);
        $totalAmountToPay  = (float) ($itemsSummary->total_amount_sold ?? 0); // Total nilai yang harus disetorkan (HPP)
        $totalQtyTitipAll  = (int) ($itemsSummary->total_qty_received ?? 0);
        $totalAmountHppAll = (float) ($itemsSummary->total_amount_received ?? 0);

        // Hitung juga nominal yang sudah disetor agar tahu sisa yang belum disetor
        $totalAmountSettled = (float) SupplierConsignmentSettlementItem::whereHas('settlement', function($q) use ($tenantId, $request) {
            $q->where('tenant_id', $tenantId)->where('status', 'approved');
            if ($request->filled('supplier_id')) {
                $q->where('supplier_id', $request->supplier_id);
            }
        })->sum('subtotal');

        $totalAmountUnsettled = max(0, $totalAmountToPay - $totalAmountSettled);

        // 🎯 REKAPITULASI PER SUPPLIER (Total Titipan Rp, Terjual Rp, Sudah Setor Rp, Sisa Belum Setor Rp)
        $suppliersList = Supplier::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($request->filled('supplier_id'), fn($q) => $q->where('id', $request->supplier_id))
            ->orderBy('name')
            ->get();

        $supplierSummaries = [];
        foreach ($suppliersList as $sup) {
            $supConsignmentIds = SupplierConsignment::where('tenant_id', $tenantId)
                ->where('supplier_id', $sup->id)
                ->where('status', 'approved')
                ->pluck('id');

            if ($supConsignmentIds->isEmpty()) {
                continue;
            }

            $supItemSummary = SupplierConsignmentItem::whereIn('supplier_consignment_id', $supConsignmentIds)
                ->select(
                    DB::raw('COALESCE(SUM(qty_received), 0) as total_received'),
                    DB::raw('COALESCE(SUM(qty_sold), 0) as total_sold'),
                    DB::raw('COALESCE(SUM(qty_received * unit_cost_price), 0) as total_titipan_rp'),
                    DB::raw('COALESCE(SUM(qty_sold * unit_cost_price), 0) as total_terjual_rp')
                )
                ->first();

            $totalTitipanRp = (float) ($supItemSummary->total_titipan_rp ?? 0);
            $totalTerjualRp = (float) ($supItemSummary->total_terjual_rp ?? 0);
            $totalQtyMasuk  = (int) ($supItemSummary->total_received ?? 0);
            $totalQtyLaku   = (int) ($supItemSummary->total_sold ?? 0);

            // Sudah disetorkan ke supplier ini
            $setorRp = (float) SupplierConsignmentSettlementItem::whereHas('settlement', function($q) use ($tenantId, $sup) {
                $q->where('tenant_id', $tenantId)
                  ->where('supplier_id', $sup->id)
                  ->where('status', 'approved');
            })->sum('subtotal');

            // Sisa belum disetorkan (berdasarkan barang laku terjual)
            $sisaBelumSetorRp = max(0, $totalTerjualRp - $setorRp);
            // Sisa saldo keseluruhan jika dibandingkan total titipan masuk
            $sisaTitipanRp = max(0, $totalTitipanRp - $setorRp);

            // 🎯 Ambil Histori Setoran Pembayaran Supplier ini (bisa dilihat, diedit, dan dihapus)
            $settlementHistory = SupplierConsignmentSettlement::with(['bankAccount'])
                ->where('tenant_id', $tenantId)
                ->where('supplier_id', $sup->id)
                ->orderByDesc('settlement_date')
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $supplierSummaries[] = [
                'supplier_id'         => $sup->id,
                'supplier_name'       => $sup->name,
                'supplier_phone'      => $sup->phone,
                'total_invoices'      => $supConsignmentIds->count(),
                'total_qty_masuk'     => $totalQtyMasuk,
                'total_qty_laku'      => $totalQtyLaku,
                'total_titipan_rp'    => $totalTitipanRp,
                'total_terjual_rp'    => $totalTerjualRp,
                'setor_rp'            => $setorRp,
                'sisa_belum_setor_rp' => $sisaBelumSetorRp,
                'sisa_titipan_rp'     => $sisaTitipanRp,
                'settlement_history'  => $settlementHistory,
            ];
        }

        return view('v2.titipan_barang.index', compact(
            'consignments',
            'suppliers',
            'totalQtySoldAll',
            'totalAmountToPay',
            'totalQtyTitipAll',
            'totalAmountHppAll',
            'totalAmountSettled',
            'totalAmountUnsettled',
            'supplierSummaries'
        ));
    }

    /**
     * Form Penerimaan Barang Konsinyasi Baru.
     */
    public function create()
    {
        return redirect()->route('v2.titipan_barang.create');
    }

    /**
     * AJAX Search untuk MasterProduct (Optimasi 20.000+ data dengan Select2).
     */
    public function searchProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search   = trim($request->input('q', ''));

        $query = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('sku_induk', 'like', '%' . $search . '%');
            });
        }

        $products = $query->select(['id', 'sku', 'name', 'stock', 'cost_price', 'price'])
            ->orderBy('name')
            ->limit(30)
            ->get();

        $results = $products->map(function ($p) {
            return [
                'id'         => $p->id,
                'text'       => '[' . $p->sku . '] ' . $p->name . ' (Stok: ' . number_format($p->stock) . ')',
                'sku'        => $p->sku,
                'name'       => $p->name,
                'cost_price' => (float) $p->cost_price,
                'price'      => (float) $p->price,
                'stock'      => (int) $p->stock,
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Simpan Penerimaan Barang Konsinyasi (Status: Pending).
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id'      => 'required|exists:suppliers,id',
            'consignment_date' => 'required|date',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.master_product_id'  => 'required|exists:master_products,id',
            'items.*.qty_received'       => 'required|integer|min:1',
            'items.*.unit_cost_price'    => 'required|numeric|min:0',
            'items.*.unit_selling_price' => 'required|numeric|min:0',
        ], [
            'supplier_id.required' => 'Supplier penyedia barang konsinyasi wajib dipilih.',
            'items.required'       => 'Minimal satu produk konsinyasi harus diisikan.',
        ]);

        $userId = Auth::id();
        $consignment = DB::transaction(function () use ($request, $tenantId, $userId) {
            $refNumber        = SupplierConsignment::generateReferenceNumber();
            $totalQtyReceived = 0;
            $totalAmountHpp   = 0;

            $consignment = SupplierConsignment::create([
                'tenant_id'          => $tenantId,
                'supplier_id'        => $request->supplier_id,
                'reference_number'   => $refNumber,
                'consignment_date'   => $request->consignment_date,
                'status'             => 'approved',
                'notes'              => $request->notes,
                'created_by'         => $userId,
                'approved_by'        => $userId,
                'approved_at'        => now(),
            ]);

            foreach ($request->items as $row) {
                $qty          = (int) $row['qty_received'];
                $costPrice    = (float) $row['unit_cost_price'];
                $sellingPrice = (float) $row['unit_selling_price'];

                $totalQtyReceived += $qty;
                $totalAmountHpp   += ($qty * $costPrice);

                $consignmentItem = $consignment->items()->create([
                    'master_product_id'  => $row['master_product_id'],
                    'qty_received'       => $qty,
                    'unit_cost_price'    => $costPrice,
                    'unit_selling_price' => $sellingPrice,
                    'notes'              => $row['notes'] ?? null,
                ]);

                // Langsung tambah stok master produk & update harga
                $product = MasterProduct::find($row['master_product_id']);
                if ($product) {
                    $product->increment('stock', $qty);
                    $product->update([
                        'cost_price' => $costPrice > 0 ? $costPrice : $product->cost_price,
                        'price'      => $sellingPrice > 0 ? $sellingPrice : $product->price,
                    ]);

                    $newStock = $product->fresh()->stock;

                    // Catat mutasi stok masuk
                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $product->id,
                        'user_id'           => $userId,
                        'type'              => 'in',
                        'quantity'          => $qty,
                        'balance_after'     => $newStock,
                        'reference'         => "Penerimaan Konsinyasi: {$refNumber}",
                    ]);
                }
            }

            $consignment->update([
                'total_qty_received' => $totalQtyReceived,
                'total_amount_hpp'   => $totalAmountHpp,
            ]);

            return $consignment;
        });

        return redirect()->route('v2.titipan_barang.show', $consignment)
            ->with('success', 'Penerimaan barang konsinyasi berhasil disimpan dan stok master produk telah langsung bertambah.');
    }

    /**
     * Detail Penerimaan Barang Konsinyasi.
     */
    public function show(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $consignment->load(['supplier', 'items.masterProduct', 'creator', 'approver', 'items.deductions.order']);

        return view('v2.titipan_barang.show', compact('consignment'));
    }

    /**
     * Cetak Label Stiker Barcode / QR Produk Barang Titipan Konsinyasi
     */
    public function printItemLabels(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $consignment->load(['supplier', 'items.masterProduct']);

        return view('v2.titipan_barang.print_labels', compact('consignment'));
    }

    /**
     * Setujui Penerimaan Barang: Tambah Stok MasterProduct & Catat Stock Movement.
     */
    public function approve(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        if ($consignment->status !== 'pending') {
            return back()->with('error', 'Penerimaan barang konsinyasi ini sudah disetujui atau dibatalkan.');
        }

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        DB::transaction(function () use ($consignment, $tenantId, $userId) {
            $consignment->load('items.masterProduct');

            foreach ($consignment->items as $item) {
                $product = $item->masterProduct;
                if ($product) {
                    // Update stok & cost_price / price pada MasterProduct
                    $product->increment('stock', $item->qty_received);
                    
                    // Update cost_price (HPP) dan price (Harga Jual) pada master produk jika bernilai > 0
                    $product->update([
                        'cost_price' => $item->unit_cost_price > 0 ? $item->unit_cost_price : $product->cost_price,
                        'price'      => $item->unit_selling_price > 0 ? $item->unit_selling_price : $product->price,
                    ]);

                    $newStock = $product->fresh()->stock;

                    // Catat mutasi stok masuk
                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $product->id,
                        'user_id'           => $userId,
                        'type'              => 'in',
                        'quantity'          => $item->qty_received,
                        'reference'         => 'Penitipan Barang Konsinyasi Supplier (' . ($consignment->supplier ? $consignment->supplier->name : 'Supplier') . ') Ã¢â‚¬â€ ' . $consignment->reference_number,
                        'balance_after'     => $newStock,
                    ]);
                }
            }

            $consignment->update([
                'status'      => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('supplier_consignments.show', $consignment)
            ->with('success', 'Penerimaan barang konsinyasi berhasil disetujui! Stok master produk telah bertambah secara otomatis.');
    }

    /**
     * Form Edit Penerimaan Barang Konsinyasi.
     */
    public function edit(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $tenantId  = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $products  = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'sku', 'name', 'stock', 'unit', 'cost_price', 'price']);
        $consignment->load(['supplier', 'items.masterProduct']);

        return view('v2.titipan_barang.edit', compact('consignment', 'suppliers', 'products'));
    }

    /**
     * Update Penerimaan Barang Konsinyasi & Penyesuaian Stok.
     */
    public function update(Request $request, SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        $request->validate([
            'supplier_id'      => 'required|exists:suppliers,id',
            'consignment_date' => 'required|date',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.master_product_id'  => 'required|exists:master_products,id',
            'items.*.qty_received'       => 'required|integer|min:1',
            'items.*.unit_cost_price'    => 'required|numeric|min:0',
            'items.*.unit_selling_price' => 'required|numeric|min:0',
        ], [
            'supplier_id.required' => 'Supplier penyedia barang konsinyasi wajib dipilih.',
            'items.required'       => 'Minimal satu produk konsinyasi harus diisikan.',
        ]);

        DB::transaction(function () use ($request, $consignment, $tenantId, $userId) {
            $consignment->load('items.masterProduct');

            // 1. Revert stok lama dari master product
            foreach ($consignment->items as $oldItem) {
                if ($product = $oldItem->masterProduct) {
                    $product->decrement('stock', $oldItem->qty_received);
                }
            }

            // 2. Hapus log Mutasi Stok Gudang (Penerimaan Konsinyasi) lama terkait transaksi ini
            StockMovement::where('tenant_id', $tenantId)
                ->where('reference', 'like', "%{$consignment->reference_number}%")
                ->delete();

            // 3. Hapus item lama
            $consignment->items()->delete();

            // 4. Tambahkan item baru & perbarui stok serta mutasi gudang masuk
            $totalQtyReceived = 0;
            $totalAmountHpp   = 0;

            foreach ($request->items as $row) {
                $qty          = (int) $row['qty_received'];
                $costPrice    = (float) $row['unit_cost_price'];
                $sellingPrice = (float) $row['unit_selling_price'];

                $totalQtyReceived += $qty;
                $totalAmountHpp   += ($qty * $costPrice);

                $consignment->items()->create([
                    'master_product_id'  => $row['master_product_id'],
                    'qty_received'       => $qty,
                    'unit_cost_price'    => $costPrice,
                    'unit_selling_price' => $sellingPrice,
                    'notes'              => $row['notes'] ?? null,
                ]);

                $product = MasterProduct::find($row['master_product_id']);
                if ($product) {
                    $product->increment('stock', $qty);
                    $product->update([
                        'cost_price' => $costPrice > 0 ? $costPrice : $product->cost_price,
                        'price'      => $sellingPrice > 0 ? $sellingPrice : $product->price,
                    ]);

                    $newStock = $product->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $product->id,
                        'user_id'           => $userId,
                        'type'              => 'in',
                        'quantity'          => $qty,
                        'balance_after'     => $newStock,
                        'reference'         => "Penerimaan Konsinyasi: {$consignment->reference_number}",
                    ]);
                }
            }

            // 5. Update Header Consignment
            $consignment->update([
                'supplier_id'        => $request->supplier_id,
                'consignment_date'   => $request->consignment_date,
                'notes'              => $request->notes,
                'total_qty_received' => $totalQtyReceived,
                'total_amount_hpp'   => $totalAmountHpp,
            ]);
        });

        return redirect()->route('supplier_consignments.show', $consignment)
            ->with('success', 'Penerimaan barang konsinyasi berhasil diperbarui dan stok master produk telah disesuaikan.');
    }

    /**
     * Batal/Hapus Penerimaan Barang Konsinyasi (Kembalikan Stok).
     */
    public function destroy(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        DB::transaction(function () use ($consignment, $tenantId, $userId) {
            $consignment->load('items.masterProduct');

            // 1. Kembalikan/kurangi stok master product yang sebelumnya masuk
            foreach ($consignment->items as $item) {
                if ($product = $item->masterProduct) {
                    $product->decrement('stock', $item->qty_received);
                }
            }

            // 2. Hapus log Mutasi Stok Gudang Masuk agar transaksi otomatis hilang dari Gudang Masuk & tidak masuk stok
            StockMovement::where('tenant_id', $tenantId)
                ->where('reference', 'like', "%{$consignment->reference_number}%")
                ->delete();

            // 3. Hapus item & header konsinyasi
            $consignment->items()->delete();
            $consignment->delete();
        });

        return redirect()->route('supplier_consignments.index')
            ->with('success', 'Transaksi penerimaan barang konsinyasi berhasil dihapus dan otomatis dibatalkan dari stok gudang masuk.');
    }

    /**
     * Kartu Stok, Mutasi & Rekapitulasi Persediaan Barang Konsinyasi per Supplier.
     */
    public function stockCard(Request $request)
    {
        $tenantId       = Auth::user()->tenant_id;
        $searchSupplier = trim($request->input('supplier', ''));
        $selectedSupplierId = $request->input('supplier_id');

        $suppliersQuery = Supplier::where('tenant_id', $tenantId)->where('is_active', true);
        if (!empty($searchSupplier)) {
            $suppliersQuery->where('name', 'like', '%' . $searchSupplier . '%');
        }
        $suppliers = $suppliersQuery->orderBy('name')->get();

        if (!$selectedSupplierId && !empty($searchSupplier) && $suppliers->count() === 1) {
            $selectedSupplierId = $suppliers->first()->id;
        }

        $reportData = [];
        $totalReceivedAll  = 0;
        $totalSoldAll      = 0;
        $totalRemainingAll = 0;
        $totalSettledAll   = 0;
        $totalUnsettledAll = 0;
        $totalPaidAmountAll= 0;
        $totalProfitAll    = 0;

        $itemsQuery = SupplierConsignmentItem::whereHas('consignment', function ($q) use ($tenantId, $selectedSupplierId) {
            $q->where('tenant_id', $tenantId)
              ->where('status', 'approved');
            if ($selectedSupplierId) {
                $q->where('supplier_id', $selectedSupplierId);
            }
        })->with(['masterProduct', 'consignment.supplier']);

        $items = $itemsQuery->get();
        $grouped = $items->groupBy('master_product_id');

        foreach ($grouped as $productId => $groupItems) {
            $product = MasterProduct::find($productId);
            if (!$product) continue;

            $qtyReceivedTotal = $groupItems->sum('qty_received');
            $unitCost         = $groupItems->avg('unit_cost_price') ?: $product->cost_price;
            $unitSelling      = $groupItems->avg('unit_selling_price') ?: $product->price;

            $qtySettledTotal  = (int) SupplierConsignmentSettlementItem::whereHas('settlement', function ($q) use ($tenantId, $selectedSupplierId) {
                $q->where('tenant_id', $tenantId)
                  ->where('status', 'approved');
                if ($selectedSupplierId) {
                    $q->where('supplier_id', $selectedSupplierId);
                }
            })->where('master_product_id', $productId)->sum('qty_settled');

            $actualQtySold = (int) $groupItems->sum('qty_sold');
            $qtySoldTotal  = $actualQtySold;

            // 🎯 Rumus Sisa Gudang: Masuk - Terjual (Group by per SKU/Product)
            $qtyRemainingTotal = max(0, $qtyReceivedTotal - $qtySoldTotal);
            $qtyUnsettledTotal = max(0, $qtySoldTotal - $qtySettledTotal);

            $nominalPaid      = $qtySettledTotal * $unitCost;
            $nominalUnsettled = $qtyUnsettledTotal * $unitCost;
            $profitTotal      = $qtySoldTotal * ($unitSelling - $unitCost);

            $totalReceivedAll  += $qtyReceivedTotal;
            $totalSoldAll      += $qtySoldTotal;
            $totalRemainingAll += $qtyRemainingTotal;
            $totalSettledAll   += $qtySettledTotal;
            $totalUnsettledAll += $qtyUnsettledTotal;
            $totalPaidAmountAll+= $nominalPaid;
            $totalProfitAll    += $profitTotal;

            $reportData[] = [
                'product_id'          => $product->id,
                'sku'                 => $product->sku,
                'name'                => $product->name,
                'unit'                => $product->unit ?: 'PCS',
                'unit_cost'           => $unitCost,
                'unit_selling'        => $unitSelling,
                'qty_received'        => $qtyReceivedTotal,
                'current_stock'       => $qtyRemainingTotal, // Sisa Gudang = Masuk - Terjual
                'qty_sold'            => $qtySoldTotal,
                'qty_remaining'       => $qtyRemainingTotal,
                'qty_settled'         => $qtySettledTotal,
                'qty_unsettled'       => $qtyUnsettledTotal,
                'nominal_paid'        => $nominalPaid,
                'nominal_unsettled'   => $nominalUnsettled,
                'profit_total'        => $profitTotal,
            ];
        }

        // Dapatkan riwayat setoran supplier ini
        $settlements = SupplierConsignmentSettlement::where('tenant_id', $tenantId)
            ->when($selectedSupplierId, function ($q) use ($selectedSupplierId) {
                $q->where('supplier_id', $selectedSupplierId);
            })
            ->orderByDesc('settlement_date')
            ->orderByDesc('id')
            ->get();

        $selectedSupplier = Supplier::find($selectedSupplierId);

        return view('v2.titipan_barang.stock_card', compact(
            'suppliers',
            'searchSupplier',
            'selectedSupplierId',
            'selectedSupplier',
            'reportData',
            'settlements',
            'totalReceivedAll',
            'totalSoldAll',
            'totalRemainingAll',
            'totalSettledAll',
            'totalUnsettledAll',
            'totalPaidAmountAll',
            'totalProfitAll'
        ));
    }

    /**
     * Form Input Setoran Hasil Penjualan ke Supplier.
     */
    public function createSettlement(Request $request)
    {
        return app(\App\Http\Controllers\V2\TitipanBarangController::class)->createSettlement($request);
    }

    /**
     * Simpan Setoran ke Supplier.
     */
    public function storeSettlement(Request $request)
    {
        return app(\App\Http\Controllers\V2\TitipanBarangController::class)->storeSettlement($request);
    }

    /**
     * Riwayat Setoran Pembayaran ke Supplier.
     */
    public function indexSettlement(Request $request)
    {
        $tenantId  = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        $query = SupplierConsignmentSettlement::where('tenant_id', $tenantId)
            ->with(['supplier', 'creator', 'items.masterProduct']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('settlement_number', 'like', '%' . $request->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('settlement_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('settlement_date', '<=', $request->date_to);
        }

        $settlements = $query->orderByDesc('settlement_date')->orderByDesc('id')->paginate(20)->withQueryString();

        // KPI Aggregates (based on filtered query without pagination)
        $aggregateQuery = SupplierConsignmentSettlement::where('tenant_id', $tenantId);
        if ($request->filled('search')) {
            $aggregateQuery->where(function ($q) use ($request) {
                $q->where('settlement_number', 'like', '%' . $request->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('supplier_id')) {
            $aggregateQuery->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('date_from')) {
            $aggregateQuery->whereDate('settlement_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $aggregateQuery->whereDate('settlement_date', '<=', $request->date_to);
        }
        $totalQtySettled    = (int) $aggregateQuery->sum('total_qty_settled');
        $totalAmountSettled = (float) $aggregateQuery->sum('total_amount_paid');

        return view('v2.titipan_barang.settlement_index', compact('settlements', 'suppliers', 'totalQtySettled', 'totalAmountSettled'));

    }

    /**
     * Detail Rincian Setoran Pembayaran Supplier.
     */
    public function showSettlement(SupplierConsignmentSettlement $settlement)
    {
        abort_unless($settlement->tenant_id === Auth::user()->tenant_id, 403);

        $settlement->load(['supplier', 'creator', 'bankAccount', 'items.masterProduct', 'items.consignmentItem.consignment']);

        return view('v2.titipan_barang.settlement_show', compact('settlement'));
    }

    /**
     * Hapus Transaksi Setoran Supplier.
     */
    public function destroySettlement(SupplierConsignmentSettlement $settlement)
    {
        abort_unless($settlement->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($settlement) {
            // Rollback mutasi kas pengeluaran (Expense) terkait setoran ini
            Expense::where('tenant_id', $settlement->tenant_id)
                ->where('category', 'Setoran Barang Konsinyasi')
                ->where(function ($q) use ($settlement) {
                    $q->where('title', 'like', "%{$settlement->settlement_number}%")
                      ->orWhere('description', 'like', "%{$settlement->settlement_number}%");
                })
                ->delete();

            $settlement->items()->delete();
            $settlement->delete();
        });

        return redirect()->route('supplier_consignments.settlement.index')
            ->with('success', 'Riwayat setoran supplier berhasil dihapus.');
    }
}

