<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfflineSaleController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query    = OfflineSale::with('user')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('buyer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sold_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sold_at', '<=', $request->date_to);
        }

        $sales   = $query->paginate(20)->withQueryString();
        $summary = OfflineSale::where('tenant_id', $tenantId)
            ->where('status', OfflineSale::STATUS_COMPLETED)
            ->selectRaw('COUNT(*) as total_count, SUM(grand_total) as total_revenue')
            ->first();

        return view('offline_sales.index', compact('sales', 'summary'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('stock', '>', 0)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'reseller_price', 'stock', 'unit']);

        $customers = \App\Models\Customer::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNull('marketplace_username')
                  ->orWhere('marketplace_username', '');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'address']);

        return view('offline_sales.create', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $rules = [
            'customer_id'               => 'nullable|exists:customers,id',
            'items'                     => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.unit_price'        => 'required|numeric|min:0',
            'payment_method'            => 'required|in:tunai,transfer,qris,kartu,reseller_balance,piutang',
            'paid_amount'               => 'required|numeric|min:0',
            'discount_amount'           => 'nullable|numeric|min:0',
            'buyer_name'                => 'nullable|string|max:100',
            'buyer_phone'               => 'nullable|string|max:20',
            'buyer_address'             => 'nullable|string|max:500',
            'notes'                     => 'nullable|string|max:500',
            'is_dropship'               => 'nullable|boolean',
            'dropshipper_name'          => 'nullable|required_if:is_dropship,1|string|max:100',
            'dropshipper_phone'         => 'nullable|required_if:is_dropship,1|string|max:20',
        ];

        if ($request->payment_method === 'piutang') {
            if (!$request->filled('customer_id')) {
                $rules['buyer_name']  = 'required|string|max:100';
                $rules['buyer_phone'] = 'required|string|max:20';
            }
        } else {
            if (!$request->filled('customer_id') && $request->filled('buyer_name')) {
                $rules['buyer_phone'] = 'required|string|max:20';
            }
        }

        $request->validate($rules);

        $tenantId = Auth::user()->tenant_id;

        if ($request->filled('customer_id')) {
            $customerExists = \App\Models\Customer::where('tenant_id', $tenantId)->where('id', $request->customer_id)->exists();
            if (!$customerExists) {
                return back()->withErrors(['customer_id' => 'Pelanggan tidak valid untuk perusahaan Anda.']);
            }
        }

        DB::transaction(function () use ($request, $tenantId) {
            $totalAmount    = 0;
            $discountAmount = (float) ($request->discount_amount ?? 0);
            $itemsData      = [];

            foreach ($request->items as $item) {
                $product = MasterProduct::where('tenant_id', $tenantId)
                    ->findOrFail($item['master_product_id']);

                $qty      = (int) $item['quantity'];
                $price    = (float) $item['unit_price'];
                $subtotal = $qty * $price;

                // Pastikan stok cukup
                if ($product->stock < $qty) {
                    abort(422, "Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}");
                }

                $totalAmount += $subtotal;

                $itemsData[] = [
                    'master_product_id' => $product->id,
                    'product_name'      => $product->name,
                    'sku'               => $product->sku,
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'subtotal'          => $subtotal,
                ];
            }

            $grandTotal   = max(0, $totalAmount - $discountAmount);
            $paidAmount   = (float) $request->paid_amount;
            $changeAmount = max(0, $paidAmount - $grandTotal);

            // Auto create customer if cashier filled in general buyer name but no customer_id exists
            $customerId = $request->customer_id;
            if (!$customerId && $request->filled('buyer_name')) {
                $customerQuery = \App\Models\Customer::where('tenant_id', $tenantId);
                if ($request->filled('buyer_phone')) {
                    $customerQuery->where('phone', $request->buyer_phone);
                } else {
                    $customerQuery->where('name', $request->buyer_name)
                                  ->where(function($q) {
                                      $q->whereNull('marketplace_username')
                                        ->orWhere('marketplace_username', '');
                                  });
                }
                
                $customer = $customerQuery->first();
                if (!$customer) {
                    $customer = \App\Models\Customer::create([
                        'tenant_id' => $tenantId,
                        'name'      => $request->buyer_name,
                        'phone'     => $request->buyer_phone,
                        'address'   => $request->buyer_address,
                    ]);
                } else {
                    if (empty($customer->address) && $request->filled('buyer_address')) {
                        $customer->update(['address' => $request->buyer_address]);
                    }
                }
                $customerId = $customer->id;
            } elseif ($customerId && $request->filled('buyer_address')) {
                $customer = \App\Models\Customer::where('tenant_id', $tenantId)->find($customerId);
                if ($customer && empty($customer->address)) {
                    $customer->update(['address' => $request->buyer_address]);
                }
            }

            $sale = OfflineSale::create([
                'tenant_id'       => $tenantId,
                'user_id'         => Auth::id(),
                'customer_id'     => $customerId,
                'sale_number'     => OfflineSale::generateSaleNumber(),
                'status'          => OfflineSale::STATUS_COMPLETED,
                'buyer_name'      => $request->buyer_name,
                'buyer_phone'     => $request->buyer_phone,
                'payment_method'  => $request->payment_method,
                'total_amount'    => $totalAmount,
                'discount_amount' => $discountAmount,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'change_amount'   => $changeAmount,
                'notes'           => $request->notes,
                'sold_at'         => now(),
                'is_dropship'      => (bool) $request->is_dropship,
                'dropshipper_name'  => $request->is_dropship ? $request->dropshipper_name : null,
                'dropshipper_phone' => $request->is_dropship ? $request->dropshipper_phone : null,
            ]);

            // Potong saldo jika menggunakan metode pembayaran 'reseller_balance'
            if ($request->payment_method === 'reseller_balance') {
                $customerObj = \App\Models\Customer::where('tenant_id', $tenantId)->find($customerId);
                if (!$customerObj || $customerObj->balance < $grandTotal) {
                    abort(422, 'Saldo reseller tidak mencukupi.');
                }
                $customerObj->adjustBalance($grandTotal, 'out', "Pembayaran transaksi kasir #{$sale->sale_number}", Auth::id());
            }

            foreach ($itemsData as $itemData) {
                $sale->items()->create($itemData);

                // Kurangi stok
                $product = MasterProduct::find($itemData['master_product_id']);
                $product->recordStockMovement(
                    $itemData['quantity'],
                    'out',
                    'Penjualan Offline: ' . $sale->sale_number,
                    Auth::id()
                );
            }
        });

        return redirect()->route('offline_sales.index')
            ->with('success', '✅ Transaksi penjualan offline berhasil dicatat!');
    }

    public function show(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        $offlineSale->load('items.masterProduct', 'user', 'customer');
        return view('offline_sales.show', compact('offlineSale'));
    }

    public function complete(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        $offlineSale->update(['status' => OfflineSale::STATUS_COMPLETED]);
        return back()->with('success', 'Transaksi ditandai selesai.');
    }

    public function cancel(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);

        if ($offlineSale->status === OfflineSale::STATUS_CANCELLED) {
            return back()->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
        }

        DB::transaction(function () use ($offlineSale) {
            // Kembalikan stok
            foreach ($offlineSale->items as $item) {
                if ($item->master_product_id) {
                    $product = MasterProduct::find($item->master_product_id);
                    if ($product) {
                        $product->recordStockMovement(
                            $item->quantity,
                            'in',
                            'Pembatalan Penjualan Offline: ' . $offlineSale->sale_number,
                            Auth::id()
                        );
                    }
                }
            }
            $offlineSale->update(['status' => OfflineSale::STATUS_CANCELLED]);
        });

        return back()->with('success', 'Transaksi dibatalkan dan stok telah dikembalikan.');
    }

    public function printReceipt(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        $offlineSale->load('items.masterProduct', 'user');
        $tenant = $offlineSale->tenant;
        return view('offline_sales.receipt', compact('offlineSale', 'tenant'));
    }
}
