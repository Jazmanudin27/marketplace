<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\OfflineSalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfflineSaleController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Auto-heal: Jika ada pesanan PO berstatus spk_diproses atau pending_approval,
        // tetapi SPK-nya telah dihapus dari antrean produksi (count = 0), kembalikan statusnya
        // agar tombol "Buat SPK" muncul kembali.
        $orphanedSales = OfflineSale::where('tenant_id', $tenantId)
            ->where('is_po', true)
            ->whereIn('status', [OfflineSale::STATUS_SPK_PROCESSING, OfflineSale::STATUS_PENDING_APPROVAL])
            ->whereDoesntHave('spks')
            ->get();

        foreach ($orphanedSales as $orphanedSale) {
            $reverted = ((float) $orphanedSale->paid_amount > 0)
                ? OfflineSale::STATUS_PENDING_SPK
                : OfflineSale::STATUS_WAITING_DP;
            $orphanedSale->update(['status' => $reverted]);
        }

        $query    = OfflineSale::with(['user', 'items', 'spks'])
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
            if ($request->status === 'perlu_follow_up') {
                $query->where('status', OfflineSale::STATUS_WAITING_DP)
                      ->where('paid_amount', '<=', 0)
                      ->whereNotNull('follow_up_date')
                      ->whereDate('follow_up_date', '<', now()->toDateString());
            } elseif ($request->status === 'spk_diproses') {
                $query->where(function($q) {
                    $q->where('status', OfflineSale::STATUS_SPK_PROCESSING)
                      ->orWhere(function($sub) {
                          $sub->where('status', OfflineSale::STATUS_PENDING_APPROVAL)
                              ->where('is_po', true);
                      });
                });
            } elseif ($request->status === 'pending_approval') {
                $query->where('status', OfflineSale::STATUS_PENDING_APPROVAL)
                      ->where('is_po', false);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('payment_method')) {
            if ($request->payment_method === 'piutang' || $request->payment_method === 'kredit') {
                $query->where('payment_method', 'piutang');
            } elseif ($request->payment_method === 'tunai') {
                $query->where('payment_method', '!=', 'piutang');
            } else {
                $query->where('payment_method', $request->payment_method);
            }
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'lunas') {
                $query->where('status', '!=', OfflineSale::STATUS_CANCELLED)
                      ->whereRaw('paid_amount >= grand_total');
            } elseif ($request->payment_status === 'belum_lunas') {
                $query->where('status', '!=', OfflineSale::STATUS_CANCELLED)
                      ->whereRaw('paid_amount < grand_total');
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

        // Hitung pesanan PO yang perlu follow up (lewat tanggal follow up dan belum bayar DP)
        $overdueFollowUpCount = OfflineSale::where('tenant_id', $tenantId)
            ->where('status', OfflineSale::STATUS_WAITING_DP)
            ->where('paid_amount', '<=', 0)
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<', now()->toDateString())
            ->count();

        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('offline_sales.index', compact('sales', 'summary', 'bankAccounts', 'overdueFollowUpCount'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->nonBundle()
            ->orderBy('name')
            ->get();

        $customers = \App\Models\Customer::where('tenant_id', $tenantId)
            ->offline()
            ->orderBy('name')
            ->get();

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
            'payment_method'            => 'nullable|string|in:tunai,transfer,qris,piutang,reseller_balance',
            'paid_amount'               => 'nullable|numeric|min:0',
            'discount_amount'           => 'nullable|numeric|min:0',
            'buyer_name'                => 'nullable|string|max:100',
            'buyer_phone'               => 'nullable|string|max:20',
            'buyer_address'             => 'nullable|string|max:500',
            'institution_name'          => 'nullable|string|max:150',
            'notes'                     => 'nullable|string|max:500',
            'is_dropship'               => 'nullable|boolean',
            'dropshipper_name'          => 'nullable|required_if:is_dropship,1|string|max:100',
            'dropshipper_phone'         => 'nullable|required_if:is_dropship,1|string|max:20',
            'resi_number'               => 'nullable|string|max:100',
            'resi_file'                 => 'nullable|file|mimes:jpeg,jpg,png,pdf,webp|max:5120',
            'follow_up_date'            => 'nullable|date',
        ];

        $paymentMethod = $request->payment_method ?: 'piutang';
        if (!$request->boolean('is_po') && !$request->filled('customer_id')) {
            if ($paymentMethod === 'piutang') {
                $rules['buyer_name']  = 'required|string|max:100';
                $rules['buyer_phone'] = 'required|string|max:20';
            } elseif ($request->filled('buyer_name')) {
                $rules['buyer_phone'] = 'required|string|max:20';
            }
        }

        $request->validate($rules);

        $resiFilePath = null;
        if ($request->hasFile('resi_file') && $request->file('resi_file')->isValid()) {
            $resiFilePath = $request->file('resi_file')->store('dropship_resi', 'public');
        }

        $tenantId = Auth::user()->tenant_id;

        if ($request->filled('customer_id')) {
            $customerExists = \App\Models\Customer::where('tenant_id', $tenantId)->where('id', $request->customer_id)->exists();
            if (!$customerExists) {
                return back()->withErrors(['customer_id' => 'Pelanggan tidak valid untuk perusahaan Anda.']);
            }
        }

        DB::transaction(function () use ($request, $tenantId, $resiFilePath) {
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
                if (!$isPo && !$product->is_preorder && $product->stock < $qty) {
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

            $grandTotal    = max(0, $totalAmount - $discountAmount);
            $paymentMethod = $request->payment_method ?: 'piutang';
            $paidAmount    = (float) ($request->paid_amount ?? 0);
            $changeAmount  = max(0, $paidAmount - $grandTotal);

            // Handle reseller balance
            if ($paymentMethod === 'reseller_balance') {
                $cust = $request->filled('customer_id') ? \App\Models\Customer::where('tenant_id', $tenantId)->find($request->customer_id) : null;
                if (!$cust || (float) $cust->balance < $paidAmount) {
                    abort(422, "Saldo reseller tidak mencukupi. Saldo saat ini: Rp " . number_format($cust ? $cust->balance : 0, 0, ',', '.'));
                }
            }

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
                        'category'  => 'umum',
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

            $isPo = $request->boolean('is_po');
            $initialStatus = $isPo ? OfflineSale::STATUS_WAITING_DP : OfflineSale::STATUS_COMPLETED;
            $followUpDate = $isPo ? ($request->follow_up_date ?: now()->addDays(3)->toDateString()) : null;

            $sale = OfflineSale::create([
                'tenant_id'       => $tenantId,
                'user_id'         => Auth::id(),
                'customer_id'     => $customerId,
                'sale_number'     => OfflineSale::generateSaleNumber(),
                'status'          => $initialStatus,
                'buyer_name'      => $request->buyer_name,
                'buyer_phone'     => $request->buyer_phone,
                'institution_name'=> $request->institution_name,
                'payment_method'  => $paymentMethod,
                'total_amount'    => $totalAmount,
                'discount_amount' => $discountAmount,
                'discount_type'   => $globalDiscType,
                'discount_value'  => $globalDiscVal,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'change_amount'   => $changeAmount,
                'notes'           => $request->notes,
                'sold_at'         => now(),
                'is_dropship'      => (bool) $request->is_dropship,
                'dropshipper_name'  => $request->is_dropship ? $request->dropshipper_name : null,
                'dropshipper_phone' => $request->is_dropship ? $request->dropshipper_phone : null,
                'resi_number'       => $request->is_dropship ? $request->resi_number : null,
                'resi_file'         => $request->is_dropship ? $resiFilePath : null,
                'is_po'             => $isPo,
                'follow_up_date'    => $followUpDate,
            ]);

            if ($paymentMethod === 'reseller_balance' && $paidAmount > 0) {
                $custToDeduct = $customer ?? ($customerId ? \App\Models\Customer::where('tenant_id', $tenantId)->find($customerId) : null);
                if ($custToDeduct) {
                    $custToDeduct->adjustBalance($paidAmount, 'out', "Pembayaran Penjualan Offline #{$sale->sale_number}", Auth::id());
                }
            }

            foreach ($itemsData as $itemData) {
                $sale->items()->create($itemData);
            }

            // Langsung kurangi stok produk jika non-PO (karena tidak ada approval gudang)
            if (!$isPo) {
                foreach ($itemsData as $itemData) {
                    if (!empty($itemData['master_product_id'])) {
                        $product = MasterProduct::find($itemData['master_product_id']);
                        if ($product) {
                            $product->recordStockMovement(
                                $itemData['quantity'],
                                'out',
                                'Penjualan Offline POS: ' . $sale->sale_number,
                                Auth::id()
                            );
                        }
                    }
                }
            }
        });

        $successMsg = $request->boolean('is_po')
            ? '✅ Pesanan PO berhasil dibuat dan berstatus Menunggu DP Masuk.'
            : '✅ Transaksi penjualan offline berhasil dibuat.';

        return redirect()->route('offline_sales.index')
            ->with('success', $successMsg);
    }

    public function show(OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        $offlineSale->load('items.masterProduct', 'user', 'customer', 'payments.user', 'spks');

        // Auto-heal: jika pesanan PO berstatus SPK sedang diproses tetapi seluruh SPK-nya sudah dihapus
        if ($offlineSale->is_po && in_array($offlineSale->status, [OfflineSale::STATUS_SPK_PROCESSING, OfflineSale::STATUS_PENDING_APPROVAL])) {
            if ($offlineSale->spks->isEmpty()) {
                $reverted = ((float) $offlineSale->paid_amount > 0)
                    ? OfflineSale::STATUS_PENDING_SPK
                    : OfflineSale::STATUS_WAITING_DP;
                $offlineSale->update(['status' => $reverted]);
                $offlineSale->status = $reverted;
            }
        }

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

        if (!in_array($offlineSale->status, [OfflineSale::STATUS_PENDING_APPROVAL, OfflineSale::STATUS_SPK_PROCESSING])) {
            return back()->with('error', 'Transaksi ini tidak dalam status menunggu approval atau proses SPK.');
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

            // 2. Tambahkan saldo ke Bank Account jika ada yang cocok (Hanya dari pembayaran riil)
            $paymentDest = $request->payment_destination;
            $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
                ->where(function($q) use ($paymentDest) {
                    $q->where('bank_name', $paymentDest)
                      ->orWhere('id', $paymentDest);
                })->first();

            $actualPayment = min($offlineSale->paid_amount, $offlineSale->grand_total);
            if ($bank && $actualPayment > 0) {
                $bank->increment('current_balance', $actualPayment);
            }

            // 3. Catat Pemasukan (Income) di Keuangan jika ada pembayaran riil
            if ($actualPayment > 0) {
                \App\Models\Income::create([
                    'tenant_id'           => $tenantId,
                    'title'               => "Penjualan Offline POS #{$offlineSale->sale_number}",
                    'category'            => 'services',
                    'payment_destination' => $paymentDest,
                    'amount'              => $actualPayment,
                    'income_date'         => now(),
                    'description'         => "Pemasukan otomatis dari Penjualan Offline POS #{$offlineSale->sale_number} (Pembeli: " . ($offlineSale->buyer_name ?: 'Umum') . ")",
                ]);
            }

            // 4. Update status penjualan & kas tujuan
            $offlineSale->update([
                'status'              => OfflineSale::STATUS_COMPLETED,
                'payment_destination' => $paymentDest,
                'approved_by'         => Auth::id(),
                'approved_at'         => now(),
            ]);
        });

        return back()->with('success', '✅ Transaksi disetujui! Stok telah dikurangi & pembayaran (jika ada) telah dimasukkan ke Kas/Bank.');
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

    public function recordPayment(Request $request, OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);

        if ($offlineSale->status === OfflineSale::STATUS_CANCELLED) {
            return back()->with('error', 'Transaksi yang dibatalkan tidak dapat dicatat pembayarannya.');
        }

        $remainingAmount = $offlineSale->remaining_amount;
        if ($remainingAmount <= 0) {
            return back()->with('error', 'Transaksi ini sudah lunas.');
        }

        $request->validate([
            'amount'              => 'required|numeric|min:1|max:' . $remainingAmount,
            'payment_method'      => 'required|string|in:tunai,transfer,qris,piutang,reseller_balance,lainnya',
            'payment_destination' => 'required|string|max:100',
            'payment_date'        => 'required|date',
            'reference_number'    => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ], [
            'amount.required'              => 'Nominal pembayaran wajib diisi.',
            'amount.min'                   => 'Nominal pembayaran minimal Rp 1.',
            'amount.max'                   => 'Nominal pembayaran tidak boleh melebihi sisa tagihan (Rp ' . number_format($remainingAmount, 0, ',', '.') . ').',
            'payment_method.required'      => 'Metode pembayaran wajib dipilih.',
            'payment_destination.required' => 'Kas / Bank Tujuan wajib dipilih.',
            'payment_date.required'        => 'Tanggal pembayaran wajib diisi.',
        ]);

        $tenantId    = Auth::user()->tenant_id;
        $payAmount   = (float) $request->amount;
        $paymentDest = $request->payment_destination;

        DB::transaction(function () use ($offlineSale, $request, $tenantId, $payAmount, $paymentDest) {
            $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
                ->where(function($q) use ($paymentDest) {
                    $q->where('bank_name', $paymentDest)
                      ->orWhere('id', $paymentDest);
                })->first();

            if ($bank) {
                $bank->increment('current_balance', $payAmount);
            }

            $paymentNumber = OfflineSalePayment::generatePaymentNumber($tenantId);

            $income = \App\Models\Income::create([
                'tenant_id'           => $tenantId,
                'title'               => "Pembayaran Cicilan POS #{$offlineSale->sale_number} ({$paymentNumber})",
                'category'            => 'services',
                'payment_destination' => $paymentDest,
                'amount'              => $payAmount,
                'income_date'         => $request->payment_date,
                'description'         => "Pembayaran cicilan offline POS #{$offlineSale->sale_number} oleh " . ($offlineSale->buyer_name ?: 'Umum') . ($request->notes ? " - " . $request->notes : ''),
            ]);

            $offlineSale->payments()->create([
                'tenant_id'           => $tenantId,
                'payment_number'      => $paymentNumber,
                'payment_date'        => $request->payment_date,
                'amount'              => $payAmount,
                'payment_method'      => $request->payment_method,
                'payment_destination' => $paymentDest,
                'reference_number'    => $request->reference_number,
                'notes'               => $request->notes,
                'created_by'          => Auth::id(),
                'income_id'           => $income ? $income->id : null,
            ]);

            $newPaid = (float) $offlineSale->paid_amount + $payAmount;
            $updateData = [
                'paid_amount'         => $newPaid,
                'change_amount'       => 0,
                'payment_destination' => $paymentDest,
                'payment_method'      => $request->payment_method,
            ];

            // Jika transaksi sebelumnya berstatus Menunggu DP dan sekarang ada pembayaran DP masuk,
            // transisikan status menjadi Belum dibuat SPK
            if ($offlineSale->status === OfflineSale::STATUS_WAITING_DP && $newPaid > 0) {
                $updateData['status'] = OfflineSale::STATUS_PENDING_SPK;
            }

            $offlineSale->update($updateData);
        });

        $fresh = $offlineSale->fresh();
        if ($fresh->is_paid) {
            $message = '✅ Pembayaran berhasil dicatat dan transaksi dinyatakan LUNAS!';
        } elseif ($offlineSale->status === OfflineSale::STATUS_WAITING_DP && $fresh->status === OfflineSale::STATUS_PENDING_SPK) {
            $message = '✅ Pembayaran DP sebesar Rp ' . number_format($payAmount, 0, ',', '.') . ' berhasil dicatat! Status transaksi kini: Belum dibuat SPK.';
        } else {
            $message = '✅ Pembayaran cicilan sebesar Rp ' . number_format($payAmount, 0, ',', '.') . ' berhasil dicatat!';
        }

        return back()->with('success', $message);
    }

    public function destroyPayment(OfflineSale $offlineSale, OfflineSalePayment $payment)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($payment->offline_sale_id === $offlineSale->id, 404);
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isOwner() || in_array(Auth::user()->role, ['admin', 'owner']), 403);

        $tenantId    = Auth::user()->tenant_id;
        $payAmount   = (float) $payment->amount;
        $paymentDest = $payment->payment_destination;
        $revertedToWaitingDp = false;

        DB::transaction(function () use ($offlineSale, $payment, $tenantId, $payAmount, $paymentDest, &$revertedToWaitingDp) {
            // 1. Kurangi saldo rekening jika cocok
            if ($paymentDest) {
                $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)
                    ->where(function($q) use ($paymentDest) {
                        $q->where('bank_name', $paymentDest)
                          ->orWhere('id', $paymentDest);
                    })->first();

                if ($bank) {
                    $bank->decrement('current_balance', min((float)$bank->current_balance, $payAmount));
                }
            }

            // 1b. Kembalikan saldo reseller jika pembayaran menggunakan reseller balance
            if ($payment->payment_method === 'reseller_balance' && $offlineSale->customer_id) {
                $cust = \App\Models\Customer::where('tenant_id', $tenantId)->find($offlineSale->customer_id);
                if ($cust) {
                    $cust->adjustBalance($payAmount, 'in', "Rollback Pembayaran Penjualan Offline #{$offlineSale->sale_number} ({$payment->payment_number})", Auth::id());
                }
            }

            // 2. Hapus data Income di Keuangan (Mutasi Masuk & Keluar)
            if ($payment->income_id) {
                \App\Models\Income::where('id', $payment->income_id)->where('tenant_id', $tenantId)->delete();
            }

            // Fallback: hapus Income jika income_id tidak tersimpan/null (berdasarkan nomor transaksi & nomor pembayaran)
            \App\Models\Income::where('tenant_id', $tenantId)
                ->where(function ($q) use ($offlineSale, $payment) {
                    $q->where('title', 'like', "%#{$offlineSale->sale_number}%")
                      ->where(function ($sub) use ($payment) {
                          $sub->where('title', 'like', "%{$payment->payment_number}%")
                              ->orWhere('description', 'like', "%{$payment->payment_number}%");
                      });
                })
                ->delete();

            // 3. Update nominal pembayaran transaksi
            $newPaid = max(0, (float) $offlineSale->paid_amount - $payAmount);
            $updateData = [
                'paid_amount' => $newPaid,
            ];

            // 4. Jika transaksi berstatus "Belum dibuat SPK" (atau PO) dan pembayarannya menjadi 0 (DP dihapus),
            // kembalikan status transaksi menjadi "Menunggu DP Masuk"
            if ($offlineSale->status === OfflineSale::STATUS_PENDING_SPK && $newPaid <= 0) {
                $updateData['status'] = OfflineSale::STATUS_WAITING_DP;
                $revertedToWaitingDp = true;
            } elseif ($offlineSale->is_po && $newPaid <= 0 && in_array($offlineSale->status, [OfflineSale::STATUS_PENDING_SPK, OfflineSale::STATUS_WAITING_DP])) {
                $updateData['status'] = OfflineSale::STATUS_WAITING_DP;
                $revertedToWaitingDp = true;
            }

            $offlineSale->update($updateData);

            // 5. Hapus riwayat pembayaran
            $payment->delete();
        });

        $msg = $revertedToWaitingDp
            ? '✅ Riwayat pembayaran DP berhasil dihapus & mutasi keuangan ditarik. Status transaksi dikembalikan ke "Menunggu DP Masuk".'
            : '✅ Riwayat pembayaran cicilan berhasil dihapus & mutasi keuangan ditarik.';

        return back()->with('success', $msg);
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

        $request->merge([
            'amount'              => $request->amount ?: $offlineSale->remaining_amount,
            'payment_method'      => $request->payment_method ?: 'tunai',
            'payment_destination' => $request->payment_destination ?: ($offlineSale->payment_destination ?: 'kas_besar'),
            'payment_date'        => $request->payment_date ?: now()->toDateString(),
            'notes'               => $request->notes ?: 'Pelunasan Transaksi',
        ]);

        return $this->recordPayment($request, $offlineSale);
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

        // Pesanan PO yang sudah ada pembayaran DP tidak dapat dibatalkan langsung
        if ($offlineSale->is_po && (float) $offlineSale->paid_amount > 0) {
            return back()->with('error', 'Pesanan PO tidak dapat dibatalkan karena pembayaran DP sudah masuk. Silakan hapus riwayat pembayaran DP terlebih dahulu jika ingin membatalkan pesanan.');
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

            // Reversi Pemasukan Keuangan (baik dari approval maupun cicilan/DP)
            \App\Models\Income::where('tenant_id', $offlineSale->tenant_id)
                ->where('title', 'like', "%#{$offlineSale->sale_number}%")
                ->delete();

            // Jika ada SPK yang terbit untuk pesanan ini, hapus dari antrean produksi
            $linkedSpks = \App\Models\Spk::where('tenant_id', $offlineSale->tenant_id)
                ->where(function ($q) use ($offlineSale) {
                    $q->where('no_pesanan', $offlineSale->sale_number)
                      ->orWhere('instansi', 'like', "%#{$offlineSale->sale_number}%")
                      ->orWhere('instansi', 'like', "%{$offlineSale->sale_number}%");
                })->get();

            foreach ($linkedSpks as $itemSpk) {
                foreach ($itemSpk->items as $item) {
                    \App\Models\SpkItemExtra::where('spk_item_id', $item->id)->delete();
                    \App\Models\SpkItemProgres::where('spk_item_id', $item->id)->delete();
                    \App\Models\SpkItemPickup::where('spk_item_id', $item->id)->delete();
                    $item->delete();
                }
                \App\Models\SpkProses::where('spk_id', $itemSpk->id)->delete();
                $itemSpk->delete();
            }

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

    public function createSpk(Request $request, OfflineSale $offlineSale)
    {
        abort_unless($offlineSale->tenant_id === Auth::user()->tenant_id, 403);

        $tenantId = Auth::user()->tenant_id;
        $offlineSale->load('items.masterProduct');

        $request->validate([
            'no_produksi'    => 'nullable|string|max:100',
            'tahap_saat_ini' => 'nullable|string|max:100',
            'deadline'       => 'nullable|date',
        ]);

        $noProduksi = trim((string) $request->input('no_produksi'));
        if (empty($noProduksi)) {
            $noProduksi = \App\Models\Spk::generateNoProduksi();
        }

        $tahapSaatIni = $request->input('tahap_saat_ini', 'Antrian & Sampling');
        if (empty($tahapSaatIni) || strtoupper($tahapSaatIni) === 'DRAFT') {
            $tahapSaatIni = 'Antrian & Sampling';
        }

        $deadline = $request->filled('deadline') ? $request->deadline : now()->addDays(7);
        $spkGroups = $request->input('spk_group', []);
        $spkKategori = $request->input('spk_kategori', $request->input('spk_title', []));
        $createdSpkSummaries = [];

        DB::transaction(function () use ($offlineSale, $tenantId, $noProduksi, $tahapSaatIni, $deadline, $spkGroups, $spkKategori, &$createdSpkSummaries) {
            $today = date('Ymd');
            $countToday = \App\Models\Spk::where('no_spk', 'like', "SPK-{$today}-%")->count();

            // Kelompokkan item pesanan: Jika ada pilihan custom spk_group, gunakan grouping tersebut
            if (!empty($spkGroups) && is_array($spkGroups)) {
                $groupedItems = $offlineSale->items->filter(function ($item) use ($spkGroups) {
                    $grp = (int) ($spkGroups[$item->id] ?? 1);
                    return $grp > 0;
                })->groupBy(function ($item) use ($spkGroups) {
                    return (int) ($spkGroups[$item->id] ?? 1);
                });
            } else {
                // Fallback default: kelompokkan per jenis produk
                $groupedItems = $offlineSale->items->groupBy(function ($item) {
                    return $item->master_product_id ?: $item->product_name;
                });
            }

            $spkCounter = 0;
            foreach ($groupedItems as $groupKey => $group) {
                $spkCounter++;
                $firstItem = $group->first();
                $customTitle = !empty($spkKategori[$groupKey]) ? trim((string) $spkKategori[$groupKey]) : null;
                $kategori = $customTitle ?: ($firstItem->product_name ?: 'Produk SPK');

                $noSpk = 'SPK-' . $today . '-' . sprintf('%04d', $countToday + $spkCounter);

                $spk = \App\Models\Spk::create([
                    'tenant_id'      => $tenantId,
                    'no_produksi'    => $noProduksi,
                    'no_spk'         => $noSpk,
                    'no_pesanan'     => $offlineSale->sale_number,
                    'tipe_spk'       => 'pesanan_pelanggan',
                    'kategori'       => $kategori,
                    'tahap_saat_ini' => $tahapSaatIni,
                    'tanggal'        => now(),
                    'deadline'       => $deadline,
                    'pemesan'        => $offlineSale->buyer_name ?: 'Pelanggan PO',
                    'no_hp_pemesan'  => $offlineSale->buyer_phone ?: '',
                    'instansi'       => $offlineSale->institution_name ?: ('Penjualan PO #' . $offlineSale->sale_number),
                    'nama_pic'       => Auth::user()->name,
                    'penginput_id'   => Auth::id(),
                ]);

                foreach ($group as $itemData) {
                    $ukuran = null;
                    if (preg_match('/\b(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL)\b/i', $itemData->product_name, $m)) {
                        $ukuran = strtoupper($m[1]);
                    }

                    \App\Models\SpkItem::create([
                        'spk_id'            => $spk->id,
                        'master_product_id' => $itemData->master_product_id,
                        'nama_produk'       => $itemData->product_name,
                        'sku'               => $itemData->sku,
                        'ukuran'            => $ukuran,
                        'quantity'          => $itemData->quantity,
                        'hpp'               => $itemData->unit_price,
                        'status'            => 'Pending',
                    ]);
                }

                $createdSpkSummaries[] = "{$kategori} (#{$noSpk})";
            }

            // Setelah SPK dibuat, status penjualan offline PO menjadi SPK Sedang Diproses
            $offlineSale->update([
                'status' => OfflineSale::STATUS_SPK_PROCESSING,
            ]);
        });

        $totalSpk = count($createdSpkSummaries);
        $summaryText = implode(', ', $createdSpkSummaries);

        return back()->with('success', "✅ Berhasil menerbitkan {$totalSpk} SPK Produksi ({$summaryText}) di bawah Kode Produksi {$noProduksi} dengan status: {$tahapSaatIni}. Status transaksi kini: SPK Sedang Diproses.");
    }
}
