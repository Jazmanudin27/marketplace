<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class SyncOrderFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync-fees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghitung ulang potongan biaya marketplace dan dana dilepas (escrow) untuk semua orderan di database ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi & rekalkulasi potongan biaya pesanan ERP...');

        $count = 0;
        Order::chunk(100, function ($orders) use (&$count) {
            foreach ($orders as $order) {
                $details = $order->fee_breakdown_details;
                $order->fee_platform_amount = abs($details['platform_fee'] ?? 0);
                $order->fee_free_shipping_amount = abs($details['free_shipping'] ?? 0);
                $order->fee_service_amount = abs($details['service_fee'] ?? 0);
                $order->fee_promo_amount = abs($details['promo_fee'] ?? 0);
                $order->fee_other_amount = abs($details['other_fee'] ?? 0);

                $totalFee = abs($details['total_fee'] ?? 0);
                if ($totalFee > 0) {
                    $order->marketplace_fee = $totalFee;
                    if (empty($order->financial_breakdown['escrow_amount'])) {
                        $order->net_amount = max(0.0, (float) $order->total_amount - $totalFee);
                    }
                }
                $order->saveQuietly();
                $count++;
            }
        });

        $this->info("Berhasil memperbarui {$count} pesanan di database ERP.");
    }
}
