<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\OfflineSale;
use App\Models\OfflineSalePayment;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $sales = OfflineSale::where('paid_amount', '>', 0)
            ->doesntHave('payments')
            ->get();

        foreach ($sales as $sale) {
            $paymentNumber = OfflineSalePayment::generatePaymentNumber($sale->tenant_id);
            $actualPayment = min((float) $sale->paid_amount, (float) $sale->grand_total);
            if ($actualPayment <= 0) {
                $actualPayment = (float) $sale->paid_amount;
            }

            $sale->payments()->create([
                'tenant_id'           => $sale->tenant_id,
                'payment_number'      => $paymentNumber,
                'payment_date'        => $sale->sold_at ? Carbon::parse($sale->sold_at)->toDateString() : ($sale->created_at ? Carbon::parse($sale->created_at)->toDateString() : now()->toDateString()),
                'amount'              => $actualPayment,
                'payment_method'      => $sale->payment_method ?: 'tunai',
                'payment_destination' => $sale->payment_destination ?: 'kas_besar',
                'notes'               => 'Pembayaran Kasir Saat Transaksi Dibuat',
                'created_by'          => $sale->user_id,
                'income_id'           => null,
            ]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};
