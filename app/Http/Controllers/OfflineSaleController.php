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

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'lunas') {
                $query->where('status', '!=', OfflineSale::STATUS_CANCELLED)
                      ->where(function($q) {
                          $q->where('payment_method', '!=', 'piutang')
                            ->whereRaw('paid_amount >= grand_total');
                      });
            } elseif ($request->payment_status === 'belum_lunas') {
                $query->where('status', '!=', OfflineSale::STATUS_CANCELLED)
                      ->where(function($q) {
                          $q->where('payment_method', 'piutang')
                            ->orWhereRaw('paid_amount < grand_total');
                      });
            }
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

        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('offline_sales.index', compact('sales', 'summary', 'bankAccounts'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)
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
            'payment_method'            => 'required|in:tunai,transfer,qris,piutang',
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
            $globalDiscType = $request->discount_type ?? 'fixed';
            $globalDiscVal  = (float) ($request->discount_value ?? $request->discount_amount ?? 0);
            $itemsData      = [];

            foreach ($request->items as $item) {
                $product = MasterProduct::where('tenant_id', $tenantId)
                    ->findOrFail($item['master_product_id']);

                $qty       = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                $itemDiscType = $item['discount_type'] ?? 'fixed';
                $itemDiscVal  = (float) ($item['discount_value'] ?? 0);

                if ($itemDiscType === 'percentage') {
                    $itemDiscPerUnit = ($unitPrice * min(100, max(0, $itemDiscVal))) / 100;
                } else {
                    $itemDiscPerUnit = min($unitPrice, max(0, $itemDiscVal));
                }

                $itemDiscTotal  = $itemDiscPerUnit * $qty;
                $effectivePrice = max(0, $unitPrice - $itemDiscPerUnit);
                $subtotal       = $qty * $effectivePrice;

                $isPo = $request->boolean('is_po');

                // Pastikan stok cukup jika bukan pesanan Pre-Order / PO Produksi
                if (!$isPo && $product->stock < $qty) {
                    abort(422, "Stok {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}");
                }

                $totalAmount += $subtotal;

                $itemsData[] = [
                    'master_product_id' => $product->id,
                    'product_name'      => $product->name,
                    'sku'               => $product->sku,
                    'quantity'          => $qty,
                    'unit_price'        => $unitPrice,
                    'discount_type'     => $itemDiscType,
                    'discount_value'    => $itemDiscVal,
                    'discount_amount'   => $itemDiscTotal,
                    'subtotal'          => $subtotal,
                ];
            }

            if ($globalDiscType === 'percentage') {
                $discountAmount = ($totalAmount * min(100, max(0, $globalDiscVal))) / 100;
            } else {
                $discountAmount = min($totalAmount, max(0, $globalDiscVal));
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
                'status'          => OfflineSale::STATUS_PENDING_APPROVAL,
                'buyer_name'      => $request->buyer_name,
                'buyer_phone'     => $request->buyer_phone,
                'payment_method'  => $request->payment_method,
                'total_amount'    => $totalAmount,
                'discount_amount' => $discountAmount,
                'discount_type'   => $globalDiscType,
                'discount_value'  => $globalDiscVal,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'change_amount'   => $changeAmount,
                'notes'           => $request->notes,
                'sold_at'         => now(),
                'is_po'            => $request->boolean('is_po'),
                'is_dropship'      => (bool) $request->is_dropship,
                'dropshipper_name'  => $request->is_dropship ? $request->dropshipper_name : null,
                'dropshipper_phone' => $request->is_dropship ? $request->dropshipper_phone : null,
            ]);

            foreach ($itemsData as $itemData) {
                $sale->items()->create($itemData);
            }

            // Jika pesanan Pre-Order (PO Produksi), buatkan SPK otomatis untuk Tim Produksi
            if ($request->boolean('is_po')) {
                $today = date('Ymd');
                $countToday = \App\Models\Spk::where('tenant_id', $tenantId)
                    ->whereDate('tanggal', date('Y-m-d'))
                    ->count();
                $noSpk = 'SPK-PO-' . $today . '-' . sprintf('%03d', $countToday + 1);

                $spk = \App\Models\Spk::create([
                    'tenant_id'     => $tenantId,
                    'no_spk'        => $noSpk,
                    'tanggal'       => now(),
                    'deadline'      => $request->filled('deadline') ? $request->deadline : now()->addDays(7),
                    'pemesan'       => $request->buyer_name ?: 'Pelanggan PO',
                    'no_hp_pemesan' => $request->buyer_phone ?: '',
                    'instansi'      => 'Penjualan PO #' . $sale->sale_number,
                    'penginput_id'  => Auth::id(),
                ]);

                foreach ($itemsData as $itemData) {
                    \App\Models\SpkItem::create([
                        'spk_id'            => $spk->id,
                        'master_product_id' => $itemData['master_product_id'],
                        'nama_produk'       => $itemData['product_name'],
                        'sku'               => $itemData['sku'],
                        'quantity'          => $itemData['quantity'],
                        'hpp'               => $itemData['unit_price'],
                        'status'            => 'Pending',
                    ]);
                }
            }
        });

        return redirect()->route('offline_sales.index')
            ->with('success', '✅ Transaksi berhasil dibuat dan menunggu approval Gudang.');
    }

    public function show(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        $offlineSale->load('items.masterProduct', 'user', 'customer');
        $bankAccounts = \App\Models\BankAccount::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('offline_sales.show', compact('offlineSale', 'bankAccounts'));
    }

    public function approve(Request $request, OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless(Auth::user()->canDo('offline-sales.approve') || Auth::user()->isAdmin() || Auth::user()->isOwner() || in_array(Auth::user()->role, ['admin', 'owner', 'warehouse', 'gudang']), 403);

        if ($offlineSale->status !== OfflineSale::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Transaksi ini tidak dalam status menunggu approval.');
        }

        $request->validate([
            'payment_destination' => 'required|string|max:100',
        ], [
            'payment_destination.required' => 'Silakan pilih Kas / Bank Tujuan terlebih dahulu.',
        ]);

        // Cek ulang stok sebelum approve
        $offlineSale->load('items');
        foreach ($offlineSale->items as $item) {
            if ($item->master_product_id) {
                $product = MasterProduct::find($item->master_product_id);
                if ($product && $product->stock < $item->quantity) {
                    if ($offlineSale->is_po) {
                        return back()->with('error', "Pesanan PO ini masih dalam proses produksi (SPK). Stok {$product->name} di gudang belum mencukupi (tersedia: {$product->stock}, dibutuhkan: {$item->quantity}). Silakan selesaikan SPK produksi terlebih dahulu.");
                    }
                    return back()->with('error', "Stok {$product->name} tidak mencukupi (tersedia: {$product->stock}, dibutuhkan: {$item->quantity}). Tidak bisa diapprove.");
                }
            }
        }

        $tenantId = Auth::user()->tenant_id;

        DB::transaction(function () use ($offlineSale, $request, $tenantId) {
            // 1. Kurangi stok
            foreach ($offlineSale->items as $item) {
                if ($item->master_product_id) {
                    $product = MasterProduct::find($item->master_product_id);
                    if ($product) {
                        $product->recordStockMovement(
                            $item->quantity,
                            'out',
                            'Penjualan Offline (Approved): ' . $offlineSale->sale_number,
                            Auth::id()
                        );
                    }
                }
            }

            // 2. Tambahkan saldo ke Bank Account jika ada yang cocok
            $paymentDest = $request->payment_destination;
            $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
                ->where(function($q) use ($paymentDest) {
                    $q->where('bank_name', $paymentDest)
                      ->orWhere('id', $paymentDest);
                })->first();

            if ($bank) {
                $bank->increment('current_balance', $offlineSale->grand_total);
            }

            // 3. Catat Pemasukan (Income) di Keuangan
            \App\Models\Income::create([
                'tenant_id'           => $tenantId,
                'title'               => "Penjualan Offline POS #{$offlineSale->sale_number}",
                'category'            => 'services',
                'payment_destination' => $paymentDest,
                'amount'              => $offlineSale->grand_total,
                'income_date'         => now(),
                'description'         => "Pemasukan otomatis dari Penjualan Offline POS #{$offlineSale->sale_number} (Pembeli: " . ($offlineSale->buyer_name ?: 'Umum') . ")",
            ]);

            // 4. Update status penjualan & kas tujuan
            $offlineSale->update([
                'status'              => OfflineSale::STATUS_COMPLETED,
                'payment_destination' => $paymentDest,
                'approved_by'         => Auth::id(),
                'approved_at'         => now(),
            ]);
        });

        return back()->with('success', '✅ Transaksi disetujui! Stok telah dikurangi & uang dimasukkan ke Kas/Bank.');
    }

    public function processReturn(Request $request, OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);

        if ($offlineSale->status !== OfflineSale::STATUS_COMPLETED) {
            return back()->with('error', 'Retur hanya dapat dilakukan untuk transaksi yang sudah selesai (approved).');
        }

        $request->validate([
            'returns'             => 'required|array',
            'returns.*'           => 'required|integer|min:0',
            'reason'              => 'required|string|min:3|max:500',
            'refund_method'       => 'required|in:cash,bank,customer_balance,no_refund',
            'payment_destination' => 'nullable|string|max:100',
        ], [
            'returns.required' => 'Pilih setidaknya satu produk yang akan diretur.',
            'reason.required'  => 'Alasan retur wajib diisi.',
            'reason.min'       => 'Alasan retur minimal 3 karakter.',
        ]);

        $tenantId    = Auth::user()->tenant_id;
        $returnItems = [];
        $totalReturnAmount = 0;

        foreach ($offlineSale->items as $saleItem) {
            $returnQty = (int) ($request->returns[$saleItem->id] ?? 0);
            if ($returnQty <= 0) continue;

            $maxReturnable = $saleItem->remaining_quantity;
            if ($returnQty > $maxReturnable) {
                return back()->with('error', "Jumlah retur untuk {$saleItem->product_name} melebihi batas maksimal ({$maxReturnable}).");
            }

            $effectivePrice = $saleItem->quantity > 0 ? ($saleItem->subtotal / $saleItem->quantity) : 0;
            $subtotalReturn = $returnQty * $effectivePrice;

            $totalReturnAmount += $subtotalReturn;

            $returnItems[] = [
                'sale_item'      => $saleItem,
                'quantity'       => $returnQty,
                'unit_price'     => $effectivePrice,
                'subtotal'       => $subtotalReturn,
            ];
        }

        if (empty($returnItems)) {
            return back()->with('error', 'Jumlah produk yang diretur harus lebih dari 0.');
        }

        DB::transaction(function () use ($offlineSale, $request, $tenantId, $returnItems, $totalReturnAmount) {
            // 1. Buat record OfflineSaleReturn
            $saleReturn = \App\Models\OfflineSaleReturn::create([
                'tenant_id'           => $tenantId,
                'offline_sale_id'     => $offlineSale->id,
                'return_number'       => \App\Models\OfflineSaleReturn::generateReturnNumber(),
                'user_id'             => Auth::id(),
                'total_return_amount' => $totalReturnAmount,
                'refund_method'       => $request->refund_method,
                'payment_destination' => $request->payment_destination,
                'reason'              => $request->reason,
                'returned_at'         => now(),
            ]);

            // 2. Buat detail return item & kembalikan stok
            foreach ($returnItems as $itemData) {
                $saleItem = $itemData['sale_item'];

                \App\Models\OfflineSaleReturnItem::create([
                    'offline_sale_return_id' => $saleReturn->id,
                    'offline_sale_item_id'   => $saleItem->id,
                    'master_product_id'      => $saleItem->master_product_id,
                    'quantity'               => $itemData['quantity'],
                    'unit_price'             => $itemData['unit_price'],
                    'subtotal'               => $itemData['subtotal'],
                ]);

                // Kembalikan stok fisik ke gudang
                if ($saleItem->masterProduct) {
                    $saleItem->masterProduct->recordStockMovement(
                        'in',
                        $itemData['quantity'],
                        "Retur Penjualan POS #{$offlineSale->sale_number} (Nota Retur: {$saleReturn->return_number})",
                        'POS Return',
                        Auth::id()
                    );
                }
            }

            // 3. Proses Pengembalian Dana (Refund)
            if ($totalReturnAmount > 0) {
                if (in_array($request->refund_method, ['cash', 'bank'])) {
                    $paymentDest = $request->payment_destination ?: ($offlineSale->payment_destination ?: 'kas_besar');

                    $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
                        ->where(function($q) use ($paymentDest) {
                            $q->where('bank_name', $paymentDest)
                              ->orWhere('id', $paymentDest);
                        })->first();

                    if ($bank) {
                        $bank->decrement('current_balance', $totalReturnAmount);
                    }

                    \App\Models\Expense::create([
                        'tenant_id'           => $tenantId,
                        'title'               => "Refund Retur Penjualan POS #{$offlineSale->sale_number}",
                        'category'            => 'other',
                        'payment_destination' => $paymentDest,
                        'amount'              => $totalReturnAmount,
                        'expense_date'        => now(),
                        'description'         => "Pengembalian dana retur produk (Nota Retur: {$saleReturn->return_number}) untuk pembeli " . ($offlineSale->buyer_name ?: 'Umum'),
                    ]);
                } elseif ($request->refund_method === 'customer_balance' && $offlineSale->customer) {
                    $offlineSale->customer->adjustBalance(
                        $totalReturnAmount,
                        'in',
                        "Refund retur barang POS #{$offlineSale->sale_number} (Retur: {$saleReturn->return_number})",
                        Auth::id()
                    );
                }
            }
        });

        return back()->with('success', '✅ Retur sebagian barang berhasil diproses. Stok produk telah dikembalikan!');
    }

    public function markPaid(Request $request, OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);

        if ($offlineSale->status === OfflineSale::STATUS_CANCELLED) {
            return back()->with('error', 'Transaksi yang dibatalkan tidak dapat dilunasi.');
        }

        if ($offlineSale->is_paid) {
            return back()->with('error', 'Transaksi ini sudah berstatus Lunas.');
        }

        $tenantId = Auth::user()->tenant_id;
        $unpaidAmount = max(0, $offlineSale->grand_total - $offlineSale->paid_amount);

        DB::transaction(function () use ($offlineSale, $request, $tenantId, $unpaidAmount) {
            $paymentDest = $request->payment_destination ?: ($offlineSale->payment_destination ?: 'kas_besar');

            // Jika status sudah COMPLETED, catat pemasukan pelunasan & update saldo bank
            if ($offlineSale->status === OfflineSale::STATUS_COMPLETED && $unpaidAmount > 0) {
                $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
                    ->where(function($q) use ($paymentDest) {
                        $q->where('bank_name', $paymentDest)
                          ->orWhere('id', $paymentDest);
                    })->first();

                if ($bank) {
                    $bank->increment('current_balance', $unpaidAmount);
                }

                \App\Models\Income::create([
                    'tenant_id'           => $tenantId,
                    'title'               => "Pelunasan Penjualan Offline POS #{$offlineSale->sale_number}",
                    'category'            => 'services',
                    'payment_destination' => $paymentDest,
                    'amount'              => $unpaidAmount,
                    'income_date'         => now(),
                    'description'         => "Pelunasan piutang transaksi #{$offlineSale->sale_number} (Pembeli: " . ($offlineSale->buyer_name ?: 'Umum') . ")",
                ]);
            }

            $offlineSale->update([
                'paid_amount'         => $offlineSale->grand_total,
                'change_amount'       => 0,
                'payment_destination' => $paymentDest,
            ]);
        });

        return back()->with('success', '✅ Pembayaran berhasil dilunasi!');
    }

    public function complete(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        $offlineSale->update(['status' => OfflineSale::STATUS_COMPLETED]);
        return back()->with('success', 'Transaksi ditandai selesai.');
    }

    public function cancel(Request $request, OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);

        if ($offlineSale->status === OfflineSale::STATUS_CANCELLED) {
            return back()->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
        }

        $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:500',
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancellation_reason.min'      => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        // Simpan status lama SEBELUM update untuk kebutuhan pesan & logika stok
        $statusBefore = $offlineSale->status;

        DB::transaction(function () use ($offlineSale, $request, $statusBefore) {
            // Kembalikan stok HANYA jika sudah approved/completed (stok sudah dikurangi)
            if ($statusBefore === OfflineSale::STATUS_COMPLETED) {
                $offlineSale->load('items');
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

                // Reversi Pemasukan Keuangan & saldo bank jika ada
                \App\Models\Income::where('tenant_id', $offlineSale->tenant_id)
                    ->where('title', 'like', "%#{$offlineSale->sale_number}%")
                    ->delete();

                if ($offlineSale->payment_destination) {
                    $bank = \App\Models\BankAccount::where('tenant_id', $offlineSale->tenant_id)
                        ->where(function($q) use ($offlineSale) {
                            $q->where('bank_name', $offlineSale->payment_destination)
                              ->orWhere('id', $offlineSale->payment_destination);
                        })->first();

                    if ($bank && $bank->current_balance >= $offlineSale->grand_total) {
                        $bank->decrement('current_balance', $offlineSale->grand_total);
                    }
                }
            }
            // Jika status masih pending_approval, stok belum dikurangi → tidak perlu dikembalikan
            $offlineSale->update([
                'status'              => OfflineSale::STATUS_CANCELLED,
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by'        => Auth::id(),
            ]);
        });

        $msg = 'Transaksi berhasil dibatalkan.';
        if ($statusBefore === OfflineSale::STATUS_COMPLETED) {
            $msg .= ' Stok produk telah dikembalikan.';
        }

        return back()->with('success', $msg);
    }

    public function printReceipt(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        $offlineSale->load('items.masterProduct', 'user');
        $tenant = $offlineSale->tenant;
        return view('offline_sales.receipt', compact('offlineSale', 'tenant'));
    }
}
