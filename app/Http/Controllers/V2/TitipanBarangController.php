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
use Illuminate\Validation\Rule;

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
     * Form Input Setoran Hasil Penjualan V2 (Berbasis Nominal Per Invoice / Inputan Titipan).
     */
    public function createSettlement(Request $request)
    {
        $tenantId     = Auth::user()->tenant_id;
        $suppliers    = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        $selectedSupplierId = $request->supplier_id ?: ($suppliers->first() ? $suppliers->first()->id : null);
        $availableInvoices  = [];

        if ($selectedSupplierId) {
            $consignments = SupplierConsignment::with(['items.masterProduct'])
                ->where('tenant_id', $tenantId)
                ->where('supplier_id', $selectedSupplierId)
                ->where('status', 'approved')
                ->orderByDesc('consignment_date')
                ->orderByDesc('id')
                ->get();

            foreach ($consignments as $consignment) {
                $totalQtyReceived = (int) $consignment->items->sum('qty_received');
                $totalAmountHpp   = (float) $consignment->items->sum(function ($it) {
                    return $it->qty_received * $it->unit_cost_price;
                });

                $itemIds = $consignment->items->pluck('id');
                $totalSettledAmount = (float) SupplierConsignmentSettlementItem::whereIn('supplier_consignment_item_id', $itemIds)
                    ->whereHas('settlement', function ($q) {
                        $q->where('status', 'approved');
                    })
                    ->sum('subtotal');

                $totalSettledQty = (int) SupplierConsignmentSettlementItem::whereIn('supplier_consignment_item_id', $itemIds)
                    ->whereHas('settlement', function ($q) {
                        $q->where('status', 'approved');
                    })
                    ->sum('qty_settled');

                $remainingAmount = max(0, $totalAmountHpp - $totalSettledAmount);
                $remainingQty    = max(0, $totalQtyReceived - $totalSettledQty);

                $availableInvoices[] = [
                    'consignment_id'     => $consignment->id,
                    'reference_number'   => $consignment->reference_number,
                    'consignment_date'   => $consignment->consignment_date ? $consignment->consignment_date->format('d/m/Y') : '-',
                    'raw_date'           => $consignment->consignment_date ? $consignment->consignment_date->format('Y-m-d') : '',
                    'notes'              => $consignment->notes ?: '-',
                    'total_qty'          => $totalQtyReceived,
                    'total_amount'       => $totalAmountHpp,
                    'settled_amount'     => $totalSettledAmount,
                    'remaining_amount'   => $remainingAmount,
                    'settled_qty'        => $totalSettledQty,
                    'remaining_qty'      => $remainingQty,
                    'is_paid_off'        => $remainingAmount <= 0,
                    'items_count'        => $consignment->items->count(),
                    'items'              => $consignment->items->map(function ($it) {
                        return [
                            'name'            => $it->masterProduct ? $it->masterProduct->name : 'Item',
                            'sku'             => $it->masterProduct ? $it->masterProduct->sku : '-',
                            'qty'             => $it->qty_received,
                            'unit_cost_price' => $it->unit_cost_price,
                            'subtotal'        => $it->qty_received * $it->unit_cost_price,
                        ];
                    }),
                ];
            }
        }

        $settlementNumber = SupplierConsignmentSettlement::generateSettlementNumber();

        return view('v2.titipan_barang.settlement_create', compact(
            'suppliers',
            'bankAccounts',
            'selectedSupplierId',
            'availableInvoices',
            'settlementNumber'
        ));
    }

    /**
     * Simpan Setoran ke Supplier V2 (Nominal Per Invoice).
     */
    public function storeSettlement(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id'     => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'settlement_date' => 'required|date',
            'payment_method'  => 'required|in:cash,transfer',
            'bank_account_id' => [
                'required_if:payment_method,transfer',
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId)),
            ],
            'reference_number'=> 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:1000',
            'invoices'        => 'required|array|min:1',
        ], [
            'supplier_id.required'        => 'Supplier penerima setoran wajib dipilih.',
            'supplier_id.exists'          => 'Supplier yang dipilih tidak valid atau bukan milik toko Anda.',
            'settlement_date.required'    => 'Tanggal setoran wajib diisi.',
            'payment_method.required'     => 'Metode pembayaran wajib dipilih.',
            'bank_account_id.required_if' => 'Sumber Rekening Bank wajib dipilih jika metode pembayaran adalah Transfer Bank.',
            'bank_account_id.exists'      => 'Rekening bank yang dipilih tidak valid atau bukan milik toko Anda.',
            'invoices.required'           => 'Pilih minimal satu nota atau dokumen titipan untuk disetorkan.',
        ]);

        try {
            $settlement = DB::transaction(function () use ($request, $tenantId) {
                $settlementNumber = SupplierConsignmentSettlement::generateSettlementNumber();
                $totalQtySettled  = 0;
                $totalAmountPaid  = 0;
                $invoicesNotes    = [];

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

                foreach ($request->invoices as $consignmentId => $invData) {
                    $rawAmount = (string)($invData['amount'] ?? '0');
                    $cleanAmount = trim(str_ireplace(['rp', ' '], '', $rawAmount));
                    if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $cleanAmount)) {
                        $cleanAmount = str_replace('.', '', $cleanAmount);
                        $cleanAmount = str_replace(',', '.', $cleanAmount);
                    } elseif (str_contains($cleanAmount, ',')) {
                        $cleanAmount = str_replace(',', '.', $cleanAmount);
                    }
                    $payAmount = (float) $cleanAmount;

                    if ($payAmount <= 0) {
                        continue;
                    }

                    $consignment = SupplierConsignment::with('items')
                        ->where('tenant_id', $tenantId)
                        ->where('supplier_id', $request->supplier_id)
                        ->where('status', 'approved')
                        ->lockForUpdate()
                        ->find($consignmentId);

                    if (!$consignment) {
                        continue;
                    }

                    $remainingPay = $payAmount;
                    $invoicesNotes[] = $consignment->reference_number . ' (Rp ' . number_format($payAmount, 0, ',', '.') . ')';

                    // Alokasikan nominal pembayaran ke item-item consignment yang belum terbayar penuh
                    foreach ($consignment->items as $cItem) {
                        if ($remainingPay <= 0) {
                            break;
                        }

                        $itemTotalCost = (float) ($cItem->qty_received * $cItem->unit_cost_price);
                        $itemPrevSettled = (float) SupplierConsignmentSettlementItem::where('supplier_consignment_item_id', $cItem->id)
                            ->whereHas('settlement', fn($q) => $q->where('status', 'approved'))
                            ->lockForUpdate()
                            ->sum('subtotal');
                        $itemPrevQty = (int) SupplierConsignmentSettlementItem::where('supplier_consignment_item_id', $cItem->id)
                            ->whereHas('settlement', fn($q) => $q->where('status', 'approved'))
                            ->lockForUpdate()
                            ->sum('qty_settled');

                        $itemRemaining = max(0, $itemTotalCost - $itemPrevSettled);
                        if ($itemRemaining <= 0) {
                            continue;
                        }

                        $allocate = min($remainingPay, $itemRemaining);
                        $remainingPay -= $allocate;

                        $costPrice = (float) $cItem->unit_cost_price;
                        $qtyRemaining = max(0, (int)$cItem->qty_received - $itemPrevQty);

                        if ($allocate >= $itemRemaining) {
                            $allocatedQty = $qtyRemaining;
                        } else {
                            $allocatedQty = $costPrice > 0 ? (int) round($allocate / $costPrice) : 0;
                            if ($allocatedQty == 0 && $allocate > 0 && $qtyRemaining > 0) {
                                $allocatedQty = 1;
                            }
                            $allocatedQty = min($allocatedQty, $qtyRemaining);
                        }
                        $totalQtySettled += $allocatedQty;

                        $settlement->items()->create([
                            'supplier_consignment_item_id' => $cItem->id,
                            'master_product_id'           => $cItem->master_product_id,
                            'qty_settled'                 => $allocatedQty,
                            'unit_cost_price'             => $costPrice,
                            'subtotal'                    => $allocate,
                        ]);
                    }

                    // Cegah overpayment jika nominal melampaui total sisa tagihan pada nota ini
                    if ($remainingPay > 0.01) {
                        throw new \Exception("Nominal setoran untuk nota {$consignment->reference_number} melebihi sisa tagihan sebesar Rp " . number_format($remainingPay, 0, ',', '.') . ".");
                    }

                    $totalAmountPaid += $payAmount;
                }

                if ($totalAmountPaid <= 0) {
                    throw new \Exception('Nominal pembayaran setoran harus lebih dari 0. Silakan isi nominal pada minimal 1 invoice titipan.');
                }

                $settlement->update([
                    'total_qty_settled' => $totalQtySettled,
                    'total_amount_paid' => $totalAmountPaid,
                ]);

                $supplier = Supplier::where('tenant_id', $tenantId)->find($request->supplier_id);
                $supplierName = $supplier ? $supplier->name : 'Supplier';

                // Tentukan payment_source kas/bank agar tersinkron ke Laporan Mutasi Kas & Keuangan
                $paymentSource = 'kas_besar';
                if ($request->payment_method === 'transfer' && $request->bank_account_id) {
                    $bank = BankAccount::where('tenant_id', $tenantId)->find($request->bank_account_id);
                    $paymentSource = $bank ? ($bank->bank_name ?: (string)$bank->id) : (string)$request->bank_account_id;
                }

                $notesDesc = !empty($invoicesNotes) ? ' Invoice: ' . implode(', ', $invoicesNotes) : '';
                Expense::create([
                    'tenant_id'      => $tenantId,
                    'title'          => "Setoran Titipan Konsinyasi: {$supplierName} ({$settlementNumber})",
                    'category'       => 'Setoran Barang Konsinyasi',
                    'payment_source' => $paymentSource,
                    'amount'         => $totalAmountPaid,
                    'expense_date'   => $request->settlement_date,
                    'description'    => "Setoran Titipan Supplier {$supplierName} ({$settlementNumber}) — Total Rp " . number_format($totalAmountPaid, 0, ',', '.') . ".{$notesDesc}" . ($request->notes ? " Catatan: {$request->notes}" : ''),
                ]);

                return $settlement;
            });

            return redirect()->route('v2.titipan_barang.settlement.index', ['supplier_id' => $request->supplier_id])
                ->with('success', 'Setoran nominal per invoice ke supplier berhasil disimpan sebesar Rp ' . number_format($settlement->total_amount_paid, 0, ',', '.') . '.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
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
            ->with('success', 'Riwayat setoran supplier berhasil dihapus dan mutasi kas dibatalkan.');
    }

    /**
     * Pastikan kolom tambahan untuk manual CRUD pengeluaran titipan tersedia di database.
     */
    protected function ensureDeductionsColumnsExist(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('supplier_consignment_deductions', 'deduction_date')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}

            if (!\Illuminate\Support\Facades\Schema::hasColumn('supplier_consignment_deductions', 'deduction_date')) {
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` MODIFY `order_id` BIGINT UNSIGNED NULL");
                } catch (\Throwable $e) {}
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `reason` VARCHAR(50) DEFAULT 'unscanned_order'");
                } catch (\Throwable $e) {}
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `reference_no` VARCHAR(100) NULL");
                } catch (\Throwable $e) {}
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `notes` TEXT NULL");
                } catch (\Throwable $e) {}
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `deduction_date` DATETIME NULL");
                } catch (\Throwable $e) {}
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `deduct_master_stock` TINYINT(1) DEFAULT 0");
                } catch (\Throwable $e) {}
            }
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('supplier_consignment_deductions', 'actual_stock')) {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `system_stock` INT NULL");
            } catch (\Throwable $e) {}
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `actual_stock` INT NULL");
            } catch (\Throwable $e) {}
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` ADD COLUMN `difference_qty` INT NULL");
            } catch (\Throwable $e) {}
        }
    }

    /**
     * ─── CRUD PENGELUARAN STOK TITIPAN (CONSIGNMENT DEDUCTION) ───────────
     */

    /**
     * Daftar Riwayat Pengeluaran / Potongan Stok Titipan Barang.
     */
    public function indexDeductions(Request $request)
    {
        $this->ensureDeductionsColumnsExist();
        $tenantId = Auth::user()->tenant_id;
        $hasDeductionDate = \Illuminate\Support\Facades\Schema::hasColumn('supplier_consignment_deductions', 'deduction_date');
        $hasReason = \Illuminate\Support\Facades\Schema::hasColumn('supplier_consignment_deductions', 'reason');
        $hasReferenceNo = \Illuminate\Support\Facades\Schema::hasColumn('supplier_consignment_deductions', 'reference_no');
        $hasNotes = \Illuminate\Support\Facades\Schema::hasColumn('supplier_consignment_deductions', 'notes');

        $query = SupplierConsignmentDeduction::with([
            'consignmentItem.consignment.supplier',
            'consignmentItem.masterProduct',
            'order.store',
            'orderItem',
            'user'
        ])->where('tenant_id', $tenantId);

        if ($hasDeductionDate) {
            $query->orderByDesc('deduction_date')->orderByDesc('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

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
        if ($request->filled('reason') && $hasReason) {
            $query->where('reason', $request->reason);
        }

        // Filter Search (Invoice / Resi / Reference / Notes / Produk / SKU)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search, $hasReferenceNo, $hasNotes) {
                if ($hasReferenceNo) {
                    $q->where('reference_no', 'like', "%{$search}%");
                }
                if ($hasNotes) {
                    $q->orWhere('notes', 'like', "%{$search}%");
                }
                $q->orWhere('scanned_barcode', 'like', "%{$search}%")
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
            $query->where(function ($q) use ($request, $hasDeductionDate) {
                if ($hasDeductionDate) {
                    $q->whereDate('deduction_date', '>=', $request->date_from)
                      ->orWhere(function ($sub) use ($request) {
                          $sub->whereNull('deduction_date')->whereDate('created_at', '>=', $request->date_from);
                      });
                } else {
                    $q->whereDate('created_at', '>=', $request->date_from);
                }
            });
        }
        if ($request->filled('date_to')) {
            $query->where(function ($q) use ($request, $hasDeductionDate) {
                if ($hasDeductionDate) {
                    $q->whereDate('deduction_date', '<=', $request->date_to)
                      ->orWhere(function ($sub) use ($request) {
                          $sub->whereNull('deduction_date')->whereDate('created_at', '<=', $request->date_to);
                      });
                } else {
                    $q->whereDate('created_at', '<=', $request->date_to);
                }
            });
        }

        // Filter Sumber Stok (Hanya Titipan Supplier vs Potong Gudang Master Juga)
        if ($request->filled('stock_source') && $hasDeductMasterStock) {
            if ($request->stock_source === 'gudang') {
                $query->where('deduct_master_stock', 1);
            } elseif ($request->stock_source === 'supplier') {
                $query->where(function ($q) {
                    $q->where('deduct_master_stock', 0)->orWhereNull('deduct_master_stock');
                });
            }
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

        // Metrik Opname Spesifik (Fisik, Selisih Kurang, Selisih Lebih)
        $totalActualExamined = (int) $allFiltered->sum(fn($d) => $d->actual_stock !== null ? $d->actual_stock : $d->quantity);
        $totalDiffMinus      = (int) $allFiltered->where('difference_qty', '<', 0)->sum(fn($d) => abs($d->difference_qty));
        $totalDiffPlus       = (int) $allFiltered->where('difference_qty', '>', 0)->sum('difference_qty');
        $totalMatchCount     = (int) $allFiltered->where('difference_qty', 0)->count();

        // Rekap Sumber Stok: Berapa Qty hanya Titipan Supplier vs Ikut Potong Gudang Master
        $qtyPotongGudang = (int) $allFiltered->where('deduct_master_stock', 1)->sum('quantity');
        $qtyHanyaTitipan = (int) $allFiltered->where('deduct_master_stock', '!=', 1)->sum('quantity');

        // Rekap per Supplier (Nama Supplier => Total Qty)
        $rekapPerSupplier = $allFiltered->groupBy(function ($d) {
            $c = $d->consignmentItem ? $d->consignmentItem->consignment : null;
            $s = $c ? $c->supplier : null;
            return $s ? $s->name : 'Supplier Tidak Diketahui';
        })->map(fn($group) => [
            'count' => $group->count(),
            'qty'   => (int) $group->sum('quantity'),
            'hpp'   => (float) $group->sum(fn($d) => $d->quantity * ($d->consignmentItem ? (float) $d->consignmentItem->unit_cost_price : 0)),
        ])->sortByDesc('qty');

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
            'totalManualCorrections',
            'totalActualExamined',
            'totalDiffMinus',
            'totalDiffPlus',
            'totalMatchCount',
            'qtyPotongGudang',
            'qtyHanyaTitipan',
            'rekapPerSupplier'
        ));
    }

    /**
     * Form Scan Stok Opname Titipan Barang V2.
     */
    public function createDeduction(Request $request)
    {
        $this->ensureDeductionsColumnsExist();
        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        $preselectedItemId = $request->input('consignment_item_id');
        $preselectedItem   = $preselectedItemId ? SupplierConsignmentItem::with(['consignment.supplier', 'masterProduct'])->find($preselectedItemId) : null;
        $preselectedOrderId = $request->input('order_id');
        $preselectedOrder   = $preselectedOrderId ? Order::with(['store', 'items.masterProduct'])->where('tenant_id', $tenantId)->find($preselectedOrderId) : null;

        // Ambil semua item titipan konsinyasi aktif yang disetujui (memungkinkan opname walau sisa_stok di ERP 0)
        $availableItemsRaw = SupplierConsignmentItem::with(['consignment.supplier', 'masterProduct'])
            ->whereHas('consignment', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('status', 'approved');
            })
            ->get()
            ->filter(fn($it) => $it->qty_received > 0)
            ->values();

        $availableItems = $availableItemsRaw->map(function ($it) {
            $p = $it->masterProduct;
            $c = $it->consignment;
            $s = $c ? $c->supplier : null;
            return [
                'id'            => $it->id,
                'supplier_id'   => $c ? (string) $c->supplier_id : '',
                'supplier_name' => $s ? $s->name : '-',
                'batch_ref'     => $c ? $c->reference_number : '-',
                'product_id'    => $it->master_product_id,
                'product_name'  => $p ? $p->name : 'Produk',
                'sku'           => $p ? $p->sku : '-',
                'barcode'       => $p ? ($p->barcode ?: '') : '',
                'sisa_stok'     => (int) $it->sisa_stok, // Stok fisik di ERP saat ini
                'qty_received'  => (int) $it->qty_received,
                'qty_sold'      => (int) ($it->qty_sold ?? 0),
                'hpp'           => (float) $it->unit_cost_price,
            ];
        })->values();

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
     * Simpan Stok Opname Titipan Barang / Penyesuaian Stok V2.
     */
    public function storeDeduction(Request $request)
    {
        $this->ensureDeductionsColumnsExist();
        $tenantId = Auth::user()->tenant_id;

        // 1. Dukung batch items dari keranjang scan stok opname
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            $request->validate([
                'items'                                 => 'required|array|min:1',
                'items.*.supplier_consignment_item_id' => 'required|exists:supplier_consignment_items,id',
                'items.*.actual_stock'                 => 'nullable|integer|min:0',
                'items.*.quantity'                     => 'nullable|integer|min:0',
                'reason'                               => 'required|string',
                'opname_date'                          => 'nullable|date',
                'deduction_date'                       => 'nullable|date',
                'pic'                                  => 'nullable|string|max:150',
                'notes'                                => 'nullable|string|max:1000',
                'deduct_master_stock'                  => 'nullable|boolean',
            ]);

            $deductMasterStock = $request->boolean('deduct_master_stock');
            $opnameDate = $request->filled('opname_date') 
                ? $request->opname_date 
                : ($request->filled('deduction_date') ? $request->deduction_date : now());
            $pic = trim($request->pic ?: Auth::user()->name);
            $totalScannedQty = 0;
            $itemsCount = count($request->items);

            try {
                DB::transaction(function () use ($request, $tenantId, $deductMasterStock, $opnameDate, $pic, &$totalScannedQty) {
                    foreach ($request->items as $row) {
                        $consItemId = $row['supplier_consignment_item_id'];
                        $consItem = SupplierConsignmentItem::with(['consignment', 'masterProduct'])
                            ->where('id', $consItemId)
                            ->whereHas('consignment', fn($q) => $q->where('tenant_id', $tenantId))
                            ->firstOrFail();

                        $systemStock = (int) $consItem->sisa_stok;

                        // Tentukan stok fisik terhitung
                        if (isset($row['actual_stock']) && $row['actual_stock'] !== null && $row['actual_stock'] !== '') {
                            $actualStock = (int) $row['actual_stock'];
                            $diff = $actualStock - $systemStock;
                        } else {
                            // Fallback jika dikirim quantity pengeluaran biasa
                            $qtyKeluar = (int) ($row['quantity'] ?? 0);
                            $diff = -$qtyKeluar;
                            $actualStock = max(0, $systemStock - $qtyKeluar);
                        }

                        $refBatch = $consItem->consignment ? $consItem->consignment->reference_number : '-';
                        $prodName = $consItem->masterProduct ? $consItem->masterProduct->name : 'Produk';
                        $reason = $request->reason ?: 'manual_adjustment';
                        $userNotes = trim($request->notes ?? '');

                        if ($diff < 0) {
                            // Stok fisik kurang dari ERP (misal ERP 11, Fisik 10 => Selisih -1 pcs)
                            $shortage = abs($diff);
                            $consItem->increment('qty_sold', $shortage);

                            if ($deductMasterStock && $consItem->masterProduct) {
                                $consItem->masterProduct->decrement('stock', $shortage);
                            }

                            $deduction = SupplierConsignmentDeduction::create([
                                'tenant_id'                    => $tenantId,
                                'supplier_consignment_item_id' => $consItem->id,
                                'order_id'                     => null,
                                'order_item_id'                => null,
                                'quantity'                     => $shortage,
                                'system_stock'                 => $systemStock,
                                'actual_stock'                 => $actualStock,
                                'difference_qty'               => $diff,
                                'reason'                       => $reason,
                                'reference_no'                 => $pic ? "PIC: {$pic}" : null,
                                'notes'                        => "Opname: ERP {$systemStock} -> Fisik {$actualStock} (Selisih {$diff} pcs). " . $userNotes,
                                'deduction_date'               => $opnameDate,
                                'deduct_master_stock'          => $deductMasterStock,
                                'user_id'                      => Auth::id(),
                                'scanned_barcode'              => 'OPNAME-SCAN',
                            ]);

                            StockMovement::create([
                                'tenant_id'         => $tenantId,
                                'master_product_id' => $consItem->master_product_id,
                                'user_id'           => Auth::id(),
                                'type'              => 'adj',
                                'quantity'          => -$shortage,
                                'balance_after'     => $consItem->masterProduct ? $consItem->masterProduct->stock : 0,
                                'reference'         => "Stok Opname Titipan ({$refBatch}) - Selisih -{$shortage} pcs [Fisik: {$actualStock}, ERP: {$systemStock}]",
                            ]);
                        } elseif ($diff > 0) {
                            // Stok fisik lebih dari ERP (Surplus: misal ERP 10, Fisik 12 => Selisih +2 pcs)
                            $surplus = $diff;
                            $currQtySold = (int) ($consItem->qty_sold ?? 0);
                            if ($currQtySold >= $surplus) {
                                $consItem->decrement('qty_sold', $surplus);
                            } else {
                                $excess = $surplus - $currQtySold;
                                $consItem->update(['qty_sold' => 0]);
                                $consItem->increment('qty_received', $excess);
                            }

                            if ($deductMasterStock && $consItem->masterProduct) {
                                $consItem->masterProduct->increment('stock', $surplus);
                            }

                            $deduction = SupplierConsignmentDeduction::create([
                                'tenant_id'                    => $tenantId,
                                'supplier_consignment_item_id' => $consItem->id,
                                'order_id'                     => null,
                                'order_item_id'                => null,
                                'quantity'                     => $surplus,
                                'system_stock'                 => $systemStock,
                                'actual_stock'                 => $actualStock,
                                'difference_qty'               => $diff,
                                'reason'                       => $reason,
                                'reference_no'                 => $pic ? "PIC: {$pic}" : null,
                                'notes'                        => "Opname Surplus: ERP {$systemStock} -> Fisik {$actualStock} (Selisih +{$diff} pcs). " . $userNotes,
                                'deduction_date'               => $opnameDate,
                                'deduct_master_stock'          => $deductMasterStock,
                                'user_id'                      => Auth::id(),
                                'scanned_barcode'              => 'OPNAME-SCAN',
                            ]);

                            StockMovement::create([
                                'tenant_id'         => $tenantId,
                                'master_product_id' => $consItem->master_product_id,
                                'user_id'           => Auth::id(),
                                'type'              => 'adj',
                                'quantity'          => $surplus,
                                'balance_after'     => $consItem->masterProduct ? $consItem->masterProduct->stock : 0,
                                'reference'         => "Stok Opname Titipan Surplus ({$refBatch}) - Surplus +{$surplus} pcs [Fisik: {$actualStock}, ERP: {$systemStock}]",
                            ]);
                        } else {
                            // Fisik cocok persis dengan ERP (Selisih 0)
                            SupplierConsignmentDeduction::create([
                                'tenant_id'                    => $tenantId,
                                'supplier_consignment_item_id' => $consItem->id,
                                'order_id'                     => null,
                                'order_item_id'                => null,
                                'quantity'                     => 0,
                                'system_stock'                 => $systemStock,
                                'actual_stock'                 => $actualStock,
                                'difference_qty'               => 0,
                                'reason'                       => $reason,
                                'reference_no'                 => $pic ? "PIC: {$pic}" : null,
                                'notes'                        => "Opname Sesuai: Fisik sama dengan ERP ({$systemStock} pcs). " . $userNotes,
                                'deduction_date'               => $opnameDate,
                                'deduct_master_stock'          => $deductMasterStock,
                                'user_id'                      => Auth::id(),
                                'scanned_barcode'              => 'OPNAME-SCAN',
                            ]);
                        }

                        $totalScannedQty += $actualStock;
                    }
                });

                return redirect()->route('v2.titipan_barang.deductions.index')
                    ->with('success', "✅ Stok Opname Titipan berhasil disimpan untuk {$itemsCount} item produk (Total Fisik Terhitung: {$totalScannedQty} PCS).");
            } catch (\Throwable $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        // 2. Fallback single-item
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
        $this->ensureDeductionsColumnsExist();
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
        $this->ensureDeductionsColumnsExist();
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
                $diff = $deduction->difference_qty;
                if ($diff === null || $diff < 0) {
                    // Revert pengurangan / kekurangan stok (kembalikan stok)
                    $consItem->decrement('qty_sold', $qty);

                    if ($deduction->deduct_master_stock && $consItem->masterProduct) {
                        $consItem->masterProduct->increment('stock', $qty);
                    }

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $consItem->master_product_id,
                        'user_id'           => Auth::id(),
                        'type'              => 'in',
                        'quantity'          => $qty,
                        'balance_after'     => $consItem->masterProduct ? $consItem->masterProduct->stock : 0,
                        'reference'         => "Pembatalan Opname Titipan ({$consItem->consignment->reference_number}) #" . $deduction->id,
                    ]);
                } elseif ($diff > 0) {
                    // Revert surplus (kurangi kembali surplus)
                    $consItem->increment('qty_sold', $qty);

                    if ($deduction->deduct_master_stock && $consItem->masterProduct) {
                        $consItem->masterProduct->decrement('stock', $qty);
                    }

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $consItem->master_product_id,
                        'user_id'           => Auth::id(),
                        'type'              => 'out',
                        'quantity'          => $qty,
                        'balance_after'     => $consItem->masterProduct ? $consItem->masterProduct->stock : 0,
                        'reference'         => "Pembatalan Surplus Opname Titipan ({$consItem->consignment->reference_number}) #" . $deduction->id,
                    ]);
                }
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
            ->with('success', "Data stok opname titipan berhasil dibatalkan dan stok telah disesuaikan kembali.");
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

