<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductionRequest;
use App\Models\ProductionRequestItem;
use App\Models\ProductionOrder;
use App\Models\Department;
use App\Models\MasterProduct;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionRequestController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $pendingRequests = ProductionRequest::where('tenant_id', $tenantId)
            ->with(['department', 'store'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $approvedRequests = ProductionRequest::where('tenant_id', $tenantId)
            ->with(['department', 'store', 'approvedBy'])
            ->where('status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $otherRequests = ProductionRequest::where('tenant_id', $tenantId)
            ->with(['department', 'store', 'rejectedBy'])
            ->whereIn('status', ['rejected', 'completed'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('inventory.production_requests.index', compact('pendingRequests', 'approvedRequests', 'otherRequests'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.production_requests.create', compact('products', 'departments'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'department_name' => 'required|string',
            'request_type' => 'required|in:Stok Gudang Jadi,PO Pelanggan',
            'customer_name' => 'nullable|required_if:request_type,PO Pelanggan|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|required_if:request_type,PO Pelanggan|string',
            'items' => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $dept = Department::where('tenant_id', $tenantId)
            ->where('name', $request->department_name)
            ->firstOrFail();

        $productionRequest = null;

        DB::transaction(function() use ($request, $tenantId, $dept, &$productionRequest) {
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Generate automatic request number
            $today = date('Ymd');
            $count = ProductionRequest::where('request_number', 'like', "REQ-{$today}-%")->count();
            $requestNumber = 'REQ-' . $today . '-' . sprintf('%04d', $count + 1);

            // Create request
            $productionRequest = ProductionRequest::create([
                'tenant_id' => $tenantId,
                'request_number' => $requestNumber,
                'request_type' => $request->request_type === 'PO Pelanggan' ? 'po' : 'stock',
                'department_id' => $dept->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->request_type === 'PO Pelanggan' ? $request->shipping_address : 'Stok Gudang Jadi (Penyimpanan Utama)',
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // Create items
            foreach ($request->items as $itemData) {
                ProductionRequestItem::create([
                    'production_request_id' => $productionRequest->id,
                    'master_product_id' => $itemData['master_product_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                ]);
            }
        });

        return redirect()->route('production_requests.show', $productionRequest->id)
            ->with('success', 'Permintaan produksi berhasil diajukan! Menunggu persetujuan Bagian Produksi.');
    }

    public function show(ProductionRequest $productionRequest)
    {
        abort_unless($productionRequest->tenant_id === Auth::user()->tenant_id, 403);
        $productionRequest->load(['items.masterProduct', 'department', 'store', 'approvedBy', 'rejectedBy']);

        return view('inventory.production_requests.show', compact('productionRequest'));
    }

    public function approve(ProductionRequest $productionRequest)
    {
        abort_unless($productionRequest->tenant_id === Auth::user()->tenant_id, 403);

        if ($productionRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini tidak sedang dalam status pending.');
        }

        DB::transaction(function() use ($productionRequest) {
            $productionRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            // Create Production Order (SPK) for each item in the request
            foreach ($productionRequest->items as $item) {
                ProductionOrder::create([
                    'tenant_id' => $productionRequest->tenant_id,
                    'master_product_id' => $item->master_product_id,
                    'quantity' => $item->quantity,
                    'status' => 'pending',
                    'requested_by' => Auth::id(),
                ]);
            }
        });

        return redirect()->route('production_requests.show', $productionRequest)
            ->with('success', 'Permintaan produksi disetujui! Perintah Kerja (SPK) untuk masing-masing produk telah dibuat.');
    }

    public function reject(Request $request, ProductionRequest $productionRequest)
    {
        abort_unless($productionRequest->tenant_id === Auth::user()->tenant_id, 403);

        if ($productionRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini tidak sedang dalam status pending.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $productionRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('production_requests.show', $productionRequest)
            ->with('success', 'Permintaan produksi telah ditolak.');
    }

    public function printSpk(ProductionRequest $productionRequest)
    {
        abort_unless($productionRequest->tenant_id === Auth::user()->tenant_id, 403);
        $productionRequest->load(['items.masterProduct', 'department', 'approvedBy']);

        $grouped = [];
        $sizesHeader = ['S', 'M', 'L', 'XL', 'XXL', '3XL'];

        foreach ($productionRequest->items as $item) {
            $product = $item->masterProduct;
            if (!$product) continue;

            $modelKey = $product->sku_induk;
            if (empty($modelKey)) {
                $modelKey = $product->name;
                if ($product->ukuran) {
                    $modelKey = trim(str_ireplace($product->ukuran, '', $modelKey));
                }
            }

            if (!isset($grouped[$modelKey])) {
                $grouped[$modelKey] = [
                    'model' => $modelKey,
                    'name'  => $product->name,
                    'color' => $product->warna ?: '—',
                    'image' => $product->image_url,
                    'sizes' => [
                        'S'   => 0,
                        'M'   => 0,
                        'L'   => 0,
                        'XL'  => 0,
                        'XXL' => 0,
                        '3XL' => 0,
                    ],
                    'total' => 0,
                ];
            }

            $sz = strtoupper(trim($product->ukuran));
            if ($sz === 'XXXL' || $sz === '3XL') {
                $sz = '3XL';
            }

            if (array_key_exists($sz, $grouped[$modelKey]['sizes'])) {
                $grouped[$modelKey]['sizes'][$sz] += $item->quantity;
            } else {
                $grouped[$modelKey]['sizes'][$sz] = $item->quantity;
                if (!in_array($sz, $sizesHeader)) {
                    $sizesHeader[] = $sz;
                }
            }
            $grouped[$modelKey]['total'] += $item->quantity;
        }

        return view('inventory.production_requests.print_spk', compact('productionRequest', 'grouped', 'sizesHeader'));
    }
}
