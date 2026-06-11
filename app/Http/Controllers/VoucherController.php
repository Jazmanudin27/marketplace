<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Voucher;
use App\Services\ShopeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $vouchers = Voucher::with('store.channel')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $stores = Store::where('tenant_id', Auth::user()->tenant_id)
            ->whereHas('channel', fn($q) => $q->whereIn('code', ['shopee', 'tiktok']))
            ->where('status', 'connected')
            ->with('channel')
            ->get();

        return view('vouchers.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'required|string|max:30|unique:vouchers,code',
            'type'         => 'required|in:percentage,fixed',
            'value'        => 'required|numeric|min:1',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
            'usage_limit'  => 'nullable|integer|min:1',
            'store_id'     => 'nullable|exists:stores,id',
        ]);

        $voucher = Voucher::create([
            'tenant_id'    => Auth::user()->tenant_id,
            'store_id'     => $request->store_id ?: null,
            'name'         => $request->name,
            'code'         => strtoupper($request->code),
            'type'         => $request->type,
            'value'        => $request->value,
            'min_purchase' => $request->min_purchase ?? 0,
            'max_discount' => $request->max_discount ?: null,
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'usage_limit'  => $request->usage_limit ?: null,
            'is_active'    => true,
        ]);

        return redirect()->route('vouchers.index')
            ->with('success', "Voucher \"{$voucher->code}\" berhasil dibuat.");
    }

    public function edit(Voucher $voucher)
    {
        abort_unless($voucher->tenant_id === Auth::user()->tenant_id, 403);

        $stores = Store::where('tenant_id', Auth::user()->tenant_id)
            ->whereHas('channel', fn($q) => $q->whereIn('code', ['shopee', 'tiktok']))
            ->where('status', 'connected')
            ->with('channel')
            ->get();

        return view('vouchers.edit', compact('voucher', 'stores'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        abort_unless($voucher->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'required|string|max:30|unique:vouchers,code,' . $voucher->id,
            'type'         => 'required|in:percentage,fixed',
            'value'        => 'required|numeric|min:1',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
            'usage_limit'  => 'nullable|integer|min:1',
            'store_id'     => 'nullable|exists:stores,id',
            'is_active'    => 'boolean',
        ]);

        $voucher->update([
            'store_id'     => $request->store_id ?: null,
            'name'         => $request->name,
            'code'         => strtoupper($request->code),
            'type'         => $request->type,
            'value'        => $request->value,
            'min_purchase' => $request->min_purchase ?? 0,
            'max_discount' => $request->max_discount ?: null,
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'usage_limit'  => $request->usage_limit ?: null,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return redirect()->route('vouchers.index')
            ->with('success', "Voucher \"{$voucher->code}\" berhasil diperbarui.");
    }

    public function destroy(Voucher $voucher)
    {
        abort_unless($voucher->tenant_id === Auth::user()->tenant_id, 403);
        $code = $voucher->code;
        $voucher->delete();

        return back()->with('success', "Voucher \"{$code}\" berhasil dihapus.");
    }

    /**
     * Sync voucher ke Shopee API.
     */
    public function syncToShopee(Voucher $voucher, ShopeeService $shopeeService)
    {
        abort_unless($voucher->tenant_id === Auth::user()->tenant_id, 403);

        if (!$voucher->store_id) {
            return back()->with('error', 'Pilih toko Shopee terlebih dahulu sebelum sync.');
        }

        $store = $voucher->store()->with('channel')->first();

        if (!$store || $store->channel->code !== 'shopee') {
            return back()->with('error', 'Toko yang dipilih bukan toko Shopee.');
        }

        if ($store->status !== 'connected') {
            return back()->with('error', 'Toko Shopee tidak terhubung. Silakan hubungkan ulang.');
        }

        try {
            $payload = [
                'voucher_name'       => $voucher->name,
                'voucher_code'       => $voucher->code,
                'voucher_type'       => 1, // 1 = Shop voucher (seller voucher)
                'reward_type'        => $voucher->type === 'percentage' ? 1 : 2, // 1 = by percentage, 2 = by amount
                'usage_quantity'     => $voucher->usage_limit ?? 999999,
                'display_start_time' => $voucher->start_date->timestamp,
                'start_time'         => $voucher->start_date->timestamp,
                'end_time'           => $voucher->end_date->timestamp,
                'min_basket_price'   => (float) $voucher->min_purchase,
                'discount_amount'    => $voucher->type === 'fixed' ? (float) $voucher->value : 0,
                'percentage'         => $voucher->type === 'percentage' ? (int) $voucher->value : 0,
                'max_price'          => $voucher->max_discount ? (float) $voucher->max_discount : 0,
            ];

            $result = $shopeeService->createVoucher(
                $store->access_token,
                (int) $store->marketplace_store_id,
                $payload
            );

            $voucher->update([
                'marketplace_voucher_id' => $result['voucher_id'] ?? null,
                'marketplace_status'     => 'upcoming',
            ]);

            return back()->with('success', "Voucher berhasil di-sync ke Shopee! ID: " . ($result['voucher_id'] ?? '-'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal sync ke Shopee: ' . $e->getMessage());
        }
    }

    /**
     * Nonaktifkan voucher di Shopee.
     */
    public function endOnShopee(Voucher $voucher, ShopeeService $shopeeService)
    {
        abort_unless($voucher->tenant_id === Auth::user()->tenant_id, 403);

        if (!$voucher->marketplace_voucher_id || !$voucher->store_id) {
            return back()->with('error', 'Voucher belum di-sync ke Shopee.');
        }

        $store = $voucher->store()->with('channel')->first();

        try {
            $shopeeService->endVoucher(
                $store->access_token,
                (int) $store->marketplace_store_id,
                (int) $voucher->marketplace_voucher_id
            );

            $voucher->update(['marketplace_status' => 'cancelled', 'is_active' => false]);

            return back()->with('success', 'Voucher berhasil diakhiri di Shopee.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengakhiri voucher di Shopee: ' . $e->getMessage());
        }
    }
}
