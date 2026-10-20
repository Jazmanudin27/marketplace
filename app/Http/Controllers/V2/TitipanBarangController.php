<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierConsignment;
use App\Models\SupplierConsignmentDeduction;
use App\Models\SupplierConsignmentItem;
use App\Models\SupplierConsignmentSettlement;
use App\Models\SupplierConsignmentSettlementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TitipanBarangController extends Controller
{
    /**
     * Daftar Penerimaan Barang Konsinyasi V2.
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = SupplierConsignment::with(['supplier', 'creator', 'approver'])
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

        return view('v2.titipan_barang.index', compact('consignments', 'suppliers'));
    }

    /**
     * Form Penerimaan Barang Konsinyasi Baru V2.
     */
    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $refNumber = SupplierConsignment::generateReferenceNumber();
        $products  = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'sku', 'name', 'stock', 'unit', 'cost_price', 'price']);

        return view('v2.titipan_barang.create', compact('suppliers', 'refNumber', 'products'));
    }

    /**
     * AJAX Search untuk MasterProduct dengan Select2.
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
     * Simpan Penerimaan Barang Konsinyasi V2.
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

        return redirect()->route('supplier_consignments.show', $consignment)
            ->with('success', 'Penerimaan barang konsinyasi berhasil disimpan dan stok master produk telah langsung bertambah.');
    }

    /**
     * Detail Penerimaan Barang Konsinyasi V2.
     */
    public function show(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $consignment->load([
            'supplier',
            'items.masterProduct',
            'creator',
            'approver',
            'items.deductions.order',
            'items.settlementItems.settlement.bankAccount',
            'items.settlementItems.settlement.creator'
        ]);

        return view('v2.titipan_barang.show', compact('consignment'));
    }

    /**
     * Cetak Label Stiker Barcode / QR Produk Barang Titipan Konsinyasi V2.
     */
    public function printItemLabels(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $consignment->load(['supplier', 'items.masterProduct']);

        return view('v2.titipan_barang.print_labels', compact('consignment'));
    }

    /**
     * Setujui Penerimaan Barang V2.
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
                    $product->increment('stock', $item->qty_received);
                    $product->update([
                        'cost_price' => $item->unit_cost_price > 0 ? $item->unit_cost_price : $product->cost_price,
                        'price'      => $item->unit_selling_price > 0 ? $item->unit_selling_price : $product->price,
                    ]);

                    $newStock = $product->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $product->id,
                        'user_id'           => $userId,
                        'type'              => 'in',
                        'quantity'          => $item->qty_received,
                        'reference'         => 'Penitipan Barang Konsinyasi Supplier (' . ($consignment->supplier ? $consignment->supplier->name : 'Supplier') . ') — ' . $consignment->reference_number,
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
            ->with('success', 'Penerimaan barang konsinyasi berhasil disetujui!');
    }

    /**
     * Form Edit Penerimaan Barang Konsinyasi V2.
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
     * Update Penerimaan Barang Konsinyasi V2.
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
        ]);

        DB::transaction(function () use ($request, $consignment, $tenantId, $userId) {
            $consignment->load('items.masterProduct');

            // 1. Revert stok lama pada produk master
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

            $consignment->update([
                'supplier_id'        => $request->supplier_id,
                'consignment_date'   => $request->consignment_date,
                'notes'              => $request->notes,
                'total_qty_received' => $totalQtyReceived,
                'total_amount_hpp'   => $totalAmountHpp,
            ]);
        });

        return redirect()->route('supplier_consignments.show', $consignment)
            ->with('success', 'Penerimaan barang konsinyasi berhasil diperbarui dan stok gudang terupdate.');
    }

    /**
     * Hapus Penerimaan Barang Konsinyasi V2.
     */
    public function destroy(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        DB::transaction(function () use ($consignment, $tenantId, $userId) {
            $consignment->load('items.masterProduct');

            // 1. Kurangi/kembalikan stok produk master yang sebelumnya masuk
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
     * Kartu Stok, Mutasi & Rekapitulasi Persediaan Konsinyasi V2.
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

        $reportData         = [];
        $totalReceivedAll   = 0;
        $totalSoldAll       = 0;
        $totalRemainingAll  = 0;
        $totalSettledAll    = 0;
        $totalUnsettledAll  = 0;
        $totalPaidAmountAll = 0;
        $totalProfitAll     = 0;

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

            $firstItem    = $groupItems->first();
            $supplierName = ($firstItem && $firstItem->consignment && $firstItem->consignment->supplier)
                ? $firstItem->consignment->supplier->name
                : 'Variasi Supplier';

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
            $currentStock  = $product->stock;
            $qtySoldTotal  = $actualQtySold > 0 ? min($qtyReceivedTotal, $actualQtySold) : max(0, $qtyReceivedTotal - $currentStock);
            if ($qtySoldTotal > $qtyReceivedTotal) {
                $qtySoldTotal = $qtyReceivedTotal;
            }

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
                'product_id'        => $product->id,
                'sku'               => $product->sku,
                'name'              => $product->name,
                'supplier_name'     => $supplierName,
                'unit'              => $product->unit ?: 'PCS',
                'unit_cost'         => $unitCost,
                'unit_selling'      => $unitSelling,
                'qty_received'      => $qtyReceivedTotal,
                'current_stock'     => $product->stock,
                'qty_sold'          => $qtySoldTotal,
                'qty_remaining'     => $qtyRemainingTotal,
                'qty_settled'       => $qtySettledTotal,
                'qty_unsettled'     => $qtyUnsettledTotal,
                'nominal_paid'      => $nominalPaid,
                'nominal_unsettled' => $nominalUnsettled,
                'profit_total'      => $profitTotal,
            ];
        }

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
     * Form Input Setoran Hasil Penjualan V2.
     */
    public function createSettlement(Request $request)
    {
        $tenantId     = Auth::user()->tenant_id;
        $suppliers    = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        $selectedSupplierId = $request->supplier_id ?: ($suppliers->first() ? $suppliers->first()->id : null);
        $availableItems     = [];

        if ($selectedSupplierId) {
            $items = SupplierConsignmentItem::whereHas('consignment', function ($q) use ($tenantId, $selectedSupplierId) {
                $q->where('tenant_id', $tenantId)
                  ->where('supplier_id', $selectedSupplierId)
                  ->where('status', 'approved');
            })->with(['masterProduct', 'consignment'])->get();

            foreach ($items as $item) {
                $product = $item->masterProduct;
                if (!$product) continue;

                $qtyReceived  = $item->qty_received;
                $currentStock = $product->stock;
                $actualSold   = (int) ($item->qty_sold ?? 0);
                $qtySold      = $actualSold > 0 ? min($qtyReceived, $actualSold) : max(0, $qtyReceived - $currentStock);

                $qtySettled = (int) SupplierConsignmentSettlementItem::where('supplier_consignment_item_id', $item->id)
                    ->whereHas('settlement', function ($q) {
                        $q->where('status', 'approved');
                    })
                    ->sum('qty_settled');

                $basisQty     = $actualSold > 0 ? $qtySold : $qtyReceived;
                $qtyUnsettled = max(0, $basisQty - $qtySettled);

                if ($qtyUnsettled > 0) {
                    $availableItems[] = [
                        'consignment_item_id' => $item->id,
                        'ref_number'          => $item->consignment ? $item->consignment->reference_number : '-',
                        'consignment_date'    => $item->consignment ? $item->consignment->consignment_date->format('Y-m-d') : '-',
                        'master_product_id'   => $product->id,
                        'sku'                 => $product->sku,
                        'name'                => $product->name,
                        'qty_received'        => $qtyReceived,
                        'qty_sold'            => $qtySold,
                        'qty_settled'         => $qtySettled,
                        'qty_unsettled'       => $qtyUnsettled,
                        'unit_cost_price'     => $item->unit_cost_price,
                    ];
                }
            }
        }

        $settlementNumber = SupplierConsignmentSettlement::generateSettlementNumber();

        return view('v2.titipan_barang.settlement_create', compact(
            'suppliers',
            'bankAccounts',
            'selectedSupplierId',
            'availableItems',
            'settlementNumber'
        ));
    }

    /**
     * Simpan Setoran ke Supplier V2.
     */
    public function storeSettlement(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id'     => 'required|exists:suppliers,id',
            'settlement_date' => 'required|date',
            'payment_method'  => 'required|in:cash,transfer',
            'bank_account_id' => 'required_if:payment_method,transfer|nullable|exists:bank_accounts,id',
            'notes'           => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.consignment_item_id' => 'required|exists:supplier_consignment_items,id',
            'items.*.master_product_id'   => 'required|exists:master_products,id',
            'items.*.qty_settled'         => 'required|integer|min:1',
            'items.*.unit_cost_price'     => 'required|numeric|min:0',
        ]);

        $settlement = DB::transaction(function () use ($request, $tenantId) {
            $settlementNumber = SupplierConsignmentSettlement::generateSettlementNumber();
            $totalQtySettled  = 0;
            $totalAmountPaid  = 0;

            $settlement = SupplierConsignmentSettlement::create([
                'tenant_id'         => $tenantId,
                'supplier_id'       => $request->supplier_id,
                'settlement_number' => $settlementNumber,
                'settlement_date'   => $request->settlement_date,
                'payment_method'    => $request->payment_method,
                'bank_account_id'   => $request->payment_method === 'transfer' ? $request->bank_account_id : null,
                'reference_number'  => $request->reference_number,
                'status'            => 'approved',
                'notes'             => $request->notes,
                'created_by'        => Auth::id(),
            ]);

            foreach ($request->items as $row) {
                $qtySettled = (int) $row['qty_settled'];
                $costPrice  = (float) $row['unit_cost_price'];
                $subtotal   = $qtySettled * $costPrice;

                if ($qtySettled > 0) {
                    $totalQtySettled += $qtySettled;
                    $totalAmountPaid += $subtotal;

                    $settlement->items()->create([
                        'supplier_consignment_item_id' => $row['consignment_item_id'],
                        'master_product_id'           => $row['master_product_id'],
                        'qty_settled'                 => $qtySettled,
                        'unit_cost_price'             => $costPrice,
                        'subtotal'                    => $subtotal,
                    ]);
                }
            }

            $settlement->update([
                'total_qty_settled' => $totalQtySettled,
                'total_amount_paid' => $totalAmountPaid,
            ]);

            $supplier = Supplier::find($request->supplier_id);
            Expense::create([
                'tenant_id'       => $tenantId,
                'category'        => 'Setoran Barang Konsinyasi',
                'amount'          => $totalAmountPaid,
                'expense_date'    => $request->settlement_date,
                'payment_method'  => $request->payment_method,
                'bank_account_id' => $request->bank_account_id,
                'description'     => 'Setoran Penjualan Barang Konsinyasi ' . ($supplier ? $supplier->name : '') . ' (' . $settlementNumber . ') — Total ' . $totalQtySettled . ' PCS',
                'created_by'      => Auth::id(),
            ]);

            return $settlement;
        });

        return redirect()->route('supplier_consignments.settlement.index', ['supplier_id' => $request->supplier_id])
            ->with('success', 'Setoran hasil penjualan ke supplier berhasil disimpan.');
    }

    /**
     * Riwayat Setoran Pembayaran Supplier V2.
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
     * Detail Rincian Setoran Pembayaran Supplier V2.
     */
    public function showSettlement(SupplierConsignmentSettlement $settlement)
    {
        abort_unless($settlement->tenant_id === Auth::user()->tenant_id, 403);

        $settlement->load(['supplier', 'creator', 'bankAccount', 'items.masterProduct', 'items.consignmentItem.consignment']);

        return view('v2.titipan_barang.settlement_show', compact('settlement'));
    }

    /**
     * Hapus Transaksi Setoran Supplier V2.
     */
    public function destroySettlement(SupplierConsignmentSettlement $settlement)
    {
        abort_unless($settlement->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($settlement) {
            $settlement->items()->delete();
            $settlement->delete();
        });

        return redirect()->route('supplier_consignments.settlement.index')
            ->with('success', 'Riwayat setoran supplier berhasil dihapus.');
    }

    /**
     * ─── CRUD PENGELUARAN STOK TITIPAN (CONSIGNMENT DEDUCTION) ───────────
     */

    /**
     * Daftar Riwayat Pengeluaran / Potongan Stok Titipan Barang.
     */
    public function indexDeductions(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = SupplierConsignmentDeduction::with([
            'consignmentItem.consignment.supplier',
            'consignmentItem.masterProduct',
            'order.store',
            'orderItem',
            'user'
        ])->where('tenant_id', $tenantId)
          ->orderByDesc('deduction_date')
          ->orderByDesc('id');

        // Filter Supplier
        if ($request->filled('supplier_id')) {
            $query->whereHas('consignmentItem.consignment', function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        // Filter Consignment Batch
        if ($request->filled('consignment_id')) {
            $query->whereHas('consignmentItem', function ($q) use ($request) {
                $q->where('supplier_consignment_id', $request->consignment_id);
            });
        }

        // Filter Reason
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        // Filter Search (Invoice / Resi / Reference / Notes / Produk / SKU)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('scanned_barcode', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('invoice_number', 'like', "%{$search}%")
                         ->orWhere('order_marketplace_id', 'like', "%{$search}%")
                         ->orWhere('tracking_number', 'like', "%{$search}%")
                         ->orWhere('buyer_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('consignmentItem.masterProduct', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%");
                  })
                  ->orWhereHas('consignmentItem.consignment', function ($cq) use ($search) {
                      $cq->where('reference_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Tanggal
        if ($request->filled('date_from')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('deduction_date', '>=', $request->date_from)
                  ->orWhere(function ($sub) use ($request) {
                      $sub->whereNull('deduction_date')->whereDate('created_at', '>=', $request->date_from);
                  });
            });
        }
        if ($request->filled('date_to')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('deduction_date', '<=', $request->date_to)
                  ->orWhere(function ($sub) use ($request) {
                      $sub->whereNull('deduction_date')->whereDate('created_at', '<=', $request->date_to);
                  });
            });
        }

        // KPI Metrik dari dataset terfilter
        $metricsQuery = clone $query;
        $allFiltered = $metricsQuery->get();
        $totalCount = $allFiltered->count();
        $totalQty = (int) $allFiltered->sum('quantity');
        $totalHppAmount = (float) $allFiltered->sum(function ($d) {
            $unitCost = $d->consignmentItem ? (float) $d->consignmentItem->unit_cost_price : 0;
            return $d->quantity * $unitCost;
        });
        $totalUnscannedOrders = $allFiltered->where('reason', 'unscanned_order')->count();
        $totalManualCorrections = $allFiltered->where('reason', '!=', 'unscanned_order')->count();

        $deductions = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $consignments = SupplierConsignment::where('tenant_id', $tenantId)->orderByDesc('id')->get(['id', 'reference_number']);

        return view('v2.titipan_barang.deductions.index', compact(
            'deductions',
            'suppliers',
            'consignments',
            'totalCount',
            'totalQty',
            'totalHppAmount',
            'totalUnscannedOrders',
            'totalManualCorrections'
        ));
    }

    /**
     * Form Pencatatan Pengeluaran Stok Titipan Manual V2.
     */
    public function createDeduction(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        $preselectedItemId = $request->input('consignment_item_id');
        $preselectedItem   = $preselectedItemId ? SupplierConsignmentItem::with(['consignment.supplier', 'masterProduct'])->find($preselectedItemId) : null;
        $preselectedOrderId = $request->input('order_id');
        $preselectedOrder   = $preselectedOrderId ? Order::with(['store', 'items.masterProduct'])->where('tenant_id', $tenantId)->find($preselectedOrderId) : null;

        // Ambil item titipan yang masih ada sisa stok
        $availableItems = SupplierConsignmentItem::with(['consignment.supplier', 'masterProduct'])
            ->whereHas('consignment', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('status', 'approved');
            })
            ->get()
            ->filter(fn($it) => $it->sisa_stok > 0)
            ->values();

        return view('v2.titipan_barang.deductions.create', compact(
            'suppliers',
            'availableItems',
            'preselectedItemId',
            'preselectedItem',
            'preselectedOrderId',
            'preselectedOrder'
        ));
    }

    /**
     * Simpan Pengeluaran Stok Titipan Baru V2.
     */
    public function storeDeduction(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_consignment_item_id' => 'required|exists:supplier_consignment_items,id',
            'quantity'                     => 'required|integer|min:1',
            'reason'                       => 'required|in:unscanned_order,manual_adjustment,return_supplier,damaged,direct_sale,other',
            'deduction_date'               => 'nullable|date',
            'reference_no'                 => 'nullable|string|max:100',
            'notes'                        => 'nullable|string|max:1000',
            'order_id'                     => 'nullable|exists:orders,id',
            'order_item_id'                => 'nullable|exists:order_items,id',
            'deduct_master_stock'          => 'nullable|boolean',
        ]);

        $consItem = SupplierConsignmentItem::with(['consignment', 'masterProduct'])
            ->where('id', $request->supplier_consignment_item_id)
            ->whereHas('consignment', fn($q) => $q->where('tenant_id', $tenantId))
            ->firstOrFail();

        $qty = (int) $request->quantity;

        // Pastikan sisa stok titipan cukup
        if ($consItem->sisa_stok < $qty) {
            return back()->withInput()->with('error', "Stok titipan tidak mencukupi! Sisa stok batch titipan ini adalah {$consItem->sisa_stok} pcs.");
        }

        $deduction = DB::transaction(function () use ($request, $consItem, $qty, $tenantId) {
            $deductMasterStock = $request->boolean('deduct_master_stock');
            $deductionDate = $request->filled('deduction_date') ? $request->deduction_date : now();

            // 1. Tambah counter qty_sold pada batch titipan
            $consItem->increment('qty_sold', $qty);

            // 2. Hubungkan ke order dan item jika dipilih
            $orderId = $request->order_id ?: null;
            $orderItemId = $request->order_item_id ?: null;

            if ($orderId && !$orderItemId) {
                // Cari item order yang cocok dengan master_product_id
                $matchingItem = OrderItem::where('order_id', $orderId)
                    ->where('master_product_id', $consItem->master_product_id)
                    ->first();
                if ($matchingItem) {
                    $orderItemId = $matchingItem->id;
                }
            }

            if ($orderItemId) {
                $orderItem = OrderItem::find($orderItemId);
                if ($orderItem) {
                    $orderItem->update([
                        'supplier_consignment_item_id' => $consItem->id,
                        'fulfillment_source'           => 'consignment',
                    ]);
                }
            }

            // 3. Jika user meminta kurangi stok gudang master fisik
            if ($deductMasterStock && $consItem->masterProduct) {
                $consItem->masterProduct->decrement('stock', $qty);
            }

            // 4. Buat record pemotongan konsinyasi
            $deduction = SupplierConsignmentDeduction::create([
                'tenant_id'                    => $tenantId,
                'supplier_consignment_item_id' => $consItem->id,
                'order_id'                     => $orderId,
                'order_item_id'                => $orderItemId,
                'quantity'                     => $qty,
                'reason'                       => $request->reason,
                'reference_no'                 => $request->reference_no,
                'notes'                        => $request->notes,
                'deduction_date'               => $deductionDate,
                'deduct_master_stock'          => $deductMasterStock,
                'user_id'                      => Auth::id(),
                'scanned_barcode'              => 'MANUAL-INPUT',
            ]);

            // 5. Catat audit trail di StockMovement
            $refNote = "Pengeluaran Titipan ({$consItem->consignment->reference_number}) - " . $deduction->reason_label;
            if ($deduction->order) {
                $refNote .= " (Order: " . ($deduction->order->invoice_number ?: $deduction->order->order_marketplace_id) . ")";
            } elseif ($request->reference_no) {
                $refNote .= " (Ref: {$request->reference_no})";
            }

            StockMovement::create([
                'tenant_id'         => $tenantId,
                'master_product_id' => $consItem->master_product_id,
                'user_id'           => Auth::id(),
                'type'              => 'out',
                'quantity'          => $qty,
                'balance_after'     => $consItem->masterProduct ? $consItem->masterProduct->stock : 0,
                'reference'         => $refNote,
            ]);

            return $deduction;
        });

        return redirect()->route('v2.titipan_barang.deductions.index')
            ->with('success', "Pengeluaran stok titipan sebanyak {$qty} pcs berhasil dicatat.");
    }

    /**
     * Rincian Detail Pengeluaran Stok Titipan V2.
     */
    public function showDeduction(SupplierConsignmentDeduction $deduction)
    {
        abort_unless($deduction->tenant_id === Auth::user()->tenant_id, 403);

        $deduction->load([
            'consignmentItem.consignment.supplier',
            'consignmentItem.masterProduct',
            'order.store.channel',
            'order.items.masterProduct',
            'orderItem',
            'user'
        ]);

        return view('v2.titipan_barang.deductions.show', compact('deduction'));
    }

    /**
     * Form Edit Pengeluaran Stok Titipan V2.
     */
    public function editDeduction(SupplierConsignmentDeduction $deduction)
    {
        abort_unless($deduction->tenant_id === Auth::user()->tenant_id, 403);
        $tenantId = Auth::user()->tenant_id;

        $deduction->load([
            'consignmentItem.consignment.supplier',
            'consignmentItem.masterProduct',
            'order.store',
            'order.items.masterProduct',
            'orderItem'
        ]);

        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $availableItems = SupplierConsignmentItem::with(['consignment.supplier', 'masterProduct'])
            ->whereHas('consignment', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('status', 'approved');
            })
            ->get()
            ->filter(fn($it) => $it->id === $deduction->supplier_consignment_item_id || $it->sisa_stok > 0)
            ->values();

        return view('v2.titipan_barang.deductions.edit', compact('deduction', 'suppliers', 'availableItems'));
    }

    /**
     * Update Pengeluaran Stok Titipan V2.
     */
    public function updateDeduction(Request $request, SupplierConsignmentDeduction $deduction)
    {
        abort_unless($deduction->tenant_id === Auth::user()->tenant_id, 403);
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_consignment_item_id' => 'required|exists:supplier_consignment_items,id',
            'quantity'                     => 'required|integer|min:1',
            'reason'                       => 'required|in:unscanned_order,manual_adjustment,return_supplier,damaged,direct_sale,other',
            'deduction_date'               => 'nullable|date',
            'reference_no'                 => 'nullable|string|max:100',
            'notes'                        => 'nullable|string|max:1000',
            'order_id'                     => 'nullable|exists:orders,id',
            'order_item_id'                => 'nullable|exists:order_items,id',
            'deduct_master_stock'          => 'nullable|boolean',
        ]);

        $newConsItem = SupplierConsignmentItem::with(['consignment', 'masterProduct'])
            ->where('id', $request->supplier_consignment_item_id)
            ->whereHas('consignment', fn($q) => $q->where('tenant_id', $tenantId))
            ->firstOrFail();

        $oldConsItem = $deduction->consignmentItem;
        $oldQty = (int) $deduction->quantity;
        $newQty = (int) $request->quantity;
        $newDeductMasterStock = $request->boolean('deduct_master_stock');
        $oldDeductMasterStock = (bool) $deduction->deduct_master_stock;

        // Cek ketersediaan stok
        $availableStock = ($oldConsItem && $newConsItem->id === $oldConsItem->id)
            ? ($newConsItem->sisa_stok + $oldQty)
            : $newConsItem->sisa_stok;

        if ($availableStock < $newQty) {
            return back()->withInput()->with('error', "Stok titipan tidak mencukupi! Maksimal yang dapat dikeluarkan adalah {$availableStock} pcs.");
        }

        DB::transaction(function () use ($request, $deduction, $oldConsItem, $newConsItem, $oldQty, $newQty, $oldDeductMasterStock, $newDeductMasterStock, $tenantId) {
            // 1. Revert stok titipan lama
            if ($oldConsItem) {
                $oldConsItem->decrement('qty_sold', $oldQty);
            }
            // Tambahkan stok titipan baru
            $newConsItem->increment('qty_sold', $newQty);

            // 2. Koreksi stok gudang master fisik jika ada perubahan opsi potong stok
            if ($oldDeductMasterStock && $oldConsItem && $oldConsItem->masterProduct) {
                $oldConsItem->masterProduct->increment('stock', $oldQty);
            }
            if ($newDeductMasterStock && $newConsItem->masterProduct) {
                $newConsItem->masterProduct->decrement('stock', $newQty);
            }

            // 3. Relasi Order & Order Item
            $oldOrderItem = $deduction->orderItem;
            $newOrderId = $request->order_id ?: null;
            $newOrderItemId = $request->order_item_id ?: null;

            if ($newOrderId && !$newOrderItemId) {
                $matchingItem = OrderItem::where('order_id', $newOrderId)
                    ->where('master_product_id', $newConsItem->master_product_id)
                    ->first();
                if ($matchingItem) {
                    $newOrderItemId = $matchingItem->id;
                }
            }

            if ($oldOrderItem && $oldOrderItem->id !== $newOrderItemId) {
                $oldOrderItem->update([
                    'supplier_consignment_item_id' => null,
                    'fulfillment_source'           => 'warehouse',
                ]);
            }
            if ($newOrderItemId) {
                $newOrderItem = OrderItem::find($newOrderItemId);
                if ($newOrderItem) {
                    $newOrderItem->update([
                        'supplier_consignment_item_id' => $newConsItem->id,
                        'fulfillment_source'           => 'consignment',
                    ]);
                }
            }

            $deduction->update([
                'supplier_consignment_item_id' => $newConsItem->id,
                'order_id'                     => $newOrderId,
                'order_item_id'                => $newOrderItemId,
                'quantity'                     => $newQty,
                'reason'                       => $request->reason,
                'reference_no'                 => $request->reference_no,
                'notes'                        => $request->notes,
                'deduction_date'               => $request->filled('deduction_date') ? $request->deduction_date : $deduction->deduction_date,
                'deduct_master_stock'          => $newDeductMasterStock,
            ]);
        });

        return redirect()->route('v2.titipan_barang.deductions.index')
            ->with('success', "Data pengeluaran stok titipan berhasil diperbarui.");
    }

    /**
     * Hapus / Batalkan Pengeluaran Stok Titipan V2.
     */
    public function destroyDeduction(SupplierConsignmentDeduction $deduction)
    {
        abort_unless($deduction->tenant_id === Auth::user()->tenant_id, 403);
        $tenantId = Auth::user()->tenant_id;

        $consItem = $deduction->consignmentItem;
        $qty = (int) $deduction->quantity;

        // Validasi jika sudah disetorkan ke supplier
        if ($consItem) {
            $qtySettled = $consItem->qty_settled;
            $qtySoldAfterRevert = max(0, (int) $consItem->qty_sold - $qty);
            if ($qtySoldAfterRevert < $qtySettled) {
                return back()->with('error', "Tidak dapat membatalkan pengeluaran ini karena {$qtySettled} pcs telah disetorkan dan disetujui dalam pembayaran ke supplier!");
            }
        }

        DB::transaction(function () use ($deduction, $consItem, $qty, $tenantId) {
            if ($consItem) {
                $consItem->decrement('qty_sold', $qty);

                if ($deduction->deduct_master_stock && $consItem->masterProduct) {
                    $consItem->masterProduct->increment('stock', $qty);
                }

                // Catat audit pengembalian stok titipan
                StockMovement::create([
                    'tenant_id'         => $tenantId,
                    'master_product_id' => $consItem->master_product_id,
                    'user_id'           => Auth::id(),
                    'type'              => 'in',
                    'quantity'          => $qty,
                    'balance_after'     => $consItem->masterProduct ? $consItem->masterProduct->stock : 0,
                    'reference'         => "Pembatalan Pengeluaran Titipan ({$consItem->consignment->reference_number}) #" . $deduction->id,
                ]);
            }

            if ($deduction->orderItem) {
                $deduction->orderItem->update([
                    'supplier_consignment_item_id' => null,
                    'fulfillment_source'           => 'warehouse',
                ]);
            }

            $deduction->delete();
        });

        return redirect()->route('v2.titipan_barang.deductions.index')
            ->with('success', "Pengeluaran stok titipan berhasil dibatalkan dan stok dikembalikan.");
    }

    /**
     * AJAX Search Orders untuk dropdown modal / form.
     */
    public function searchOrders(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = trim($request->input('q', ''));

        $query = Order::with(['store', 'items.masterProduct'])
            ->where('tenant_id', $tenantId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('order_marketplace_id', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $results = $orders->map(function ($order) {
            $storeName = $order->store ? $order->store->store_name : 'Toko';
            $itemSummary = $order->items->map(function ($it) {
                $pName = $it->masterProduct ? $it->masterProduct->name : ($it->product_name ?: 'Item');
                return "{$pName} ({$it->quantity} pcs)";
            })->implode(', ');

            return [
                'id'              => $order->id,
                'text'            => ($order->invoice_number ?: $order->order_marketplace_id) . " [{$storeName}] - " . ($order->buyer_name ?: 'Customer'),
                'invoice_number'  => $order->invoice_number ?: $order->order_marketplace_id,
                'tracking_number' => $order->tracking_number,
                'store_name'      => $storeName,
                'status'          => $order->order_status,
                'buyer_name'      => $order->buyer_name,
                'items_summary'   => $itemSummary,
                'items'           => $order->items->map(fn($it) => [
                    'id'                => $it->id,
                    'master_product_id' => $it->master_product_id,
                    'product_name'      => $it->masterProduct ? $it->masterProduct->name : $it->product_name,
                    'sku'               => $it->sku,
                    'quantity'          => $it->quantity,
                ]),
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * AJAX Get Available Consignment Items by Supplier or Product.
     */
    public function getConsignmentItems(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $supplierId = $request->input('supplier_id');
        $productId = $request->input('master_product_id');

        $query = SupplierConsignmentItem::with(['consignment.supplier', 'masterProduct'])
            ->whereHas('consignment', function ($q) use ($tenantId, $supplierId) {
                $q->where('tenant_id', $tenantId)
                  ->where('status', 'approved');
                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }
            });

        if ($productId) {
            $query->where('master_product_id', $productId);
        }

        $items = $query->get()->filter(fn($it) => $it->sisa_stok > 0)->values();

        $results = $items->map(function ($item) {
            $p = $item->masterProduct;
            $c = $item->consignment;
            return [
                'id'                => $item->id,
                'consignment_id'    => $c->id,
                'consignment_ref'   => $c->reference_number,
                'consignment_date'  => $c->consignment_date ? $c->consignment_date->format('d/m/Y') : '-',
                'supplier_id'       => $c->supplier_id,
                'supplier_name'     => $c->supplier ? $c->supplier->name : '-',
                'master_product_id' => $item->master_product_id,
                'product_name'      => $p ? $p->name : 'Produk',
                'sku'               => $p ? $p->sku : '-',
                'unit'              => $p ? ($p->unit ?: 'PCS') : 'PCS',
                'sisa_stok'         => $item->sisa_stok,
                'qty_received'      => $item->qty_received,
                'qty_sold'          => (int) ($item->qty_sold ?? 0),
                'unit_cost_price'   => (float) $item->unit_cost_price,
                'label'             => "[{$c->reference_number}] " . ($p ? $p->name : '-') . " (Sisa: {$item->sisa_stok} pcs)",
            ];
        });

        return response()->json(['items' => $results]);
    }
}

