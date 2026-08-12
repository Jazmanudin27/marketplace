<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Batch recalculate all existing orders with financial breakdown details
        Order::chunk(100, function ($orders) {
            foreach ($orders as $order) {
                $details = $order->fee_breakdown_details;
                $totalFee = abs($details['total_fee'] ?? 0);
                
                if ($totalFee > 0) {
                    $order->marketplace_fee = $totalFee;
                    $order->net_amount = max(0.0, (float) $order->total_amount - $totalFee);
                    $order->saveQuietly();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed on down
    }
};
