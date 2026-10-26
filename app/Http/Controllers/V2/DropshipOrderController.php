<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\DropshipOrder;
use App\Models\DropshipOrderItem;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropshipOrderController extends Controller
{
    /**
     * Pastikan tabel dropship_orders dan dropship_order_items sudah dibuat di database.
     */
    protected function ensureTableExists(): void
    {
        if (!Schema::hasTable('dropship_orders')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}

            if (!Schema::hasTable('dropship_orders')) {
                DB::statement("CREATE TABLE IF NOT EXISTS `dropship_orders` (
                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    `tenant_id` bigint(20) unsigned NOT NULL,
                    `user_id` bigint(20) unsigned DEFAULT NULL,
                    `order_number` varchar(50) NOT NULL,
                    `channel_type` varchar(30) NOT NULL DEFAULT 'dropship',
                    `order_date` date NOT NULL,
                    `customer_name` varchar(150) NOT NULL,
                    `customer_phone` varchar(50) DEFAULT NULL,
                    `notes` text DEFAULT NULL,
                    `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                    `total_qty` int(11) NOT NULL DEFAULT 0,
                    `status` varchar(30) NOT NULL DEFAULT 'completed',
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `dropship_orders_order_number_unique` (`order_number`),
                    KEY `dropship_orders_tenant_id_order_date_index` (`tenant_id`,`order_date`),
                    KEY `dropship_orders_tenant_id_channel_type_index` (`tenant_id`,`channel_type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        }

        if (!Schema::hasTable('dropship_order_items')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `dropship_order_items` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `dropship_order_id` bigint(20) unsigned NOT NULL,
                `master_product_id` bigint(20) unsigned DEFAULT NULL,
                `product_name` varchar(255) NOT NULL,
                `sku` varchar(100) DEFAULT NULL,
                `quantity` int(11) NOT NULL DEFAULT 1,
                `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
                `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `dropship_order_items_dropship_order_id_index` (`dropship_order_id`),
                KEY `dropship_order_items_master_product_id_index` (`master_product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    /**
     * Daftar Pesanan Dropship / Lazada.
     */
    public function index(Request $request)
    {
        $this->ensureTableExists();
        $tenantId = Auth::user()->tenant_id;

        $channelType = $request->input('channel_type', 'all');

        $query = DropshipOrder::with(['items.masterProduct', 'user'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if ($channelType !== 'all' && !empty($channelType)) {
            $query->where('channel_type', $channelType);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $orders = $query->paginate(20)->withQueryString();

        // Hitung Tab Counts
        $countBase = DropshipOrder::where('tenant_id', $tenantId);
        if ($request->filled('date_from')) $countBase->whereDate('order_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $countBase->whereDate('order_date', '<=', $request->date_to);
        if ($request->filled('search')) {
            $s = trim($request->search);
            $countBase->where(fn($q) => $q->where('order_number', 'like', "%{$s}%")->orWhere('customer_name', 'like', "%{$s}%"));
        }

        $tabCounts = [
            'all'      => (clone $countBase)->count(),
            'dropship' => (clone $countBase)->where('channel_type', 'dropship')->count(),
            'lazada'   => (clone $countBase)->where('channel_type', 'lazada')->count(),
        ];

        // Summary KPI
        $kpiQuery = clone $query;
        $totalOrdersCount = $kpiQuery->count();
        $totalOmset       = (float) (clone $countBase)->sum('total_amount');
        $totalQty         = (int) (clone $countBase)->sum('total_qty');

        return view('v2.marketing.dropship.index', compact(
            'orders',
            'tabCounts',
            'channelType',
            'totalOrdersCount',
            'totalOmset',
            'totalQty'
        ));
    }

    /**
     * Form Buat Pesanan Dropship Baru.
     */
    public function create(Request $request)
    {
        $this->ensureTableExists();
        $tenantId = Auth::user()->tenant_id;

        $defaultChannel = $request->input('channel_type', 'dropship');
        if (!in_array($defaultChannel, ['dropship', 'lazada'])) {
            $defaultChannel = 'dropship';
        }

        $orderNumber = DropshipOrder::generateOrderNumber($defaultChannel);

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->nonBundle()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'sku', 'name', 'stock', 'price', 'cost_price', 'lazada_price', 'lazada_dropship_price', 'shopee_dropship_price']);

        return view('v2.marketing.dropship.create', compact('defaultChannel', 'orderNumber', 'products'));
    }

    /**
     * Simpan Pesanan Dropship Baru.
     */
    public function store(Request $request)
    {
        $this->ensureTableExists();
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'channel_type'   => 'required|in:dropship,lazada',
            'order_date'     => 'required|date',
            'customer_name'  => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:1000',
            'items'          => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.unit_price'        => 'required|numeric|min:0',
        ], [
            'customer_name.required' => 'Nama Pelanggan wajib diisi manual.',
            'items.required'         => 'Minimal satu produk pesanan harus diisikan.',
            'items.*.master_product_id.required' => 'Pilih produk pesanan.',
        ]);

        try {
            DB::transaction(function () use ($request, $tenantId) {
                $orderNumber = DropshipOrder::generateOrderNumber($request->channel_type);
                $totalAmount = 0;
                $totalQty    = 0;
                $itemsData   = [];

                foreach ($request->items as $row) {
                    $product = MasterProduct::where('tenant_id', $tenantId)->find($row['master_product_id']);
                    if (!$product) continue;

                    $qty       = (int) $row['quantity'];
                    $price     = (float) $row['unit_price'];
                    $subtotal  = $qty * $price;
                    $totalAmount += $subtotal;
                    $totalQty    += $qty;

                    $itemsData[] = [
                        'master_product_id' => $product->id,
                        'product_name'      => $product->name,
                        'sku'               => $product->sku,
                        'quantity'          => $qty,
                        'unit_price'        => $price,
                        'subtotal'          => $subtotal,
                    ];
                }

                if (empty($itemsData)) {
                    throw new \Exception('Tidak ada produk yang valid untuk disimpan.');
                }

                $order = DropshipOrder::create([
                    'tenant_id'      => $tenantId,
                    'user_id'        => Auth::id(),
                    'order_number'   => $orderNumber,
                    'channel_type'   => $request->channel_type,
                    'order_date'     => $request->order_date,
                    'customer_name'  => trim($request->customer_name),
                    'customer_phone' => trim($request->customer_phone ?? ''),
                    'notes'          => $request->notes,
                    'total_amount'   => $totalAmount,
                    'total_qty'      => $totalQty,
                    'status'         => 'completed',
                ]);

                foreach ($itemsData as $it) {
                    $order->items()->create($it);

                    // Potong stok master product
                    $prod = MasterProduct::find($it['master_product_id']);
                    if ($prod) {
                        $prod->recordStockMovement(
                            $it['quantity'],
                            'out',
                            'Pesanan ' . ucfirst($order->channel_type) . ': ' . $order->order_number,
                            Auth::id()
                        );
                    }
                }
            });

            return redirect()->route('v2.marketing.dropship.index', ['channel_type' => $request->channel_type])
                ->with('success', 'Pesanan ' . ucfirst($request->channel_type) . ' untuk pelanggan "' . $request->customer_name . '" berhasil disimpan!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Detail Pesanan Dropship.
     */
    public function show($id)
    {
        $this->ensureTableExists();
        $tenantId = Auth::user()->tenant_id;

        $order = DropshipOrder::with(['items.masterProduct', 'user'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return view('v2.marketing.dropship.show', compact('order'));
    }

    /**
     * Form Edit Pesanan Dropship.
     */
    public function edit($id)
    {
        $this->ensureTableExists();
        $tenantId = Auth::user()->tenant_id;

        $order = DropshipOrder::with(['items.masterProduct'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->nonBundle()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'sku', 'name', 'stock', 'price', 'cost_price', 'lazada_price', 'lazada_dropship_price', 'shopee_dropship_price']);

        return view('v2.marketing.dropship.edit', compact('order', 'products'));
    }

    /**
     * Update Pesanan Dropship.
     */
    public function update(Request $request, $id)
    {
        $this->ensureTableExists();
        $tenantId = Auth::user()->tenant_id;

        $order = DropshipOrder::with('items')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $request->validate([
            'channel_type'   => 'required|in:dropship,lazada',
            'order_date'     => 'required|date',
            'customer_name'  => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:1000',
            'items'          => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.unit_price'        => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $order, $tenantId) {
                // 1. Kembalikan stok lama
                foreach ($order->items as $oldItem) {
                    if ($oldItem->master_product_id) {
                        $prod = MasterProduct::find($oldItem->master_product_id);
                        if ($prod) {
                            $prod->recordStockMovement(
                                $oldItem->quantity,
                                'in',
                                'Koreksi Edit Pesanan: ' . $order->order_number,
                                Auth::id()
                            );
                        }
                    }
                }

                $order->items()->delete();

                // 2. Alokasikan item baru
                $totalAmount = 0;
                $totalQty    = 0;
                $itemsData   = [];

                foreach ($request->items as $row) {
                    $product = MasterProduct::where('tenant_id', $tenantId)->find($row['master_product_id']);
                    if (!$product) continue;

                    $qty       = (int) $row['quantity'];
                    $price     = (float) $row['unit_price'];
                    $subtotal  = $qty * $price;
                    $totalAmount += $subtotal;
                    $totalQty    += $qty;

                    $itemsData[] = [
                        'master_product_id' => $product->id,
                        'product_name'      => $product->name,
                        'sku'               => $product->sku,
                        'quantity'          => $qty,
                        'unit_price'        => $price,
                        'subtotal'          => $subtotal,
                    ];
                }

                $order->update([
                    'channel_type'   => $request->channel_type,
                    'order_date'     => $request->order_date,
                    'customer_name'  => trim($request->customer_name),
                    'customer_phone' => trim($request->customer_phone ?? ''),
                    'notes'          => $request->notes,
                    'total_amount'   => $totalAmount,
                    'total_qty'      => $totalQty,
                ]);

                foreach ($itemsData as $it) {
                    $order->items()->create($it);

                    // Potong stok produk baru
                    $prod = MasterProduct::find($it['master_product_id']);
                    if ($prod) {
                        $prod->recordStockMovement(
                            $it['quantity'],
                            'out',
                            'Update Pesanan ' . ucfirst($order->channel_type) . ': ' . $order->order_number,
                            Auth::id()
                        );
                    }
                }
            });

            return redirect()->route('v2.marketing.dropship.index', ['channel_type' => $order->channel_type])
                ->with('success', 'Pesanan ' . $order->order_number . ' berhasil diperbarui!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Hapus Pesanan Dropship.
     */
    public function destroy($id)
    {
        $this->ensureTableExists();
        $tenantId = Auth::user()->tenant_id;

        $order = DropshipOrder::with('items')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        DB::transaction(function () use ($order) {
            // Kembalikan stok produk
            foreach ($order->items as $item) {
                if ($item->master_product_id) {
                    $prod = MasterProduct::find($item->master_product_id);
                    if ($prod) {
                        $prod->recordStockMovement(
                            $item->quantity,
                            'in',
                            'Batal/Hapus Pesanan ' . ucfirst($order->channel_type) . ': ' . $order->order_number,
                            Auth::id()
                        );
                    }
                }
            }

            $order->items()->delete();
            $order->delete();
        });

        return redirect()->route('v2.marketing.dropship.index')
            ->with('success', 'Pesanan ' . $order->order_number . ' berhasil dihapus dan stok produk telah dikembalikan.');
    }

    /**
     * AJAX Search Produk untuk Form Dropship.
     */
    public function searchProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search   = trim($request->input('q', ''));

        $query = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->nonBundle();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('sku_induk', 'like', '%' . $search . '%');
            });
        }

        $channel = $request->input('channel_type', 'dropship');

        $products = $query->select(['id', 'sku', 'name', 'stock', 'cost_price', 'price', 'lazada_price', 'lazada_dropship_price', 'shopee_dropship_price'])
            ->orderBy('name')
            ->limit(30)
            ->get();

        $results = $products->map(function ($p) use ($channel) {
            $price = (float) $p->price;
            if ($channel === 'lazada') {
                $price = (float) ($p->lazada_dropship_price ?: ($p->lazada_price ?: $p->price));
            } else {
                $price = (float) ($p->shopee_dropship_price ?: $p->price);
            }

            return [
                'id'         => $p->id,
                'text'       => '[' . $p->sku . '] ' . $p->name . ' (Stok: ' . number_format($p->stock) . ' | Rp ' . number_format($price, 0, ',', '.') . ')',
                'sku'        => $p->sku,
                'name'       => $p->name,
                'cost_price' => (float) $p->cost_price,
                'price'      => $price,
                'stock'      => (int) $p->stock,
            ];
        });

        return response()->json(['results' => $results]);
    }
}
