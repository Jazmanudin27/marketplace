<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class ReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil hanya pesanan yang sudah selesai (COMPLETED) untuk direkonsiliasi
        $query = Order::with('store.channel')
            ->where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_COMPLETED);

        // Filter status rekonsiliasi
        if ($request->filled('recon_status')) {
            $query->where('recon_status', $request->recon_status);
        }

        $orders = $query->orderByDesc('order_date')
            ->paginate(30)
            ->withQueryString();

        // Calculate discrepancies for the view
        $orders->getCollection()->transform(function ($order) {
            // Expected Net = Total Penjualan - Ongkir - Biaya Admin
            $expectedNet = $order->total_amount - $order->shipping_fee - $order->marketplace_fee;
            
            // Discrepancy = Selisih antara Expected Net dan Actual Net Amount dari Escrow API
            $discrepancy = $expectedNet - $order->net_amount;
            
            // Toleransi pembulatan 100 rupiah
            $order->has_discrepancy = abs($discrepancy) > 100;
            $order->discrepancy_amount = $discrepancy;
            
            // Deteksi "High Fee" jika potongan marketplace melebihi 10%
            $feePercentage = $order->total_amount > 0 ? ($order->marketplace_fee / $order->total_amount) * 100 : 0;
            $order->is_high_fee = $feePercentage > 10;
            $order->fee_percentage = round($feePercentage, 1);

            return $order;
        });

        // Summary metrics for the top of the page
        $totalNetPage = collect($orders->items())->sum('net_amount');
        $totalDiscrepancyPage = collect($orders->items())->sum('discrepancy_amount');

        return view('finance.reconciliation', compact('orders', 'totalNetPage', 'totalDiscrepancyPage'));
    }

    public function update(Request $request, Order $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'recon_status' => 'required|in:pending,investigating,resolved',
            'recon_notes' => 'nullable|string|max:1000',
        ]);

        $order->update($request->only(['recon_status', 'recon_notes']));

        return back()->with('success', 'Status rekonsiliasi pesanan #' . ($order->invoice_number ?? $order->order_marketplace_id) . ' berhasil diperbarui.');
    }
}
