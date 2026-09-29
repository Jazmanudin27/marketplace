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
}
