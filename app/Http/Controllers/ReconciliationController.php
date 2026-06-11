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
        $orders = Order::with('store.channel')
            ->where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_COMPLETED)
            ->orderByDesc('order_date')
            ->paginate(30);

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
        // Note: this only sums the current page, for a real app we might want to query the whole month
        $totalNetPage = collect($orders->items())->sum('net_amount');
        $totalDiscrepancyPage = collect($orders->items())->sum('discrepancy_amount');

        return view('finance.reconciliation', compact('orders', 'totalNetPage', 'totalDiscrepancyPage'));
    }
}
