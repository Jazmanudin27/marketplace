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

        return view('inventory.supplier_consignments.index', compact('consignments', 'suppliers'));
    }

    /**
     * Form Penerimaan Barang Konsinyasi Baru.
     */
    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $refNumber = SupplierConsignment::generateReferenceNumber();

        return view('inventory.supplier_consignments.create', compact('suppliers', 'refNumber'));
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

        $consignment = DB::transaction(function () use ($request, $tenantId) {
            $refNumber        = SupplierConsignment::generateReferenceNumber();
            $totalQtyReceived = 0;
            $totalAmountHpp   = 0;

            $consignment = SupplierConsignment::create([
                'tenant_id'          => $tenantId,
                'supplier_id'        => $request->supplier_id,
                'reference_number'   => $refNumber,
                'consignment_date'   => $request->consignment_date,
                'status'             => 'pending',
                'notes'              => $request->notes,
                'created_by'         => Auth::id(),
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
            }

            $consignment->update([
                'total_qty_received' => $totalQtyReceived,
                'total_amount_hpp'   => $totalAmountHpp,
            ]);

            return $consignment;
        });

        return redirect()->route('supplier_consignments.show', $consignment)
            ->with('success', 'Penerimaan barang konsinyasi berhasil dibuat (Status: Pending). Silakan periksa dan setujui untuk menambah stok.');
    }

    /**
     * Detail Penerimaan Barang Konsinyasi.
     */
    public function show(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        $consignment->load(['supplier', 'items.masterProduct', 'creator', 'approver']);

        return view('inventory.supplier_consignments.show', compact('consignment'));
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
            ->with('success', 'Penerimaan barang konsinyasi berhasil disetujui! Stok master produk telah bertambah secara otomatis.');
    }

    /**
     * Batal/Hapus Penerimaan Barang Konsinyasi.
     */
    public function destroy(SupplierConsignment $consignment)
    {
        abort_unless($consignment->tenant_id === Auth::user()->tenant_id, 403);

        if ($consignment->status === 'approved') {
            return back()->with('error', 'Penerimaan barang konsinyasi yang sudah disetujui tidak dapat dihapus langsung.');
        }

        $consignment->delete();

        return redirect()->route('supplier_consignments.index')
            ->with('success', 'Transaksi penerimaan barang konsinyasi berhasil dihapus.');
    }

    /**
     * Kartu Stok, Mutasi & Rekapitulasi Persediaan Barang Konsinyasi per Supplier.
     */
    public function stockCard(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        $selectedSupplierId = $request->supplier_id ?: ($suppliers->first() ? $suppliers->first()->id : null);

        $reportData = [];
        $totalReceivedAll  = 0;
        $totalSoldAll      = 0;
        $totalRemainingAll = 0;
        $totalSettledAll   = 0;
        $totalUnsettledAll = 0;
        $totalPaidAmountAll= 0;
        $totalProfitAll    = 0;

        if ($selectedSupplierId) {
            // Ambil semua item dari penerimaan konsinyasi yang approved untuk supplier ini
            $items = SupplierConsignmentItem::whereHas('consignment', function ($q) use ($tenantId, $selectedSupplierId) {
                $q->where('tenant_id', $tenantId)
                  ->where('supplier_id', $selectedSupplierId)
                  ->where('status', 'approved');
            })->with(['masterProduct', 'consignment'])->get();

            // Kelompokkan per MasterProduct
            $grouped = $items->groupBy('master_product_id');

            foreach ($grouped as $productId => $groupItems) {
                $product = MasterProduct::find($productId);
                if (!$product) continue;

                $qtyReceivedTotal = $groupItems->sum('qty_received');
                $unitCost         = $groupItems->avg('unit_cost_price') ?: $product->cost_price;
                $unitSelling      = $groupItems->avg('unit_selling_price') ?: $product->price;

                // Hitung total setoran yang sudah dilakukan untuk produk ini pada supplier ini
                $qtySettledTotal  = (int) SupplierConsignmentSettlementItem::whereHas('settlement', function ($q) use ($tenantId, $selectedSupplierId) {
                    $q->where('tenant_id', $tenantId)
                      ->where('supplier_id', $selectedSupplierId)
                      ->where('status', 'approved');
                })->where('master_product_id', $productId)->sum('qty_settled');

                // Hitung estimasi barang terjual berdasarkan pengurangan stok produk master sejak penerimaan disetujui,
                // atau dengan memperhitungkan sisa stok saat ini.
                $currentStock = $product->stock;
                // Total Terjual (Estimasi dari penerimaan konsinyasi - sisa stok terkini yang tersedia, dibatasi minimal 0)
                $qtySoldTotal = max(0, $qtyReceivedTotal - $currentStock);
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
                    'product_id'          => $product->id,
                    'sku'                 => $product->sku,
                    'name'                => $product->name,
                    'unit'                => $product->unit ?: 'PCS',
                    'unit_cost'           => $unitCost,
                    'unit_selling'        => $unitSelling,
                    'qty_received'        => $qtyReceivedTotal,
                    'qty_sold'            => $qtySoldTotal,
                    'qty_remaining'       => $qtyRemainingTotal,
                    'qty_settled'         => $qtySettledTotal,
                    'qty_unsettled'       => $qtyUnsettledTotal,
                    'nominal_paid'        => $nominalPaid,
                    'nominal_unsettled'   => $nominalUnsettled,
                    'profit_total'        => $profitTotal,
                ];
            }
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

        return view('inventory.supplier_consignments.stock_card', compact(
            'suppliers',
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
        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        $selectedSupplierId = $request->supplier_id ?: ($suppliers->first() ? $suppliers->first()->id : null);
        $availableItems = [];

        if ($selectedSupplierId) {
            $items = SupplierConsignmentItem::whereHas('consignment', function ($q) use ($tenantId, $selectedSupplierId) {
                $q->where('tenant_id', $tenantId)
                  ->where('supplier_id', $selectedSupplierId)
                  ->where('status', 'approved');
            })->with(['masterProduct', 'consignment'])->get();

            foreach ($items as $item) {
                $product = $item->masterProduct;
                if (!$product) continue;

                $qtyReceived = $item->qty_received;
                $currentStock = $product->stock;
                $qtySold = max(0, $qtyReceived - $currentStock);

                $qtySettled = (int) SupplierConsignmentSettlementItem::where('supplier_consignment_item_id', $item->id)
                    ->whereHas('settlement', function ($q) {
                        $q->where('status', 'approved');
                    })
                    ->sum('qty_settled');

                $qtyUnsettled = max(0, $qtySold - $qtySettled);

                if ($qtyUnsettled > 0 || $request->has('show_all')) {
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

        return view('inventory.supplier_consignments.settlement_create', compact(
            'suppliers',
            'bankAccounts',
            'selectedSupplierId',
            'availableItems',
            'settlementNumber'
        ));
    }

    /**
     * Simpan Setoran ke Supplier.
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
        ], [
            'supplier_id.required' => 'Supplier penerima setoran wajib dipilih.',
            'items.required'       => 'Pilih minimal satu barang yang akan disetorkan.',
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

            // Opsional: Catat Pengeluaran Keuangan (Expense)
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

        return redirect()->route('supplier_consignments.stock_card', ['supplier_id' => $request->supplier_id])
            ->with('success', 'Setoran hasil penjualan ke supplier berhasil disimpan dan dicatat dalam laporan keuangan.');
    }
}
