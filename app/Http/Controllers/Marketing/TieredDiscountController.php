<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\TieredDiscount;
use App\Models\TieredDiscountTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TieredDiscountController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $discounts = TieredDiscount::with(['masterProduct', 'tiers'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('marketing.tiered_discounts.index', compact('discounts'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('marketing.tiered_discounts.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'master_product_id' => 'nullable|exists:master_products,id',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'notes'             => 'nullable|string',
            'tiers'             => 'required|array|min:1',
            'tiers.*.min_qty'   => 'required|integer|min:1',
            'tiers.*.max_qty'   => 'nullable|integer|min:1',
            'tiers.*.discount_type'  => 'required|in:percentage,fixed_amount',
            'tiers.*.discount_value' => 'required|numeric|min:0.1',
        ]);

        $tenantId = Auth::user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $discount = TieredDiscount::create([
                'tenant_id'         => $tenantId,
                'name'              => $request->name,
                'master_product_id' => $request->master_product_id,
                'start_date'        => $request->start_date,
                'end_date'          => $request->end_date,
                'is_active'         => true,
                'notes'             => $request->notes,
            ]);

            foreach ($request->tiers as $t) {
                TieredDiscountTier::create([
                    'tiered_discount_id' => $discount->id,
                    'min_qty'            => $t['min_qty'],
                    'max_qty'            => $t['max_qty'] ?? null,
                    'discount_type'      => $t['discount_type'],
                    'discount_value'     => $t['discount_value'],
                ]);
            }
        });

        return redirect()->route('marketing.tiered_discounts.index')
            ->with('success', 'Aturan Diskon Bertingkat berhasil dibuat.');
    }

    public function destroy(TieredDiscount $tieredDiscount)
    {
        if ($tieredDiscount->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $tieredDiscount->delete();
        return redirect()->route('marketing.tiered_discounts.index')
            ->with('success', 'Aturan Diskon Bertingkat berhasil dihapus.');
    }

    public function toggle(TieredDiscount $tieredDiscount)
    {
        if ($tieredDiscount->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $tieredDiscount->update([
            'is_active' => !$tieredDiscount->is_active
        ]);

        return redirect()->back()->with('success', 'Status Diskon Bertingkat diperbarui.');
    }
}
