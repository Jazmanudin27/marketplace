<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BarangMasukController extends Controller
{
    private function checkAccess($permission = 'goods-receipts.index')
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->role !== 'admin' && !$user->can($permission)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola Penerimaan Barang Masuk.');
        }
    }

    /**
     * Display V2 Barang Masuk (Goods Receipts) Index page.
     */
    public function index(Request $request)
    {
        $this->checkAccess('goods-receipts.index');
        $tenantId = Auth::user()->tenant_id;

        $query = GoodsReceipt::with(['supplier', 'department', 'purchaseOrder', 'items.inventoryItem', 'items.masterProduct', 'createdBy', 'approvedBy', 'payable'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('receipt_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', '%' . $search . '%')
                  ->orWhere('notes', 'like', '%' . $search . '%')
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('source') && $request->source !== 'all') {
            $query->where('source', $request->source);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id') && $request->supplier_id !== 'all') {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }

        // Summary Statistics (calculated from active filtered base query)
        $statsQuery = GoodsReceipt::where('tenant_id', $tenantId);
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('receipt_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('receipt_date', '<=', $request->date_to);
        }

        $totalTransactions = (clone $statsQuery)->count();
        $totalApproved     = (clone $statsQuery)->where('status', 'approved')->count();
        $totalPending      = (clone $statsQuery)->where('status', 'pending')->count();
        $totalValue        = (clone $statsQuery)->sum('total_amount');

        $receipts  = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('v2.barang_masuk.index', compact(
            'receipts',
            'suppliers',
            'totalTransactions',
            'totalApproved',
            'totalPending',
            'totalValue'
        ));
    }

    /**
     * Show form to create new Barang Masuk.
     */
    public function create()
    {
        $this->checkAccess('goods-receipts.index');
        $tenantId = Auth::user()->tenant_id;

        $suppliers      = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments    = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('v2.barang_masuk.create', compact('suppliers', 'departments', 'inventoryItems'));
    }

    /**
     * Store Goods Receipt.
     */
    public function store(Request $request)
    {
        $this->checkAccess('goods-receipts.index');
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id'   => 'required_if:source,pembelian|nullable|exists:suppliers,id',
            'department_id' => 'nullable|exists:departments,id',
            'receipt_date'  => 'required|date',
            'source'        => 'required|in:pembelian,percetakan,produksi,lain_lain',
            'notes'         => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.item_id'   => 'required|integer|exists:inventory_items,id',
            'items.*.quantity'  => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'supplier_id.required_if' => 'Supplier wajib diisi untuk penerimaan barang dari Pembelian.',
            'items.required'          => 'Minimal masukkan 1 item barang yang diterima.',
        ]);

        $receipt = DB::transaction(function () use ($request, $tenantId) {
            $userId        = Auth::id();
            $receiptNumber = GoodsReceipt::generateReceiptNumber();
            $totalAmount   = 0;

            $receipt = GoodsReceipt::create([
                'tenant_id'      => $tenantId,
                'supplier_id'    => $request->supplier_id,
                'department_id'  => $request->department_id,
                'receipt_number' => $receiptNumber,
                'receipt_date'   => $request->receipt_date,
                'source'         => $request->source,
                'status'         => 'pending',
                'notes'          => $request->notes,
                'total_amount'   => 0,
                'created_by'     => $userId,
            ]);

            foreach ($request->items as $row) {
                $qty      = (float) $row['quantity'];
                $price    = (float) $row['unit_price'];
                $subtotal = $qty * $price;
                $totalAmount += $subtotal;

                $receipt->items()->create([
                    'inventory_item_id' => $row['item_id'],
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'notes'             => $row['notes'] ?? null,
                ]);
            }

            $receipt->update(['total_amount' => $totalAmount]);

            return $receipt;
        });

        return redirect()->route('v2.barang_masuk.show', $receipt)
            ->with('success', "✅ Penerimaan barang ({$receipt->receipt_number}) berhasil dicatat. Silakan setujui (approve) agar stok masuk ke gudang.");
    }

    /**
     * Show detail of Goods Receipt.
     */
    public function show(GoodsReceipt $goodsReceipt)
    {
        $this->checkAccess('goods-receipts.index');
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);

        $goodsReceipt->load([
            'supplier',
            'department',
            'purchaseOrder',
            'items.inventoryItem',
            'items.masterProduct',
            'createdBy',
            'approvedBy',
            'payable'
        ]);

        return view('v2.barang_masuk.show', compact('goodsReceipt'));
    }

    /**
     * Approve Goods Receipt and update stock.
     */
    public function approve(GoodsReceipt $goodsReceipt)
    {
        $this->checkAccess('goods-receipts.index');
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);

        if ($goodsReceipt->status !== 'pending') {
            return back()->with('error', 'Hanya penerimaan berstatus Pending yang dapat disetujui.');
        }

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        DB::transaction(function () use ($goodsReceipt, $tenantId, $userId) {
            $goodsReceipt->load(['items.inventoryItem', 'items.masterProduct', 'purchaseOrder.items']);

            foreach ($goodsReceipt->items as $item) {
                $qty = $item->quantity;

                if ($item->inventory_item_id && $item->inventoryItem) {
                    $invItem = $item->inventoryItem;
                    $invItem->increment('stock', $qty);
                    $newStock = $invItem->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'inventory_item_id' => $invItem->id,
                        'department_id'     => $goodsReceipt->department_id,
                        'goods_receipt_id'  => $goodsReceipt->id,
                        'user_id'           => $userId,
                        'type'              => 'in',
                        'quantity'          => $qty,
                        'reference'         => 'Terima Barang ' . $goodsReceipt->source_label . ' — ' . $goodsReceipt->receipt_number,
                        'balance_after'     => $newStock,
                    ]);
                }
            }

            // Update status Goods Receipt
            $goodsReceipt->update([
                'status'      => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Auto-create Hutang Supplier jika ada supplier
            if ($goodsReceipt->supplier_id && !$goodsReceipt->payable()->exists()) {
                SupplierPayable::create([
                    'tenant_id'        => $tenantId,
                    'supplier_id'      => $goodsReceipt->supplier_id,
                    'goods_receipt_id' => $goodsReceipt->id,
                    'reference_number' => SupplierPayable::generateReferenceNumber(),
                    'payable_date'     => $goodsReceipt->receipt_date,
                    'total_amount'     => $goodsReceipt->total_amount,
                    'paid_amount'      => 0,
                    'status'           => 'unpaid',
                    'notes'            => 'Otomatis dari Penerimaan Barang V2: ' . $goodsReceipt->receipt_number,
                    'created_by'       => $userId,
                ]);
            }
        });

        return redirect()->route('v2.barang_masuk.show', $goodsReceipt)
            ->with('success', '✅ Penerimaan barang berhasil disetujui. Stok telah resmi bertambah ke gudang.');
    }

    /**
     * Delete / Cancel Goods Receipt.
     */
    public function destroy(GoodsReceipt $goodsReceipt)
    {
        $this->checkAccess('goods-receipts.index');
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($goodsReceipt) {
            $tenantId = Auth::user()->tenant_id;
            $userId   = Auth::id();

            if ($goodsReceipt->status === 'approved') {
                $goodsReceipt->load(['items.inventoryItem']);

                foreach ($goodsReceipt->items as $item) {
                    $qty = $item->quantity;
                    if ($item->inventory_item_id && $item->inventoryItem) {
                        $item->inventoryItem->decrement('stock', $qty);
                        $newStock = $item->inventoryItem->fresh()->stock;

                        StockMovement::create([
                            'tenant_id'         => $tenantId,
                            'inventory_item_id' => $item->inventory_item_id,
                            'user_id'           => $userId,
                            'type'              => 'adjustment',
                            'quantity'          => -$qty,
                            'reference'         => 'Batal Penerimaan (Stok Keluar) — ' . $goodsReceipt->receipt_number,
                            'balance_after'     => $newStock,
                        ]);
                    }
                }
            }

            $goodsReceipt->delete();
        });

        return redirect()->route('v2.barang_masuk.index')
            ->with('success', '✅ Transaksi Penerimaan Barang berhasil dibatalkan / dihapus.');
    }
}
